<?php

/*
 * State constants
 */
define('ST_BGA_GAME_SETUP', 1);
define('ST_BGA_END_GAME', 99);

define('ST_NEXT_FORCE_DISCARD', 91);
define('ST_NEXT_FINISH_FORCED_DISCARD', 92);
define('ST_END_ROUND', 93);
define('ST_NEW_ROUND', 94);
define('ST_NEXT_PLAYER', 95);

define('ST_PLAYER_CHOOSE_ACTION', 10);
define('ST_PLAYER_FROM_STAGING', 20);
define('ST_PLAYER_CHOOSE_CARDS_FORCED_TO_DISCARD', 30);

define('ST_PLAYERS_BLOCK_ONE_OFF', 11);
define('ST_COMPLETE_BLOCKABLE_EFFECT', 12);

/*
 * Rules constants
 */

define('K_RULE_SET', 'rule_set');
define('OPT_RULE_SET', 100);

define('K_WINNING_SCORE', 'winning_score');
define('OPT_GAME_LENGTH', 101);

define('K_FOURS_RANDOM', 'fours_random');
define('OPT_FOURS_RANDOM', 110);

define('K_DECK_NINES', 'deck_nines');
define('OPT_DECK_NINES', 111);

define('K_DEALER_ID', 'dealer_id');

define('K_ROUND_NUMBER', 'round_number');
define('K_PASS_COUNT', 'pass_count');

define('RULESET_BALANCED', 0);
define('RULESET_TRADITIONAL', 1);
define('RULESET_3_PLAYER', 3);
define('RULESET_NAMES', [
    0 => clienttranslate('Balanced'),
    1 => clienttranslate('Traditional'),
    3 => clienttranslate('3-Player'),
]);

define('RULE_FOURS_RANDOM', 'FOURS_RANDOM');
define('RULE_DECK_NINES', 'DECK_NINES');
define('RULE_TARGET_KING_POINTS', 'TARGET_KING_POINTS');
define('RULE_HAND_LIMIT', 'HAND_LIMIT');
define('RULE_STARTING_HAND_SIZE_DEALER', 'STARTING_HAND_SIZE_DEALER');
define('RULE_STARTING_HAND_SIZE_PLAYER', 'STARTING_HAND_SIZE_PLAYER');
define('RULE_DRAW_AND_PLAY_COUNT', 'DRAW_AND_PLAY_COUNT');
define('RULE_RETURNED_CARD_UNPLAYABLE', 'RETURNED_CARD_UNPLAYABLE');
define('RULE_5_DISCARD_TO_DRAW', '5_DISCARD_TO_DRAW');
define('RULE_5_DRAW_COUNT', '5_DRAW_COUNT');
define('RULE_USE_JOKERS', 'USE_JOKERS');
define('RULE_9_TARGET_ANY', '9_TARGET_ANY');

define('T_UNPLAYABLE_RETURNED_CARD', clienttranslate('Cannot play card on the same turn it was returned to your hand'));
define('T_UNPLAYABLE_NO_DECK', clienttranslate("There are no cards left in the draw pile"));
define('T_UNPLAYABLE_NO_DISCARD', clienttranslate("There are no discarded cards to take"));
define('T_UNPLAYABLE_NO_ACTIVE_POINTS', clienttranslate("There are no active point cards to affect"));
define('T_UNPLAYABLE_NO_ACTIVE_EFFECTS', clienttranslate("There are no active permanent effect cards to affect"));
define('T_UNPLAYABLE_NO_TARGET_HAND', clienttranslate("The other player does not have any cards in their hand"));

/*
 * Card data
 */
