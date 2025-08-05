<?php

namespace Bga\Games\cuttle;

trait NotifArgsTrait
{
    private function notif_revealPlayerHands($activePlayerId)
    {
        $playerIds = $this->getPlayerIds();
        foreach ($playerIds as $playerId) {
            $handCards = $this->getCards($this->cards->getCardsInLocation('hand', $playerId));

            if ($this->handVisibleToTable($playerId)) {
                $this->notifyAllPlayers("revealHand", clienttranslate('${player_name} reveals ${card_names} to everyone at the table'), [
                    "playerId" => $playerId,
                    "player_name" => $this->getPlayerNameById($playerId),
                    "card_names" => implode(', ', array_map(fn($c) => self::_($c['name']), $handCards)),
                    "i18n" => ['card_names'],

                    "playerHands" => [$playerId => $handCards],
                ]);
            } else if ($playerId != $activePlayerId) {
                $this->notifyPlayer($activePlayerId, "revealHand", clienttranslate('${player_name} reveals ${card_names} to you in their hand'), [
                    "playerId" => $playerId,
                    "player_name" => $this->getPlayerNameById($playerId),
                    "card_names" => implode(', ', array_map(fn($c) => self::_($c['name']), $handCards)),
                    "i18n" => ['card_names'],

                    "playerHands" => [$playerId => $handCards],
                ]);
            }
        }
    }

    private function notif_revealNewHandCards($activePlayerId, $cards)
    {
        if ($this->handVisibleToTable($activePlayerId)) {
            $this->notifyAllPlayers("revealHand", clienttranslate('${player_name} reveals ${card_names} added to their hand'), [
                "playerId" => $activePlayerId,
                "player_name" => $this->getPlayerNameById($activePlayerId),
                "card_names" => implode(', ', array_map(fn($c) => self::_($c['name']), $cards)),
                "i18n" => ['card_names'],

                "playerHands" => [$activePlayerId => $cards],
            ]);
        } else {
            $playerIdsWithSpyglass = $this->dbGetPlayerIdsWithSpyglass();
            foreach ($playerIdsWithSpyglass as $spyingPlayerId) {
                if ($spyingPlayerId == $activePlayerId) continue;

                $this->notifyPlayer($spyingPlayerId, "revealHand", clienttranslate('${player_name} reveals ${card_names} added to their hand'), [
                    "playerId" => $activePlayerId,
                    "player_name" => $this->getPlayerNameById($activePlayerId),
                    "card_names" => implode(', ', array_map(fn($c) => self::_($c['name']), $cards)),
                    "i18n" => ['card_names'],

                    "playerHands" => [$activePlayerId => $cards],
                ]);
            }
        }
    }
}
