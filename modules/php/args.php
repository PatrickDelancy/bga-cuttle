<?php

namespace Bga\Games\cuttle;

trait ArgsTrait
{
    public function argPlayerChooseAction(): array
    {
        return [
            'passCount' => $this->globals->get(K_PASS_COUNT, 0)
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
