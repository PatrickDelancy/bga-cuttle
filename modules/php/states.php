<?php

namespace Bga\Games\cuttle;

trait StateTrait
{
    function stNewRound()
    {
        $this->globals->inc(K_ROUND_NUMBER, 1);
        $this->globals->set(K_PASS_COUNT, 0);

        $this->dbClearCardParentIds();
        $this->dbClearCardPlayMeta();
        $this->cards->moveAllCardsInLocation(null, 'deck');
        $this->cards->shuffle('deck');

        $this->dbResetPlayerPoints();

        // Deal cards to each players
        $playerIds = $this->getPlayerIds();
        $dealerId = $this->globals->get(K_DEALER_ID);

        $this->notifyAllPlayers(
            "newRound",
            clienttranslate('Round ${current_round} begins'),
            array_merge_recursive($this->dbRecalculatePlayerStatuses(), [
                "current_round" => $this->globals->get(K_ROUND_NUMBER),
                "dealerId" => $dealerId,
            ])
        );

        foreach ($playerIds as $playerId) {
            $initialHandCards = $playerId == $dealerId
                ? $this->globals->get(RULE_STARTING_HAND_SIZE_DEALER)
                : $this->globals->get(RULE_STARTING_HAND_SIZE_PLAYER);

            $cards = $this->getCards($this->cards->pickCards($initialHandCards, 'deck', $playerId));

            // This commented out block can be used to setup player hands for testing
            // if ($playerId == $dealerId) {
            //     $cards = $this->getCards([
            //         array_values($this->cards->getCardsOfTypeInLocation(1, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(2, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(3, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(4, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(5, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(6, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(7, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(8, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(9, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(10, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(11, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(12, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(13, null, 'deck'))[0],
            //     ]);
            // } else {
            //     $cards = $this->getCards([
            //         array_values($this->cards->getCardsOfTypeInLocation(1, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(2, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(3, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(4, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(5, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(6, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(7, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(8, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(9, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(10, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(11, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(12, null, 'deck'))[0],
            //         array_values($this->cards->getCardsOfTypeInLocation(13, null, 'deck'))[0],
            //     ]);
            // }
            // $this->cards->moveCards(array_map(fn($c) => $c['id'], $cards), 'hand', $playerId);

            $this->notifyPlayer($playerId, "cardDrawnSelf", clienttranslate('You start the round with ${card_names} in your hand'), [
                "playerId" => $playerId,
                "drawToHand" => $cards,
                "card_names" => implode(', ', array_map(fn($c) => self::_($c['name']), $cards)),
                "i18n" => ['card_name'],
            ]);

            $this->notifyAllPlayers("cardDrawn", clienttranslate('${player_name} starts the round with ${card_count} cards in their hand'), [
                "playerId" => $playerId,
                "player_name" => $this->getPlayerNameById($playerId),
                "card_count" => count($cards),
                "drawToHand" => Card::onlyIds($cards),
            ]);
        }

        $this->gamestate->changeActivePlayer($this->getPlayerAfter($dealerId));
        $this->gamestate->nextState("");
    }

    function stEndRound(): void
    {
        $currentRound = $this->globals->get(K_ROUND_NUMBER);
        $roundWinnerId = $this->dbGetCurrentRoundWinnerId();

        $score = 0;
        $winningScore = $this->getGameStateValue(K_WINNING_SCORE);

        $playerIds = $this->getPlayerIds();
        $playerHands = [];
        foreach ($playerIds as $playerId) {
            $playerHands[$playerId] = $this->getCards($this->cards->getCardsInLocation('hand', $playerId));
        }
        if ($roundWinnerId !== null) {
            $score = $this->dbIncScore($roundWinnerId, 1);

            $playerScores = [];
            $this->notifyAllPlayers("endRound", clienttranslate('${player_name} wins round ${current_round}'), [
                "player_name" => $this->getPlayerNameById($roundWinnerId),
                "current_round" => $currentRound,

                "playerScores" => $playerScores,
                "playerHands" => $playerHands,
            ]);
        } else {
            $this->incStat(1, "tied_rounds_count");
            $this->notifyAllPlayers("endRound", clienttranslate('Round ${current_round} ends in a tie'), [
                "current_round" => $currentRound,
                "playerHands" => $playerHands,
            ]);
        }

        if ($score >= $winningScore) {
            $this->gamestate->nextState("endGame");
        } else {
            // rotate dealer for the next round
            $dealerId = $this->globals->get(K_DEALER_ID);
            $nextDealerId = $this->getPlayerAfter($dealerId);
            $this->globals->set(K_DEALER_ID, $nextDealerId);

            $this->gamestate->nextState("newRound");
        }
    }

