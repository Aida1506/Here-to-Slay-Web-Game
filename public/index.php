<?php

// aceasta sectiune configureaza raportarea erorilor si logging-ul
// seteaza afisarea erorilor logarea lor si calea catre fisierul de log
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../error.log');

// aceasta sectiune importa toate clasele si interfetele necesare
// include ACL database middleware repository service si Slim pentru API
use App\Acl\AccessControlList;
use App\Database\Database;
use App\Database\DatabaseConfig;
use App\Middleware\AclMiddleware;
use App\Repository\CardRepository;
use App\Repository\GameRepository;
use App\Repository\MonsterRepository;
use App\Repository\PlayerRepository;
use App\Service\GameService;
use App\Service\TutorialService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// aceasta linie incarca autoloader-ul Composer pentru a putea folosi clasele
require __DIR__ . '/../vendor/autoload.php';

// aceasta sectiune initializeaza baza de date si serviciile
// creeaza conexiunea la baza de date apoi instanteaza serviciile cu repository-urile lor
$db = new Database(DatabaseConfig::getConfig());
$gameService = new GameService(
    new GameRepository($db),
    new PlayerRepository($db),
    new CardRepository($db),
    new MonsterRepository($db)
);
$tutorialService = new TutorialService();

// aceasta sectiune initializeaza aplicatia Slim si configureaza calea de baza
// creeaza aplicatia apoi seteaza base path-ul daca e necesar
$app = AppFactory::create();
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

if ($basePath !== '' && $basePath !== '/') {
    $app->setBasePath($basePath);
}

// aceasta sectiune adauga middleware-uri la aplicatie
// adauga parsing pentru body error handling si ACL middleware
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add(new AclMiddleware(new AccessControlList()));

// aceasta functie helper obtine body-ul parsat din request
// returneaza array-ul parsat sau array gol daca nu exista
function body(Request $request): array
{
    return $request->getParsedBody() ?? [];
}

// aceasta functie helper creeaza un raspuns JSON
// scrie datele JSON in body seteaza header-ul content-type si status-ul
function jsonResponse(Response $response, mixed $data, int $status = 200): Response
{
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}

// aceasta functie helper creeaza un raspuns 404 cu mesaj
// foloseste jsonResponse pentru a returna mesajul cu status 404
function notFound(Response $response, string $message): Response
{
    return jsonResponse($response, ['message' => $message], 404);
}

// aceasta functie helper creeaza un raspuns pentru actiuni
// foloseste jsonResponse cu status 200 daca success e true altfel 400
function actionResponse(Response $response, array $result): Response
{
    return jsonResponse($response, $result, ($result['success'] ?? false) ? 200 : 400);
}

// aceasta ruta returneaza pagina principala a jocului
// incarca template-ul game.php si il scrie in raspuns
$app->get('/', function (Request $request, Response $response) use ($basePath) {
    ob_start();
    require __DIR__ . '/../templates/game.php';
    $response->getBody()->write(ob_get_clean());
    return $response;
});

// aceasta ruta returneaza toate jocurile
// apeleaza getAllGames din gameService si returneaza JSON
$app->get('/games', fn (Request $request, Response $response) => jsonResponse($response, $gameService->getAllGames()));

// aceasta ruta creeaza un joc nou
// apeleaza createGame cu numele din body si returneaza jocul creat cu status 201
$app->post('/games', function (Request $request, Response $response) use ($gameService) {
    return jsonResponse($response, $gameService->createGame(body($request)['name'] ?? 'New Game'), 201);
});

// aceasta ruta returneaza un joc specific dupa ID
// apeleaza getGame si returneaza jocul sau 404 daca nu exista
$app->get('/games/{gameId}', function (Request $request, Response $response, array $args) use ($gameService) {
    $game = $gameService->getGame($args['gameId']);
    return $game ? jsonResponse($response, $game) : notFound($response, 'Game not found');
});

// aceasta sectiune adauga rute pentru PUT si PATCH pe games si players
// pentru fiecare metoda adauga ruta care apeleaza updateGame sau updatePlayer si returneaza rezultatul sau 404
foreach (['put', 'patch'] as $method) {
    $app->$method('/games/{gameId}', function (Request $request, Response $response, array $args) use ($gameService) {
        $game = $gameService->updateGame($args['gameId'], body($request));
        return $game ? jsonResponse($response, $game) : notFound($response, 'Game not found');
    });

    $app->$method('/players/{playerId}', function (Request $request, Response $response, array $args) use ($gameService) {
        $player = $gameService->updatePlayer($args['playerId'], body($request));
        return $player ? jsonResponse($response, $game) : notFound($response, 'Player not found');
    });
}

// aceasta ruta sterge un joc dupa ID
// apeleaza deleteGame si returneaza mesaj de succes sau 404
$app->delete('/games/{gameId}', function (Request $request, Response $response, array $args) use ($gameService) {
    return $gameService->deleteGame($args['gameId'])
        ? jsonResponse($response, ['message' => 'Game deleted'])
        : notFound($response, 'Game not found');
});

$app->post('/games/{gameId}/turn/end', function (Request $request, Response $response, array $args) use ($gameService) {
    $game = $gameService->endTurn($args['gameId']);
    return $game ? jsonResponse($response, $game) : notFound($response, 'Game not found');
});

