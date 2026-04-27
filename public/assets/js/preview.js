// aceasta constanta contine datele pentru jucatori
// fiecare jucator are nume lider clasa numar carti in mana si monstri ucisi
const players = [
    {
        name: 'Player 1',
        leader: 'Blade Leader',
        className: 'fighter',
        hand: 5,
        slain: 1
    },
    {
        name: 'Player 2',
        leader: 'Shadow Leader',
        className: 'thief',
        hand: 4,
        slain: 0
    },
    {
        name: 'Player 3',
        leader: 'Mystic Leader',
        className: 'wizard',
        hand: 6,
        slain: 2
    },
    {
        name: 'Player 4',
        leader: 'Forest Leader',
        className: 'ranger',
        hand: 3,
        slain: 0
    }
];

// aceasta constanta contine datele pentru monstri
// fiecare monstru are id nume roll necesar penalizare si recompensa
const monsters = [
    {
        id: 'm1',
        name: 'Dark Bat',
        roll: 7,
        penalty: 'Sacrifice a hero.',
        reward: 'Draw two cards.'
    },
    {
        id: 'm2',
        name: 'Forest Beast',
        roll: 8,
        penalty: 'Discard two cards.',
        reward: 'Gain +1 on attacks.'
    },
    {
        id: 'm3',
        name: 'Crystal Dragon',
        roll: 9,
        penalty: 'Destroy one item.',
        reward: 'Draw one card each turn.'
    }
];

// aceasta constanta contine cartile din mana jucatorului
// fiecare carte are nume tip clasa descriere si roll daca e cazul
const hand = [
    {
        name: 'Brave Fighter',
        type: 'hero',
        className: 'fighter',
        description: 'Roll 5+ to draw a card.',
        roll: 5
    },
    {
        name: 'Shield Guardian',
        type: 'hero',
        className: 'guardian',
        description: 'Roll 6+ to protect a hero.',
        roll: 6
    },
    {
        name: 'Forest Ranger',
        type: 'hero',
        className: 'ranger',
        description: 'Roll 7+ to search the deck.',
        roll: 7
    },
    {
        name: 'Fire Spell',
        type: 'magic',
        className: 'magic',
        description: 'Use a one-time powerful spell.',
        roll: null
    },
    {
        name: 'Challenge',
        type: 'challenge',
        className: 'challenge',
        description: 'Try to stop another player from playing a card.',
        roll: null
    }
];

// aceasta variabila tine id-ul monstruului selectat
let selectedMonsterId = null;
// aceasta variabila tine punctele de actiune disponibile
let actionPoints = 3;

// aceasta functie reda toti jucatorii pe tabla
// apeleaza renderPlayer pentru fiecare pozitie
function renderPlayers() {
    document.getElementById('playerBottom').innerHTML = renderPlayer(players[0], true);
    document.getElementById('playerLeft').innerHTML = renderPlayer(players[1], false);
    document.getElementById('playerTop').innerHTML = renderPlayer(players[2], false);
    document.getElementById('playerRight').innerHTML = renderPlayer(players[3], false);
}

// aceasta functie reda un singur jucator
// primeste obiectul jucator si daca e activ
// returneaza html-ul pentru zona jucatorului
function renderPlayer(player, active) {
    return `
        <div class="player-title">
            <span>${player.name}</span>
            <span>${active ? 'Active' : 'Waiting'}</span>
        </div>

        <div class="player-mini-data">
            <span class="badge">${player.leader}</span>
            <span class="badge">${player.className}</span>
            <span class="badge">Hand ${player.hand}</span>
            <span class="badge">Slain ${player.slain}</span>
        </div>

        <div class="party-grid">
            <div class="party-slot card hero ${player.className}">
                <h3>${player.leader}</h3>
                <p>Party Leader</p>
                <small>${player.className}</small>
            </div>
            <div class="party-slot"></div>
            <div class="party-slot"></div>
            <div class="party-slot"></div>
            <div class="party-slot"></div>
        </div>
    `;
}

