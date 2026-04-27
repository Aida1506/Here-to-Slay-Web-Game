// aceasta sectiune defineste constantele si variabilele globale
const API_BASE = document.querySelector('meta[name="api-base"]').content;
// aceasta constanta contine calea de baza pentru API din meta tag
const GAME_KEY = 'hereToSlayGameId';
// aceasta constanta e cheia pentru localStorage unde se salveaza ID-ul jocului

let currentGame = null;
// aceasta variabila tine jocul curent incarcat
let selectedMonsterId = null;
// aceasta variabila tine ID-ul monstruului selectat pentru atac
let lastRoll = 0;
// aceasta variabila tine ultimul rezultat al zarurilor

// aceasta functie helper obtine un element dupa ID
const $ = id => document.getElementById(id);
// aceasta functie helper obtine elementele DOM dupa ID-urile lor
const els = {
    newGameBtn: $('newGameBtn'),
    refreshBtn: $('refreshBtn'),
    currentPlayer: $('currentPlayer'),
    actionPoints: $('actionPoints'),
    mainDeckCount: $('mainDeckCount'),
    mainDeckMiniCount: $('mainDeckMiniCount'),
    monsterDeckCount: $('monsterDeckCount'),
    discardCount: $('discardCount'),
    activeMonsters: $('activeMonsters'),
    playerTop: $('playerTop'),
    playerLeft: $('playerLeft'),
    playerRight: $('playerRight'),
    playerBottom: $('playerBottom'),
    playerHand: $('playerHand'),
    handOwner: $('handOwner'),
    messageBox: $('messageBox'),
    drawBtn: $('drawBtn'),
    discardDrawBtn: $('discardDrawBtn'),
    attackBtn: $('attackBtn'),
    endTurnBtn: $('endTurnBtn'),
    rollBtn: $('rollBtn'),
    dieOne: $('dieOne'),
    dieTwo: $('dieTwo')
};

// aceasta functie face un request HTTP la API
// primeste calea si optiunile apoi returneaza datele JSON sau arunca eroare
async function request(path, options = {}) {
    const response = await fetch(`${API_BASE}${path}`, {
        headers: {
            'Content-Type': 'application/json',
            'X-User-Role': 'player'
        },
        ...options
    });

    const data = await response.json().catch(() => ({ success: false, message: 'Server response could not be read.' }));

    if (!response.ok) {
        throw new Error(data.message || data.error || 'Request failed.');
    }

    return data;
}

// aceasta functie helper face un POST request
// primeste calea si datele apoi apeleaza request cu metoda POST
function post(path, data = {}) {
    return request(path, {
        method: 'POST',
        body: JSON.stringify(data)
    });
}

// aceasta functie ruleaza o actiune si prinde erorile
// apeleaza actiunea si daca esueaza afiseaza mesajul de eroare in messageBox
async function runAction(action) {
    try {
        await action();
    } catch (error) {
        els.messageBox.textContent = error.message;
    }
}

// aceasta functie initializeaza aplicatia
// incarca jocul salvat din localStorage sau creeaza unul nou apoi afiseaza jocul
async function init() {
    const savedGameId = localStorage.getItem(GAME_KEY);

    if (savedGameId) {
        currentGame = await request(`/games/${savedGameId}`).catch(() => null);
    }

    if (!currentGame) {
        const games = await request('/games');
        currentGame = games[0] || await createGame();
    }

    localStorage.setItem(GAME_KEY, currentGame.id);
    renderGame();
}

// aceasta functie creeaza un joc nou
// face POST la /games cu numele apoi salveaza ID-ul in localStorage
async function createGame() {
    const game = await post('/games', { name: 'Table Game' });
    localStorage.setItem(GAME_KEY, game.id);
    return game;
}

// aceasta functie reincarca jocul curent
// face request la API pentru jocul curent apoi reafiseaza
async function reloadGame() {
    if (!currentGame?.id) {
        await init();
        return;
    }

    currentGame = await request(`/games/${currentGame.id}`);
    renderGame();
}

// aceasta functie afiseaza jocul in interfata
// verifica daca jocul exista apoi actualizeaza toate elementele UI cu datele jocului
function renderGame() {
    if (!currentGame) {
        return;
    }

    const player = getCurrentPlayer();
    const hasWinner = gameHasWinner();

    els.currentPlayer.textContent = player?.name || '-';
    els.actionPoints.textContent = currentGame.actionPoints;
    els.mainDeckCount.textContent = currentGame.mainDeck.length;
    els.mainDeckMiniCount.textContent = currentGame.mainDeck.length;
    els.monsterDeckCount.textContent = currentGame.monsterDeck.length;
    els.discardCount.textContent = currentGame.discardPile.length;
    els.messageBox.textContent = currentGame.lastMessage || 'Game loaded.';

    renderPlayers();
    renderMonsters();
    renderHand(player);
    setButtonsState(player, hasWinner);
}

