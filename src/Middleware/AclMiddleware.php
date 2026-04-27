<?php

namespace App\Middleware;

use App\Acl\AccessControlList;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// aceasta clasa implementeaza middleware pentru controlul accesului folosind ACL
// verifica daca utilizatorul are permisiunea pentru ruta solicitata si returneaza 403 daca nu
class AclMiddleware implements MiddlewareInterface
{
    // aceasta functie initializeaza middleware-ul cu ACL-ul
    // primeste instanta AccessControlList ca parametru privat
    public function __construct(private AccessControlList $acl) {}

    // aceasta functie proceseaza request-ul si verifica permisiunile
    // obtine ruta rolul resursa si privilegiul apoi verifica daca e permis daca da continua cu handler-ul altfel returneaza 403
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $route = $request->getAttribute('route');

        if (!$route) {
            return $handler->handle($request);
        }

        $role = $this->getRole($request);
        $pattern = $route->getPattern();
        $resource = $this->getResource($pattern);
        $privilege = $this->getPrivilege($pattern, $request->getMethod());

        if ($this->acl->isAllowed($role, $resource, $privilege)) {
            return $handler->handle($request);
        }

        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Access denied']));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

    // aceasta functie privata obtine rolul utilizatorului din request
    // cauta rolul in query params sau header X-User-Role daca nu gaseste foloseste guest si valideaza ca e unul din rolurile permise
    private function getRole(Request $request): string
    {
        $role = $request->getQueryParams()['role'] ?? $request->getHeaderLine('X-User-Role') ?: 'guest';
        return in_array($role, ['guest', 'player', 'admin'], true) ? $role : 'guest';
    }

    // aceasta functie privata obtine resursa din pattern-ul rutei
    // verifica daca pattern-ul contine cuvinte cheie pentru card player monster altfel returneaza game
    private function getResource(string $pattern): string
    {
        if ($this->contains($pattern, ['hand', 'party', 'cards', 'deck', 'discard-draw'])) {
            return 'card';
        }
        if (str_contains($pattern, 'players')) {
            return 'player';
        }
        if (str_contains($pattern, 'monsters')) {
            return 'monster';
        }
        return 'game';
    }

    // aceasta functie privata obtine privilegiul din pattern si metoda HTTP
    // verifica metoda GET apoi cuvinte cheie in pattern pentru draw play discard attack altfel foloseste match pentru metoda
    private function getPrivilege(string $pattern, string $method): string
    {
        if ($method === 'GET') {
            return 'view';
        }
        if (str_contains($pattern, 'deck/draw')) {
            return 'draw';
        }
        if (str_contains($pattern, 'cards/play') || str_contains($pattern, 'turn/end')) {
            return 'play';
        }
        if ($this->contains($pattern, ['discard-draw', 'hand', 'party'])) {
            return 'discard';
        }
        if (str_contains($pattern, 'attack')) {
            return 'attack';
        }

        return match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update_own',
            'DELETE' => 'delete',
            default => 'view'
        };
    }

    // aceasta functie privata verifica daca textul contine vreun cuvant din array
    // parcurge array-ul de cuvinte si verifica daca fiecare apare in text returnand true la primul gasit sau false
    private function contains(string $text, array $words): bool
    {
        foreach ($words as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }
        return false;
    }
}
