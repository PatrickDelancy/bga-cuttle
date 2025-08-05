<?php

namespace Bga\Games\cuttle;

trait ActionCardEffectsTrait
{
    private function executePlayCardEffect($activePlayerId, $notifArgs, $card, $targetCard, $targetPlayerId)
    {
        switch ($card['type']) {
            case 1:
                $this->playScrapAllPointCards($activePlayerId, $notifArgs, $card);
                break;
            case 2:
                $this->playScrapAnEffect($activePlayerId, $notifArgs, $card, $targetCard);
                break;
            case 3:
                $this->playTakeScrappedCard($activePlayerId, $notifArgs, $card, $targetCard);
                $this->incStat(1, "drawn_from_discard", $activePlayerId);
                break;
            case 4:
                $this->playForceDiscard($activePlayerId, $notifArgs, $card, $targetPlayerId);
                if (!$this->globals->get(RULE_FOURS_RANDOM, false)) {
                    $nextState = "forceDiscard";
                }
                break;
            case 5:
                $this->playDiscardAndDraw($activePlayerId, $notifArgs, $card, $targetCard);
                break;
            case 6:
                $this->playScrapAllEffectCards($activePlayerId, $notifArgs, $card);
                break;
            case 7:
                $this->playRevealCardsPlay1($activePlayerId, $notifArgs, $card);
                $nextState = "playFromStaging";
                break;
            case 8:
                $this->playRevealPlayerHand($activePlayerId, $notifArgs, $card);
                break;
            case 9:
                $this->playReturnEffectCard($activePlayerId, $notifArgs, $card, $targetCard);
                break;
            case 11:
                $this->playStealActiveCard($activePlayerId, $notifArgs, $card, $targetCard, 'points');
                break;
            case 12:
                $this->playShield($activePlayerId, $notifArgs, $card);
                break;
            case 13:
                $this->playReduceTargetPoints($activePlayerId, $notifArgs, $card);
                break;

            case 15:
                $this->playStealActiveCard($activePlayerId, $notifArgs, $card, $targetCard, 'effects');
                break;

            default:
                throw new \BgaUserException(self::_('Unrecognized or unsupported card type'));
                break;
        }
        $this->globals->set(K_PASS_COUNT, 0);
        $this->incStat(1, "played_for_effect", $activePlayerId);
        $this->gamestate->nextState($nextState ?? "endTurn");
    }

    private function playScrapAllPointCards($activePlayerId, $notifArgs, $card)
    {
        $playerIds = $this->getPlayerIds();

        $cardsToDiscard = [$card];
        foreach ($playerIds as $playerId) {
            $pointCards = $this->getCards($this->cards->getCardsInLocation('points', $playerId));
            $cardsToDiscard = array_merge($cardsToDiscard, $pointCards);
        }
        $idsToDiscard = array_map(fn($card) => $card['id'], $cardsToDiscard);
        $this->cards->moveCards($idsToDiscard, 'discard');
        $this->dbClearCardParentIds($idsToDiscard);
        $this->dbClearCardPlayMeta($idsToDiscard);

        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name} to scrap all active point cards'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "i18n" => ['card_name'],