define("CUTTLE_CARD_DATA", [
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
        'effect_help' => clienttranslate('Your opponent must discard 2 cards of their choice from their hand. If they do not have 2 cards in their hand, they must discard all of the cards they have left.'),
        'effect_help-110' => clienttranslate('Your opponent must discard 2 cards from their hand at random. If they do not have 2 cards in their hand, they must discard all of the cards they have left.'),
        'effect_instruct' => clienttranslate('Choose a target player'),
        'unplayable_reasons' => ['notargets'  => T_UNPLAYABLE_NO_TARGET_HAND],
    ],
    5  => [
        'type' => '5',
        'name' => clienttranslate('5'),
        'points' => 5,
        'effect_type' => 'One-Off',
        'effect' => clienttranslate('Discard 1 then draw 3 (${effect_type})'),
        'effect-1' => clienttranslate('Draw 2 (${effect_type})'),
        'effect_help' => clienttranslate('Choose one card to discard, then draw three new cards from the draw pile. If there are less than three cards in the draw pile, draw all remaining cards.'),
        'effect_help-1' => clienttranslate('Draw two cards from the draw pile. If there are less than two cards in the draw pile, draw all remaining cards.'),
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
        'effect' => clienttranslate('Reveal 2 from deck, play 1 (${effect_type})'),
        'effect_help' => clienttranslate('Reveal the top two cards from the draw pile for all players to see. Choose one card to play immediately, return the other card to the top of the draw pile.'),
        'effect-1' => clienttranslate('Reveal 1 from deck, play it (${effect_type})'),
        'effect_help-1' => clienttranslate('Reveal the top cards from the draw pile for all players to see. Play it as you choose.'),
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
        'effect' => clienttranslate('Return 1 card to owner\'s hand (${effect_type})'),
        'effect-1' => clienttranslate('Return 1 effect to owner\'s hand (${effect_type})'),
        'effect-111' => clienttranslate('Move 1 card to draw pile (${effect_type})'),
        'effect-1-111' => clienttranslate('Return 1 effect to owner\'s hand (${effect_type})'),
        'effect_help' => clienttranslate('Choose one active point or effect card in front of your opponent. This card is returned to their hand. They cannot play this card on their next turn.'),
        'effect_help-111' => clienttranslate('Choose one active point or effect card in front of your opponent. This card is placed on top of the draw pile.'),
        'effect_help-1' => clienttranslate('Choose one active permanent effect in front of your opponent. This card is returned to their hand.'),
        'effect_help-1-111' => clienttranslate('Choose one active permanent effect in front of your opponent. This card is placed on top of the draw pile.'),
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
        'effect_help' => clienttranslate('While a King is active in front of you, the number of points required for you to win is reduced based on the number of active Kings in front of you. With 1/2/3/4 Kings active in front of you, your target points becomes 14/10/5/0.'),
        'effect_help-1' => clienttranslate('While a King is active in front of you, the number of points required for you to win is reduced based on the number of active Kings in front of you. With 1/2/3/4 Kings active in front of you, your target points becomes 14/10/7/4.'),
        'effect_help-3' => clienttranslate('While a King is active in front of you, the number of points required for you to win is reduced based on the number of active Kings in front of you. With 1/2/3 Kings active in front of you, your target points becomes 9/5/0.'),
    ],
    15 => [
        'type' => '15',
        'name' => clienttranslate('Joker'),
        'points' => 0,
        'effect_type' => 'Permanent',
        'effect' => clienttranslate('Steal a permenent effect card (${effect_type})'),
        'effect_help' => clienttranslate('Play on top of one of your opponents permanent effect cards, transferring the ownership of that effect card to you for as long as that this Joker remains in play. Jokers may be played on top of other Jokers. If an effect card with a Joker (or Jokers) on top of it is discarded the Joker(s) are also discarded.'),
        'effect_instruct' => clienttranslate('Select a permanent effect'),
        'effect_target_confirm_text' => clienttranslate('Steal card'),
        'unplayable_reasons' => ['notarget'  => T_UNPLAYABLE_NO_ACTIVE_EFFECTS],
    ],
]);
define("CUTTLE_CARD_NAMES", [
    '1_1' => clienttranslate("A♣"),
    '1_2' => clienttranslate("A♢"),
    '1_3' => clienttranslate("A♡"),
    '1_4' => clienttranslate("A♠"),
    '2_1' => clienttranslate("2♣"),
    '2_2' => clienttranslate("2♢"),
    '2_3' => clienttranslate("2♡"),
    '2_4' => clienttranslate("2♠"),
    '3_1' => clienttranslate("3♣"),
    '3_2' => clienttranslate("3♢"),
    '3_3' => clienttranslate("3♡"),
    '3_4' => clienttranslate("3♠"),
    '4_1' => clienttranslate("4♣"),
    '4_2' => clienttranslate("4♢"),
    '4_3' => clienttranslate("4♡"),
    '4_4' => clienttranslate("4♠"),
    '5_1' => clienttranslate("5♣"),
    '5_2' => clienttranslate("5♢"),
    '5_3' => clienttranslate("5♡"),
    '5_4' => clienttranslate("5♠"),
    '6_1' => clienttranslate("6♣"),
    '6_2' => clienttranslate("6♢"),
    '6_3' => clienttranslate("6♡"),
    '6_4' => clienttranslate("6♠"),
    '7_1' => clienttranslate("7♣"),
    '7_2' => clienttranslate("7♢"),
    '7_3' => clienttranslate("7♡"),
    '7_4' => clienttranslate("7♠"),
    '8_1' => clienttranslate("8♣"),
    '8_2' => clienttranslate("8♢"),
    '8_3' => clienttranslate("8♡"),
    '8_4' => clienttranslate("8♠"),
    '9_1' => clienttranslate("9♣"),
    '9_2' => clienttranslate("9♢"),
    '9_3' => clienttranslate("9♡"),
    '9_4' => clienttranslate("9♠"),
    '10_1' => clienttranslate("10♣"),
    '10_2' => clienttranslate("10♢"),
    '10_3' => clienttranslate("10♡"),
    '10_4' => clienttranslate("10♠"),
    '11_1' => clienttranslate("J♣"),
    '11_2' => clienttranslate("J♢"),
    '11_3' => clienttranslate("J♡"),
    '11_4' => clienttranslate("J♠"),
    '12_1' => clienttranslate("Q♣"),
    '12_2' => clienttranslate("Q♢"),
    '12_3' => clienttranslate("Q♡"),
    '12_4' => clienttranslate("Q♠"),
    '13_1' => clienttranslate("K♣"),
    '13_2' => clienttranslate("K♢"),
    '13_3' => clienttranslate("K♡"),
    '13_4' => clienttranslate("K♠"),
    '15_2' => clienttranslate("Joker"),
]);
