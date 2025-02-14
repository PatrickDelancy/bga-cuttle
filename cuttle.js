/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * cuttle implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * cuttle.js
 *
 * cuttle user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    // https://github.com/thoun/bga-cards/blob/main/EXAMPLES.md
    g_gamethemeurl + "modules/js/bga-cards/bga-cards.js",
    g_gamethemeurl + "modules/js/cuttle/cuttle-rules.js",
    g_gamethemeurl + "modules/js/cuttle/cuttle-players.js",
    g_gamethemeurl + "modules/js/cuttle/cuttle-cards.js",
],
    function (dojo, declare, gamegui) {

        return declare("bgagame.cuttle", gamegui, {
            cardWidth: 110,
            cardHeight: 150,

            constructor: function () {
                console.log('cuttle constructor');
                CuttleCards.createCardManager();
            },

            /*
                setup:
                
                This method must set up the game user interface according to current game situation specified
                in parameters.
                
                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refreshes the game page (F5)
                
                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */
            setup: function (gamedatas) {
                console.log("Starting game setup", gamedatas);

                CuttlePlayers.setup(gamedatas);
                CuttleCards.setup(gamedatas);
                CuttleRules.setup(gamedatas);

                if (CuttleRules.winningScore > 0) {
                    document.querySelectorAll('.player_score_value').forEach(el => {
                        el.dataset['scoresuffix'] = '/' + CuttleRules.winningScore;
                    });
                }

                const cardData = CuttleCards.getCardData();
                let cardsHelpRows = Object
                    .values(cardData)
                    .map(v => `<tr><td>${v.name || '-'}</td><td>${v.points || '-'}</td><td>${v.effect_type || '-'}</td><td style="text-align: left;">${v.effect_help || '-'}</td></tr>`)
                    .join('');
                let rulesSetLabel = dojo.string.substitute(_('${rulesSetName} Rules'), { rulesSetName: _(CuttleRules.rulesSetName) });
                let rulesOptionsLabel = (CuttleRules.foursRandom ? `<span title="${_('Discards from 4 One-Off effect are random')}"><i class="fa6-solid fa6-4"></i><i class="fa6-solid fa6-shuffle"></i></span>` : '')
                    + (CuttleRules.deckNines ? `<span title="${_('9 One-Off effect moves cards to draw pile')}"><i class="fa6-solid fa6-9"></i><i class="fa6-solid fa6-right-to-bracket"></i></span>` : '')
                let rulesSummary = `
                    <p>${dojo.string.substitute(_('First to ${target_points} points wins the round.'), { target_points: CuttleRules.targetKingPoints[0] })}</p>
                    <p>${_('On your turn, draw a card or play a card for 1) points, 2) scuttle, or 3) effect.')}</p>
                    <p><b>${_('Scuttling')}:</b>${_('Use a point card from your hand to discard a lower-value point card from in front of your opponent. For cards with the same number value, the stronger suit wins (♠ > ♡ > ♢ > ♣).')}</p>
                    <p><b>${_('Points or Effect')}:</b>${_('Card points and effects are summarized in the following table.')}</p>
                `;
                document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                <div id="table">
                    <button id="cardrules-help-button" data-folded="true"><h2>${rulesSetLabel}</h2><div class="rules-summary">${rulesSummary}</div><table><tbody><tr><th>Card</th><th>Points</th><th>Effect Type</th><th style="text-align: left;">Effect</th></tr>${cardsHelpRows}</tbody></table></button>
                    <div id="commonTable">
                        <div id="piles">
                            ${CuttleRules.winningScore > 1
                        ? `<div id="roundMarker">${dojo.string.substitute(
                            _('Round ${current_round} (First to ${winning_score})'),
                            {
                                current_round: gamedatas.current_round,
                                winning_score: CuttleRules.winningScore
                            })}</div>`
                        : ''}
                            <div id="drawpile" class="pile"></div>
                            <div id="discardpile" class="pile"></div>
                            <div id="rulesetLabel">${rulesSetLabel}${rulesOptionsLabel ? ' +' + rulesOptionsLabel : ''}</div>
                        </div>
                        <div id="discardViewWrapper"><a href="#" id="discardViewClose" class="close">${_('Close')}</a><div id="discardView"></div></div>
                        <div id="stagingLine"></div>
                        <div id="blockableActionLine"></div>
                        ${CuttlePlayers.isSpectator ? '<div id="pendingPile"></div>' : ''}
                    </div>
                    <div id="player-tables"></div>
                    <div id="cardCommands" class="card-commands"></div>
                    <div id="cardTargetCommands" class="card-commands"></div>
                </div>`);
                const helpButton = document.getElementById('cardrules-help-button');
                helpButton.addEventListener('click', () => helpButton.dataset.folded = helpButton.dataset.folded == 'true' ? 'false' : 'true');

                // NOTE: If adding a new command container (or any other element that gets moved to card elements and must be preserved),
                //      be sure to save it in the "resetDeck()" action, otherwise it might get destroyed between rounds.
                document.getElementById('cardCommands').onclick = function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    return false;
                };
                const deck = CuttleCards.setCardStock('deck',
                    new Deck(CuttleCards.manager, document.getElementById('drawpile'), {
                        cardNumber: gamedatas.deckCount || 0,
                        counter: { hideWhenEmpty: false, position: 'center', extraClasses: 'text-shadow', },
                        autoUpdateCardNumber: false
                    })
                );
                if (!CuttlePlayers.isSpectator) deck.onCardClick = this.onDrawPileClick.bind(this);

                const discard = CuttleCards.setCardStock('discard',
                    new AllVisibleDeck(CuttleCards.manager, document.getElementById('discardpile'), {
                        cardNumber: Object.keys(gamedatas.discard || {}).length || 0,
                        counter: { hideWhenEmpty: true, position: 'center', extraClasses: 'text-shadow', },
                        autoUpdateCardNumber: true,
                        direction: 'horizontal',
                        shift: '0px'
                    })
                );
                CuttleCards.setCardStock('discardView', new LineStock(CuttleCards.manager, document.getElementById('discardView')));
                discard.onCardClick = this.examineDiscardPile.bind(this);
                document.getElementById('discardViewClose').onclick = this.hideDiscardPile.bind(this);

                if (gamedatas.discard)
                    discard.addCards(Object.values(gamedatas.discard));

                const staging = CuttleCards.setCardStock('staging', new LineStock(CuttleCards.manager, document.getElementById(`stagingLine`)));
                if (gamedatas.staging)
                    staging.addCards(Object.values(gamedatas.staging));

                CuttleCards.setCardStock('blockableActions', new HandStock(CuttleCards.manager, document.getElementById('blockableActionLine'), { inclination: 5, cardShift: '10px', cardOverlap: '25px' }));

                const statusSpyglassMarkup = `<div class="playerstatus-spyglass" title="${_("This player can see the contents of other players' hands")}"><i class="fa6-solid fa6-glasses"></i></div>`;
                const statusShieldMarkup = `<div class="playerstatus-shield" title="${_("This player's active cards are protected from targeted effects")}"><i class="fa6-solid fa6-shield"></i></div>`;
                const statusDealerMarkup = `<div class="playerstatus-dealer" title="${_("This player is dealer for this round")}"><i class="fa6-regular fa6-hand"></i></div>`;

                CuttlePlayers.getPlayers().forEach(player => {
                    const isCurrentPlayer = player.id == CuttlePlayers.myId;

                    const handLimit = CuttlePlayers.getHandLimit(player.id);

                    document.getElementById('player-tables').insertAdjacentHTML(isCurrentPlayer ? 'afterbegin' : 'beforeend', `
                    <!-- BEGIN player ${player.name} -->
                    <div class="playertable playerstatus-container playerstatus-container-${player.id} ${isCurrentPlayer ? 'currentplayertable' : 'otherplayertable'}" id="playertable-${player.id}">
                        <div class="playertableheader">
                            <div class="playertablename" style="color:#${player.color}">${player.name}</div>
                            <div class="playerstatus-points">${_("Points")}: <span class="playerstatus-points-${player.id}"></span>/<span class="playerstatus-targetpoints-${player.id}"></span></div>
                            <div class="playerstatus-icons">
                                ${statusDealerMarkup}
                                ${statusSpyglassMarkup}
                                ${statusShieldMarkup}
                            </div>
                            ${isCurrentPlayer && handLimit ? `<div class="playerstatus-handlimit">${_("Hand Limit")}: <span class="playerstatus-handlimit-${player.id}">${handLimit}</span></div>` : ''}
                        </div>
                        <div class="playerfield" id="playerfield_${player.id}">
                            <div class="playerpoints" id="playerpoints_${player.id}"></div>
                            <div class="playereffects" id="playereffects_${player.id}"></div>
                        </div>
                        <div class="playerhandrow">
                            ${isCurrentPlayer ? '<div id="pendingPile"></div>' : ''}
                            <div class="playerhand" id="playerhand_${player.id}"></div>
                        </div>
                    </div>
                    <!-- END player -->`);

                    let hand = CuttleCards.setCardStock('hand', player.id,
                        new HandStock(CuttleCards.manager, document.getElementById(`playerhand_${player.id}`), {
                            cardOverlap: '50px',
                            inclination: 5,
                            sort: sortFunction('type', 'type_arg')
                        })
                    );
                    hand.addCards(Object.values(player.hand || {}));

                    let pointStock = CuttleCards.setCardStock('points', player.id,
                        new LineStock(CuttleCards.manager, document.getElementById(`playerpoints_${player.id}`), { sort: sortFunction('type', 'type_arg') })
                    );
                    CuttleCards.enableCardParenting(pointStock);
                    pointStock.addCards(Object.values(player.point_cards || {}));

                    let effectStock = CuttleCards.setCardStock('effects', player.id,
                        new LineStock(CuttleCards.manager, document.getElementById(`playereffects_${player.id}`), { sort: sortFunction('type', 'type_arg') })
                    );
                    CuttleCards.enableCardParenting(effectStock);
                    effectStock.addCards(Object.values(player.effect_cards || {}));

                    if (!CuttlePlayers.isSpectator) {
                        hand.onCardClick = this.onCardClickedShowReason.bind(this);
                        pointStock.onCardClick = this.onCardClickedShowReason.bind(this);
                        effectStock.onCardClick = this.onCardClickedShowReason.bind(this);
                    }

                    this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', `
                        <div id="playerpanel_${player.id}" class="playerstatus-container playerstatus-container-${player.id}">
                            ${statusDealerMarkup}
                            <div class="playerstatus-points" title="${_("Player's current points and target points to win the round")}"><i class="fa6-solid fa6-trophy"></i> <span class="playerstatus-points-${player.id}"></span>/<span class="playerstatus-targetpoints-${player.id}"></span></div>
                            <div class="playerstatus-hand" title="${_("Number of cards in player's hand")}"><i class="fa6-solid fa6-money-bill" style="rotate: 90deg;"></i> <span class="playerstatus-cardcount-${player.id}"></span>${handLimit ? `/<span class="playerstatus-handlimit-${player.id}">${handLimit}</span>` : ''}</div>
                            ${statusSpyglassMarkup}
                            ${statusShieldMarkup}
                        </div>`);
                    this.onPlayerShieldChanged(player.id, player.shielded);
                    this.onPlayerSpyglassChanged(player.id, player.has_spyglass);
                    this.onPlayerPointsChanged(player.id, player.points);
                    this.onPlayerTargetPointsChanged(player.id, player.target_points);
                    this.onPlayerCardCountChanged(player.id, Object.values(player.hand || {}).length);
                });

                this.onDealerChanged(CuttlePlayers.dealerId);

                const pending = CuttleCards.setCardStock('pending', new LineStock(CuttleCards.manager, document.getElementById(`pendingPile`)));
                if (!CuttlePlayers.isSpectator) pending.onCardClick = this.onCardClickedShowReason.bind(this);

                if (gamedatas.blockableActions) {
                    for (let action of gamedatas.blockableActions) {
                        this.addCardToBlockableActions(
                            action.card,
                            action.playerId,
                            action.targetCard,
                            action.targetPlayerId
                        );
                    }
                }

                this.setupNotifications();

                console.log("Ending game setup");
            },

            onCardClickedShowReason: function (card) {
                // console.log("Card clicked", card);
                // if (CuttleCards.isCardSelectable(card) && card?.playability?.reason)
                //     this.showMessage(card.playability.reason, 'info');
            },

            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName, args);
                if (stateName == 'gameEnd') {
                    for (let result of args.args.result) {
                        this.scoreCtrl[result.id].setValue(parseInt(result.score));
                    }
                    return;
                }

                switch (stateName) {
                    case 'playerChooseAction': {
                        if (!this.isCurrentPlayerActive() || this.isReadOnly())
                            return;

                        let playerHand = CuttleCards.getCardStock('hand', CuttlePlayers.myId);
                        let handCards = playerHand.getCards().map(c => CuttleCards.updateCardPlayability(c));
                        const deck = CuttleCards.getCardStock('deck');

                        const handLimit = CuttlePlayers.getHandLimit(CuttlePlayers.myId);
                        if ((handLimit == 0 || handCards.length < handLimit) && deck.getCards().length > 0) {
                            deck.setSelectionMode('single');
                        }
                        playerHand.onSelectionChange = this.onSelectionChangeChooseAction.bind(this);
                        playerHand.setSelectionMode('single',
                            handCards.filter(c => c.playability.playableForPoints || c.playability.playableForScuttle || c.playability.playableForEffect)
                        );
                        break;
                    }
                    case 'playerFromStaging': {
                        if (!this.isCurrentPlayerActive() || this.isReadOnly())
                            return;

                        let lineCards = CuttleCards.getCardStock('staging').getCards().map(c => CuttleCards.updateCardPlayability(c));

                        let playableCards = lineCards.filter(c => c.playability.playableForPoints ||
                            c.playability.playableForScuttle ||
                            c.playability.playableForEffect);

                        if (playableCards.length == 0) {
                            playableCards = lineCards.map(c => {
                                c.playability.playableForDiscard = true;
                                return c;
                            });
                        }

                        const staging = CuttleCards.getCardStock('staging');
                        staging.onSelectionChange = this.onSelectionChangeChooseAction.bind(this);
                        staging.setSelectionMode('single', playableCards);
                        break;
                    }
                    case 'playerForcedDiscard': {
                        if (!this.isCurrentPlayerActive() || this.isReadOnly())
                            return;

                        let playerHand = CuttleCards.getCardStock('hand', CuttlePlayers.myId);
                        let handSize = playerHand.getCards().length;

                        playerHand.setSelectionMode('multiple');
                        playerHand.element.dataset.selectreason = 'discard';
                        playerHand.onSelectionChange = (selection, lastchange) => {
                            if (selection.length >= 2 || selection.length >= handSize) {
                                playerHand.setSelectableCards(selection);
                                dojo.removeClass('actDiscard-btn', 'disabled');
                            } else {
                                playerHand.setSelectableCards();
                                dojo.addClass('actDiscard-btn', 'disabled');
                            }
                        }
                        break;
                    }
                    case 'playersBlockOneOff': {
                        break;
                    }
                    default: {

                    }
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {
                    default:
                        CuttleCards.disableAllSelectionModes();
                        break;
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //        
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName, args);
                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'playerChooseAction':
                            if (CuttleCards.getCardStock('deck').getCards().length > 0) {
                                this.addActionButton('actDrawCard-btn', _('Draw a card'), () => this.onDrawPileClick());

                                let handLimit = CuttlePlayers.getHandLimit(CuttlePlayers.myId);
                                if (handLimit > 0) {
                                    let handSize = CuttleCards.getCardStock('hand', CuttlePlayers.myId).getCards().length;
                                    if (handSize >= handLimit) {
                                        dojo.addClass('actDrawCard-btn', 'disabled');
                                    }
                                }
                            } else {
                                this.addActionButton('actPass-btn', _('Pass'), () => this.bgaPerformAction("actPass"), null, null, 'gray');
                            }
                            break;
                        case 'playerFromStaging':
                            break;
                        case 'playerForcedDiscard':
                            this.addActionButton('actDiscard-btn', _('Discard selected cards'),
                                () => {
                                    let cards = CuttleCards.getCardStock('hand', this.getCurrentPlayerId()).getSelection();
                                    let params = cards.reduce((o, v, i) => ({ ...o, [`cardId${i > 0 ? i + 1 : ''}`]: v.id }), {});
                                    this.bgaPerformAction("actPlayDiscard", params);
                                }, null, null, 'blue');
                            dojo.addClass('actDiscard-btn', 'disabled');
                            break;
                        case 'playersBlockOneOff':
                            let handCardsThatCanBlock = CuttleCards
                                .getCardStock('hand', this.getCurrentPlayerId())
                                .getCards()
                                .filter(c => c.type == 2 && !args.blockableActions.find(a => a.cardId == c.id));

                            for (let card of handCardsThatCanBlock) {
                                this.addActionButton(`actBlockOneOff${card.id}-btn`,
                                    dojo.string.substitute(
                                        _('Block with ${card_name}'),
                                        { card_name: card.name }
                                    ), () => {
                                        console.log("actBlockOneOff", card);
                                        this.bgaPerformAction("actBlockOneOff", { cardId: card.id });
                                    });
                            }
                            this.addActionButton('actAllowOneOff-btn', _('Allow'), () => {
                                this.stopActionTimer();
                                this.bgaPerformAction("actAllowOneOff");
                            }, null, null, 'gray');
                            if (handCardsThatCanBlock.length == 0) {
                                this.statusBar.setTitle(_('${you} do not have any 2\'s to block this effect'), {});
                                if (this.getGameUserPreference(101) == 1) {
                                    this.startActionTimer('actAllowOneOff-btn', 12, 1);
                                }
                            }
                            break;
                    }
                }
            },
            onSelectionChangeChooseAction: function (selection, lastChange) {
                let cardCommandsDiv = document.getElementById('cardCommands');
                cardCommandsDiv.replaceChildren();

                document.getElementById('cardInfo')?.remove();

                if (selection?.length != 1) {
                    document.getElementById('table').appendChild(cardCommandsDiv);
                    return;
                }

                let card = selection[0]; //CuttleCards.updateCardPlayability(selection[0]);
                // setup card action buttons
                // console.log("Updated card playability", card);

                if (card.playability.playableForPoints) {
                    this.addActionButton('actPointCard',
                        dojo.string.substitute(
                            _('Play for ${points} Point(s)'),
                            { points: CuttleCards.getCardData(card)?.points }
                        ),
                        () => this.playCardForPoints(card),
                        'cardCommands', false, 'blue');
                }
                if (card.points > 0) {
                    this.addActionButton('actScuttleCard', _('Scuttle a Point Card'), () => this.playCardForScuttle(card), 'cardCommands', false, 'blue');
                    if (!card.playability.playableForScuttle) {
                        dojo.addClass('actScuttleCard', 'disabled');
                        if (card.playability.scuttle_reason)
                            this.addTooltip('actScuttleCard', _(card.playability.scuttle_reason), '');
                    }
                }
                if (card.effect_type) {
                    this.addActionButton('actEffectCard',
                        dojo.string.substitute(
                            _(CuttleCards.getCardData(card)?.effect || 'Play for Effect'),
                            { effect_type: CuttleCards.getCardData(card)?.effect_type }
                        ),
                        () => this.playCardForEffect(card),
                        'cardCommands', false, 'blue');
                    if (!card.playability.playableForEffect) {
                        dojo.addClass('actEffectCard', 'disabled');
                        if (card.playability.effect_reason)
                            this.addTooltip('actEffectCard', _(card.playability.effect_reason), '');
                    }
                }
                if (card.playability.playableForDiscard) {
                    this.addActionButton('actDiscardCard', _('Discard'), () => this.discardCard(card), 'cardCommands', false, 'red');
                }

                let cardDiv = CuttleCards.manager.getCardElement(card);
                cardDiv.appendChild(cardCommandsDiv);

                // let cardInfoMarkup = CuttleCards.getCardInfoMarkup(card);
                // cardDiv.insertAdjacentHTML('beforeend', cardInfoMarkup);

                // let cardInfoDiv = document.getElementById('cardInfo');
                // let sizes = cardInfoDiv.getBoundingClientRect();
                // if (sizes.left + sizes.width > window.innerWidth) {
                //     cardInfoDiv.classList.add('card-info-left');
                // }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            isReadOnly() {
                return CuttlePlayers.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
            },

            addCardToBlockableActions(card, playerId, targetCard = null, targetPlayerId = null) {
                let stock = CuttleCards.getCardStock('blockableActions');
                let existingCards = stock.getCards();
                stock.addCard(card);

                let message = "";

                let blockingCardName = null;
                if (existingCards.length > 0) {
                    blockingCardName = existingCards[existingCards.length - 1].name;
                    message = dojo.string.substitute(
                        _('${player_name} is blocking ${card_name}'),
                        { player_name: this.getFormattedPlayerName(playerId), card_name: blockingCardName }
                    );
                } else if (targetCard != null && targetPlayerId != null) {
                    message = dojo.string.substitute(
                        _('${player_name} is playing against ${target_player_name}\'s ${target_card_name}'),
                        {
                            player_name: this.getFormattedPlayerName(playerId),
                            target_player_name: this.getFormattedPlayerName(targetPlayerId),
                            target_card_name: targetCard.name
                        }
                    );
                } else if (targetCard != null) {
                    message = dojo.string.substitute(
                        _('${player_name} is playing against ${target_card_name}'),
                        {
                            player_name: this.getFormattedPlayerName(playerId),
                            target_card_name: targetCard.name
                        }
                    );
                } else if (targetPlayerId != null) {
                    message = dojo.string.substitute(
                        _('${player_name} is playing against ${target_player_name}'),
                        {
                            player_name: this.getFormattedPlayerName(playerId),
                            target_player_name: this.getFormattedPlayerName(targetPlayerId)
                        }
                    );
                } else {
                    message = dojo.string.substitute(
                        _('${player_name} is playing'),
                        { player_name: this.getFormattedPlayerName(playerId) }
                    );
                }

                for (let i = 0; i < existingCards.length; i++) {
                    let cardEl = CuttleCards.manager.getCardElement(existingCards[i]);
                    cardEl.classList.toggle("effect-blocked", (existingCards.length - i) % 2 != 0);
                }

                let cardEl = CuttleCards.manager.getCardElement(card);
                cardEl.classList.toggle("effect-blocked", false);
                cardEl.insertAdjacentHTML('beforeEnd', `<div class="blockable-action-note">${message}</div>`);
            },

            /*
             * Add a timer on an action button :
             * params:
             *  - buttonId : id of the action button
             *  - time : time before auto click
             *  - pref : 0 is disabled (auto-click), 1 if normal timer, 2 if no timer and show normal button
             */
            startActionTimer(buttonId, time, pref, autoclick = false) {
                var button = document.getElementById(buttonId);
                var isReadOnly = this.isReadOnly();
                if (button == null || isReadOnly || pref == 2) {
                    return;
                }

                // If confirm disabled, click on button
                if (pref == 0) {
                    if (autoclick) button.click();
                    return;
                }

                this._actionTimerLabel = button.innerHTML;
                this._actionTimerSeconds = time;
                this._actionTimerFunction = ((force = false) => {
                    if (!force && this._actionTimerId == null)
                        return;

                    var button = document.getElementById(buttonId);
                    if (button == null) {
                        this.stopActionTimer();
                    } else if (this._actionTimerSeconds-- > 1) {
                        button.innerHTML = this._actionTimerLabel + ' (' + this._actionTimerSeconds + ')';
                        this._actionTimerId = window.setTimeout(this._actionTimerFunction, 1000);
                    } else {
                        button.click();
                    }
                });
                this._actionTimerFunction(true);
            },

            stopActionTimer() {
                if (this._actionTimerId != null) {
                    window.clearTimeout(this._actionTimerId);
                    delete this._actionTimerId;
                }
            },

            ///////////////////////////////////////////////////
            //// Player's action

            /*
            
                Here, you are defining methods to handle player's action (ex: results of mouse click on 
                game objects).
                
                Most of the time, these methods:
                _ check the action is possible at this game state.
                _ make a call to the game server
            
            */


            onDrawPileClick: function () {
                console.log('onDrawPileClick');

                this.bgaPerformAction("actDrawCard", {});
            },

            examineDiscardPile: function () {
                if (document.getElementById('discardViewWrapper').classList.contains('show'))
                    return; // already visible

                let discard = CuttleCards.getCardStock('discard');
                let discardView = CuttleCards.getCardStock('discardView');

                // reveal the discard pile in a user-friendly window
                document.getElementById('discardViewWrapper').classList.add('show');
                discardView.addCards(discard.getCards());
            },
            hideDiscardPile: function () {
                if (!document.getElementById('discardViewWrapper').classList.contains('show'))
                    return; // already hidden

                let discard = CuttleCards.getCardStock('discard');
                let discardView = CuttleCards.getCardStock('discardView');

                discard.addCards(discardView.getCards());
                document.getElementById('discardViewWrapper').classList.remove('show');
            },

            discardCard: function (card) {
                this.bgaPerformAction("actPlayDiscard", { cardId: card.id })?.then(() => { });
            },
            playCardForPoints: function (card) {
                this.bgaPerformAction("actPlayCardPoints", { cardId: card.id })?.then(() => { });
            },
            playCardForScuttle: function (card) {
                CuttleCards.getCardStock('pending').addCard(card);

                this.setClientState(`client_confirmScuttle_${this.gamedatas.gamestate.name}`, {
                    descriptionmyturn: _('Choose a card to scuttle'),
                });

                let cardCommandsDiv = document.getElementById('cardCommands');
                cardCommandsDiv.replaceChildren();
                let cardDiv = CuttleCards.manager.getCardElement(card);
                cardDiv.appendChild(cardCommandsDiv);

                this.addActionButton('actConfirmScuttleInstruct', _('Choose a card to scuttle'), null, 'cardCommands', false, 'gray');
                dojo.addClass('actConfirmScuttleInstruct', 'disabled');

                const cancelScuttleFn = () => {
                    if (this.gamedatas.gamestate.name.endsWith("playerFromStaging"))
                        CuttleCards.getCardStock('staging').addCard(card);
                    else
                        CuttleCards.getCardStock('hand', CuttlePlayers.myId).addCard(card);

                    this.restoreServerGameState();
                };
                this.addActionButton('actCancelScuttleTarget', _('Cancel'), cancelScuttleFn, null, null, 'red');
                this.addActionButton('actCancelScuttleTarget_card', _('Cancel'), cancelScuttleFn, 'cardCommands', null, 'red');

                for (var targetPlayerId of CuttlePlayers.getPlayerIds().filter(id => id != CuttlePlayers.myId)) {
                    let allPointCards = CuttleCards.getCardStock('points', targetPlayerId).getCards();
                    let cards = allPointCards.filter(c => c.points > 0 && c.strength < card.strength);
                    if (cards.length > 0) {
                        for (let i = cards.length; i > 0; i--)
                            cards.push(...CuttleCards.getAllDescendants(cards[i - 1], allPointCards));

                        let stock = CuttleCards.getCardStock('points', targetPlayerId);

                        stock.setSelectionMode('multiple', cards);
                        stock.onSelectionChange = (selection, lastchange) => {
                            if (lastchange?.parent_card_id) {
                                stock.unselectCard(lastchange, true);
                                let rootCard = CuttleCards.getRootCard(lastchange, stock.getCards());
                                if (selection.indexOf(rootCard) >= 0)
                                    stock.unselectCard(rootCard);
                                else
                                    stock.selectCard(rootCard);
                                return;
                            }
                            if (selection.length > 1) {
                                selection.filter(c => c != lastchange).forEach(c => stock.unselectCard(c, true));
                                selection = [lastchange];
                            }

                            let cardCommandsDiv = document.getElementById('cardTargetCommands');
                            cardCommandsDiv.replaceChildren();

                            if (selection?.length != 1) {
                                document.getElementById('table').appendChild(cardCommandsDiv);
                                return;
                            }

                            let selectedTargetCard = selection[0];
                            CuttleCards.manager.getCardElement(selectedTargetCard).appendChild(cardCommandsDiv);

                            this.addActionButton('actConfirmScuttleTarget',
                                _('Scuttle this card'),
                                () => { this.bgaPerformAction("actPlayCardScuttle", { cardId: card.id, targetCardId: selectedTargetCard.id }).then(() => { }); },
                                'cardTargetCommands', false, 'blue');

                        }
                    }
                }
            },
            playCardForEffect: function (card) {
                if (!card) return;

                if (!card.targets) {
                    this.bgaPerformAction("actPlayCardEffect", { cardId: card.id })?.then(() => { });
                    return;
                }

                if (card.targets.discardPile) {
                    CuttleCards.getCardStock('pending').addCard(card);

                    let cardData = CuttleCards.getCardData(card);
                    this.setClientState(`client_confirmEffect_${this.gamedatas.gamestate.name}`, {
                        descriptionmyturn: _(cardData.effect_instruct),
                    });

                    let cardCommandsDiv = document.getElementById('cardCommands');
                    cardCommandsDiv.replaceChildren();
                    let cardDiv = CuttleCards.manager.getCardElement(card);
                    cardDiv.appendChild(cardCommandsDiv);

                    if (cardData.effect_instruct) {
                        this.addActionButton('actConfirmEffectInstruct', _(cardData.effect_instruct), null, 'cardCommands', false, 'gray');
                        dojo.addClass('actConfirmEffectInstruct', 'disabled');
                    }
                    const cancelEffectFn = () => {
                        if (this.gamedatas.gamestate.name.endsWith("playerFromStaging"))
                            CuttleCards.getCardStock('staging').addCard(card);
                        else
                            CuttleCards.getCardStock('hand', CuttlePlayers.myId).addCard(card);

                        this.hideDiscardPile();
                        this.restoreServerGameState();
                    };
                    this.addActionButton('actCancelEffectTarget', _('Cancel'), cancelEffectFn, null, null, 'red');
                    this.addActionButton('actCancelEffectTarget_card', _('Cancel'), cancelEffectFn, 'cardCommands', null, 'red');

                    this.examineDiscardPile();
                    let stock = CuttleCards.getCardStock('discardView');

                    stock.setSelectionMode('single');
                    stock.onSelectionChange = (selection, lastchange) => {
                        let cardCommandsDiv = document.getElementById('cardTargetCommands');
                        cardCommandsDiv.replaceChildren();

                        if (selection?.length != 1) {
                            document.getElementById('table').appendChild(cardCommandsDiv);
                            return;
                        }

                        let selectedTargetCard = selection[0];
                        CuttleCards.manager.getCardElement(selectedTargetCard).appendChild(cardCommandsDiv);

                        this.addActionButton('actConfirmEffectTarget',
                            _(cardData?.effect_target_confirm_text || 'Confirm'),
                            () => {
                                this.bgaPerformAction("actPlayCardEffect", { cardId: card.id, targetCardId: selectedTargetCard.id })
                                    ?.then(() => {
                                        this.hideDiscardPile();
                                    });
                            },
                            'cardTargetCommands', false, 'blue');
                    }
                } else if (Array.isArray(card.targets.players)) {
                    if (card.targets.players.length == 1) {
                        this.bgaPerformAction("actPlayCardEffect", { cardId: card.id, targetPlayerId: card.targets.players[0] })?.then(() => { });
                    } else {
                        CuttleCards.getCardStock('pending').addCard(card);

                        let cardData = CuttleCards.getCardData(card);
                        this.setClientState(`client_confirmEffect_${this.gamedatas.gamestate.name}`, {
                            descriptionmyturn: _(cardData.effect_instruct),
                        });

                        let cardCommandsDiv = document.getElementById('cardCommands');
                        cardCommandsDiv.replaceChildren();
                        let cardDiv = CuttleCards.manager.getCardElement(card);
                        cardDiv.appendChild(cardCommandsDiv);

                        if (cardData.effect_instruct) {
                            this.addActionButton('actConfirmEffectInstruct', _(cardData.effect_instruct), null, 'cardCommands', false, 'gray');
                            dojo.addClass('actConfirmEffectInstruct', 'disabled');
                        }
                        const cancelEffectFn = () => {
                            if (this.gamedatas.gamestate.name.endsWith("playerFromStaging"))
                                CuttleCards.getCardStock('staging').addCard(card);
                            else
                                CuttleCards.getCardStock('hand', CuttlePlayers.myId).addCard(card);

                            CuttleCards.disableAllSelectionModes();
                            this.restoreServerGameState();
                        };
                        this.addActionButton('actCancelEffectTarget', _('Cancel'), cancelEffectFn, null, null, 'red');
                        this.addActionButton('actCancelEffectTarget_card', _('Cancel'), cancelEffectFn, 'cardCommands', null, 'red');

                        for (const targetPlayerId of card.targets.players) {
                            let playerHeaderEl = document.querySelector(`#playertable-${targetPlayerId} .playertableheader`);
                            playerHeaderEl.classList.toggle('selectable', true);
                            playerHeaderEl.onclick = () => {
                                this.bgaPerformAction("actPlayCardEffect", { cardId: card.id, targetPlayerId: targetPlayerId });
                            };
                        }
                    }
                } else if (card.targets.players) {
                    CuttleCards.getCardStock('pending').addCard(card);

                    let cardData = CuttleCards.getCardData(card);
                    this.setClientState(`client_confirmEffect_${this.gamedatas.gamestate.name}`, {
                        descriptionmyturn: _(cardData.effect_instruct),
                    });

                    let cardCommandsDiv = document.getElementById('cardCommands');
                    cardCommandsDiv.replaceChildren();
                    let cardDiv = CuttleCards.manager.getCardElement(card);
                    cardDiv.appendChild(cardCommandsDiv);

                    if (cardData.effect_instruct) {
                        this.addActionButton('actConfirmEffectInstruct', _(cardData.effect_instruct), null, 'cardCommands', false, 'gray');
                        dojo.addClass('actConfirmEffectInstruct', 'disabled');
                    }
                    const cancelEffectFn = () => {
                        if (this.gamedatas.gamestate.name.endsWith("playerFromStaging"))
                            CuttleCards.getCardStock('staging').addCard(card);
                        else
                            CuttleCards.getCardStock('hand', CuttlePlayers.myId).addCard(card);

                        CuttleCards.disableAllSelectionModes();
                        this.restoreServerGameState();
                    };
                    this.addActionButton('actCancelEffectTarget', _('Cancel'), cancelEffectFn, null, null, 'red');
                    this.addActionButton('actCancelEffectTarget_card', _('Cancel'), cancelEffectFn, 'cardCommands', null, 'red');

                    for (const [targetPlayerId, targetStocks] of Object.entries(card.targets.players)) {
                        for (const [stockName, cardIds] of Object.entries(targetStocks)) {
                            let stock = CuttleCards.getCardStock(stockName, targetPlayerId);
                            let allStockCards = stock.getCards();
                            let selectableCards = allStockCards.filter(c => cardIds.includes(c.id));

                            if (card.blockable_by_shield && CuttlePlayers.getPlayerShield(targetPlayerId)) {
                                selectableCards = selectableCards.filter(c => !c.shieldable);
                                for (let i = selectableCards.length - 1; i >= 0; i--) {
                                    selectableCards.push(...CuttleCards.getAllDescendants(selectableCards[i], allStockCards));
                                }
                            }

                            stock.setSelectionMode('multiple', selectableCards);
                            stock.element?.classList.add('playing-blockable-card');
                            stock.onSelectionChange = (selection, lastchange) => {
                                if (lastchange?.parent_card_id) {
                                    stock.unselectCard(lastchange, true);
                                    let rootCard = CuttleCards.getRootCard(lastchange, stock.getCards());
                                    if (selection.indexOf(rootCard) >= 0)
                                        stock.unselectCard(rootCard);
                                    else
                                        stock.selectCard(rootCard);
                                    return;
                                }
                                if (selection.length > 1) {
                                    selection.filter(c => c != lastchange).forEach(c => stock.unselectCard(c, true));
                                    selection = [lastchange];
                                }

                                let cardCommandsDiv = document.getElementById('cardTargetCommands');
                                cardCommandsDiv.replaceChildren();

                                if (selection?.length != 1) {
                                    document.getElementById('table').appendChild(cardCommandsDiv);
                                    return;
                                }

                                let selectedTargetCard = selection[0];
                                CuttleCards.manager.getCardElement(selectedTargetCard).appendChild(cardCommandsDiv);

                                this.addActionButton('actConfirmEffectTarget',
                                    _(cardData?.effect_target_confirm_text || 'Confirm'),
                                    () => { this.bgaPerformAction("actPlayCardEffect", { cardId: card.id, targetCardId: selectedTargetCard.id })?.then(() => { }); },
                                    'cardTargetCommands', false, 'blue');

                            }
                        }
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            onGameUserPreferenceChanged: function (pref_id, pref_value) {
                switch (pref_id) {
                    case 100: // theme change
                        with (document.documentElement) {
                            classList.toggle('theme_cuttlefish', pref_value == 1);
                            classList.toggle('theme_bga', pref_value == 2);
                        }
                        break;
                }
            },

            onDealerChanged: function (dealerId) {
                document.querySelectorAll('.playerstatus-container')
                    .forEach(el => {
                        el.classList.toggle('is-dealer', el.classList.contains(`playerstatus-container-${dealerId}`));
                    });

                CuttlePlayers.dealerId = dealerId;
            },
            onPlayerCardCountChanged: function (playerId, cardCount) {
                document.querySelectorAll(`.playerstatus-cardcount-${playerId}`)
                    .forEach(el => {
                        el.innerText = cardCount;
                    });
            },
            onPlayerPointsChanged: function (playerId, points) {
                document.querySelectorAll(`.playerstatus-points-${playerId}`)
                    .forEach(el => {
                        el.innerText = points;
                    });
            },
            onPlayerTargetPointsChanged: function (playerId, targetPoints) {
                document.querySelectorAll(`.playerstatus-targetpoints-${playerId}`)
                    .forEach(el => {
                        el.innerText = targetPoints;
                    });
            },
            onPlayerShieldChanged: function (playerId, shielded) {
                document.querySelectorAll(`.playerstatus-container-${playerId}`)
                    .forEach(el => {
                        el.classList.toggle('player-shielded', !!shielded);
                    });

                CuttlePlayers.setPlayerShield(playerId, !!shielded);
            },
            onPlayerSpyglassChanged: function (playerId, hasSpyglass) {
                let playerTableEl = document.getElementById(`playertable-${playerId}`);
                if (playerId == CuttlePlayers.myId || CuttlePlayers.isSpectator) {
                    let hadSpyglass = playerTableEl.classList.contains('has-spyglass');
                    if (hadSpyglass && !hasSpyglass) {
                        // hide player hands
                        for (let handPlayerId of CuttlePlayers.getPlayerIds()) {
                            if (handPlayerId == CuttlePlayers.myId) continue;

                            let stock = CuttleCards.getCardStock('hand', handPlayerId);
                            for (let card of stock.getCards()) {
                                stock.setCardVisible({ id: card.id }, false, { updateMain: true });
                            }
                        }
                    }
                }
                document.querySelectorAll(`.playerstatus-container-${playerId}`)
                    .forEach(el => {
                        el.classList.toggle('has-spyglass', !!hasSpyglass);
                    });
            },

            setupNotifications: function () {
                console.log('notifications subscriptions setup');

                this.bgaSetupPromiseNotifications();
                this.notifqueue.setIgnoreNotificationCheck('cardDrawn', (notif) => (notif.args.playerId == CuttlePlayers.myId));
                this.notifqueue.setIgnoreNotificationCheck('revealHand', (notif) => (notif.args.playerId == CuttlePlayers.myId));
                this.notifqueue.setSynchronous('endRound', 1000);
            },
            handleCommonNotifArgs: function (args) {
                if (!args) return;
                let recalcPlayerCardCounts = false;

                if (args.playerReturnedCardIds)
                    for (let $playerId in args.playerReturnedCardIds)
                        CuttlePlayers.setReturnedCardId($playerId, args.playerReturnedCardIds[$playerId]);

                if (args.playerScores)
                    for (let $playerId in args.playerScores)
                        this.scoreCtrl[$playerId].setValue(parseInt(args.playerScores[$playerId]));
                if (args.playerPoints)
                    for (let $playerId in args.playerPoints)
                        this.onPlayerPointsChanged($playerId, args.playerPoints[$playerId]);
                if (args.targetPoints)
                    for (let $playerId in args.targetPoints)
                        this.onPlayerTargetPointsChanged($playerId, args.targetPoints[$playerId]);
                if (args.playerSpyglasses)
                    for (let $playerId in args.playerSpyglasses)
                        this.onPlayerSpyglassChanged($playerId, args.playerSpyglasses[$playerId]);
                if (args.playerShields)
                    for (let $playerId in args.playerShields)
                        this.onPlayerShieldChanged($playerId, args.playerShields[$playerId]);

                if (args.unstageCards)
                    CuttleCards.getCardStock('deck').addCards(args.unstageCards, null, { visible: false });

                if (args.moveToPoints && args.playerId) {
                    CuttleCards.getCardStock('points', args.playerId).addCards(args.moveToPoints);
                    recalcPlayerCardCounts = true;
                }
                if (args.moveToEffects && args.playerId) {
                    CuttleCards.getCardStock('effects', args.playerId).addCards(args.moveToEffects);
                    recalcPlayerCardCounts = true;
                }
                if (args.moveToHand && args.playerId) {
                    CuttleCards.getCardStock('hand', args.playerId).addCards(args.playerId == CuttlePlayers.myId || CuttlePlayers.getPlayerHasSpyglass(CuttlePlayers.myId)
                        ? args.moveToHand
                        : args.moveToHand.map(c => ({ id: c.id }))
                    );
                    recalcPlayerCardCounts = true;
                }
                if (args.moveToOtherHand) {
                    for (let $playerId in args.moveToOtherHand)
                        CuttleCards
                            .getCardStock('hand', $playerId)
                            .addCards($playerId == CuttlePlayers.myId || CuttlePlayers.getPlayerHasSpyglass(CuttlePlayers.myId)
                                ? args.moveToOtherHand[$playerId]
                                : args.moveToOtherHand[$playerId].map(c => ({ id: c.id }))
                            );
                    recalcPlayerCardCounts = true;
                }
                if (args.moveToOtherPoints) {
                    for (let $playerId in args.moveToOtherPoints)
                        CuttleCards.getCardStock('points', $playerId).addCards(args.moveToOtherPoints[$playerId]);
                    recalcPlayerCardCounts = true;
                }
                if (args.moveToOtherEffects) {
                    for (let $playerId in args.moveToOtherEffects)
                        CuttleCards.getCardStock('effects', $playerId).addCards(args.moveToOtherEffects[$playerId]);
                    recalcPlayerCardCounts = true;
                }

                if (args.drawToHand && args.playerId) {
                    CuttleCards.getCardStock('hand', args.playerId).addCards(args.drawToHand, { fromElement: document.getElementById('drawpile'), originalSide: 'back' });
                    CuttleCards.getCardStock('deck').setCardNumber(CuttleCards.getCardStock('deck').getCardNumber() - (args.drawToHand.length || 1) || 0);
                    recalcPlayerCardCounts = true;
                }
                if (args.drawToStaging) {
                    CuttleCards.getCardStock('staging').addCards(args.drawToStaging, { fromElement: document.getElementById('drawpile'), originalSide: 'back' });
                    CuttleCards.getCardStock('deck').setCardNumber(CuttleCards.getCardStock('deck').getCardNumber() - (args.drawToStaging.length || 1) || 0);
                }

                if (args.moveToDiscard) {
                    CuttleCards.getCardStock('discard').addCards(args.moveToDiscard, null, { visible: true });
                    recalcPlayerCardCounts = true;
                }
                if (args.moveToDrawPile) {
                    CuttleCards.getCardStock('deck').addCards(args.moveToDrawPile.map(c => ({ id: c.id })), null, { visible: false });
                    CuttleCards.getCardStock('deck').setCardNumber(CuttleCards.getCardStock('deck').getCardNumber() + (args.moveToDrawPile.length || 1) || 0);
                }

                if (args.playerHands) {
                    //let haveSpyglass = CuttlePlayers.getPlayerHasSpyglass(CuttlePlayers.myId);
                    for (let playerId in args.playerHands) {
                        if (playerId == CuttlePlayers.myId)
                            continue;
                        let stock = CuttleCards.getCardStock('hand', playerId);
                        for (let card of args.playerHands[playerId]) {
                            stock.setCardVisible(card, !!card.type, { updateMain: true });
                        }
                    }
                    recalcPlayerCardCounts = true;
                }
                if (args.deckCount != null)
                    CuttleCards.getCardStock('deck').setCardNumber(args.deckCount);

                if (recalcPlayerCardCounts) {
                    CuttlePlayers.getPlayerIds().forEach((playerId) => {
                        this.onPlayerCardCountChanged(playerId, CuttleCards.getCardStock('hand', playerId)?.getCards().length || 0);
                    });
                }
            },

            notif_newRound: function (args) {
                console.log('notif_newRound', args);
                this.handleCommonNotifArgs(args);

                let roundMarkerEl = document.getElementById('roundMarker');
                if (roundMarkerEl)
                    roundMarkerEl.innerText = dojo.string.substitute(_('Round ${current_round} (First to ${winning_score})'),
                        {
                            current_round: args.current_round,
                            winning_score: CuttleRules.winningScore
                        });

                this.onDealerChanged(args.dealerId);

                let deck = CuttleCards.resetDeck();
                deck.shuffle();
            },
            notif_endRound: function (args) { console.log('notif_endRound', args); this.handleCommonNotifArgs(args); },
            notif_passed: function (args) { console.log('notif_passed', args); this.handleCommonNotifArgs(args); },
            notif_cardPlayed: function (args) { console.log('notif_cardPlayed', args); this.handleCommonNotifArgs(args); },
            notif_cardDrawnSelf: function (args) { console.log('notif_cardDrawnSelf', args); this.handleCommonNotifArgs(args); },
            notif_cardDrawn: function (args) { console.log('notif_cardDrawn', args); this.handleCommonNotifArgs(args); },
            notif_cardPlaying: function (args) {
                console.log('notif_cardPlaying', args);
                this.handleCommonNotifArgs(args);

                if (args.blockableAction?.card) {
                    this.addCardToBlockableActions(
                        args.blockableAction.card,
                        args.blockableAction.activePlayerId,
                        args.blockableAction.targetCard,
                        args.blockableAction.targetPlayerId
                    );
                }
            },
            notif_revealHand: function (args) { console.log('notif_revealHand', args); this.handleCommonNotifArgs(args); },
            notif_effectBlocked: function (args) { console.log('notif_effectBlocked', args); this.handleCommonNotifArgs(args); },
            notif_effectAllowed: function (args) { console.log('notif_effectAllowed', args); this.handleCommonNotifArgs(args); },
        });
    });