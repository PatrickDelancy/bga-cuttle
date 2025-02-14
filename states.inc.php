<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * cuttle implementation : © PatrickDNerd
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * cuttle game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: $this->checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

$playerActionStates = [

    ST_PLAYER_CHOOSE_ACTION => [
        "name" => "playerChooseAction",
        "description" => clienttranslate('${actplayer} must choose an action'),
        "descriptionmyturn" => clienttranslate('${you} must choose an action'),
        "type" => "activeplayer",
        "args" => "argPlayerChooseAction",
        "updateGameProgression" => true,
        "possibleactions" => [
            "actPlayCardPoints",
            "actPlayCardEffect",
            "actPlayCardScuttle",
            "actDrawCard",
            "actPass"
        ],
        "transitions" => [
            "endTurn" => ST_NEXT_PLAYER,
            "pass" => ST_NEXT_PLAYER,
            "playFromStaging" => ST_PLAYER_FROM_STAGING,
            "blockableEffect" => ST_PLAYERS_BLOCK_ONE_OFF,
            "forceDiscard" => ST_NEXT_FORCE_DISCARD,
            "zombiePass" => ST_NEXT_PLAYER,
        ]
    ],

    ST_PLAYER_FROM_STAGING => [
        "name" => "playerFromStaging",
        "description" => clienttranslate('${actplayer} must play one of the revealed cards'),
        "descriptionmyturn" => clienttranslate('${you} must play one of the revealed cards'),
        "type" => "activeplayer",
        "args" => "argPlayerChooseAction",
        "possibleactions" => [
            "actPlayCardPoints",
            "actPlayCardEffect",
            "actPlayCardScuttle",
            "actPlayDiscard",
        ],
        "transitions" => [
            "endTurn" => ST_NEXT_PLAYER,
            "playFromStaging" => ST_PLAYER_FROM_STAGING,
            "blockableEffect" => ST_PLAYERS_BLOCK_ONE_OFF,
            "forceDiscard" => ST_NEXT_FORCE_DISCARD,
            "zombiePass" => ST_NEXT_PLAYER,
        ]
    ],

    ST_PLAYER_CHOOSE_CARDS_FORCED_TO_DISCARD => [
        "name" => "playerForcedDiscard",
        "description" => clienttranslate('${actplayer} must choose 2 cards to discard'),
        "descriptionmyturn" => clienttranslate('${you} must choose 2 cards to discard'),
        "type" => "activeplayer",
        "args" => "argPlayerChooseAction",
        "possibleactions" => [
            "actPlayDiscard",
        ],
        "transitions" => [
            "endTurn" => ST_NEXT_FINISH_FORCED_DISCARD,
        ]
    ],

    ST_PLAYERS_BLOCK_ONE_OFF =>  [
        'name' => 'playersBlockOneOff',
        'type' => 'multipleactiveplayer',
        'description' => clienttranslate('Players may block one-off effect'),
        'descriptionmyturn' => clienttranslate('${actingPlayerName} is playing a one-off effect, ${you} may block or allow it'),
        'possibleactions' => [
            'actBlockOneOff',
            'actAllowOneOff'
        ],
        'transitions' => [
            "complete" => ST_COMPLETE_BLOCKABLE_EFFECT,
        ],
        'action' => 'stPlayersBlockOneOff',
        'args' => 'argPlayersBlockOneOff',
    ],

];

$basicGameStates = [

    // The initial state. Please do not modify.
    ST_BGA_GAME_SETUP => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => ST_NEW_ROUND]
    ),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    ST_BGA_END_GAME => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],

];

$transitionGameStates = [

    ST_COMPLETE_BLOCKABLE_EFFECT => [
        "name" => "completeBlockableEffect",
        "description" => '',
        "type" => "game",
        "action" => "stCompleteBlockableEffect",
        "transitions" => [
            "playFromStaging" => ST_PLAYER_FROM_STAGING,
            "forceDiscard" => ST_NEXT_FORCE_DISCARD,
            "endTurn" => ST_NEXT_PLAYER
        ]
    ],

    ST_NEXT_PLAYER => [
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => [
            "endRound" => ST_END_ROUND,
            "nextPlayer" => ST_PLAYER_CHOOSE_ACTION
        ]
    ],

    ST_END_ROUND => [
        "name" => "endRound",
        "description" => '',
        "type" => "game",
        "action" => "stEndRound",
        "updateGameProgression" => true,
        "transitions" => [
            "newRound" => ST_NEW_ROUND,
            "endGame" => ST_BGA_END_GAME,
        ],
    ],

    ST_NEW_ROUND => [
        "name" => "newRound",
        "description" => '',
        "type" => "game",
        "action" => "stNewRound",
        "updateGameProgression" => true,
        "transitions" => [
            "" => ST_PLAYER_CHOOSE_ACTION,
        ],
    ],

    ST_NEXT_FORCE_DISCARD => [
        "name" => "nextForceDiscard",
        "description" => '',
        "type" => "game",
        "action" => "stNextForceDiscard",
        "transitions" => ["" => ST_PLAYER_CHOOSE_CARDS_FORCED_TO_DISCARD]
    ],

    ST_NEXT_FINISH_FORCED_DISCARD => [
        "name" => "nextFinishForcedDiscard",
        "description" => '',
        "type" => "game",
        "action" => "stNextFinishForcedDiscard",
        "transitions" => ["" => ST_PLAYER_CHOOSE_ACTION]
    ],
];

$machinestates = $basicGameStates + $transitionGameStates + $playerActionStates;