// aceasta functie reda monstrii activi
// foloseste map pentru a crea html pentru fiecare monstru
// adauga event listener pentru selectie
function renderMonsters() {
    document.getElementById('activeMonsters').innerHTML = monsters.map(monster => `
        <article class="monster-card ${selectedMonsterId === monster.id ? 'selected' : ''}" data-id="${monster.id}">
            <h3>${monster.name}</h3>
            <p>Roll ${monster.roll}+ to slay.</p>
            <p>Penalty: ${monster.penalty}</p>
            <p>Reward: ${monster.reward}</p>
            <small>Monster</small>
        </article>
    `).join('');

    document.querySelectorAll('.monster-card[data-id]').forEach(card => {
        card.addEventListener('click', () => {
            selectedMonsterId = card.dataset.id;
            renderMonsters();
            document.getElementById('messageBox').textContent = 'Monster selected. Roll dice before attacking.';
        });
    });
}

// aceasta functie reda mana jucatorului
// foloseste map pentru a crea html pentru fiecare carte
function renderHand() {
    document.getElementById('playerHand').innerHTML = hand.map(card => `
        <article class="card ${card.type} ${card.className}">
            <h3>${card.name}</h3>
            <p>${card.description}</p>
            <small>${card.roll ? 'Roll ' + card.roll + '+' : card.type}</small>
            <span class="card-type">${card.type}</span>
        </article>
    `).join('');
}

// aceasta functie simuleaza aruncarea zarurilor
// genereaza doua numere random intre 1 si 6
// actualizeaza interfata si mesajul
function rollDice() {
    const d1 = Math.floor(Math.random() * 6) + 1;
    const d2 = Math.floor(Math.random() * 6) + 1;

    document.getElementById('dieOne').textContent = d1;
    document.getElementById('dieTwo').textContent = d2;
    document.getElementById('messageBox').textContent = `You rolled ${d1 + d2}.`;
}

// aceasta functie simuleaza tragerea unei carti
// verifica daca sunt puncte de actiune suficiente
// scade punctele si actualizeaza interfata
function drawCard() {
    if (actionPoints <= 0) {
        document.getElementById('messageBox').textContent = 'No action points left.';
        return;
    }

    actionPoints--;
    document.getElementById('actionPoints').textContent = actionPoints;
    document.getElementById('messageBox').textContent = 'Preview action: card drawn.';
}

// aceasta functie simuleaza atacul asupra unui monstru
// verifica daca e selectat un monstru si daca sunt suficiente puncte
// scade punctele si actualizeaza interfata
function attackMonster() {
    if (!selectedMonsterId) {
        document.getElementById('messageBox').textContent = 'Select a monster first.';
        return;
    }

    if (actionPoints < 2) {
        document.getElementById('messageBox').textContent = 'Not enough action points.';
        return;
    }

    actionPoints -= 2;
    document.getElementById('actionPoints').textContent = actionPoints;
    document.getElementById('messageBox').textContent = 'Preview action: monster attacked.';
}

// aceasta functie simuleaza sfarsitul turei
// reseteaza punctele de actiune si selectia
// actualizeaza interfata
function endTurn() {
    actionPoints = 3;
    selectedMonsterId = null;

    document.getElementById('actionPoints').textContent = actionPoints;
    document.getElementById('dieOne').textContent = '?';
    document.getElementById('dieTwo').textContent = '?';
    document.getElementById('messageBox').textContent = 'Preview action: turn ended.';
    renderMonsters();
}

// acestea sunt event listener-ele pentru butoane
// fiecare buton apeleaza functia corespunzatoare
document.getElementById('rollBtn').addEventListener('click', rollDice);
document.getElementById('drawBtn').addEventListener('click', drawCard);
document.getElementById('attackBtn').addEventListener('click', attackMonster);
document.getElementById('endTurnBtn').addEventListener('click', endTurn);
document.getElementById('newGameBtn').addEventListener('click', endTurn);
document.getElementById('refreshBtn').addEventListener('click', endTurn);

// acestea sunt apelurile initiale pentru redarea interfetei
renderPlayers();
renderMonsters();
renderHand();