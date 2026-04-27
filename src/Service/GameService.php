<?php

namespace App\Service;

use App\Repository\CardRepository;
use App\Repository\GameRepository;
use App\Repository\MonsterRepository;
use App\Repository\PlayerRepository;

// aceasta clasa gestioneaza logica jocului inclusiv crearea jocurilor gestionarea jucatorilor si actiunile din joc
// foloseste repositoryuri pentru a interactiona cu datele si ofera metode pentru operatiunile principale ale jocului
class GameService
{
    // aceasta functie initializeaza serviciul cu repositoryurile necesare
    // primeste repositoryurile ca parametri privati si le atribuie proprietatilor pentru a fi folosite in metodele clasei
    public function __construct(
        private GameRepository $gameRepo,
        private PlayerRepository $playerRepo,
        private CardRepository $cardRepo,
        private MonsterRepository $monsterRepo
    ) {}

    // aceasta functie returneaza toate jocurile cu jucatorii atasati
    // parcurge toate jocurile din repository aplica metoda attachPlayers pentru fiecare si returneaza arrayul rezultat
    public function getAllGames(): array
    {
        return array_map(fn (array $game) => $this->attachPlayers($game), $this->gameRepo->findAll());
    }

    // aceasta functie returneaza un joc specific dupa id cu jucatorii atasati
    // cauta jocul in repository daca exista aplica attachPlayers si returneaza altfel returneaza null
    public function getGame(string $gameId): ?array
    {
        $game = $this->gameRepo->findById($gameId);
        return $game ? $this->attachPlayers($game) : null;
    }

    // aceasta functie creeaza un nou joc cu nume dat
    // genereaza un id unic construieste pachetul principal si pachetul de monstri amesteca ambele creeaza jucatorii distribuie cartile initiale si salveaza jocul si jucatorii in repositoryuri returnand jocul creat
    public function createGame(string $name): array
    {
        $gameId = uniqid('game_', true);
        $mainDeck = $this->buildMainDeck();
        $monsterDeck = $this->monsterRepo->findAll();

        shuffle($mainDeck);
        shuffle($monsterDeck);

        $players = $this->buildPlayers($gameId);
        foreach ($players as &$player) {
            $player['hand'] = array_splice($mainDeck, 0, 5);
        }
        unset($player);

        $game = [
            'id' => $gameId,
            'name' => trim($name) ?: 'New Game',
            'players' => $players,
            'currentTurnPlayer' => $players[0],
            'currentTurnPlayerId' => $players[0]['id'],
            'actionPoints' => 3,
            'mainDeck' => $mainDeck,
            'discardPile' => [],
            'monsterDeck' => array_slice($monsterDeck, 3),
            'activeMonsters' => array_slice($monsterDeck, 0, 3),
            'lastMessage' => 'Game created. Player 1 starts. Win with 3 slain monsters or 5 heroes in your party.'
        ];

        $this->gameRepo->save($game);
        $this->playerRepo->saveMultiple($players);

        return $game;
    }

    // aceasta functie actualizeaza un joc cu datele furnizate
    // verifica daca jocul exista apoi actualizeaza campurile specificate daca sunt valide salveaza jocul si returneaza jocul actualizat sau null daca nu exista
    public function updateGame(string $gameId, array $data): ?array
    {
        $game = $this->getGame($gameId);
        if (!$game) {
            return null;
        }

        if (isset($data['name']) && trim((string) $data['name']) !== '') {
            $game['name'] = trim((string) $data['name']);
        }

        if (isset($data['currentTurnPlayerId'])) {
            $player = $this->findById($game['players'], (string) $data['currentTurnPlayerId']);
            if ($player) {
                $game['currentTurnPlayerId'] = $player['id'];
                $game['currentTurnPlayer'] = $player;
            }
        }

        if (isset($data['actionPoints'])) {
            $game['actionPoints'] = max(0, min(3, (int) $data['actionPoints']));
        }

        if (array_key_exists('lastMessage', $data)) {
            $game['lastMessage'] = (string) $data['lastMessage'];
        }

        $this->gameRepo->save($game);
        return $this->getGame($gameId);
    }

    // aceasta functie sterge un joc dupa id
    // verifica daca jocul exista apoi sterge jucatorii asociati si jocul din repositoryuri returnand true sau false daca nu exista
    public function deleteGame(string $gameId): bool
    {
        if (!$this->gameRepo->findById($gameId)) {
            return false;
        }

        $this->playerRepo->deleteByGameId($gameId);
        return $this->gameRepo->delete($gameId);
    }

