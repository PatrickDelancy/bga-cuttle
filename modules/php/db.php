<?php

namespace Bga\Games\cuttle;

trait DbTrait
{
    public function dbGetPoints($player_id)
    {
        return $this->getUniqueValueFromDB("SELECT player_points FROM player WHERE player_id = '$player_id'");
    }
    public function dbSetPoints($player_id, $count)
    {
        $this->DbQuery("UPDATE player SET player_points = '$count' WHERE player_id = '$player_id'");
    }
    public function dbIncPoints($player_id, $inc)
    {
        $count = $this->dbGetPoints($player_id);
        if ($inc != 0) {
            $count += $inc;
            $this->dbSetPoints($player_id, $count);
        }
        return $count;
    }
    public function dbResetPlayerPoints()
    {
        $this->DbQuery("UPDATE player SET player_points = 0");
    }


    public function dbGetScore($player_id)
    {
        return $this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id = '$player_id'");
    }
    public function dbSetScore($player_id, $score)
    {
        $this->DbQuery("UPDATE player SET player_score = '$score' WHERE player_id = '$player_id'");
    }
    public function dbIncScore($player_id, $inc)
    {
        $count = $this->dbGetScore($player_id);
        if ($inc != 0) {
            $count += $inc;
            $this->dbSetScore($player_id, $count);
        }
        return $count;
    }


    public function dbGetTargetPoints($player_id)
    {
        return $this->getUniqueValueFromDB("SELECT player_target_points FROM player WHERE player_id = '$player_id'");
    }
    public function dbSetTargetPoints($player_id, $count)
    {
        $this->DbQuery("UPDATE player SET player_target_points = '$count' WHERE player_id = '$player_id'");
    }


    public function dbRecalculatePlayerPoints($playerId)
    {
        $points = 0;
        $pointCards = $this->getCards($this->cards->getCardsInLocation('points', $playerId));

        foreach ($pointCards as $card) {
            $points += $card['points'];
        }

        $this->dbSetPoints($playerId, $points);
        return $points;
    }
    public function dbRecalculatePlayersPoints()
    {
        $playerIds = $this->getPlayerIds();
        $playerPoints = [];
        foreach ($playerIds as $playerId) {
            $playerPoints[$playerId] = $this->dbRecalculatePlayerPoints($playerId);
        }
        return $playerPoints;
    }

    public function dbGetPlayerIdsWithSpyglass(): array
    {
        return array_keys($this->getCollectionFromDb("SELECT `card_location_arg` FROM `card` WHERE `card_location` = 'effects' AND `card_type` = '8'"));
    }
    public function dbGetPlayerHasSpyglass($playerId): bool
    {
        $playerIdsWithSpyglass = $this->dbGetPlayerIdsWithSpyglass();
        return in_array($playerId, $playerIdsWithSpyglass);
    }

    public function dbGetCurrentRoundWinnerId()
    {
        $playerId = $this->getUniqueValueFromDB("SELECT `player_id` FROM `player` WHERE `player_points` >= `player_target_points`");
        return $playerId == null ? null : intval($playerId);
    }

    public function dbGetPlayerShield($playerId): bool
    {
        return boolval($this->getUniqueValueFromDB("SELECT player_shielded FROM player WHERE player_id = '$playerId'"));
    }
    public function dbSetPlayerShield($playerId, bool $shielded)
    {
        $shieldedBit = $shielded ? 1 : 0;
        $this->DbQuery("UPDATE player SET player_shielded = $shieldedBit WHERE player_id = '$playerId'");
    }

    public function dbGetPlayerReturnedCardId($playerId): ?int
    {
        return intval($this->getUniqueValueFromDB("SELECT player_returned_card_id FROM player WHERE player_id = '$playerId'"));
    }
    public function dbSetPlayerReturnedCardId($playerId, ?int $cardId)
    {
        $sqlCardId = $cardId == null ? 'null' : $cardId;
        $this->DbQuery("UPDATE player SET player_returned_card_id = $sqlCardId WHERE player_id = '$playerId'");
    }

    // public function dbGetPrivateUserStatuses($activePlayerId)
    // {
    //     $playerIds = $this->getPlayerIds();

    //     $playerHands = [];
    //     foreach ($playerIds as $playerId) {
    //         if ($this->handVisibleToPlayer($playerId, $activePlayerId)) {
    //             $playerHands[$playerId] = $this->getCards($this->cards->getCardsInLocation('hand', $playerId));
    //         }
    //     }

    //     return [
    //         'playerHands' => $playerHands,
    //     ];
    // }

