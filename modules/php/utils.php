<?php

namespace Bga\Games\cuttle;

trait UtilsTrait
{
    private function getRandomKey(array $array)
    {
        $size = count($array);
        if ($size == 0) {
            trigger_error("getRandomKey(): Array is empty", E_USER_WARNING);
            return null;
        }
        $rand = random_int(0, $size - 1);
        $slice = array_slice($array, $rand, 1, true);
        foreach ($slice as $key => $value) {
            return $key;
        }
    }

    private function getRandomValue(array $array)
    {
        $size = count($array);
        if ($size == 0) {
            trigger_error("getRandomValue(): Array is empty", E_USER_WARNING);
            return null;
        }
        $rand = random_int(0, $size - 1);
        $slice = array_slice($array, $rand, 1, true);
        foreach ($slice as $key => $value) {
            return $value;
        }
    }

    private function getRandomSlice(array $array, int $count)
    {
        $size = count($array);
        if ($size == 0) {
            trigger_error("getRandomSlice(): Array is empty", E_USER_WARNING);
            return null;
        }
        if ($count < 1 || $count > $size) {
            trigger_error("getRandomSlice(): Invalid count $count for array with size $size", E_USER_WARNING);
            return null;
        }
        $slice = [];
        $randUnique = [];
        while (count($randUnique) < $count) {
            $rand = random_int(0, $size - 1);
            if (array_key_exists($rand, $randUnique)) {
                continue;
            }
            $randUnique[$rand] = true;
            $slice += array_slice($array, $rand, 1, true);
        }
        return $slice;
    }

    private function getPlayerIds()
    {
        return array_keys(self::loadPlayersBasicInfos());
    }

    private function otherHandsRevealed($activePlayerId)
    {
        $playerIds = $this->getPlayerIds();
        $playerIdsWithSpyglass = $this->dbGetPlayerIdsWithSpyglass();

        if (count($playerIds) == 2 && in_array($activePlayerId, $playerIdsWithSpyglass))
            return true;

        // if all other players have 0 cards in hand
        $cardCounts = $this->cards->countCardsByLocationArgs('hand');
        $otherHandsEmpty = true;
        foreach ($playerIds as $playerId) {
            $otherHandsEmpty = $otherHandsEmpty && ($playerId == $activePlayerId) || !isset($cardCounts[$playerId]) || $cardCounts[$playerId] == 0;
        }
        if ($otherHandsEmpty)
            return true;

        return $this->allHandsVisibleToTable();
    }

    private function allHandsVisibleToTable()
    {
        $playerIds = $this->getPlayerIds();

        if (count($playerIds) == 2 && (int)$this->cards->countCardsInLocation('deck') == 0)
            return true;

        $playerIdsWithSpyglass = $this->dbGetPlayerIdsWithSpyglass();

        return count($playerIds) == count(array_filter($playerIds, fn($id) => in_array($id, $playerIdsWithSpyglass)));
    }
    private function handVisibleToPlayer($handPlayerId, $otherPlayerId)
    {
        // $playerIds = $this->getPlayerIds();
        // $playerIdsWithSpyglass = $this->dbGetPlayerIdsWithSpyglass();
        // if (count($playerIds) == 2 && count($playerIdsWithSpyglass) > 0)
        //     return true;

        return $handPlayerId == $otherPlayerId || $this->dbGetPlayerHasSpyglass($otherPlayerId) || $this->handVisibleToTable($handPlayerId);
    }
    private function handVisibleToTable($handPlayerId)
    {
        $playerIds = $this->getPlayerIds();
        $otherPlayerIds = array_values(array_diff($playerIds, [$handPlayerId]));
        $playerIdsWithSpyglass = $this->dbGetPlayerIdsWithSpyglass();

        if (count($otherPlayerIds) == 1 && in_array($otherPlayerIds[0], $playerIdsWithSpyglass))
            return true;

        return $this->allHandsVisibleToTable();
    }
}