    // aceasta functie actualizeaza un jucator cu datele furnizate
    // cauta jucatorul in repository daca exista actualizeaza campurile specificate daca sunt valide si salveaza returnand jucatorul actualizat sau null daca nu exista
    public function updatePlayer(string $playerId, array $data): ?array
    {
        $player = $this->playerRepo->findById($playerId);
        if (!$player) {
            return null;
        }

        foreach (['name', 'partyLeader', 'partyLeaderClass'] as $field) {
            if (isset($data[$field]) && trim((string) $data[$field]) !== '') {
                $player[$field] = trim((string) $data[$field]);
            }
        }

        $this->playerRepo->save($player);
        return $this->playerRepo->findById($playerId);
    }

    // aceasta functie arunca o carte din mana unui jucator
    // foloseste metoda privata removePlayerCard pentru a indeparta cartea din mana si o adauga la discardPile
    public function discardCardFromHand(string $gameId, string $playerId, string $cardId): array
    {
        return $this->removePlayerCard($gameId, $playerId, $cardId, 'hand');
    }

    // aceasta functie indeparteaza o carte din party unui jucator
    // foloseste metoda privata removePlayerCard pentru a indeparta cartea din party si o adauga la discardPile
    public function removeCardFromParty(string $gameId, string $playerId, string $cardId): array
    {
        return $this->removePlayerCard($gameId, $playerId, $cardId, 'party');
    }

    // aceasta functie indeparteaza un monstru activ din joc
    // cauta jocul si monstrii activi indeparteaza monstrii specificat reumple monstrii activi actualizeaza mesajul si salveaza jocul returnand rezultatul
    public function removeActiveMonster(string $gameId, string $monsterId): array
    {
        $game = $this->getGame($gameId);
        if (!$game) {
            return $this->fail('Game not found.');
        }

        $monster = $this->takeById($game['activeMonsters'], $monsterId);
        if (!$monster) {
            return $this->fail('Monster not found.');
        }

        $this->refillActiveMonsters($game);
        $game['lastMessage'] = $monster['name'] . ' was removed from the active monsters.';
        $this->gameRepo->save($game);

        return $this->ok($game['lastMessage'], ['monster' => $monster, 'game' => $this->getGame($gameId)]);
    }

    // aceasta functie trage o carte pentru un jucator
    // verifica daca jocul exista daca jucatorul poate folosi actiunea si daca pachetul nu e gol apoi trage o carte o adauga la mana cheltuieste punctele de actiune actualizeaza mesajul si salveaza
    public function drawCard(string $gameId, string $playerId): ?array
    {
        $game = $this->getGame($gameId);
        if (!$game || !$this->canUseAction($game, $playerId, 1)) {
            return null;
        }

        $playerIndex = $this->indexById($game['players'], $playerId);
        if ($playerIndex === null) {
            return null;
        }

        $card = $this->drawOneCard($game);
        if (!$card) {
            $game['lastMessage'] = 'The main deck is empty.';
            $this->gameRepo->save($game);
            return null;
        }

        $game['players'][$playerIndex]['hand'][] = $card;
        $this->spendActionPoints($game, $playerIndex, 1);
        $game['lastMessage'] = $game['players'][$playerIndex]['name'] . ' drew ' . $card['name'] . '.';
        $this->saveGameWithPlayers($game);

        return $card;
    }

    // aceasta functie joaca o carte din mana unui jucator
    // verifica jocul si actiunea apoi scoate cartea din mana aplica efectul cartii cheltuieste punctele actualizeaza mesajul si salveaza returnand rezultatul
    public function playCard(string $gameId, string $playerId, string $cardId): array
    {
        $game = $this->getGame($gameId);
        if (!$game) {
            return $this->fail('Game not found.');
        }
        if (!$this->canUseAction($game, $playerId, 1)) {
            return $this->fail($game['lastMessage'] ?? 'Action not allowed.');
        }

        $playerIndex = $this->indexById($game['players'], $playerId);
        if ($playerIndex === null) {
            return $this->fail('Player not found.');
        }

        $card = $this->takeById($game['players'][$playerIndex]['hand'], $cardId);
        if (!$card) {
            return $this->fail('Card not found in hand.');
        }

        $player = &$game['players'][$playerIndex];
        $message = $this->applyPlayedCard($game, $player, $card);

        if ($message === '') {
            $player['hand'][] = $card;
            return $this->fail('You can keep maximum 5 heroes in this simplified version.');
        }

        $this->spendActionPoints($game, $playerIndex, 1);
        $game['lastMessage'] = $this->appendWinMessage($player, $message);
        $this->saveGameWithPlayers($game);

        return $this->ok($game['lastMessage'], ['card' => $card, 'game' => $game]);
    }

