<?php

namespace App\Acl;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;
use Laminas\Permissions\Acl\Role\GenericRole as Role;

// aceasta clasa gestioneaza lista de control al accesului folosind Laminas ACL
// defineste roluri resurse si permisiuni pentru a controla ce actiuni pot fi efectuate de fiecare rol
class AccessControlList
{
    private Acl $acl;

    // aceasta functie initializeaza ACL-ul
    // creeaza instanta Acl apoi adauga roluri resurse si permisiuni
    public function __construct()
    {
        $this->acl = new Acl();
        $this->addRoles();
        $this->addResources();
        $this->addPermissions();
    }

    // aceasta functie verifica daca un rol are permisiunea pentru o resursa si privilegiu
    // apeleaza metoda isAllowed din Acl pentru a verifica permisiunea
    public function isAllowed(string $role, string $resource, string $privilege): bool
    {
        return $this->acl->isAllowed($role, $resource, $privilege);
    }

    // aceasta functie returneaza instanta Acl
    // returneaza obiectul Acl pentru utilizare directa daca e necesar
    public function getAcl(): Acl
    {
        return $this->acl;
    }

    // aceasta functie privata adauga rolurile in ACL
    // adauga rolurile guest player si admin cu ierarhia player mosteneste de la guest admin mosteneste de la player
    private function addRoles(): void
    {
        $this->acl->addRole(new Role('guest'));
        $this->acl->addRole(new Role('player'), 'guest');
        $this->acl->addRole(new Role('admin'), 'player');
    }

    // aceasta functie privata adauga resursele in ACL
    // adauga resursele game player card monster si admin ca GenericResource
    private function addResources(): void
    {
        foreach (['game', 'player', 'card', 'monster', 'admin'] as $resource) {
            $this->acl->addResource(new Resource($resource));
        }
    }

    // aceasta functie privata adauga permisiunile in ACL
    // permite guest sa vada toate resursele permite player sa efectueze actiuni specifice pe resurse si permite admin toate actiunile
    private function addPermissions(): void
    {
        foreach (['game', 'player', 'card', 'monster'] as $resource) {
            $this->acl->allow('guest', $resource, 'view');
        }

        $this->acl->allow('player', 'game', ['create', 'join', 'play', 'update_own']);
        $this->acl->allow('player', 'player', ['update_own']);
        $this->acl->allow('player', 'card', ['draw', 'play', 'discard']);
        $this->acl->allow('player', 'monster', ['attack']);
        $this->acl->allow('admin');
    }
}
