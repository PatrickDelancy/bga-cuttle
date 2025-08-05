<?php

namespace Bga\Games\cuttle;

require_once('actions.cardeffects.php');

trait ActionTrait
{
    use ActionCardEffectsTrait;

    public function actDrawCard(): void
    {
        $activePlayerId = (int)$this->getActivePlayerId();

        $handLimit = $this->globals->get(RULE_HAND_LIMIT);
        if ($handLimit > 0 &&  $this->cards->countCardsInLocation('hand', $activePlayerId) >= $handLimit)
            throw new \BgaUserException(self::_('You are already at the hand limit and cannot draw more cards'));

        $drawnCard = $this->getCard($this->cards->pickCard('deck', $activePlayerId));
        if ($drawnCard == null)
            throw new \BgaUserException(self::_('Draw pile is empty. Choose a different action.'));

        $this->incStat(1, "drawn_from_deck", $activePlayerId);
        $this->notifyAllPlayers("cardDrawn", clienttranslate('${player_name} adds ${card_count} card(s) to their hand'), [
            "playerId" => $activePlayerId,
            "player_name" => $this->getPlayerNameById($activePlayerId),
            "card_count" => 1,

            "drawToHand" => [Card::onlyId($drawnCard)],
        ]);

        $this->notifyPlayer($activePlayerId, "cardDrawnSelf", clienttranslate('${card_name} is added to your hand'), [
            "playerId" => $activePlayerId,
            "card_name" => $drawnCard['name'],
            "i18n" => ['card_name'],

            "drawToHand" => [$drawnCard],
        ]);

        if ($this->handVisibleToTable($activePlayerId)) {
            $this->notifyAllPlayers("revealHand", clienttranslate('${player_name} reveals ${card_names} to you in their hand'), [
                "playerId" => $activePlayerId,
                "player_name" => $this->getPlayerNameById($activePlayerId),
                "card_names" => $drawnCard['name'],
                "i18n" => ['card_name'],

                "playerHands" => [$activePlayerId => [$drawnCard]],
            ]);
        } else {
            $playerIdsWithSpyglass = $this->dbGetPlayerIdsWithSpyglass();
            foreach ($playerIdsWithSpyglass as $spyingPlayerId) {
                if ($spyingPlayerId == $activePlayerId) continue;

                $this->notifyPlayer($spyingPlayerId, "revealHand", clienttranslate('${player_name} reveals ${card_names} to you in their hand'), [
                    "playerId" => $activePlayerId,
                    "player_name" => $this->getPlayerNameById($activePlayerId),
                    "card_names" => $drawnCard['name'],
                    "i18n" => ['card_name'],

                    "playerHands" => [$activePlayerId => [$drawnCard]],
                ]);
            }
        }

        $this->globals->set(K_PASS_COUNT, 0);
        $this->gamestate->nextState("endTurn");
    }

    public function actPlayDiscard(int $cardId, ?int $cardId2): void
    {
        $activePlayerId = (int)$this->getActivePlayerId();
        $cardIds = array_filter([$cardId, $cardId2]);
        $cards = $this->getCards($this->cards->getCards($cardIds));

        foreach ($cardIds as $id)
            $this->cards->playCard($id);

        $this->incStat(count($cards), "played_for_discard", $activePlayerId);
        $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} discards ${card_names}'), [
            "playerId" => $activePlayerId,
            "player_name" => $this->getActivePlayerName(),
            "card_names" => implode(', ', array_map(fn($c) => self::_($c['name']), $cards)),