    // aceasta functie ataca un monstru cu un zar dat
    // cauta jocul dupa id-ul jucatorului verifica daca poate ataca daca zarul e valid gaseste jucatorul si monstrii calculeaza bonusul si roll-ul final verifica daca castiga sau pierde aplica recompensa sau pedeapsa actualizeaza mesajul si salveaza returnand rezultatele
    public function attackMonster(string $monsterId, string $playerId, int $roll): array
    {
        $game = $this->getGameByPlayerId($playerId);
        if (!$game) {
            return $this->fail('Game not found.');
        }
        if (!$this->canUseAction($game, $playerId, 2)) {
            return $this->fail($game['lastMessage'] ?? 'Action not allowed.');
        }
        if ($roll < 2 || $roll > 12) {
            return $this->fail('Roll the dice before attacking.');
        }

        $playerIndex = $this->indexById($game['players'], $playerId);
        $monsterIndex = $this->indexById($game['activeMonsters'], $monsterId);

        if ($playerIndex === null) {
            return $this->fail('Player not found.');
        }
        if ($monsterIndex === null) {
            return $this->fail('Monster not found.');
        }

        $player = &$game['players'][$playerIndex];
        $monster = $game['activeMonsters'][$monsterIndex];
        $bonus = $this->calculateAttackBonus($player);
        $finalRoll = $roll + $bonus;
        $requiredRoll = $this->getRollRequirement($monster);
        $won = $finalRoll >= $requiredRoll;

        $this->spendActionPoints($game, $playerIndex, 2);

        if ($won) {
            array_splice($game['activeMonsters'], $monsterIndex, 1);
            $player['slainMonsters'][] = $monster;
            $this->refillActiveMonsters($game);
            $message = $player['name'] . " rolled $roll +$bonus = $finalRoll and slayed " . $monster['name'] . '. ' . $this->applyMonsterReward($game, $player, $monster);
            $game['lastMessage'] = $this->appendWinMessage($player, $message);
        } else {
            $game['lastMessage'] = $player['name'] . " rolled $roll +$bonus = $finalRoll. Needed $requiredRoll. " . $this->applyMonsterPenalty($game, $player, $monster);
        }

        $this->saveGameWithPlayers($game);

        return [
            'success' => $won,
            'message' => $game['lastMessage'],
            'roll' => $roll,
            'bonus' => $bonus,
            'finalRoll' => $finalRoll,
            'requiredRoll' => $requiredRoll,
            'game' => $game
        ];
    }

    // aceasta functie termina tura curenta si trece la urmatorul jucator
    // cauta jocul gaseste indexul jucatorului curent trece la urmatorul reseteaza punctele de actiune actualizeaza mesajul si salveaza jocul
    public function endTurn(string $gameId): ?array
    {
        $game = $this->getGame($gameId);
        if (!$game) {
            return null;
        }

        $currentIndex = $this->indexById($game['players'], $game['currentTurnPlayerId']) ?? 0;
        $nextPlayer = $game['players'][($currentIndex + 1) % count($game['players'])];

        $game['currentTurnPlayerId'] = $nextPlayer['id'];
        $game['currentTurnPlayer'] = $nextPlayer;
        $game['actionPoints'] = 3;
        $game['lastMessage'] = $nextPlayer['name'] . ' turn started.';

        $this->gameRepo->save($game);
        return $game;
    }

    // aceasta functie returneaza toti jucatorii
    // apeleaza metoda findAll din playerRepo pentru a obtine toti jucatorii
    public function getAllPlayers(): array
    {
        return $this->playerRepo->findAll();
    }

    // aceasta functie returneaza toti monstrii
    // apeleaza metoda findAll din monsterRepo pentru a obtine toti monstrii
    public function getAllMonsters(): array
    {
        return $this->monsterRepo->findAll();
    }