    public function stNextPlayer(): void
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->dbSetPlayerReturnedCardId($playerId, null);
        $this->incStat(1, "turns_count");
        $this->incStat(1, "turns_count", $playerId);
        $this->giveExtraTime($playerId);

        $roundWinnerId = $this->dbGetCurrentRoundWinnerId();
        if ($this->globals->get(K_PASS_COUNT, 0) >= 3 || $roundWinnerId !== null) {
            $this->gamestate->nextState("endRound");
        } else {
            $this->activeNextPlayer();
            $this->gamestate->nextState("nextPlayer");
        }
    }

    public function stNextForceDiscard(): void
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->giveExtraTime($playerId);

        $this->globals->set('next_turn_player', $this->getPlayerAfter($playerId));
        $discardTargetPlayer =  $this->globals->get('discard_target_player');
        $this->globals->delete('discard_target_player');

        $this->gamestate->changeActivePlayer($discardTargetPlayer);
        $this->gamestate->nextState("");
    }

    public function stNextFinishForcedDiscard(): void
    {
        $nextPlayerId = $this->globals->get('next_turn_player', null);
        $this->globals->delete('next_turn_player');

        $this->gamestate->changeActivePlayer($nextPlayerId);
        $this->gamestate->nextState("");
    }

    public function stPlayersBlockOneOff(): void
    {
        $activePlayerId = (int)$this->getActivePlayerId();
        $playerIdsThatCanBlock = $this->playerIdsThatCanBlockEffect($activePlayerId, null);

        if (count($playerIdsThatCanBlock) > 0) {
            $this->gamestate->setPlayersMultiactive($playerIdsThatCanBlock, 'complete', true);
        } else {
            $this->gamestate->setAllPlayersNonMultiactive('complete');
        }
    }

    public function stCompleteBlockableEffect(): void
    {
        $blockableActionQueue = $this->globals->get('blockable_action_queue');
        [
            'activePlayerId' => $originalActionPlayerId,
            'cardId' => $cardId,
            'targetCardId' => $targetCardId,
            'targetPlayerId' => $targetPlayerId,
        ] = $blockableActionQueue[0];

        $card = $this->getCard($this->cards->getCard($cardId));

        $moveToDiscard = [];
        for ($i = 1; $i < count($blockableActionQueue); $i++) {
            $moveToDiscard[] = $this->getCard($this->cards->getCard($blockableActionQueue[$i]['cardId']));
        }

        if (count($blockableActionQueue) % 2 == 0) {
            // block original effect
            array_unshift($moveToDiscard, $card);

            $this->cards->moveCards(array_map(fn($c) => $c['id'], $moveToDiscard), 'discard');

            $this->notifyAllPlayers("effectBlocked", clienttranslate('Card ${card_name} effect is blocked'), [
                "card_name" => $card['name'],
                "moveToDiscard" => $moveToDiscard,
            ]);

            $this->gamestate->changeActivePlayer($originalActionPlayerId);
            $this->gamestate->nextState("endTurn");
        } else {
            // allow original effect
            $targetCard = null;
            if ($targetCardId != null) {
                $targetCard = $this->getCard($this->cards->getCard($targetCardId));
            }

            $this->cards->moveCards(array_map(fn($c) => $c['id'], $moveToDiscard), 'discard');

            $this->gamestate->changeActivePlayer($originalActionPlayerId);
            $this->executePlayCardEffect($originalActionPlayerId, ['moveToDiscard' => $moveToDiscard], $card, $targetCard, $targetPlayerId);
        }

        $this->globals->delete('blockable_action_queue');
    }
}
