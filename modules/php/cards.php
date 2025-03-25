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

            $cardData = $this->getAllCardData()[$dbCard['type']];
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

    public function getAllCardData()
    {
        $rule_randomFours = $this->globals->get(RULE_FOURS_RANDOM);
        $rule_5discardToDraw = $this->globals->get(RULE_5_DISCARD_TO_DRAW);
        $rule_7drawAndPlayCount = $this->globals->get(RULE_DRAW_AND_PLAY_COUNT);
        $rule_9targetAny = $this->globals->get(RULE_9_TARGET_ANY);
        $rule_9toDeck = $this->globals->get(RULE_DECK_NINES);
        $rule_useJokers = $this->globals->get(RULE_USE_JOKERS);
        $rule_9returnedUnplayable = $this->globals->get(RULE_RETURNED_CARD_UNPLAYABLE);
        $rule_targetPointsSet = $this->globals->get(RULE_TARGET_POINTS_SET);

        $cardData = [
            1  => [
                'type' => '1',
                'name' => clienttranslate('Ace'),
                'points' => 1,
                'effect_type' => 'One-Off',
                'effect' => clienttranslate('Scrap all active point (${effect_type})'),
                'effect_help' => clienttranslate('Move all active point cards from the table in front of all players to the discard pile.'),
                'unplayable_reasons' => ['notargets'  => T_UNPLAYABLE_NO_ACTIVE_POINTS],
            ],
            2  => [
                'type' => '2',
                'name' => clienttranslate('2'),
                'points' => 2,
                'effect_type' => 'One-Off',
                'effect' => clienttranslate('Scrap an effect (${effect_type})'),
                'effect_help' => clienttranslate('Choose one active permanent effect from in front of your opponent and move it to the discard pile or block a One-Off effect during an opponent\'s turn.'),
                'effect_instruct' => clienttranslate('Select an active effect'),
                'effect_target_confirm_text' => clienttranslate('Scrap this effect'),
                'unplayable_reasons' => ['notargets'  => T_UNPLAYABLE_NO_ACTIVE_EFFECTS],
            ],
            3  => [
                'type' => '3',
                'name' => clienttranslate('3'),
                'points' => 3,
                'effect_type' => 'One-Off',
                'effect' => clienttranslate('Take from discard (${effect_type})'),
                'effect_help' => clienttranslate('Choose a card from the discard pile and add it to your hand.'),
                'effect_instruct' => clienttranslate('Select a scrapped card'),
                'effect_target_confirm_text' => clienttranslate('Add to your hand'),
                'unplayable_reasons' => ['nodiscard'  => T_UNPLAYABLE_NO_DISCARD],
            ],
            4  => [
                'type' => '4',
                'name' => clienttranslate('4'),
                'points' => 4,
                'effect_type' => 'One-Off',
                'effect' => clienttranslate('Force opponent to discard 2 (${effect_type})'),
                'effect_help' => $rule_randomFours
                    ? clienttranslate('Your opponent must discard 2 cards from their hand at random. If they do not have 2 cards in their hand, they must discard all of the cards they have left.')
                    : clienttranslate('Your opponent must discard 2 cards of their choice from their hand. If they do not have 2 cards in their hand, they must discard all of the cards they have left.'),
                'effect_instruct' => clienttranslate('Choose a target player'),
                'unplayable_reasons' => ['notargets'  => T_UNPLAYABLE_NO_TARGET_HAND],
            ],
            5  => [
                'type' => '5',
                'name' => clienttranslate('5'),
                'points' => 5,
                'effect_type' => 'One-Off',
                'effect' => $rule_5discardToDraw
                    ? clienttranslate('Discard 1 then draw 3 (${effect_type})')
                    : clienttranslate('Draw 2 (${effect_type})'),
                'effect_help' => $rule_5discardToDraw
                    ? clienttranslate('Choose one card to discard, then draw three new cards from the draw pile. If there are less than three cards in the draw pile, draw all remaining cards.')
                    : clienttranslate('Draw two cards from the draw pile. If there are less than two cards in the draw pile, draw all remaining cards.'),
                'effect_instruct' => clienttranslate('Select a card from your hand'),
                'effect_target_confirm_text' => clienttranslate('Discard and draw 3'),
                'unplayable_reasons' => ['nodeck'  => T_UNPLAYABLE_NO_DECK],
            ],
            6  => [
                'type' => '6',
                'name' => clienttranslate('6'),
                'points' => 6,
                'effect_type' => 'One-Off',
                'effect' => clienttranslate('Scrap all active effects (${effect_type})'),
                'effect_help' => clienttranslate('Move all active permanent effect cards from the table in front of all players to the discard pile.'),
                'unplayable_reasons' => ['notargets'  => T_UNPLAYABLE_NO_ACTIVE_EFFECTS],
            ],
            7  => [
                'type' => '7',
                'name' => clienttranslate('7'),
                'points' => 7,
                'effect_type' => 'One-Off',
                'effect' => $rule_7drawAndPlayCount == 2
                    ? clienttranslate('Reveal 2 from deck, play 1 (${effect_type})')
                    : clienttranslate('Reveal 1 from deck, play it (${effect_type})'),
                'effect_help' => $rule_7drawAndPlayCount == 2
                    ? clienttranslate('Reveal the top two cards from the draw pile for all players to see. Choose one card to play immediately, return the other card to the top of the draw pile.')
                    : clienttranslate('Reveal the top card from the draw pile for all players to see. Play it as you choose.'),
                'effect_instruct' => clienttranslate('Choose how to play'),
                'effect_target_confirm_text' => clienttranslate('Play this card'),
                'unplayable_reasons' => ['nodeck'  => T_UNPLAYABLE_NO_DECK],
            ],
            8  => [
                'type' => '8',
                'name' => clienttranslate('8'),
                'points' => 8,
                'effect_type' => 'Permanent',
                'effect' => clienttranslate('Reveal opponent\'s hand (${effect_type})'),
                'effect_help' => clienttranslate('While active, your opponent(s) must play with their cards visible to you.')
            ],
            9  => [
                'type' => '9',
                'name' => clienttranslate('9'),
                'points' => 9,
                'effect_type' => 'One-Off',
                // 'effect' =>
                // 'effect_help' =>
                'effect_instruct' => clienttranslate('Select an active card'),
                'effect_instruct-1' => clienttranslate('Select an active effect'),
                'effect_target_confirm_text' => clienttranslate('Return to owner\'s hand'),
                'effect_target_confirm_text-111' => clienttranslate('Move to draw pile'),
                'unplayable_reasons' => ['notarget'  => T_UNPLAYABLE_NO_ACTIVE_EFFECTS],
            ],
            10 => [
                'type' => '10',
                'name' => clienttranslate('10'),
                'points' => 10,
                'effect_type' => null,
                'effect' => null,
                'effect_help' => null
            ],
            11 => [
                'type' => '11',
                'name' => clienttranslate('Jack'),
                'points' => 0,
                'effect_type' => 'Permanent',
                'effect' => clienttranslate('Steal a point card (${effect_type})'),
                'effect_help' => clienttranslate('Play on top of one of your opponents point cards, transferring the ownership of that point card to you for as long as that this card remains in play. Jacks may be played on top of other Jacks. If a point card with a Jack (or Jacks) on top of it is discarded the Jack(s) are also discarded.'),
                'effect_instruct' => clienttranslate('Select a point card'),
                'effect_target_confirm_text' => clienttranslate('Steal card'),
                'unplayable_reasons' => ['notarget'  => T_UNPLAYABLE_NO_ACTIVE_POINTS],
            ],
            12 => [
                'type' => '12',
                'name' => clienttranslate('Queen'),
                'points' => 0,
                'effect_type' => 'Permanent',
                'effect' => clienttranslate('Protect your cards from direct attacks (${effect_type})'),
                'effect_help' => clienttranslate('While this card is active in front of you, none of your other cards may be targeted by adverse effects which target a single card (2/9/Jack). This card does not offer protection against scuttles or other effects.')
            ],
            13 => [
                'type' => '13',
                'name' => clienttranslate('King'),
                'points' => 0,
                'effect_type' => 'Permanent',
                'effect' => clienttranslate('Reduce your target points (${effect_type})'),
                'effect_help' => match ($rule_targetPointsSet) {
                    1 => clienttranslate('While a King is active in front of you, the number of points required for you to win is reduced based on the number of active Kings in front of you. With 1/2/3/4 Kings active in front of you, your target points becomes 14/10/7/4.'),
                    2 => clienttranslate('While a King is active in front of you, the number of points required for you to win is reduced based on the number of active Kings in front of you. With 1/2/3/4 Kings active in front of you, your target points becomes 14/10/5/0.'),
                    default => clienttranslate('While a King is active in front of you, the number of points required for you to win is reduced based on the number of active Kings in front of you. With 1/2/3 Kings active in front of you, your target points becomes 9/5/0.'),
                }
            ]
        ];

        if ($rule_9targetAny && $rule_9toDeck) {
            $cardData[9]['effect'] = clienttranslate('Move 1 card to draw pile (${effect_type})');
            $cardData[9]['effect_help'] = clienttranslate('Choose one active point or effect card in front of your opponent. This card is placed on top of the draw pile.');
        } else if (!$rule_9targetAny && $rule_9toDeck) {
            $cardData[9]['effect'] = clienttranslate('Move 1 effect to draw pile (${effect_type})');
            $cardData[9]['effect_help'] = clienttranslate('Choose one active permanent effect in front of your opponent. This card is placed on top of the draw pile.');
        } else if ($rule_9targetAny && !$rule_9toDeck) {
            $cardData[9]['effect'] = clienttranslate('Return 1 card to owner\'s hand (${effect_type})');
            $cardData[9]['effect_help'] = clienttranslate('Choose one active point or effect card in front of your opponent. This card is returned to their hand.');
        } else { /* !$rule_9targetAny && !$rule_9toDeck */
            $cardData[9]['effect'] = clienttranslate('Return 1 effect to owner\'s hand (${effect_type})');
            $cardData[9]['effect_help'] = clienttranslate('Choose one active permanent effect in front of your opponent. This card is returned to their hand.');
        }

        if ($rule_9returnedUnplayable && !$rule_9toDeck) {
            $cardData[9]['effect_help'] .= ' ' . clienttranslate('It cannot be played on the player\'s next turn.');
        }

        if ($rule_useJokers) {
            $cardData[15] = [
                'type' => '15',
                'name' => clienttranslate('Joker'),
                'points' => 0,
                'effect_type' => 'Permanent',
                'effect' => clienttranslate('Steal a permenent effect card (${effect_type})'),
                'effect_help' => clienttranslate('Play on top of one of your opponents permanent effect cards, transferring the ownership of that effect card to you for as long as that this Joker remains in play. Jokers may be played on top of other Jokers. If an effect card with a Joker (or Jokers) on top of it is discarded the Joker(s) are also discarded.'),
                'effect_instruct' => clienttranslate('Select a permanent effect'),
                'effect_target_confirm_text' => clienttranslate('Steal card'),
                'unplayable_reasons' => ['notarget'  => T_UNPLAYABLE_NO_ACTIVE_EFFECTS],
            ];
        }

        return $cardData;
    }
}