    // aceasta functie returneaza toate cartile
    // apeleaza metoda findAll din cardRepo pentru a obtine toate cartile
    public function getAllCards(): array
    {
        return $this->cardRepo->findAll();
    }

    // aceasta functie ruleaza zarurile pentru un erou al unui jucator
    // verifica jocul daca e tura jucatorului gaseste eroul in party ruleaza doua zaruri calculeaza totalul si verifica daca e succes returnand rezultatele
    public function rollDice(string $gameId, string $playerId, string $heroId): array
    {
        $game = $this->getGame($gameId);
        if (!$game) {
            return $this->fail('Game not found.');
        }
        if ($game['currentTurnPlayerId'] !== $playerId) {
            return $this->fail('It is not this player turn.');
        }

        $player = $this->findById($game['players'], $playerId);
        $hero = $player ? $this->findById($player['party'], $heroId) : null;

        if (!$hero || ($hero['type'] ?? '') !== 'hero') {
            return $this->fail('Hero not in party.');
        }

        $die1 = rand(1, 6);
        $die2 = rand(1, 6);
        $total = $die1 + $die2;
        $required = $this->getRollRequirement($hero);

        return [
            'success' => $total >= $required,
            'message' => "Rolled $die1 + $die2 = $total. Required $required.",
            'roll' => $total,
            'dice' => [$die1, $die2]
        ];
    }

    // aceasta functie foloseste un modificator pentru un jucator
    // apeleaza metoda playCard pentru a juca modificatorul ca o carte
    public function useModifier(string $gameId, string $playerId, string $modifierId, int $modifierValue): array
    {
        return $this->playCard($gameId, $playerId, $modifierId);
    }

    // aceasta functie provoaca o carte a unui jucator tinta folosind o carte challenge
    // verifica jocul gaseste jucatorii si challenge-ul in mana apoi arunca challenge-ul si forteaza tinta sa arunce cartea specificata sau una aleatorie actualizeaza mesajul si salveaza
    public function challengeCard(string $gameId, string $challengerId, string $targetPlayerId, string $cardId): array
    {
        $game = $this->getGame($gameId);
        if (!$game) {
            return $this->fail('Game not found.');
        }

        $challengerIndex = $this->indexById($game['players'], $challengerId);
        $targetIndex = $this->indexById($game['players'], $targetPlayerId);

        if ($challengerIndex === null || $targetIndex === null) {
            return $this->fail('Player not found.');
        }

        $challengeIndex = $this->indexByType($game['players'][$challengerIndex]['hand'], 'challenge');
        if ($challengeIndex === null) {
            return $this->fail('No challenge card in hand.');
        }

        $challengeCard = array_splice($game['players'][$challengerIndex]['hand'], $challengeIndex, 1)[0];
        $game['discardPile'][] = $challengeCard;

        $discarded = $this->takeById($game['players'][$targetIndex]['hand'], $cardId);
        if ($discarded) {
            $game['discardPile'][] = $discarded;
            $message = $game['players'][$targetIndex]['name'] . ' discarded ' . $discarded['name'] . '.';
        } else {
            $message = $game['players'][$targetIndex]['name'] . ' had no matching card to discard.';
        }

        $game['lastMessage'] = 'Challenge used. ' . $message;
        $this->saveGameWithPlayers($game);

        return $this->ok($game['lastMessage']);
    }

    // aceasta functie arunca toate cartile din mana si trage 5 noi
    // verifica jocul daca poate folosi actiunea arunca toate cartile din mana trage 5 noi cheltuieste 3 puncte actualizeaza mesajul si salveaza
    public function discardAndDraw(string $gameId, string $playerId): array
    {
        $game = $this->getGame($gameId);
        if (!$game) {
            return $this->fail('Game not found.');
        }
        if (!$this->canUseAction($game, $playerId, 3)) {
            return $this->fail($game['lastMessage'] ?? 'Action not allowed.');
        }

        $playerIndex = $this->indexById($game['players'], $playerId);
        if ($playerIndex === null) {
            return $this->fail('Player not found.');
        }

        $player = &$game['players'][$playerIndex];
        $discardedCount = count($player['hand']);
        array_push($game['discardPile'], ...$player['hand']);
        $player['hand'] = [];

        $drawnCards = $this->drawCards($game, $player, 5);
        $this->spendActionPoints($game, $playerIndex, 3);
        $game['lastMessage'] = $player['name'] . ' discarded ' . $discardedCount . ' cards and drew ' . count($drawnCards) . ' new cards.';
        $this->saveGameWithPlayers($game);

        return $this->ok($game['lastMessage'], [
            'discarded' => $discardedCount,
            'drawn' => $drawnCards,
            'game' => $game
        ]);
    }

