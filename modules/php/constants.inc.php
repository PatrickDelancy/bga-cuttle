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
define('RULE_TARGET_POINTS_SET', 'TARGET_POINTS_SET');
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

define('PREF_AUTOPASS', 102);

/*
 * Card data
 */
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