// aceasta functie seteaza starea butoanelor
// dezactiveaza butoanele daca nu e jucator activ sau jocul s-a terminat sau nu sunt suficiente AP
function setButtonsState(player, hasWinner) {
    const noPlayer = !player || hasWinner;

    els.drawBtn.disabled = noPlayer || currentGame.actionPoints < 1;
    els.discardDrawBtn.disabled = noPlayer || currentGame.actionPoints < 3;
    els.attackBtn.disabled = noPlayer || !selectedMonsterId || currentGame.actionPoints < 2 || lastRoll === 0;
    els.rollBtn.disabled = noPlayer;
    els.endTurnBtn.disabled = noPlayer;
}

// aceasta functie afiseaza jucatorii in zonele lor
// parcurge zonele si jucatorii apoi afiseaza fiecare jucator in zona corespunzatoare
function renderPlayers() {
    const zones = [
        [els.playerBottom, currentGame.players[0]],
        [els.playerLeft, currentGame.players[1]],
        [els.playerTop, currentGame.players[2]],
        [els.playerRight, currentGame.players[3]]
    ];

    zones.forEach(([zone, player]) => {
        zone.innerHTML = player ? renderPlayer(player) : '';
    });
}

// aceasta functie returneaza HTML-ul pentru un jucator
// creeaza HTML cu numele statusul badge-urile si sloturile pentru party
function renderPlayer(player) {
    const status = player.id === currentGame.currentTurnPlayerId ? 'Active' : 'Waiting';

    return `
        <div class="player-title">
            <span>${escapeHtml(player.name)}</span>
            <span>${status}</span>
        </div>
        <div class="player-mini-data">
            <span class="badge">${escapeHtml(player.partyLeader)}</span>
            <span class="badge">${escapeHtml(player.partyLeaderClass)}</span>
            <span class="badge">Hand ${player.hand.length}</span>
            <span class="badge">Party ${player.party.length}</span>
            <span class="badge">Slain ${player.slainMonsters.length}</span>
        </div>
        <div class="party-grid">${renderPartySlots(player)}</div>
    `;
}

// aceasta functie returneaza HTML-ul pentru sloturile party ale unui jucator
// combina liderul party cu cartile din party si monstrii ucisi apoi le afiseaza in sloturi
function renderPartySlots(player) {
    const leader = {
        name: player.partyLeader,
        type: 'hero',
        class: player.partyLeaderClass,
        description: 'Party Leader'
    };

    const cards = [leader, ...player.party, ...player.slainMonsters.map(monster => ({ ...monster, type: 'monster', description: monster.reward }))];

    return cards.slice(0, 4).map(renderPartyCard).join('') + '<div class="party-slot"></div>'.repeat(Math.max(0, 4 - cards.length));
}

// aceasta functie returneaza HTML-ul pentru o carte din party
// creeaza HTML cu numele descrierea si tipul cartii
function renderPartyCard(card) {
    const type = card.type === 'monster' ? 'monster-card' : `card ${escapeHtml(card.type || '')} ${escapeHtml(card.class || '')}`;
    const label = card.type === 'monster' ? 'Slain' : escapeHtml(card.class || card.type || '');

    return `
        <div class="party-slot ${type}">
            <h3>${escapeHtml(card.name)}</h3>
            <p>${escapeHtml(card.description || '')}</p>
            <small>${label}</small>
        </div>
    `;
}

// aceasta functie afiseaza monstrii activi
// creeaza HTML pentru fiecare monstru cu numele cerinta de roll penalty si reward
function renderMonsters() {
    els.activeMonsters.innerHTML = currentGame.activeMonsters.map(monster => `
        <article class="monster-card ${selectedMonsterId === monster.id ? 'selected' : ''}" data-monster-id="${escapeHtml(monster.id)}">
            <h3>${escapeHtml(monster.name)}</h3>
            <p>Roll ${monster.rollRequirement}+ to slay.</p>
            <p>Penalty: ${escapeHtml(monster.penalty)}</p>
            <p>Reward: ${escapeHtml(monster.reward)}</p>
            <small>Monster</small>
        </article>
    `).join('');
}

// aceasta functie afiseaza mana jucatorului activ
// daca nu e jucator afiseaza mesaj altfel afiseaza cartile din mana
function renderHand(player) {
    if (!player) {
        els.handOwner.textContent = '-';
        els.playerHand.innerHTML = '<p class="empty-text">No active player.</p>';
        return;
    }

    els.handOwner.textContent = `${player.name} hand`;
    els.playerHand.innerHTML = player.hand.length
        ? player.hand.map(renderHandCard).join('')
        : '<p class="empty-text">No cards in hand.</p>';
}

// aceasta functie returneaza HTML-ul pentru o carte din mana
// creeaza HTML cu numele descrierea si butonul pentru a juca cartea
function renderHandCard(card) {
    const disabled = currentGame.actionPoints < 1 || gameHasWinner() ? 'disabled' : '';
    const rollText = card.rollRequirement ? `Roll ${card.rollRequirement}+` : escapeHtml(card.type);

    return `
        <article class="card ${escapeHtml(card.type)} ${escapeHtml(card.class || '')}">
            <h3>${escapeHtml(card.name)}</h3>
            <p>${escapeHtml(card.description)}</p>
            <small>${rollText}</small>
            <span class="card-type">${escapeHtml(card.type)}</span>
            <div class="hand-card-actions">
                <button class="play-card-btn" data-card-id="${escapeHtml(card.id)}" ${disabled}>Play - 1 AP</button>
            </div>
        </article>
    `;
}