    // aceasta functie privata ataseaza jucatorii la un joc
    // adauga jucatorii din repository la array-ul players al jocului si seteaza currentTurnPlayer
    private function attachPlayers(array $game): array
    {
        $game['players'] = $this->playerRepo->findByGameId($game['id']);
        $game['currentTurnPlayer'] = $this->findById($game['players'], $game['currentTurnPlayerId']);
        return $game;
    }

    // aceasta functie privata indeparteaza o carte dintr-o zona a unui jucator
    // cauta jocul si jucatorul scoate cartea din zona specificata o adauga la discardPile actualizeaza mesajul si salveaza returnand rezultatul
    private function removePlayerCard(string $gameId, string $playerId, string $cardId, string $zone): array
    {
        $game = $this->getGame($gameId);
        if (!$game) {
            return $this->fail('Game not found.');
        }

        $playerIndex = $this->indexById($game['players'], $playerId);
        if ($playerIndex === null) {
            return $this->fail('Player not found.');
        }

        $card = $this->takeById($game['players'][$playerIndex][$zone], $cardId);
        if (!$card) {
            return $this->fail('Card not found.');
        }

        $game['discardPile'][] = $card;
        $game['lastMessage'] = $game['players'][$playerIndex]['name'] . ' discarded ' . $card['name'] . '.';
        $this->saveGameWithPlayers($game);

        return $this->ok($game['lastMessage'], ['card' => $card, 'game' => $this->getGame($gameId)]);
    }

    // aceasta functie privata aplica efectul unei carti jucate
    // foloseste match pentru a determina tipul cartii si apeleaza metoda corespunzatoare pentru aplicarea efectului returnand mesajul rezultat
    private function applyPlayedCard(array &$game, array &$player, array $card): string
    {
        return match ($card['type']) {
            'hero' => $this->playHero($player, $card),
            'item' => $this->addPartyCard($player, $card, ' equipped ' . $card['name'] . '. It gives +1 when attacking monsters.'),
            'modifier' => $this->addPartyCard($player, $card, ' prepared ' . $card['name'] . '. It gives +' . $this->getCardAttackBonus($card) . ' when attacking monsters.'),
            'magic' => $this->discardAndApplyMagic($game, $player, $card),
            'challenge' => $this->discardAndApplyChallenge($game, $player, $card),
            default => $this->discardGenericCard($game, $player, $card),
        };
    }

    // aceasta functie privata joaca un erou in party
    // verifica daca party-ul are deja 5 eroi daca nu adauga eroul si returneaza mesajul altfel returneaza string gol
    private function playHero(array &$player, array $card): string
    {
        if ($this->countByType($player['party'], 'hero') >= 5) {
            return '';
        }

        $player['party'][] = $card;
        return $player['name'] . ' played hero ' . $card['name'] . '.';
    }

    // aceasta functie privata adauga o carte la party cu un mesaj
    // adauga cartea la party-ul jucatorului si returneaza mesajul specific
    private function addPartyCard(array &$player, array $card, string $message): string
    {
        $player['party'][] = $card;
        return $player['name'] . $message;
    }

    // aceasta functie privata arunca si aplica efectul unei carti magice
    // arunca cartea la discardPile apoi verifica numele cartii si aplica efectul specific returnand mesajul
    private function discardAndApplyMagic(array &$game, array &$player, array $card): string
    {
        $game['discardPile'][] = $card;

        if ($card['name'] === 'Fire Spell') {
            if (empty($game['activeMonsters'])) {
                return $player['name'] . ' used Fire Spell, but there was no active monster.';
            }

            $monster = array_shift($game['activeMonsters']);
            $game['activeMonsters'][] = empty($game['monsterDeck']) ? $monster : array_shift($game['monsterDeck']);
            return $player['name'] . ' used Fire Spell and replaced ' . $monster['name'] . '.';
        }

        if ($card['name'] === 'Healing Spell') {
            $drawn = $this->drawCards($game, $player, 1);
            return $drawn
                ? $player['name'] . ' used Healing Spell and drew ' . $drawn[0]['name'] . '.'
                : $player['name'] . ' used Healing Spell, but the deck was empty.';
        }

        return $player['name'] . ' used ' . $card['name'] . '.';
    }