            "moveToDiscard" => $cards
        ]);

        $this->globals->set(K_PASS_COUNT, 0);
        $this->gamestate->nextState("endTurn");
    }

    private function checkCardPlayableForScuttle(int $cardId, int $targetCardId)
    {
        $activePlayerId = (int)$this->getActivePlayerId();

        $card = $this->getCard($this->cards->getCard($cardId));
        if ($card == null)
            throw new \BgaUserException(self::_('Unknown card id'));
        if ($card['location'] != 'hand' && $card['location_arg'] != $activePlayerId && $card['location'] != 'staging')
            throw new \BgaUserException(self::_('This card is not in your hand'));
        if ($card['points'] == 0)
            throw new \BgaUserException(self::_('This card is not a points card'));

        $targetCard = $this->getCard($this->cards->getCard($targetCardId));
        if ($targetCard == null)
            throw new \BgaUserException(self::_('Unknown target card id'));
        if ($targetCard['location'] != 'points')
            throw new \BgaUserException(self::_('Target card is not an active point card'));
        if ($targetCard['points'] == 0)
            throw new \BgaUserException(self::_('This card is not a points card'));

        $returnedCardId = $this->dbGetPlayerReturnedCardId($activePlayerId);
        if ($returnedCardId == $card['id'])
            throw new \BgaUserException(self::_('You cannot play a card on the round it was returned to your hand'));

        if ($card['strength'] < $targetCard['strength']) {
            throw new \BgaUserException(self::_('Target card must be lower value than the one you are playing'));
        }

        return [
            'activePlayerId' => $activePlayerId,
            'card' => $card,
            'targetCard' => $targetCard,
        ];
    }
    public function actPlayCardScuttle(int $cardId, int $targetCardId)
    {
        [
            'activePlayerId' => $activePlayerId,
            'card' => $card,
            'targetCard' => $targetCard,
        ] = $this->checkCardPlayableForScuttle($cardId, $targetCardId);

        $unstageCards = $this->returnStagedCards($card);

        $attachedCardIds = $this->dbGetAttachedCardIds($targetCard['id']);
        $cardIdsToDiscard = array_merge([$card['id'], $targetCard['id']], $attachedCardIds);

        $this->cards->moveCards($cardIdsToDiscard, 'discard');
        $this->dbClearCardParentIds($cardIdsToDiscard);
        $cardsToDiscard = $this->getCards($this->cards->getCards($cardIdsToDiscard));

        $this->incStat(1, "played_for_scuttle", $activePlayerId);
        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} uses ${card_name} to scuttle ${target_card_name} to the discard pile'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "target_card_name" => $targetCard['name'],
                "i18n" => ['card_name', 'target_card_name'],

                "unstageCards" => $unstageCards,
                "moveToDiscard" => $cardsToDiscard,
            ])
        );

        $this->globals->set(K_PASS_COUNT, 0);
        $this->gamestate->nextState("endTurn");
    }

    private function checkCardPlayableForPoints(int $cardId)
    {
        $activePlayerId = (int)$this->getActivePlayerId();

        $card = $this->getCard($this->cards->getCard($cardId));
        if ($card == null)
            throw new \BgaUserException(self::_('Unknown card id'));
        if ($card['location'] != 'hand' && $card['location_arg'] != $activePlayerId && $card['location'] != 'staging')
            throw new \BgaUserException(self::_('This card is not in your hand'));
        if ($card['points'] == 0)
            throw new \BgaUserException(self::_('This card is not a points card'));

        $returnedCardId = $this->dbGetPlayerReturnedCardId($activePlayerId);
        if ($returnedCardId == $card['id'])
            throw new \BgaUserException(self::_('You cannot play a card on the round it was returned to your hand'));

        return [
            'activePlayerId' => $activePlayerId,
            'card' => $card,
        ];
    }
    public function actPlayCardPoints(int $cardId): void
    {
        [
            'activePlayerId' => $activePlayerId,
            'card' => $card
        ] = $this->checkCardPlayableForPoints($cardId);

        $unstageCards = $this->returnStagedCards($card);

        $this->cards->moveCard($cardId, 'points', $activePlayerId);

        $newPoints = $this->dbIncPoints($activePlayerId, $card['points']);

        $this->incStat(1, "played_for_points", $activePlayerId);
        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name} for ${points} points'),
            array_merge($this->dbRecalculatePlayerStatuses(), [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "points" => $card['points'],
                "i18n" => ['card_name'],

                "unstageCards" => $unstageCards,
                "moveToPoints" => [$card],
                "playerPoints" => [$activePlayerId => $newPoints],
            ])
        );

        $this->globals->set(K_PASS_COUNT, 0);
        // at the end of the action, move to the next state
        $this->gamestate->nextState("endTurn");
    }

    private function checkCardPlayableForEffect(int $cardId, ?int $targetCardId, ?int $targetPlayerId)
    {
        $activePlayerId = (int)$this->getActivePlayerId();

        $card = $this->getCard($this->cards->getCard($cardId));

        if ($card == null)
            throw new \BgaUserException(self::_('Unknown card id'));

        $returnedCardId = $this->dbGetPlayerReturnedCardId($activePlayerId);
        if ($returnedCardId == $card['id'])
            throw new \BgaUserException(self::_('You cannot play a card on the round it was returned to your hand'));

        if ($card['location'] != 'hand' && $card['location_arg'] != $activePlayerId && $card['location'] != 'staging')
            throw new \BgaUserException(self::_('This card is not in your hand'));
        if ($card['effect_type'] == null)
            throw new \BgaUserException(self::_('This card has no playable effect'));

        $targetCard = null;
        if ($targetCardId != null) {
            $targetCard = $this->getCard($this->cards->getCard($targetCardId));
            if ($targetCard['shieldable'] && $this->dbGetPlayerShield($targetCard['location_arg']) && $card['blockable_by_shield'])
                throw new \BgaUserException(self::_('Target card is protected from this effect'));
        }

        switch ($card['type']) {
            case 2:
                if ($targetCard == null)
                    throw new \BgaUserException(self::_('You must select an active permanent effect to scrap'));
                break;
            case 3:
                $handLimit = $this->globals->get(RULE_HAND_LIMIT);
                if ($handLimit > 0 &&  $this->cards->countCardsInLocation('hand', $activePlayerId) >= $handLimit)
                    throw new \BgaUserException(self::_('You are already at the hand limit and cannot draw more cards'));

                if ($targetCard == null)
                    throw new \BgaUserException(self::_('You must select a card to add to your hand'));
                if ($targetCard == null || $targetCard['location'] != 'discard')
                    throw new \BgaUserException(self::_('Target card must come from the discard pile'));
                break;
            case 4:
                if ($targetPlayerId == null)
                    throw new \BgaUserException(self::_('Target player required'));
                break;
            case 5:
                if ($targetCard == null) {
                    $cardsInHand = count(array_filter($this->cards->getCardsInLocation('hand', $activePlayerId), fn($c) => $c['id'] != $cardId));

                    if ($cardsInHand > 0 && $this->globals->get(RULE_5_DISCARD_TO_DRAW))
                        throw new \BgaUserException(self::_('You must select a card to discard'));
                } else if ($targetCard['location'] != 'hand' || $targetCard['location_arg'] != $activePlayerId) {
                    throw new \BgaUserException(self::_('The card to discard is not in your hand'));
                }
                break;
            case 9:
                if ($targetCard == null)
                    throw new \BgaUserException(self::_('You must select an active card'));
                break;
            case 11:
                if ($targetCard == null || $targetCard['points'] <= 0 || $targetCard['location'] != 'points')
                    throw new \BgaUserException(self::_('Target card must be an active point card'));
                break;
            case 1:
            case 6:
            case 7:
            case 8:
            case 10:
            case 12:
            case 13:
            default:
                break;
        }

        return [
            'activePlayerId' => $activePlayerId,
            'card' => $card,
            'targetCard' => $targetCard,
        ];
    }
    public function actPlayCardEffect(int $cardId, ?int $targetCardId, ?int $targetPlayerId): void
    {
        [
            'activePlayerId' => $activePlayerId,
            'card' => $card,
            'targetCard' => $targetCard,
        ] = $this->checkCardPlayableForEffect($cardId, $targetCardId, $targetPlayerId);

        $notifArgs = [];
        $notifArgs['unstageCards'] = $this->returnStagedCards($card);

        $blockableBy = $this->playerIdsThatCanBlockEffect($activePlayerId, $card);
        if (count($blockableBy) > 0) {
            $blockableAction = [
                'activePlayerId' => $activePlayerId,
                'cardId' => $cardId,
                'targetCardId' => $targetCardId,
                'targetPlayerId' => $targetPlayerId,
                'blockableBy' => $blockableBy
            ];
            $this->globals->set('blockable_action_queue', [$blockableAction]);

            $targetCard = $targetCardId == null ? null : $this->getCard($this->cards->getCard($targetCardId));
            $showTargetCard = $targetCard != null && $targetCard['location'] != 'hand' && $targetCard['location'] != 'discard';

            $this->notifyAllPlayers(
                "cardPlaying",
                clienttranslate('${player_name} is playing ${card_name} for one-off effect, which may be blocked'),
                array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                    "playerId" => $activePlayerId,
                    "player_name" => $this->getPlayerNameById($activePlayerId),
                    "card_name" => $card['name'],
                    "i18n" => ['card_name'],

                    "blockableAction" => [
                        'activePlayerId' => $activePlayerId,
                        'cardId' => $cardId,
                        'card' => $this->getCard($this->cards->getCard($cardId)),
                        'targetCardId' => $showTargetCard ? $targetCardId : null,
                        'targetCard' => $showTargetCard ? $targetCard : null,
                        'targetPlayerId' => $targetPlayerId,
                    ]
                ])
            );

            $this->gamestate->nextState("blockableEffect");
            return;
        }

        $this->executePlayCardEffect($activePlayerId, $notifArgs, $card, $targetCard, $targetPlayerId);
    }

    public function actBlockOneOff(int $cardId)
    {
        $activePlayerId = (int)$this->getCurrentPlayerId();

        $card = $this->getCard($this->cards->getCard($cardId));

        if ($card == null)
            throw new \BgaUserException(self::_('Unknown card id'));
        if ($card['location'] != 'hand' && $card['location_arg'] != $activePlayerId)
            throw new \BgaUserException(self::_('This card is not in your hand'));
        if ($card['type'] != 2)
            throw new \BgaUserException(self::_('This card cannot be used to block the pending action'));

        $blockableActionQueue = $this->globals->get('blockable_action_queue');
        $blockableActionQueue[] = ['activePlayerId' => $activePlayerId, 'cardId' => $cardId];
        $this->globals->set('blockable_action_queue', $blockableActionQueue);

        // check if the 2 being played can be blocked by another player
        $playerIdsThatCanBlock = $this->playerIdsThatCanBlockEffect($activePlayerId, $card);
        if (count($playerIdsThatCanBlock) > 0) {
            $this->notifyAllPlayers(
                "cardPlaying",
                clienttranslate('${player_name} is playing ${card_name} for one-off effect, which may be blocked'),
                [
                    "playerId" => $activePlayerId,
                    "player_name" => $this->getPlayerNameById($activePlayerId),
                    "card_name" => $card['name'],
                    "i18n" => ['card_name'],

                    "blockableAction" => [
                        'activePlayerId' => $activePlayerId,
                        'cardId' => $cardId,
                        'card' => $this->getCard($this->cards->getCard($cardId))
                    ]
                ]
            );

            $this->gamestate->setPlayersMultiactive($playerIdsThatCanBlock, 'complete', true);
        } else {
            if (count($blockableActionQueue) % 2 == 0) {
                $this->notifyAllPlayers(
                    "effectBlocked",
                    clienttranslate('${player_name} plays ${card_name} to block a one-off effect'),
                    [
                        "playerId" => $activePlayerId,
                        "player_name" => $this->getPlayerNameById($activePlayerId),
                        "card_name" => $card['name'],
                        "i18n" => ['card_name']
                    ]
                );

                $this->gamestate->setAllPlayersNonMultiactive("complete");
            } else {
                $this->notifyAllPlayers(
                    "effectAllowed",
                    clienttranslate('${player_name} plays ${card_name} to block a one-off effect, un-blocking the original one-off effect'),
                    [
                        "playerId" => $activePlayerId,
                        "player_name" => $this->getPlayerNameById($activePlayerId),
                        "card_name" => $card['name'],
                        "i18n" => ['card_name']
                    ]
                );

                $this->gamestate->setAllPlayersNonMultiactive("complete");
            }
        }
    }
    public function actAllowOneOff()
    {
        $activePlayerId = $this->getCurrentPlayerId();

        $this->notifyAllPlayers(
            "effectAllowed",
            clienttranslate('${player_name} chooses not to block the one-off effect'),
            [
                "playerId" => $activePlayerId,
                "player_name" => $this->getPlayerNameById($activePlayerId),
            ]
        );

        $this->gamestate->setPlayerNonMultiactive($this->getCurrentPlayerId(), "complete");
    }

    public function actPass(?bool $zombiePass): void
    {
        $activePlayerId = (int)$this->getActivePlayerId();

        $this->incStat(1, "turns_passed", $activePlayerId);
        $this->notifyAllPlayers("passed", clienttranslate('${player_name} passes'), [
            "player_id" => $activePlayerId,
            "player_name" => $this->getActivePlayerName(),
        ]);

        $this->globals->inc(K_PASS_COUNT, 1);
        $this->gamestate->nextState($zombiePass ? "zombiePass" : "pass");
    }

    private function returnStagedCards($playedCard)
    {
        $stagedCards = $this->getCards($this->cards->getCardsInLocation('staging'));
        if (count($stagedCards) > 0) {
            $cardsToUnstage = array_filter($stagedCards, fn($c) => $c['id'] != $playedCard['id']);
            foreach ($stagedCards as $stagedCard) {
                // except for the card being played, move all staged cards back to the deck
                $this->cards->insertCardOnExtremePosition($stagedCard['id'], 'deck', true);
            }
            return Card::onlyIds($cardsToUnstage);
        }
        return null;
    }

    public function playerIdsThatCanBlockEffect(int $activePlayerId, mixed $card)
    {
        if ($card != null && $card['effect_type'] != 'One-Off') {
            return [];
        }

        $playerIds = $this->getPlayerIds();
        $playerIdsThatCanBlock = [];
        $blockableActionQueue = $this->globals->get('blockable_action_queue', []);
        $otherHandsRevealed = $this->otherHandsRevealed($activePlayerId);

        foreach ($playerIds as $playerId) {
            if ($playerId == $activePlayerId) continue;

            // if hand is hidden OR player has a 2, then they should be given the chance to block a one-off effect
            if ($otherHandsRevealed) {
                $handCards = $this->cards->getCardsInLocation('hand', $playerId);
                foreach (array_filter($handCards, fn($c) => $c['type'] == 2) as $handCard) {
                    if (count(array_filter($blockableActionQueue, fn($a) => $a['cardId'] == $handCard['id'])) == 0) {
                        $playerIdsThatCanBlock[] = $playerId;
                    }
                }
            } else {
                $playerIdsThatCanBlock[] = $playerId;
            }
        }

        return $playerIdsThatCanBlock;
    }
}
