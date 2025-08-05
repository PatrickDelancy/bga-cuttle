let CuttleCards = {
    card_data: {},
    card_names: {},
    rules_set: 0,

    manager: null,
    stocks: {},

    setup: function (gamedatas) {
        this.card_data = gamedatas.card_data;
        this.card_names = gamedatas.card_names;
        this.rules_set = gamedatas.rules_set;
    },
    getCardData: function (card = null) {
        if (card == null)
            return Object.values(this.card_data)
                .filter(d => CuttleRules.useJokers || d.type != 15)
                .map(d => this.getCardData(d));

        let data = this.card_data[card?.type];

        let suffixes = [];
        if (CuttleRules.deckNines || CuttleRules.foursRandom) {
            suffixes.push("-" + this.rules_set + (CuttleRules.foursRandom ? "-110" : "") + (CuttleRules.deckNines ? "-111" : ""));
            suffixes.push("-" + this.rules_set + (CuttleRules.foursRandom ? "-110" : ""));
            suffixes.push("-" + this.rules_set + (CuttleRules.deckNines ? "-111" : ""));
            suffixes.push((CuttleRules.foursRandom ? "-110" : "") + (CuttleRules.deckNines ? "-111" : ""));
            suffixes.push((CuttleRules.foursRandom ? "-110" : ""));
            suffixes.push((CuttleRules.deckNines ? "-111" : ""));
        }
        suffixes.push("-" + this.rules_set);

        for (let key of Object.keys(data || {}).filter(k => !/\-\d$/.test(k))) {
            data[key] = suffixes.reduce((out, c) => out || data[key + c], null) || data[key];
        }
        return data;
    },
    getCardName: function (card) { return this.card_names[card?.type]; },
    createCardManager: function () {
        this.manager = new CardManager(this, {
            getId: (card) => `card-${card.id}`,
            setupDiv: (card, div) => {
                div.classList.add('mygame-card');
                div.insertAdjacentHTML('beforeend', '<div class="nest-anchor"></div>');
                if (card.type)
                    div.classList.add(`card-type-${card.type}`);
                if (card.shieldable)
                    div.classList.add(`shieldable`);
            },
            setupFrontDiv: (card, div) => {
                if (card.type)
                    div.parentElement.parentElement.classList.add(`card-type-${card.type}`);
                if (card.shieldable)
                    div.parentElement.parentElement.classList.add(`shieldable`);

                div.classList.add('mygame-card-front');
                div.style.backgroundPositionY = `${(card.type_arg - 1) * 100 / 3}%`;
                div.style.backgroundPositionX = `${(card.type - 1) * 100 / 14}%`;

                const tooltipHtml = this.getCardInfoMarkup(card);
                if (tooltipHtml) {
                    window.gameui.addTooltipHtml(div.id, tooltipHtml, 500);
                }
            },
            setupBackDiv: (card, div) => {
                div.classList.add('mygame-card-back');
            },
            isCardVisible: card => Boolean(card.type),
            thicknesses: [0, 0],
            cardWidth: 100,
            cardHeight: 150,
        });
        return this.manager;
    },
    enableCardParenting: function (stock) {
        const originalAddCards = stock.addCards.bind(stock);
        stock.addCards = async (cards, animation, settings, shift) => {
            let success = await originalAddCards(cards, animation, settings, shift);

            for (let card of cards.filter(c => c.parent_card_id)) {
                let el = CuttleCards.manager.getCardElement(card);
                let parentEl = CuttleCards.manager.getCardElement({ id: card.parent_card_id });
                let nestAnchor = parentEl.querySelector(".nest-anchor");
                nestAnchor.appendChild(el);
            }
            return success;
        };
    },
    getCardInfoMarkup: function (card) {
        let cardData = this.getCardData(card);
        if (!cardData) return '';

        let markup = "".concat(
            '<div class="cardInfo">',
            '<div>' + _('*Card*: ${name}') + '</div>',
            cardData.points ? '<div>' + _('*Points*: ${points}') + '</div>' : '',
            cardData.effect_type ? '<div>' + _('*Effect Type*: ${effect_type}') + '</div>' : '',
            cardData.effect_help ? '<div>' + _('*Effect*: ${effect_help}') + '</div>' : '',
            '</div>'
        );
        return dojo.string.substitute(
            bga_format(_(markup), { '*': (t) => '<b>' + t + '</b>' }),
            {
                name: cardData?.name,
                points: cardData?.points,
                effect_type: cardData?.effect_type,
                effect_help: cardData?.effect_help
            });
    },
    setCardStock: function (name, playerId, stock) {
        if (typeof (playerId) == 'object') {
            stock = playerId;
            playerId = undefined;
        }
        let key = `${playerId ? playerId : ''}${name}`;
        this.stocks[key] = stock;
        return stock;
    },
    getCardStock: function (name, playerId) {
        return this.stocks[`${playerId ? playerId : ''}${name}`];
    },
    getSelectionMode: function (stock) {
        return stock?.selectionMode || 'none';
    },
    isCardSelectable: function (card) {
        const stock = this.manager.getCardStock(card);
        if (CuttleCards.getSelectionMode(stock) == "none")
            return false;
        const unselectableClass = stock.getUnselectableCardClass();
        const cardEl = this.manager.getCardElement(card);
        return !cardEl.classList.contains(unselectableClass);
    },
    disableAllSelectionModes: function () {
        let playerHeaderEls = document.querySelectorAll(`.playertableheader.selectable`);
        Array.from(playerHeaderEls).forEach(playerHeaderEl => {
            playerHeaderEl.classList.toggle('selectable', false);
            playerHeaderEl.onclick = null;
        });

        Object.values(this.stocks).forEach(stock => {
            stock.unselectAll();
            stock.setSelectionMode('none');
            stock.onSelectionChange = null;
            stock.setOpened?.(false);
            stock.element.classList.remove('playing-blockable-card');
            delete stock.element.dataset.selectreason;
        });
    },
    resetDeck: function () {
        const tableEl = document.getElementById('table');
        tableEl.appendChild(document.getElementById('cardCommands'));
        tableEl.appendChild(document.getElementById('cardTargetCommands'));

        const deck = this.getCardStock('deck');
        Object.values(this.stocks).forEach(stock => {
            if (stock != deck) {
                deck.addCards(stock.getCards());
            }
        });
        deck.getCards().forEach(card => this.manager.removeCard(card));
        deck.setCardNumber(52);
        return deck;
    },
    getRootCard: function (card, cards) {
        if (card?.parent_card_id) {
            return this.getRootCard(cards.find(c => c.id == card.parent_card_id), cards) || card;
        } else {
            return card;
        }
    },
    getAllAncestors: function (card, cards) {
        if (card?.parent_card_id) {
            let parent = cards.find(c => c.id == card.parent_card_id);
            return [parent, ...this.getAllAncestors(parent, cards)];
        }
        return [];
    },
    getAllDescendants: function (card, cards) {
        let child = cards.find(c => c.parent_card_id == card.id);
        if (child) {
            return [...this.getAllDescendants(child, cards), child];
        }
        return [];
    },
    updateCardPlayability: function (card) {
        let currentPlayerId = CuttlePlayers.myId;
        let playability = {};
        let targets = null;

        // cannot play a card on the turn after it was returned to your hand
        if (CuttlePlayers.getReturnedCardId(currentPlayerId) == card.id) {
            playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.returned || "");
        } else {
            playability.playableForPoints = card.points > 0;
            if (card.points > 0) {
                for (var $playerId of CuttlePlayers.getPlayerIds().filter(id => id != currentPlayerId)) {
                    let weakerPointCards = CuttleCards
                        .getCardStock('points', $playerId)
                        .getCards()
                        .filter(c => card.strength > c.strength);

                    if (weakerPointCards.length == 0) {
                        playability.scuttle_reason = _('No active point cards are weaker than this one');
                    } else {
                        playability.playableForScuttle = true;
                        break;
                    }
                }
            }

            switch (card.type) {
                case 1: {// Ace - if there are any point cards on the table
                    for (var $playerId of CuttlePlayers.getPlayerIds()) {
                        if (!CuttleCards.getCardStock('points', $playerId).isEmpty()) {
                            playability.playableForEffect = true;
                            break;
                        }
                    }
                    if (!playability.playableForEffect) {
                        playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.notargets || "");
                    }
                    break;
                }
                case 2: {// Can target permanent effects in opponents field
                    for (var $playerId of CuttlePlayers.getPlayerIds().filter(id => id != currentPlayerId)) {
                        let effectsCards = CuttleCards.getCardStock('effects', $playerId).getCards();

                        let allPointCards = CuttleCards.getCardStock('points', $playerId).getCards();
                        let selectablePointCards = allPointCards.filter(c => (c.points || 0) == 0);
                        for (let i = selectablePointCards.length; i > 0; i--) {
                            selectablePointCards.push(...CuttleCards.getAllAncestors(selectablePointCards[i - 1], allPointCards));
                        }

                        if (effectsCards.length > 0 || selectablePointCards.length > 0) {
                            targets = targets || {};
                            targets.players = targets.players || {};
                            targets.players[$playerId] = {
                                effects: effectsCards.map(c => c.id),
                                points: selectablePointCards.map(c => c.id)
                            };
                            playability.playableForEffect = true;
                        } else {
                            playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.notargets || "");
                        }
                    }
                    break;
                }
                case 3: {
                    let cards = CuttleCards.getCardStock('discard').getCards();
                    if (cards.length > 0) {
                        targets = targets || {};
                        targets.discardPile = cards.map(c => c.id);
                        playability.playableForEffect = true;
                    } else {
                        playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.nodiscard || "");
                    }
                    break;
                }
                case 4: {
                    let otherPlayers = CuttlePlayers.getPlayerIds().filter(id => id != currentPlayerId);
                    for (let playerId of otherPlayers) {
                        if (CuttleCards.getCardStock('hand', playerId).getCards().length) {
                            targets = targets || {};
                            targets.players = otherPlayers;
                            playability.playableForEffect = true;
                        }
                    }
                    if (!playability.playableForEffect) {
                        playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.notargets || "");
                    }
                    break;
                }
                case 5: {
                    if (CuttleCards.getCardStock('deck').getCards().length > 0) {
                        if (CuttleRules.discardToDraw_5) {
                            let cards = CuttleCards.getCardStock('hand', currentPlayerId)
                                .getCards().filter(c => c.id != card.id);
                            if (cards.length > 0) {
                                targets = targets || {};
                                targets.players = targets.players || {};
                                targets.players[currentPlayerId] = { hand: cards.map(c => c.id) };
                            }
                        }
                        playability.playableForEffect = true;
                    } else {
                        playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.nodeck || "");
                    }
                    break;
                }
                case 6: {
                    for (var $playerId of CuttlePlayers.getPlayerIds()) {
                        let effectCardsInPoints = CuttleCards.getCardStock('points', $playerId)
                            .getCards().filter(c => c.type == 11);
                        if (effectCardsInPoints.length > 0 || !CuttleCards.getCardStock('effects', $playerId).isEmpty()) {
                            playability.playableForEffect = true;
                            break;
                        }
                    }
                    if (!playability.playableForEffect) {
                        playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.notargets || "");
                    }
                    break;
                }
                case 7: {
                    if (!CuttleCards.getCardStock('deck').isEmpty()) {
                        playability.playableForEffect = true;
                    } else {
                        playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.nodeck || "");
                    }
                    break;
                }
                case 8: {
                    playability.playableForEffect = true;
                    break;
                }
                case 9: {
                    for (var $playerId of CuttlePlayers.getPlayerIds().filter(id => id != currentPlayerId)) {
                        let effectsCards = CuttleCards.getCardStock('effects', $playerId).getCards();
                        let allPointCards = CuttleCards.getCardStock('points', $playerId).getCards();
                        let selectablePointCards = CuttleRules.nineTargetAny ? allPointCards.slice() : allPointCards.filter(c => (c.points || 0) == 0);

                        for (let i = selectablePointCards.length; i > 0; i--) {
                            selectablePointCards.push(...CuttleCards.getAllAncestors(selectablePointCards[i - 1], allPointCards));
                        }
                        if (effectsCards.length > 0 || selectablePointCards.length > 0) {
                            targets = targets || {};
                            targets.players = targets.players || {};
                            targets.players[$playerId] = {
                                effects: effectsCards.map(c => c.id),
                                points: selectablePointCards.map(c => c.id)
                            };
                            playability.playableForEffect = true;
                        }
                    }
                    if (!playability.playableForEffect) {
                        playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.notarget || "");
                    }
                    break;
                }
                case 11: { // Jack
                    for (var $playerId of CuttlePlayers.getPlayerIds().filter(id => id != currentPlayerId)) {
                        let allPointCards = CuttleCards.getCardStock('points', $playerId).getCards();
                        let cards = allPointCards.filter(c => c.points > 0);
                        if (cards.length > 0) {
                            targets = targets || {};
                            targets.players = targets.players || {};

                            for (let i = cards.length; i > 0; i--)
                                cards.push(...CuttleCards.getAllDescendants(cards[i - 1], allPointCards));
                            targets.players[$playerId] = { points: cards.map(c => c.id) };
                            playability.playableForEffect = true;
                        } else {
                            playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.notarget || "");
                        }
                    }
                    break;
                }
                case 12: { // Queen
                    playability.playableForEffect = true;
                    break;
                }
                case 13: { // King
                    playability.playableForEffect = true;
                    break;
                }
                case 15: { // Joker
                    for (var $playerId of CuttlePlayers.getPlayerIds().filter(id => id != currentPlayerId)) {
                        let allEffectsCards = CuttleCards.getCardStock('effects', $playerId).getCards();
                        let cards = allEffectsCards.filter(c => c.type != 15);
                        if (cards.length > 0) {
                            targets = targets || {};
                            targets.players = targets.players || {};

                            for (let i = cards.length; i > 0; i--)
                                cards.push(...CuttleCards.getAllDescendants(cards[i - 1], allEffectsCards));
                            targets.players[$playerId] = { effects: cards.map(c => c.id) };
                            playability.playableForEffect = true;
                        } else {
                            playability.effect_reason = _(this.getCardData(card)?.unplayable_reasons?.notarget || "");
                        }
                    }
                    break;
                }
            }
        }

        let updatedCard = { ...card, playability, targets };
        CuttleCards.manager.updateCardInformations(updatedCard);
        return updatedCard;
    },
};

define([
    g_gamethemeurl + "modules/js/cuttle/cuttle-rules.js",
    g_gamethemeurl + "modules/js/cuttle/cuttle-players.js"
], () => ({
    CuttleCards: CuttleCards
}));