    // aceasta functie privata arunca si aplica efectul unei carti challenge
    // arunca cartea la discardPile apoi gaseste tinta urmatoarea forteaza sa arunce prima carte din mana si returneaza mesajul
    private function discardAndApplyChallenge(array &$game, array &$player, array $card): string
    {
        $game['discardPile'][] = $card;
        $playerIndex = $this->indexById($game['players'], $player['id']) ?? 0;
        $target = &$game['players'][($playerIndex + 1) % count($game['players'])];

        if (empty($target['hand'])) {
            return $player['name'] . ' used ' . $card['name'] . ', but ' . $target['name'] . ' had no cards.';
        }

        $discarded = array_shift($target['hand']);
        $game['discardPile'][] = $discarded;
        return $player['name'] . ' used ' . $card['name'] . '. ' . $target['name'] . ' discarded ' . $discarded['name'] . '.';
    }

    // aceasta functie privata arunca o carte generica
    // adauga cartea la discardPile si returneaza mesajul ca jucatorul a jucat cartea
    private function discardGenericCard(array &$game, array $player, array $card): string
    {
        $game['discardPile'][] = $card;
        return $player['name'] . ' played ' . $card['name'] . '.';
    }

    // aceasta functie privata aplica recompensa pentru uciderea unui monstru
    // verifica recompensa din cartea monstrii si trage cartile corespunzatoare returnand mesajul cu numele cartilor trase sau recompensa
    private function applyMonsterReward(array &$game, array &$player, array $monster): string
    {
        $reward = strtolower($monster['reward'] ?? '');
        $count = str_contains($reward, 'two') ? 2 : (str_contains($reward, 'one') || str_contains($reward, 'draw') ? 1 : 0);
        $drawn = $this->drawCards($game, $player, $count);

        return $drawn
            ? 'Reward: drew ' . implode(', ', array_column($drawn, 'name')) . '.'
            : 'Reward: ' . ($monster['reward'] ?? 'none');
    }

    // aceasta functie privata aplica pedeapsa pentru esecul atacului unui monstru
    // verifica pedeapsa din cartea monstrii si aplica efectul specific returnand mesajul corespunzator
    private function applyMonsterPenalty(array &$game, array &$player, array $monster): string
    {
        $penalty = strtolower($monster['penalty'] ?? '');

        if (str_contains($penalty, 'discard your hand')) {
            $count = count($player['hand']);
            array_push($game['discardPile'], ...$player['hand']);
            $player['hand'] = [];
            return 'Penalty: discarded the entire hand (' . $count . ' cards).';
        }

        if (str_contains($penalty, 'discard two')) {
            return $this->discardCardsFromHand($game, $player, 2);
        }

        if (str_contains($penalty, 'discard one')) {
            return $this->discardCardsFromHand($game, $player, 1);
        }

        if (str_contains($penalty, 'sacrifice')) {
            return $this->removeFirstPartyCard($game, $player, 'hero', 'sacrificed');
        }

        if (str_contains($penalty, 'item')) {
            return $this->removeFirstPartyCard($game, $player, 'item', 'destroyed item');
        }

        return 'Penalty: ' . ($monster['penalty'] ?? 'none');
    }

    // aceasta functie privata arunca un numar specific de carti din mana
    // scoate cartile din mana le adauga la discardPile si returneaza mesajul cu numele cartilor aruncate sau mesajul ca nu sunt carti
    private function discardCardsFromHand(array &$game, array &$player, int $count): string
    {
        $discarded = [];
        for ($i = 0; $i < $count && !empty($player['hand']); $i++) {
            $card = array_shift($player['hand']);
            $game['discardPile'][] = $card;
            $discarded[] = $card['name'];
        }

        return $discarded ? 'Penalty: discarded ' . implode(', ', $discarded) . '.' : 'Penalty: no cards to discard.';
    }