// aceasta functie returneaza jucatorul curent la rand
// gaseste jucatorul din lista players care are ID-ul egal cu currentTurnPlayerId
function getCurrentPlayer() {
    return currentGame?.players.find(player => player.id === currentGame.currentTurnPlayerId) || null;
}

// aceasta functie verifica daca jocul are un castigator
// verifica daca vreun jucator are 3 monstri ucisi sau 5 eroi in party
function gameHasWinner() {
    return currentGame?.players.some(player => {
        const heroes = player.party.filter(card => card.type === 'hero').length;
        return player.slainMonsters.length >= 3 || heroes >= 5;
    }) || false;
}

// aceasta functie trage o carte din pachet
// obtine jucatorul curent apoi face POST la /deck/draw si reincarca jocul
async function drawCard() {
    const player = getCurrentPlayer();
    if (!player) return;

    await post('/deck/draw', { gameId: currentGame.id, playerId: player.id });
    await reloadGame();
}

// aceasta functie joaca o carte din mana
// obtine jucatorul curent apoi face POST la /games/{id}/cards/play si afiseaza mesajul
async function playCard(cardId) {
    const player = getCurrentPlayer();
    if (!player || !cardId) return;

    const result = await post(`/games/${currentGame.id}/cards/play`, { playerId: player.id, cardId });
    els.messageBox.textContent = result.message;
    await reloadGame();
}

// aceasta functie arunca mana si trage 5 carti
// obtine jucatorul curent apoi face POST la /games/{id}/discard-draw si afiseaza mesajul
async function discardAndDraw() {
    const player = getCurrentPlayer();
    if (!player) return;

    const result = await post(`/games/${currentGame.id}/discard-draw`, { playerId: player.id });
    els.messageBox.textContent = result.message;
    await reloadGame();
}

// aceasta functie ataca un monstru selectat
// verifica daca e jucator monstru selectat si zaruri aruncate apoi face POST la /monsters/{id}/attack
async function attackMonster() {
    const player = getCurrentPlayer();

    if (!player || !selectedMonsterId || lastRoll === 0) {
        els.messageBox.textContent = 'Select a monster and roll the dice first.';
        return;
    }

    const result = await post(`/monsters/${selectedMonsterId}/attack`, { playerId: player.id, roll: lastRoll });
    els.messageBox.textContent = result.message;
    resetSelection();
    await reloadGame();
}

// aceasta functie termina randul curent
// face POST la /games/{id}/turn/end apoi reseteaza selectia si reafiseaza jocul
async function endTurn() {
    if (!currentGame) return;

    currentGame = await post(`/games/${currentGame.id}/turn/end`);
    resetSelection();
    renderGame();
}

function rollDice() {
    const d1 = randomDie();
    const d2 = randomDie();
    const player = getCurrentPlayer();
    const bonus = player ? calculateVisibleBonus(player) : 0;

    lastRoll = d1 + d2;
    els.dieOne.textContent = d1;
    els.dieTwo.textContent = d2;
    els.messageBox.textContent = `You rolled ${lastRoll}. Current attack bonus from party: +${bonus}. Select a monster and attack.`;

    renderGame();
}

function calculateVisibleBonus(player) {
    return player.party.reduce((bonus, card) => {
        if (card.type === 'hero' || card.type === 'item') return bonus + 1;
        if (card.type === 'modifier') return bonus + (card.name === 'Plus Two' ? 2 : 1);
        return bonus;
    }, 0);
}

function randomDie() {
    return Math.floor(Math.random() * 6) + 1;
}

function resetSelection() {
    selectedMonsterId = null;
    lastRoll = 0;
    els.dieOne.textContent = '?';
    els.dieTwo.textContent = '?';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

// aceasta sectiune adauga event listeners pentru interactiuni
// adauga click pe monstri pentru selectie click pe butoane pentru actiuni
els.activeMonsters.addEventListener('click', event => {
    const card = event.target.closest('[data-monster-id]');
    if (!card) return;

    selectedMonsterId = card.dataset.monsterId;
    renderGame();
});

els.playerHand.addEventListener('click', event => {
    const button = event.target.closest('.play-card-btn');
    if (button) {
        runAction(() => playCard(button.dataset.cardId));
    }
});

els.newGameBtn.addEventListener('click', () => runAction(async () => {
    currentGame = await createGame();
    resetSelection();
    renderGame();
}));

els.refreshBtn.addEventListener('click', () => runAction(reloadGame));
els.drawBtn.addEventListener('click', () => runAction(drawCard));
els.discardDrawBtn.addEventListener('click', () => runAction(discardAndDraw));
els.attackBtn.addEventListener('click', () => runAction(attackMonster));
els.endTurnBtn.addEventListener('click', () => runAction(endTurn));
els.rollBtn.addEventListener('click', rollDice);

// aceasta linie porneste initializarea aplicatiei
runAction(init);
