<!DOCTYPE html>
<!-- aceasta linie declara tipul documentului HTML -->
<html lang="en">
<!-- aceasta sectiune contine head-ul paginii cu metadate si link-uri -->
<head>
    <meta charset="UTF-8">
    <!-- aceasta meta seteaza charset-ul la UTF-8 -->
    <title>Here to Slay Board</title>
    <!-- acesta e titlul paginii afisat in browser -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- aceasta meta face pagina responsiva pe dispozitive mobile -->
    <meta name="api-base" content="<?= htmlspecialchars($basePath ?: '') ?>">
    <!-- aceasta meta contine calea de baza pentru API -->
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath ?: '') ?>/assets/css/game.css">
    <!-- acesta e link-ul catre fisierul CSS pentru stilizare -->
</head>
<!-- aceasta sectiune contine corpul paginii cu continutul vizibil -->
<body>
    <!-- aceasta sectiune e panoul principal al aplicatiei -->
    <main class="app-shell">
        <!-- aceasta sectiune e panoul de sus cu titlul si controalele jocului -->
        <section class="top-panel">
            <div>
                <p class="eyebrow">Digital Board Game</p>
                <!-- acesta e textul mic de deasupra titlului -->
                <h1>Here to Slay</h1>
                <!-- acesta e titlul principal al jocului -->
            </div>

            <div class="game-controls">
                <!-- aceasta sectiune contine butoanele pentru controlul jocului -->
                <button id="newGameBtn">New Game</button>
                <!-- acesta e butonul pentru a crea un joc nou -->
                <button id="refreshBtn">Refresh</button>
                <!-- acesta e butonul pentru a reincarca datele jocului -->
            </div>
        </section>

        <!-- aceasta sectiune e bara de status cu informatii despre joc -->
        <section class="status-bar">
            <div>
                <span>Current turn</span>
                <!-- acesta e label-ul pentru randul curent -->
                <strong id="currentPlayer">-</strong>
                <!-- acesta afiseaza numele jucatorului la rand -->
            </div>
            <div>
                <span>Action points</span>
                <!-- acesta e label-ul pentru punctele de actiune -->
                <strong id="actionPoints">3</strong>
                <!-- acesta afiseaza numarul de puncte de actiune ramase -->
            </div>
            <div>
                <span>Main deck</span>
                <!-- acesta e label-ul pentru pachetul principal -->
                <strong id="mainDeckCount">0</strong>
                <!-- acesta afiseaza numarul de carti din pachetul principal -->
            </div>
            <div>
                <span>Monster deck</span>
                <!-- acesta e label-ul pentru pachetul de monstri -->
                <strong id="monsterDeckCount">0</strong>
                <!-- acesta afiseaza numarul de monstri din pachet -->
            </div>
        </section>

        <!-- aceasta sectiune e wrapper-ul pentru tabla de joc -->
        <section class="board-wrapper">
            <div class="board">
                <!-- aceasta e tabla principala de joc -->
                <div class="board-glow"></div>
                <!-- acesta e efectul de glow pe tabla -->

                <!-- aceste sectiuni sunt zonele pentru jucatori -->
                <section id="playerTop" class="player-zone player-zone-top"></section>
                <!-- zona jucatorului de sus -->
                <section id="playerLeft" class="player-zone player-zone-left"></section>
                <!-- zona jucatorului din stanga -->
                <section id="playerRight" class="player-zone player-zone-right"></section>
                <!-- zona jucatorului din dreapta -->
                <section id="playerBottom" class="player-zone player-zone-bottom"></section>
                <!-- zona jucatorului de jos -->

                <!-- aceasta sectiune e zona centrala cu monstri si pachete -->
                <section class="center-zone">
                    <div class="monster-area">
                        <!-- aceasta zona afiseaza monstrii activi -->
                        <h2>Active Monsters</h2>
                        <!-- titlul pentru zona monstri -->
                        <div id="activeMonsters" class="monster-row"></div>
                        <!-- randul cu monstrii activi -->
                    </div>

                    <div class="deck-area">
                        <!-- aceasta zona contine pachetele si zarurile -->
                        <div class="deck-card main-deck">
                            <!-- cartea reprezentand pachetul principal -->
                            <span>Main Deck</span>
                            <!-- label-ul pentru pachetul principal -->
                            <strong id="mainDeckMiniCount">0</strong>
                            <!-- numarul de carti din pachet -->
                        </div>

                        <div class="dice-box">
                            <!-- cutia cu zaruri -->
                            <div id="dieOne" class="die">?</div>
                            <!-- primul zar -->
                            <div id="dieTwo" class="die">?</div>
                            <!-- al doilea zar -->
                            <button id="rollBtn">Roll Dice</button>
                            <!-- butonul pentru a arunca zarurile -->
                        </div>

                        <div class="deck-card discard-deck">
                            <!-- cartea reprezentand pachetul de discard -->
                            <span>Discard</span>
                            <!-- label-ul pentru discard -->
                            <strong id="discardCount">0</strong>
                            <!-- numarul de carti din discard -->
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <!-- aceasta sectiune e panoul cu actiuni disponibile -->
        <section class="action-panel">
            <button id="drawBtn">Draw Card - 1 AP</button>
            <!-- butonul pentru a trage o carte -->
            <button id="discardDrawBtn">Discard Hand + Draw 5 - 3 AP</button>
            <!-- butonul pentru a arunca mana si trage 5 carti -->
            <button id="attackBtn">Attack Selected Monster - 2 AP</button>
            <!-- butonul pentru a ataca un monstru selectat -->
            <button id="endTurnBtn">End Turn</button>
            <!-- butonul pentru a termina randul -->
        </section>

        <!-- aceasta sectiune e panoul cu mana jucatorului -->
        <section class="hand-panel">
            <div class="hand-header">
                <!-- header-ul pentru mana -->
                <div>
                    <p class="eyebrow">Active player's hand</p>
                    <!-- text mic pentru mana jucatorului activ -->
                    <h2 id="handOwner">Player 1</h2>
                    <!-- numele jucatorului al carui mana e afisata -->
                </div>
                <p id="messageBox">Create or load a game.</p>
                <!-- caseta cu mesaje pentru utilizator -->
            </div>

            <div id="playerHand" class="hand-row"></div>
            <!-- randul cu cartile din mana -->
        </section>
    </main>

    <script src="<?= htmlspecialchars($basePath ?: '') ?>/assets/js/game.js"></script>
    <!-- acesta e script-ul JavaScript pentru functionalitatea jocului -->
</body>
</html>