    // aceasta functie privata indeparteaza prima carte de un tip din party
    // gaseste indexul primei carti de tipul specific o scoate o adauga la discardPile si returneaza mesajul sau mesajul ca nu exista carte
    private function removeFirstPartyCard(array &$game, array &$player, string $type, string $action): string
    {
        $index = $this->indexByType($player['party'], $type);
        if ($index === null) {
            return 'Penalty: no ' . $type . ' to ' . ($type === 'hero' ? 'sacrifice' : 'destroy') . '.';
        }

        $card = array_splice($player['party'], $index, 1)[0];
        $game['discardPile'][] = $card;
        return 'Penalty: ' . $action . ' ' . $card['name'] . '.';
    }

    // aceasta functie privata construieste pachetul principal de carti
    // ia toate cartile din repository si creeaza 4 copii pentru fiecare returnand array-ul amestecat
    private function buildMainDeck(): array
    {
        $deck = [];
        foreach (range(1, 4) as $copy) {
            foreach ($this->cardRepo->findAll() as $card) {
                $card['id'] .= '_copy_' . $copy;
                $deck[] = $card;
            }
        }

        return $deck;
    }

    // aceasta functie privata verifica daca un jucator poate folosi o actiune cu cost dat
    // verifica daca e tura jucatorului si daca are suficiente puncte de actiune returnand true sau false si actualizand mesajul daca nu
    private function canUseAction(array &$game, string $playerId, int $cost): bool
    {
        if ($game['currentTurnPlayerId'] !== $playerId) {
            $game['lastMessage'] = 'It is not this player turn.';
            $this->gameRepo->save($game);
            return false;
        }

        if ($game['actionPoints'] < $cost) {
            $game['lastMessage'] = 'Not enough action points.';
            $this->gameRepo->save($game);
            return false;
        }

        return true;
    }

    // aceasta functie privata trage un numar specific de carti pentru un jucator
    // apeleaza drawOneCard de count ori si adauga cartile la mana jucatorului returnand array-ul cartilor trase
    private function drawCards(array &$game, array &$player, int $count): array
    {
        $cards = [];
        for ($i = 0; $i < $count; $i++) {
            $card = $this->drawOneCard($game);
            if (!$card) {
                break;
            }
            $player['hand'][] = $card;
            $cards[] = $card;
        }
        return $cards;
    }

    // aceasta functie privata trage o singura carte din pachet
    // daca mainDeck e gol si discardPile nu e gol amesteca discardPile in mainDeck apoi scoate prima carte sau returneaza null daca e gol
    private function drawOneCard(array &$game): ?array
    {
        if (empty($game['mainDeck']) && !empty($game['discardPile'])) {
            $game['mainDeck'] = $game['discardPile'];
            $game['discardPile'] = [];
            shuffle($game['mainDeck']);
        }

        return empty($game['mainDeck']) ? null : array_shift($game['mainDeck']);
    }

    // aceasta functie privata reumple monstrii activi
    // daca monsterDeck nu e gol adauga un monstru la activeMonsters
    private function refillActiveMonsters(array &$game): void
    {
        if (!empty($game['monsterDeck'])) {
            $game['activeMonsters'][] = array_shift($game['monsterDeck']);
        }
    }

    // aceasta functie privata cheltuieste punctele de actiune pentru un jucator
    // scade costul din actionPoints si actualizeaza currentTurnPlayer
    private function spendActionPoints(array &$game, int $playerIndex, int $cost): void
    {
        $game['actionPoints'] -= $cost;
        $game['currentTurnPlayer'] = $game['players'][$playerIndex];
    }

    // aceasta functie privata salveaza jocul si jucatorii
    // salveaza fiecare jucator apoi jocul in repositoryuri
    private function saveGameWithPlayers(array $game): void
    {
        foreach ($game['players'] as $player) {
            $this->playerRepo->save($player);
        }
        $this->gameRepo->save($game);
    }

    // aceasta functie privata calculeaza bonusul de atac pentru un jucator
    // parcurge party-ul jucatorului si adauga bonus pentru fiecare erou item sau modificator returnand totalul
    private function calculateAttackBonus(array $player): int
    {
        $bonus = 0;
        foreach ($player['party'] as $card) {
            $type = $card['type'] ?? '';
            if ($type === 'hero' || $type === 'item') {
                $bonus++;
            }
            if ($type === 'modifier') {
                $bonus += $this->getCardAttackBonus($card);
            }
        }
        return $bonus;
    }