    public function dbRecalculatePlayerStatuses($activePlayerId = null, $includePlayerHands = false)
    {
        $playerIds = $this->getPlayerIds();

        $targetPoints = [];
        $playerPoints = [];
        $playerShields = [];
        $playerReturnedCardIds = [];

        $playerSpyglasses = [];
        $playerHands = [];

        foreach ($playerIds as $playerId) {
            $pointCards = $this->getCards($this->cards->getCardsInLocation('points', $playerId));
            $effectCards = $this->getCards($this->cards->getCardsInLocation('effects', $playerId));

            $points = 0;
            foreach ($pointCards as $pointCard)
                $points += $pointCard['points'];

            $kingCount = 0;
            foreach ($effectCards as $effectCard) {
                switch ($effectCard['type']) {
                    case 8:
                        $playerSpyglasses[$playerId] = true;
                        break;
                    case 12:
                        $playerShields[$playerId] = true;
                        break;
                    case 13:
                        $kingCount++;
                        break;
                }
            }

            if (!isset($playerSpyglasses[$playerId])) {
                $playerSpyglasses[$playerId] = false;
            }
            if (isset($playerShields[$playerId]) && $playerShields[$playerId] == true) {
                $this->dbSetPlayerShield($playerId, true);
            } else {
                $playerShields[$playerId] = false;
                $this->dbSetPlayerShield($playerId, false);
            }

            if ($includePlayerHands && $this->handVisibleToPlayer($playerId, $activePlayerId)) {
                $playerHands[$playerId] = $this->getCards($this->cards->getCardsInLocation('hand', $playerId));
            }

            $playerReturnedCardIds[$playerId] = $this->dbGetPlayerReturnedCardId($playerId);
            $playerPoints[$playerId] = $points;
            $this->dbSetPoints($playerId, $points);
            $targetPoints[$playerId] = $this->globals->get(RULE_TARGET_KING_POINTS)[$kingCount];
            $this->dbSetTargetPoints($playerId, $targetPoints[$playerId]);
        }

        return [
            'playerHands' => $playerHands,
            'playerSpyglasses' => $playerSpyglasses,
            'targetPoints' => $targetPoints,
            'playerPoints' => $playerPoints,
            'playerShields' => $playerShields,
            'playerReturnedCardIds' => $playerReturnedCardIds,
            'deckCount' => $this->cards->countCardsInLocation('deck'),
        ];
    }

    public function dbGetCardPlayMeta(int $card_id)
    {
        $playMeta = $this->getUniqueValueFromDB("SELECT card_play_meta FROM card WHERE card_id = $card_id");
        return json_decode($playMeta, true);
    }
    public function dbSetCardPlayMeta(int $card_id, mixed $meta = null)
    {
        $encodedMeta = json_encode($meta);
        $this->DbQuery("UPDATE `card` SET card_play_meta = '$encodedMeta' WHERE card_id = $card_id");
    }
    public function dbClearCardPlayMeta(array $card_ids = null)
    {
        if ($card_ids == null) {
            $this->DbQuery("UPDATE `card` SET card_play_meta = NULL");
        } else if (count($card_ids) > 0) {
            $paramString = implode(", ", $card_ids);
            $this->DbQuery("UPDATE `card` SET card_play_meta = NULL WHERE card_id IN ( $paramString )");
        }
    }

    public function dbGetCardParentId(int $card_id)
    {
        return $this->getUniqueValueFromDB("SELECT card_parent_id FROM card WHERE card_id = $card_id");
    }
    public function dbSetCardParentId(int $card_id, ?int $parent_id = null)
    {
        if ($parent_id == null)
            $parent_id = 'NULL';
        $this->DbQuery("UPDATE `card` SET card_parent_id = $parent_id WHERE card_id = $card_id");
    }
    public function dbClearCardParentIds(array $card_ids = null)
    {
        if ($card_ids == null) {
            $this->DbQuery("UPDATE `card` SET card_parent_id = NULL");
        } else if (count($card_ids) > 0) {
            $paramString = implode(", ", $card_ids);
            $this->DbQuery("UPDATE `card` SET card_parent_id = NULL WHERE card_id IN ( $paramString )");
        }
    }
    public function dbGetAttachedCardIds(int $parentCardId)
    {
        $attachedCards = [];
        $childCardId = $this->getUniqueValueFromDB("SELECT card_id FROM card WHERE card_parent_id = $parentCardId");
        if ($childCardId != null) {
            $attachedCards = array_merge([$childCardId], $this->dbGetAttachedCardIds($childCardId));
        }
        return $attachedCards;
    }
}
