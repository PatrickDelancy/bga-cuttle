<?php

namespace Bga\Games\cuttle;

trait ArgsTrait
{
    public function argPlayerChooseAction(): array
    {
        // $playerId = intval($this->getActivePlayerId());

        // $deckCount = $this->cards->countCardInLocation('deck');
        // $discardCount = $this->cards->countCardInLocation('discard');

        // $cardsInHand = $this->getCards($this->cards->getCardsInLocation('hand', $playerId));
        // $cardsPlayability = [];
        // foreach ($cardsInHand as $cardId => $card) {
        //     $cardsPlayability[$cardId] = new CardPlayability($card, $this);
        // }

        // Get some values from the current game situation from the database.
        return [
            // "deckCount" => $deckCount,
            // "discardCount" => $discardCount,

            // "_private" => [
            //     $playerId => [
            //         "cardsPlayability" => $cardsPlayability
            //     ]
            // ]
        ];
    }

    public function argPlayersBlockOneOff()
    {
        $actions = $this->globals->get('blockable_action_queue', []);
        $actingPlayerName = '';
        foreach ($actions as &$action) {
            if (isset($action['cardId']))
                $action['card'] = $this->getCard($this->cards->getCard($action['cardId']));

            $actingPlayerName = $this->getPlayerNameById($action['activePlayerId']);
        }
        return [
            'blockableActions' => $actions,
            'actingPlayerName' => $actingPlayerName,
        ];
    }
}