                "moveToDiscard" => $cardsToDiscard,
            ])
        );
    }
    private function playScrapAnEffect($activePlayerId, $notifArgs, $card, $targetCard)
    {
        $moveToOtherPlayer = [];
        $otherLocation = 'points';
        $attachedCardIds = $this->dbGetAttachedCardIds($targetCard['id']);
        if (count($attachedCardIds) > 0) {
            $lastCardId = array_pop($attachedCardIds);
            $playMeta = $this->dbGetCardPlayMeta($lastCardId);
            $otherLocation = $targetCard['location'];

            $this->cards->moveCards(array_merge([$targetCard['id']], $attachedCardIds), $targetCard['location'], $playMeta['fromPlayerId']);
            $moveToOtherPlayer = [$playMeta['fromPlayerId'] => $this->getCards($this->cards->getCards(array_merge([$targetCard['id']], $attachedCardIds)))];

            // switch target to the lowest attached card
            $targetCardId = $lastCardId;
            $this->dbSetCardParentId($targetCardId, null);
            $targetCard = $this->getCard($this->cards->getCard($targetCardId));
        }

        $this->cards->moveCard($targetCard['id'], 'discard');
        $this->cards->moveCard($card['id'], 'discard');

        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name} to scrap ${target_card_name} from the table'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "target_card_name" => $targetCard['name'],
                "i18n" => ['card_name', 'target_card_name'],

                "moveToOther" . ($otherLocation == 'effects' ? 'Effects' : 'Points') => $moveToOtherPlayer,
                "moveToDiscard" => [$card, $targetCard],
            ])
        );
    }
    private function playTakeScrappedCard($activePlayerId, $notifArgs, $card, $targetCard)
    {
        $this->cards->moveCard($targetCard['id'], 'hand', $activePlayerId);
        $this->cards->moveCard($card['id'], 'discard');

        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name}, adding ${target_card_name} to their hand'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "target_card_name" => $targetCard['name'],
                "i18n" => ['card_name', 'target_card_name'],

                "moveToDiscard" => [$card],
                "moveToHand" => [$targetCard]
            ])
        );
    }
    private function playForceDiscard($activePlayerId, $notifArgs, $card, $targetPlayerId)
    {
        $this->cards->playCard($card['id']);

        $cardsToDiscard = [];
        $randomFours = $this->globals->get(RULE_FOURS_RANDOM, false);
        if ($randomFours) {
            $handCards = $this->getCards($this->cards->getCardsInLocation('hand', $targetPlayerId));

            $cardsToDiscard = count($handCards) > 2
                ? $this->getRandomSlice($handCards, 2)
                : $handCards;

            $this->cards->moveCards(array_map(fn($c) => $c['id'], $cardsToDiscard), 'discard');
        } else {
            $this->globals->set('discard_target_player', $targetPlayerId);
        }

        $this->notifyAllPlayers(
            "cardPlayed",
            $randomFours
                ? clienttranslate('${player_name} plays ${card_name} forcing ${target_player_name} to discard 2 cards at random (${discarded_card_names})')
                : clienttranslate('${player_name} plays ${card_name} forcing ${target_player_name} to discard 2 cards'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getPlayerNameById($activePlayerId),
                "card_name" => $card['name'],
                "discarded_card_names" => implode(', ', array_map(fn($c) => self::_($c['name']), $cardsToDiscard)),
                "target_player_name" => $this->getPlayerNameById($targetPlayerId),
                "i18n" => ['card_name'],

                "moveToDiscard" => array_merge([$card], $cardsToDiscard),
            ])
        );
    }
    private function playDiscardAndDraw($activePlayerId, $notifArgs, $card, $targetCard)
    {
        if ($targetCard != null) {
            $this->cards->moveCard($targetCard['id'], 'discard');
        }

        $this->cards->moveCard($card['id'], 'discard');

        $handLimit = $this->globals->get(RULE_HAND_LIMIT);
        if ($handLimit == 0) $handLimit = 52;
        $cardsInHand = intVal($this->cards->countCardsInLocation('hand', $activePlayerId));
        $cardsToDraw = max(0, min($handLimit - $cardsInHand, $this->globals->get(RULE_5_DRAW_COUNT))); // cannot draw over hand limit, cannot draw negative number

        if ($cardsToDraw <= 0) {
            $this->notifyAllPlayers(
                "cardDrawn",
                clienttranslate('${player_name} is already at hand limit and cannot draw any cards'),
                array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                    "playerId" => $activePlayerId,
                    "player_name" => $this->getPlayerNameById($activePlayerId),

                    "moveToDiscard" => array_filter([$card, $targetCard]),
                ])
            );
        } else {
            $drawnCards = $this->getCards($this->cards->pickCards($cardsToDraw, 'deck', $activePlayerId));

            $commonArgs = $this->dbRecalculatePlayerStatuses();

            $this->notifyAllPlayers(
                "cardDrawn",
                clienttranslate('${player_name} draws ${card_count} card(s) to their hand'),
                array_merge_recursive($commonArgs, $notifArgs, [
                    "playerId" => $activePlayerId,
                    "player_name" => $this->getPlayerNameById($activePlayerId),
                    "card_count" => count($drawnCards),

                    "drawToHand" => Card::onlyIds($drawnCards),
                    "moveToDiscard" => array_filter([$card, $targetCard]),
                ])
            );

            $this->notifyPlayer(
                $activePlayerId,
                "cardDrawnSelf",
                clienttranslate('${card_names} are added to your hand'),
                array_merge_recursive($commonArgs, $notifArgs, [
                    "playerId" => $activePlayerId,
                    "card_names" => implode(', ', array_map(fn($c) => self::_($c['name']), $drawnCards)),

                    "drawToHand" => $drawnCards,
                    "moveToDiscard" => array_filter([$card, $targetCard]),
                ])
            );

            $this->notif_revealNewHandCards($activePlayerId, $drawnCards);
        }
    }
    private function playScrapAllEffectCards($activePlayerId, $notifArgs, $card)
    {
        $playerIds = $this->getPlayerIds();

        $cardsToDiscard = [$card];
        $moveToOtherPoints = [];
        foreach ($playerIds as $playerId) {
            $effectCards = $this->getCards($this->cards->getCardsInLocation('effects', $playerId));
            $pointCards = $this->getCards($this->cards->getCardsInLocation('points', $playerId));

            foreach ($pointCards as $pointCard) {
                if ($pointCard['points'] > 0) { // skip processing non-point cards
                    $childCardIds = $this->dbGetAttachedCardIds($pointCard['id']);
                    if (count($childCardIds) > 0) {
                        $firstChildCardId = $childCardIds[0];
                        $playMeta = $this->dbGetCardPlayMeta($firstChildCardId);
                        $otherPlayerId = $playMeta['fromPlayerId'];

                        if ($otherPlayerId != $playerId) {
                            // point card needs to move
                            if (!isset($moveToOtherPoints[$otherPlayerId])) {
                                $moveToOtherPoints[$otherPlayerId] = [];
                            }
                            $moveToOtherPoints[$otherPlayerId][] = $pointCard;
                        }

                        $cardsToDiscard = array_merge($cardsToDiscard, $this->getCards($this->cards->getCards($childCardIds)));
                    }
                }
            }
            $cardsToDiscard = array_merge($cardsToDiscard, $effectCards);
        }
        foreach ($moveToOtherPoints as $otherPlayerId => $cards) {
            $this->cards->moveCards(array_map(fn($c) => $c['id'], $cards), 'points', $otherPlayerId);
        }

        $cardIdsToDiscard = array_map(fn($c) => $c['id'], $cardsToDiscard);
        $this->cards->moveCards($cardIdsToDiscard, 'discard');
        $this->dbClearCardParentIds($cardIdsToDiscard);
        $this->dbClearCardPlayMeta($cardIdsToDiscard);

        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name} to scrap all active permanent effects'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "i18n" => ['card_name'],

                "moveToDiscard" => $cardsToDiscard,
                "moveToOtherPoints" => $moveToOtherPoints,
            ])
        );
    }
    private function playRevealCardsPlay1($activePlayerId, $notifArgs, $card)
    {
        $this->cards->playCard($card['id']);
        $drawCount = $this->globals->get(RULE_DRAW_AND_PLAY_COUNT);
        $pickedCards = $this->getCards($this->cards->pickCardsForLocation($drawCount, 'deck', 'staging'));

        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name} revealing ${card_names} from the deck, and must choose how to play'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "card_names" => implode(', ', array_map(fn($c) => self::_($c['name']), $pickedCards)),
                "i18n" => ['card_name'],

                "drawToStaging" => $pickedCards,
                "moveToDiscard" => [$card],
            ])
        );
    }
    private function playRevealPlayerHand($activePlayerId, $notifArgs, $card)
    {
        $this->cards->moveCard($card['id'], 'effects', $activePlayerId);

        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name}, revealing opponents\' hands'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getPlayerNameById($activePlayerId),
                "card_name" => $card['name'],
                "i18n" => ['card_name'],

                "moveToEffects" => [$card],
            ])
        );

        $this->notif_revealPlayerHands($activePlayerId);
    }
    private function playReturnEffectCard($activePlayerId, $notifArgs, $card, $targetCard)
    {
        $moveToOtherPlayer = [];
        $otherLocation = 'points';
        $attachedCardIds = $this->dbGetAttachedCardIds($targetCard['id']);
        if (count($attachedCardIds) > 0) {
            $lastCardId = array_pop($attachedCardIds);
            $playMeta = $this->dbGetCardPlayMeta($lastCardId);
            $otherLocation = $targetCard['location'];

            $this->cards->moveCards(array_merge([$targetCard['id']], $attachedCardIds), $targetCard['location'], $playMeta['fromPlayerId']);
            $moveToOtherPlayer = [$playMeta['fromPlayerId'] => $this->getCards($this->cards->getCards(array_merge([$targetCard['id']], $attachedCardIds)))];

            // switch target to the lowest attached card
            $targetCardId = $lastCardId;
            $this->dbSetCardParentId($targetCardId, null);
            $targetCard = $this->getCard($this->cards->getCard($targetCardId));
        }

        $deckNines = $this->globals->get(RULE_DECK_NINES);

        $this->cards->playCard($card['id']);
        if ($deckNines) {
            $this->cards->insertCardOnExtremePosition($targetCard['id'], 'deck', true);
        } else {
            $targetPlayerId = $targetCard['location_arg'];
            $this->cards->moveCard($targetCard['id'], 'hand', $targetPlayerId);

            if ($this->globals->get(RULE_RETURNED_CARD_UNPLAYABLE))
                $this->dbSetPlayerReturnedCardId($targetPlayerId, $targetCard['id']);
        }

        $this->notifyAllPlayers(
            "cardPlayed",
            $deckNines
                ? clienttranslate('${player_name} plays ${card_name}, placing ${target_card_name} on top of the draw pile')
                : clienttranslate('${player_name} plays ${card_name}, returning ${target_card_name} to its owner\'s hand'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "target_card_name" => $targetCard['name'],
                "i18n" => ['card_name', 'target_card_name'],

                "moveToDiscard" => [$card],
                "moveToOther" . ($otherLocation == 'effects' ? 'Effects' : 'Points') => $moveToOtherPlayer,
                $deckNines ? "moveToDrawPile" : "moveToOtherHand" => $deckNines ? [$targetCard] : [$targetCard['location_arg'] => [$targetCard]],
            ])
        );
    }
    private function playStealActiveCard($activePlayerId, $notifArgs, $card, $targetCard, $stockName)
    {
        $moveToPlayer = [];
        $attachedCardIds = $this->dbGetAttachedCardIds($targetCard['id']);
        $this->dbSetCardPlayMeta($card['id'], ['fromPlayerId' => $targetCard['location_arg']]);

        if (count($attachedCardIds) > 0) {
            $newParentId = $attachedCardIds[count($attachedCardIds) - 1];

            $this->dbSetCardParentId($card['id'], $newParentId);
            $card['parent_card_id'] = $newParentId;

            $this->cards->moveCards($attachedCardIds, $stockName, $activePlayerId);
            $moveToPlayer = $this->getCards($this->cards->getCards($attachedCardIds));
        } else {
            $this->dbSetCardParentId($card['id'], $targetCard['id']);
            $card['parent_card_id'] = $targetCard['id'];
        }

        $moveToPlayer[] = $card;
        $moveToPlayer[] = $targetCard;
        $this->cards->moveCards(array_map(fn($card) => $card['id'], $moveToPlayer), $stockName, $activePlayerId);

        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name}, stealing ${target_card_name}'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "target_card_name" => $targetCard['name'],
                "i18n" => ['card_name', 'target_card_name'],

                "moveTo" . ($stockName == 'effects' ? 'Effects' : 'Points') => $moveToPlayer,
            ])
        );
        if ($targetCard['type'] == 8 && $stockName == 'effects') {
            $this->notif_revealPlayerHands($activePlayerId);
        }
    }
    private function playShield($activePlayerId, $notifArgs, $card)
    {
        $this->cards->moveCard($card['id'], 'effects', $activePlayerId);

        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name}, protecting their active cards from targeted attacks'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "i18n" => ['card_name'],

                "moveToEffects" => [$card],
            ])
        );
    }
    private function playReduceTargetPoints($activePlayerId, $notifArgs, $card)
    {
        $this->cards->moveCard($card['id'], 'effects', $activePlayerId);
        $statuses = $this->dbRecalculatePlayerStatuses();

        $this->notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name}, reducing their target points to ${target_points}'),
            array_merge_recursive($statuses, $notifArgs, [
                "playerId" => $activePlayerId,
                "player_name" => $this->getActivePlayerName(),
                "card_name" => $card['name'],
                "target_points" => $statuses['targetPoints'][$activePlayerId],
                "i18n" => ['card_name'],

                "moveToEffects" => [$card],
            ])
        );
    }
}
