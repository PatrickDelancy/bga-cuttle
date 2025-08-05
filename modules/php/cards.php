<?php

namespace Bga\Games\cuttle;

trait CardsTrait
{
    private $cards;

    public function getCard($dbCard)
    {
        if ($dbCard == null)
            return null;

        //$gameRuleSet = $this->getGameStateValue(K_RULE_SET);

        $dbCard['id'] = intval($dbCard['id']);
        $dbCard['location'] = $dbCard['location'];
        $dbCard['location_arg'] = intval($dbCard['location_arg']);
        $dbCard['type_arg'] = intval($dbCard['type_arg']);
        $dbCard['type'] = intval($dbCard['type']);

        if ($dbCard['type'] > 0) {
            $dbCard['name'] = CUTTLE_CARD_NAMES[$dbCard['type'] . '_' . $dbCard['type_arg']];

            if ($dbCard['location'] == 'hand')
                $dbCard['returned'] = $dbCard['id'] == $this->dbGetPlayerReturnedCardId($dbCard['location_arg']);

            $cardData = CUTTLE_CARD_DATA[$dbCard['type']];
            if ($cardData) {
                $dbCard['points'] = $cardData['points'];
                if ($dbCard['points'] > 0)
                    $dbCard['strength'] = $dbCard['points'] + ($dbCard['type_arg'] / 10);

                $dbCard['effect'] = $cardData['effect'];
                $dbCard['effect_type'] = $cardData['effect_type'];
            }

            // point cards and permanent effects (except Queens) can be shielded
            $dbCard['shieldable'] = $dbCard['type'] != 12;
            // the only effects that can be blocked by Queen are 2,9,Jack,Joker
            $dbCard['blockable_by_shield'] = in_array($dbCard['type'], [2, 9, 11, 15]);

            $parentCardId = $this->dbGetCardParentId($dbCard['id']);
            if ($parentCardId != null)
                $dbCard['parent_card_id'] = intval($parentCardId);
        }
        return $dbCard;
    }
    public function getCards(array $dbCards)
    {
        if (array_is_list($dbCards))
            return array_map(fn($dbCard) => $this->getCard($dbCard), $dbCards);
        else
            return array_map(fn($dbCard) => $this->getCard($dbCard), array_values($dbCards));
    }
}
