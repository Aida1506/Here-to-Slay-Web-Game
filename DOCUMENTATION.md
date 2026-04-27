# Documentatie Proiect - Here to Slay API

# acesta este titlul documentatiei proiectului

## 1. Descriere

# aceasta sectiune descrie proiectul in general
Proiectul implementeaza o aplicatie web si un API REST pentru un joc inspirat de "Here to Slay". Aplicatia permite crearea unei sesiuni de joc, gestionarea jucatorilor, cartilor, monstrilor si executarea actiunilor principale din joc.

Interfata web este servita din folderul `public`, iar logica de backend este organizata in `src`.

## 2. Tehnologii folosite

# aceasta sectiune listeaza tehnologiile folosite in proiect
- PHP 8
- Slim Framework 4 pentru routing
- Doctrine DBAL pentru acces la baza de date
- Laminas ACL pentru roluri si permisiuni
- SQLite pentru baza de date locala
- OpenAPI 3.0 pentru documentarea endpoint-urilor

## 3. Structura proiectului

# aceasta sectiune descrie structura folderelor proiectului
```text
config/                 configurare baza de date
public/                 punctul de intrare web si asset-uri frontend
src/Acl/                configurare roluri si permisiuni
src/Database/           conectare si abstractizare baza de date
src/Middleware/         middleware pentru ACL
src/Repository/         operatii de citire/scriere in baza de date
src/Service/            logica jocului
templates/              pagina principala a jocului
storage/                fisiere locale generate, inclusiv SQLite
openapi.yaml            specificatie API
setup_database.php      script initializare baza de date
```

## 4. Rulare locala

# aceasta sectiune explica cum sa rulezi proiectul local
Din folderul proiectului:

```powershell
cd C:\xampp\htdocs\proiectpw
php setup_database.php
php -S localhost:8000 -t public
```

Aplicatia se deschide la:

```text
http://localhost:8000
```

Daca portul 8000 este ocupat:

```powershell
php -S localhost:8101 -t public
```

## 5. Baza de date

# aceasta sectiune explica configurarea bazei de date
Configurarea se afla in:

```text
config/database.php
```

In configuratia curenta se foloseste SQLite:

```text
storage/database.sqlite
```

Scriptul `setup_database.php` initializeaza tabelele si datele necesare.

## 6. Roluri si permisiuni

# aceasta sectiune explica sistemul de roluri si permisiuni
API-ul foloseste ACL cu trei roluri:

- `guest`: poate vedea jocuri, jucatori, carti si monstri
- `player`: poate crea jocuri si poate executa actiuni de joc
- `admin`: poate face orice actiune, inclusiv stergeri

Rolul poate fi trimis prin query parameter:

```text
http://localhost:8000/games?role=player
```

sau prin header:

```text
X-User-Role: player
```

## 7. Endpoint-uri importante

# aceasta sectiune listeaza endpoint-urile importante grupate

### Jocuri

# endpoint-uri pentru gestionarea jocurilor
```text
GET    /games
POST   /games
GET    /games/{gameId}
PUT    /games/{gameId}
PATCH  /games/{gameId}
DELETE /games/{gameId}
POST   /games/{gameId}/turn/end
```

### Jucatori

# endpoint-uri pentru gestionarea jucatorilor
```text
GET   /players
PUT   /players/{playerId}
PATCH /players/{playerId}
```

### Carti si actiuni

# endpoint-uri pentru carti si actiuni de joc
```text
GET    /cards
POST   /deck/draw
POST   /games/{gameId}/cards/play
DELETE /games/{gameId}/players/{playerId}/hand/{cardId}
DELETE /games/{gameId}/players/{playerId}/party/{cardId}
```

### Monstri

# endpoint-uri pentru gestionarea monstrilor
```text
GET    /monsters
POST   /monsters/{monsterId}/attack
DELETE /games/{gameId}/active-monsters/{monsterId}
```

## 8. Exemple request

# aceasta sectiune ofera exemple de request-uri pentru API

### Creare joc

# exemplu de creare a unui joc nou
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/games?role=player" -Method Post -ContentType "application/json" -Body '{"name":"Game 1"}'
```

Body JSON:

```json
{
  "name": "Game 1"
}
```

### Actualizare joc

# exemplu de actualizare partiala a unui joc
```http
PATCH /games/{gameId}?role=player
Content-Type: application/json
```

```json
{
  "name": "Game updated",
  "actionPoints": 2
}
```

### Actualizare jucator

# exemplu de actualizare a unui jucator
```http
PATCH /players/{playerId}?role=player
Content-Type: application/json
```

```json
{
  "name": "Alex",
  "partyLeader": "Blade Leader",
  "partyLeaderClass": "fighter"
}
```

### Tragere carte

# exemplu de tragere a unei carti
```http
POST /deck/draw?role=player
Content-Type: application/json
```

```json
{
  "gameId": "game_...",
  "playerId": "game_..._p1"
}
```

### Joc carte

# exemplu de joc a unei carti
```http
POST /games/{gameId}/cards/play?role=player
Content-Type: application/json
```

```json
{
  "playerId": "game_..._p1",
  "cardId": "c1_copy_1"
}
```

### Stergere joc

# exemplu de stergere a unui joc
```http
DELETE /games/{gameId}?role=admin
```

### Obtinere tutorial

# exemplu de obtinere a tuturor pasilor tutorialului
```http
GET /tutorial
```

### Obtinere pas tutorial dupa numar

# exemplu de obtinere a unui pas specific din tutorial
```http
GET /tutorial/step/1
```

### Obtinere pas tutorial dupa actiune

# exemplu de obtinere a pasului tutorialului dupa actiune
```http
GET /tutorial/action/draw_card
```

## 9. Reguli implementate

# aceasta sectiune descrie regulile jocului implementate
- fiecare joc are 4 jucatori
- fiecare jucator primeste carti la crearea jocului
- jucatorii au puncte de actiune
- se pot trage si juca carti
- se pot ataca monstri cu zaruri
- monstrii invinsi intra la `slainMonsters`
- jocul verifica mesaje de castig
- tura poate fi schimbata intre jucatori

## 10. Observatii pentru prezentare

# aceasta sectiune ofera observatii pentru prezentarea proiectului
- Pornirea recomandata este cu serverul PHP built-in.
- MySQL din XAMPP nu este obligatoriu daca proiectul foloseste SQLite.
- Specificatia API completa este in `openapi.yaml`.
- Pentru metode care modifica date, foloseste `?role=player` sau `?role=admin`.
