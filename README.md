# Here to Slay API

# acesta este titlul proiectului API pentru jocul Here to Slay
Aplicatie web si REST API pentru jocul de carti "Here to Slay", dezvoltata in PHP cu Slim Framework.

## Cerinte

# aceasta sectiune listeaza cerintele pentru rularea proiectului
- PHP 8.0+
- Composer
- XAMPP sau PHP instalat local
- SQLite sau MySQL

Proiectul este deja configurat sa foloseasca SQLite, deci MySQL din XAMPP nu este obligatoriu pentru rulare.

## Instalare

# aceasta sectiune explica cum sa instalezi proiectul
Din folderul proiectului:

```powershell
cd C:\xampp\htdocs\proiectpw
composer install
php setup_database.php
```

Daca nu ai Composer global, poti folosi fisierul inclus:

```powershell
php composer.phar install
php setup_database.php
```

## Pornire aplicatie

# aceasta sectiune explica cum sa pornesti aplicatia
Varianta recomandata:

```powershell
cd C:\xampp\htdocs\proiectpw
php -S localhost:8000 -t public
```

Deschide in browser:

```text
http://localhost:8000
```

Daca portul 8000 este ocupat, foloseste alt port:

```powershell
php -S localhost:8101 -t public
```

si deschide:

```text
http://localhost:8101
```

## Documentatie API

# aceasta sectiune indica unde gasesti documentatia
- Specificatia OpenAPI este in [openapi.yaml](openapi.yaml)
- Documentatia proiectului este in [DOCUMENTATION.md](DOCUMENTATION.md)

## Endpoint-uri principale

# aceasta sectiune listeaza endpoint-urile principale grupate pe categorii

### Jocuri

# endpoint-uri pentru gestionarea jocurilor
- `GET /games` - lista jocurilor
- `POST /games` - creeaza joc
- `GET /games/{gameId}` - detalii joc
- `PUT /games/{gameId}` - actualizeaza campurile jocului
- `PATCH /games/{gameId}` - actualizeaza partial jocul
- `DELETE /games/{gameId}` - sterge jocul
- `POST /games/{gameId}/turn/end` - termina tura curenta

### Actiuni joc

# endpoint-uri pentru actiunile din joc
- `POST /deck/draw` - trage o carte
- `POST /games/{gameId}/cards/play` - joaca o carte
- `POST /games/{gameId}/heroes/{heroId}/roll` - arunca zarurile pentru un erou
- `POST /games/{gameId}/modifiers/use` - foloseste modifier
- `POST /games/{gameId}/challenges` - foloseste challenge
- `POST /games/{gameId}/discard-draw` - arunca mana si trage 5 carti
- `POST /monsters/{monsterId}/attack` - ataca un monstru
- `DELETE /games/{gameId}/players/{playerId}/hand/{cardId}` - arunca o carte din mana
- `DELETE /games/{gameId}/players/{playerId}/party/{cardId}` - elimina o carte din party
- `DELETE /games/{gameId}/active-monsters/{monsterId}` - elimina un monstru activ

### Date statice

# endpoint-uri pentru date statice
- `GET /players`
- `GET /cards`
- `GET /monsters`
- `GET /tutorial`
- `GET /tutorial/step/{step}`
- `GET /tutorial/action/{action}`

## Roluri ACL

# aceasta sectiune explica sistemul de roluri pentru controlul accesului
Rolul se trimite prin query parameter sau header:

```text
?role=player
X-User-Role: player
```

Roluri disponibile:

- `guest` - poate vizualiza resurse
- `player` - poate crea jocuri si face actiuni de joc
- `admin` - are acces complet, inclusiv delete pentru jocuri

## Test rapid

# aceasta sectiune arata cum sa testezi rapid API-ul
Porneste serverul:

```powershell
php -S localhost:8000 -t public
```

Creeaza un joc:

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/games?role=player" -Method Post -ContentType "application/json" -Body '{"name":"Test Game"}'
```

Lista jocurilor:

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/games" -Method Get
```