// aceasta ruta returneaza toti jucatorii
// apeleaza getAllPlayers din gameService
$app->get('/players', fn (Request $request, Response $response) => jsonResponse($response, $gameService->getAllPlayers()));

// aceasta ruta returneaza toate cartile
// apeleaza getAllCards din gameService
$app->get('/cards', fn (Request $request, Response $response) => jsonResponse($response, $gameService->getAllCards()));

// aceasta ruta returneaza toti monstrii
// apeleaza getAllMonsters din gameService
$app->get('/monsters', fn (Request $request, Response $response) => jsonResponse($response, $gameService->getAllMonsters()));

// aceasta ruta trage o carte din pachet
// apeleaza drawCard cu gameId si playerId din body si returneaza cartea sau eroare
$app->post('/deck/draw', function (Request $request, Response $response) use ($gameService) {
    $data = body($request);
    $card = $gameService->drawCard($data['gameId'] ?? '', $data['playerId'] ?? '');
    return $card ? jsonResponse($response, $card) : jsonResponse($response, ['message' => 'Card could not be drawn'], 400);
});

// aceasta ruta ataca un monstru
// apeleaza attackMonster cu monsterId playerId si roll din body
$app->post('/monsters/{monsterId}/attack', function (Request $request, Response $response, array $args) use ($gameService) {
    $data = body($request);
    return jsonResponse($response, $gameService->attackMonster($args['monsterId'], $data['playerId'] ?? '', (int) ($data['roll'] ?? 0)));
});

// aceasta ruta returneaza pasii tutorialului
// apeleaza getTutorialSteps din tutorialService
$app->get('/tutorial', fn (Request $request, Response $response) => jsonResponse($response, $tutorialService->getTutorialSteps()));

// aceasta ruta returneaza un pas specific din tutorial
// apeleaza getNextStep cu index-ul ajustat si returneaza pasul sau 404
$app->get('/tutorial/step/{step}', function (Request $request, Response $response, array $args) use ($tutorialService) {
    $step = $tutorialService->getNextStep(((int) $args['step']) - 1);
    return $step ? jsonResponse($response, $step) : notFound($response, 'Step not found');
});

// aceasta ruta joaca o carte
// apeleaza playCard cu gameId playerId si cardId din body si foloseste actionResponse
$app->post('/games/{gameId}/cards/play', function (Request $request, Response $response, array $args) use ($gameService) {
    $data = body($request);
    return actionResponse($response, $gameService->playCard($args['gameId'], $data['playerId'] ?? '', $data['cardId'] ?? ''));
});

// aceasta ruta ruleaza zarul pentru un erou
// apeleaza rollDice cu gameId playerId si heroId si foloseste actionResponse
$app->post('/games/{gameId}/heroes/{heroId}/roll', function (Request $request, Response $response, array $args) use ($gameService) {
    $data = body($request);
    return actionResponse($response, $gameService->rollDice($args['gameId'], $data['playerId'] ?? '', $args['heroId']));
});

// aceasta ruta foloseste un modificator
// apeleaza useModifier cu gameId playerId modifierId si modifierValue din body
$app->post('/games/{gameId}/modifiers/use', function (Request $request, Response $response, array $args) use ($gameService) {
    $data = body($request);
    return actionResponse($response, $gameService->useModifier($args['gameId'], $data['playerId'] ?? '', $data['modifierId'] ?? '', (int) ($data['modifierValue'] ?? 0)));
});

// aceasta ruta provoaca o carte
// apeleaza challengeCard cu gameId challengerId targetPlayerId si cardId din body
$app->post('/games/{gameId}/challenges', function (Request $request, Response $response, array $args) use ($gameService) {
    $data = body($request);
    return actionResponse($response, $gameService->challengeCard($args['gameId'], $data['challengerId'] ?? '', $data['targetPlayerId'] ?? '', $data['cardId'] ?? ''));
});

// aceasta ruta arunca si trage o carte
// apeleaza discardAndDraw cu gameId si playerId din body
$app->post('/games/{gameId}/discard-draw', function (Request $request, Response $response, array $args) use ($gameService) {
    return actionResponse($response, $gameService->discardAndDraw($args['gameId'], body($request)['playerId'] ?? ''));
});

// aceasta ruta arunca o carte din mana
// apeleaza discardCardFromHand cu gameId playerId si cardId
$app->delete('/games/{gameId}/players/{playerId}/hand/{cardId}', function (Request $request, Response $response, array $args) use ($gameService) {
    return actionResponse($response, $gameService->discardCardFromHand($args['gameId'], $args['playerId'], $args['cardId']));
});

// aceasta ruta scoate o carte din party
// apeleaza removeCardFromParty cu gameId playerId si cardId
$app->delete('/games/{gameId}/players/{playerId}/party/{cardId}', function (Request $request, Response $response, array $args) use ($gameService) {
    return actionResponse($response, $gameService->removeCardFromParty($args['gameId'], $args['playerId'], $args['cardId']));
});

// aceasta ruta scoate un monstru activ
// apeleaza removeActiveMonster cu gameId si monsterId
$app->delete('/games/{gameId}/active-monsters/{monsterId}', function (Request $request, Response $response, array $args) use ($gameService) {
    return actionResponse($response, $gameService->removeActiveMonster($args['gameId'], $args['monsterId']));
});

// aceasta linie porneste aplicatia Slim si incepe procesarea request-urilor
$app->run();