    // aceasta functie privata obtine bonusul de atac al unei carti
    // verifica daca numele contine plus two returnand 2 altfel 1
    private function getCardAttackBonus(array $card): int
    {
        return str_contains(strtolower($card['name'] ?? ''), 'plus two') ? 2 : 1;
    }

    // aceasta functie privata adauga mesajul de castig daca e cazul
    // verifica daca jucatorul are 3 monstri ucisi sau 5 eroi in party si adauga mesajul de castig la mesajul dat
    private function appendWinMessage(array $player, string $message): string
    {
        if (count($player['slainMonsters']) >= 3) {
            return $message . ' ' . $player['name'] . ' wins with 3 slain monsters!';
        }
        if ($this->countByType($player['party'], 'hero') >= 5) {
            return $message . ' ' . $player['name'] . ' wins with 5 heroes in the party!';
        }
        return $message;
    }

    // aceasta functie privata obtine jocul dupa id-ul unui jucator
    // cauta jucatorul in repository si daca are gameId returneaza jocul altfel null
    private function getGameByPlayerId(string $playerId): ?array
    {
        $player = $this->playerRepo->findById($playerId);
        return $player && !empty($player['gameId']) ? $this->getGame($player['gameId']) : null;
    }

    // aceasta functie privata gaseste indexul unui item dupa id in array
    // parcurge array-ul si returneaza indexul daca gaseste id-ul altfel null
    private function indexById(array $items, string $id): ?int
    {
        foreach ($items as $index => $item) {
            if (($item['id'] ?? '') === $id) {
                return $index;
            }
        }
        return null;
    }

    // aceasta functie privata gaseste un item dupa id in array
    // foloseste indexById pentru a obtine indexul apoi returneaza itemul sau null
    private function findById(array $items, string $id): ?array
    {
        $index = $this->indexById($items, $id);
        return $index === null ? null : $items[$index];
    }

    // aceasta functie privata scoate un item dupa id din array
    // foloseste indexById pentru a obtine indexul apoi scoate itemul cu array_splice si il returneaza sau null
    private function takeById(array &$items, string $id): ?array
    {
        $index = $this->indexById($items, $id);
        return $index === null ? null : array_splice($items, $index, 1)[0];
    }

    // aceasta functie privata gaseste indexul primei carti de un tip
    // parcurge cartile si returneaza indexul daca gaseste tipul altfel null
    private function indexByType(array $cards, string $type): ?int
    {
        foreach ($cards as $index => $card) {
            if (($card['type'] ?? '') === $type) {
                return $index;
            }
        }
        return null;
    }

    // aceasta functie privata numara cartile de un tip
    // foloseste array_filter pentru a filtra cartile dupa tip si returneaza count-ul
    private function countByType(array $cards, string $type): int
    {
        return count(array_filter($cards, fn (array $card) => ($card['type'] ?? '') === $type));
    }

    // aceasta functie privata obtine cerinta de roll pentru o carte
    // returneaza rollRequirement sau roll_requirement sau 7 ca default
    private function getRollRequirement(array $card): int
    {
        return (int) ($card['rollRequirement'] ?? $card['roll_requirement'] ?? 7);
    }

    // aceasta functie privata construieste jucatorii pentru un joc
    // foloseste un array de lideri si creeaza jucatori cu id-uri unice nume lideri clase si alte proprietati initiale
    private function buildPlayers(string $gameId): array
    {
        $leaders = [
            ['Player 1', 'Blade Leader', 'fighter'],
            ['Player 2', 'Shadow Leader', 'thief'],
            ['Player 3', 'Mystic Leader', 'wizard'],
            ['Player 4', 'Forest Leader', 'ranger']
        ];

        return array_map(function (array $leader, int $index) use ($gameId) {
            return [
                'id' => $gameId . '_p' . ($index + 1),
                'name' => $leader[0],
                'partyLeader' => $leader[1],
                'partyLeaderClass' => $leader[2],
                'hand' => [],
                'party' => [],
                'slainMonsters' => [],
                'gameId' => $gameId,
                'role' => 'player'
            ];
        }, $leaders, array_keys($leaders));
    }

    // aceasta functie privata returneaza un array de succes cu mesaj si date extra
    // combina success true mesajul si array-ul extra
    private function ok(string $message, array $extra = []): array
    {
        return ['success' => true, 'message' => $message] + $extra;
    }

    // aceasta functie privata returneaza un array de esec cu mesaj
    // returneaza success false si mesajul
    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
