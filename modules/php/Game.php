<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * cuttle implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\cuttle;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");
require_once('constants.inc.php');
require_once('utils.php');
require_once('objects/card.php');
require_once('states.php');
require_once('db.php');
require_once('actions.php');
require_once('cards.php');
require_once('args.php');
require_once('notifArgs.php');

class Game extends \Table
{
    use UtilsTrait;
    use StateTrait;
    use DbTrait;
    use NotifArgsTrait;
    use ActionTrait;
    use ArgsTrait;
    use CardsTrait;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();
        // TODO: reset returned card when player draws from discard
        // TODO: put an x to close the card detail info box
        // TODO: when playing 9 against a Jack, number card is shown on the blockable action summary
        $this->initGameStateLabels([
            K_RULE_SET => OPT_RULE_SET,
            K_WINNING_SCORE => OPT_GAME_LENGTH,
            K_FOURS_RANDOM => OPT_FOURS_RANDOM,
            K_DECK_NINES => OPT_DECK_NINES,
        ]);

        // TODO: in 4-player mode, when playing J or Joker, choose which player gets the point card stack

        $this->cards = $this->getNew("module.common.deck");
        $this->cards->init("card");
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        $currentRound = $this->globals->get(K_ROUND_NUMBER);
        $winningScore = $this->getGameStateValue(K_WINNING_SCORE);

        $turns = intval($this->getStat('turns_count'));
        if ($turns == 0) {
            $roundProgress = 0;
        } else {
            $roundProgress = max(0.1, min(0.95, $turns / 10));
        }

        if ($winningScore > 0) {
            $maxRounds = ($winningScore * 2) - 1;
            $roundPortion = $roundProgress / $maxRounds;
            $percentComplete = (($currentRound - 1) / $maxRounds) + $roundPortion;
        } else {
            $percentComplete = $roundProgress;
        }

        return intval(max(0, min(98,  $percentComplete * 100)));
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
        //       if ($from_version <= 1404301345)
        //       {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
        //
        //       if ($from_version <= 1405061421)
        //       {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas(): array
    {
        $result = [];

        $result['rules_set'] = $this->getGameStateValue(K_RULE_SET);
        $result['rules_set_name'] = RULESET_NAMES[$result['rules_set']];

        $result['globals'] = $this->globals->getAll();
        $result['card_data'] = CUTTLE_CARD_DATA;
        $result['card_names'] = CUTTLE_CARD_NAMES;

        $result['winning_score'] = $this->getGameStateValue(K_WINNING_SCORE);
        $result['current_round'] = $this->globals->get(K_ROUND_NUMBER);

        $current_player_id = (int) $this->getCurrentPlayerId();
        $result['current_player_id'] = $current_player_id;
        $result['dealer_id'] = $this->globals->get(K_DEALER_ID);

        $result['deckCount'] = (int)$this->cards->countCardsInLocation('deck');
        // discard stack and staging line are always visible to all players
        $result['discard'] = $this->getCards($this->cards->getCardsInLocation('discard'));
        $result['staging'] = $this->getCards($this->cards->getCardsInLocation('staging'));

        $result["players"] = $this->getCollectionFromDb("SELECT `player_id` `id`, `player_score` `score`, `player_name` `name`, `player_points` `points`, `player_target_points` `target_points`, `player_shielded` `shielded`, `player_returned_card_id` `returned_card_id` FROM `player`");

        $playerIdsWithSpyglass = $this->dbGetPlayerIdsWithSpyglass();

        foreach ($result["players"] as &$player) {
            $player['score'] = intval($player['score']);
            $player['shielded'] = boolval($player['shielded']);
            $player['has_spyglass'] = in_array($player['id'], $playerIdsWithSpyglass);
            $player['points'] = intval($player['points']);
            $player['target_points'] = intval($player['target_points']);

            $cardsInHand = $this->getCards($this->cards->getCardsInLocation('hand', $player['id']));
            if ($this->handVisibleToPlayer($player['id'], $current_player_id)) {
                $player['hand'] = $cardsInHand;
            } else {
                $player['hand'] = Card::onlyIds($cardsInHand);
            }
            $player['point_cards'] = $this->getCards($this->cards->getCardsInLocation('points', $player['id']));
            $player['effect_cards'] = $this->getCards($this->cards->getCardsInLocation('effects', $player['id']));
        }

        $blockableActionQueue = $this->globals->get('blockable_action_queue', []);
        if (count($blockableActionQueue) > 0) {
            $result['blockableActions'] = [];
            foreach ($blockableActionQueue as $queueEntry) {
                $targetCard = !array_key_exists('targetCardId', $queueEntry) || $queueEntry['targetCardId'] == null ? null : $this->getCard($this->cards->getCard($queueEntry['targetCardId']));
                $showTargetCard = $targetCard != null && $targetCard['location'] != 'hand' && $targetCard['location'] != 'discard';

                $result['blockableActions'][] = [
                    'card' => $this->getCard($this->cards->getCard($queueEntry['cardId'])),
                    'targetCard' => $showTargetCard ? $targetCard : null,
                    'targetPlayerId' => array_key_exists('targetPlayerId', $queueEntry) ? $queueEntry['targetPlayerId'] : null,
                    'playerId' => $queueEntry['activePlayerId']
                ];
            }
        }

        return $result;
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName()
    {
        return "cuttle";
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        /**
         * Setup the initial game options and variants
         */
        $this->globals->set(K_ROUND_NUMBER, 0);
        $this->globals->set(RULE_FOURS_RANDOM, $this->getGameStateValue(K_FOURS_RANDOM) == 1);
        $this->globals->set(RULE_DECK_NINES, $this->getGameStateValue(K_DECK_NINES) == 1);

        $gameRuleSet = $this->getGameStateValue(K_RULE_SET);
        switch ($gameRuleSet) {
            case RULESET_3_PLAYER:
                $this->globals->set(RULE_USE_JOKERS, true);
                $this->globals->set(RULE_TARGET_KING_POINTS, [14, 9, 5, 0, 0]);
                $this->globals->set(RULE_HAND_LIMIT, 7);
                $this->globals->set(RULE_STARTING_HAND_SIZE_DEALER, 5);
                $this->globals->set(RULE_STARTING_HAND_SIZE_PLAYER, 5);
                $this->globals->set(RULE_DRAW_AND_PLAY_COUNT, 2);
                $this->globals->set(RULE_RETURNED_CARD_UNPLAYABLE, true);
                $this->globals->set(RULE_5_DISCARD_TO_DRAW, true);
                $this->globals->set(RULE_5_DRAW_COUNT, 3);
                $this->globals->set(RULE_9_TARGET_ANY, true);
                break;
            case RULESET_TRADITIONAL:
                $this->globals->set(RULE_USE_JOKERS, false);
                $this->globals->set(RULE_TARGET_KING_POINTS, [21, 14, 10, 7, 4]);
                $this->globals->set(RULE_HAND_LIMIT, 0);
                $this->globals->set(RULE_STARTING_HAND_SIZE_DEALER, 6);
                $this->globals->set(RULE_STARTING_HAND_SIZE_PLAYER, 5);
                $this->globals->set(RULE_DRAW_AND_PLAY_COUNT, 1);
                $this->globals->set(RULE_RETURNED_CARD_UNPLAYABLE, false);
                $this->globals->set(RULE_5_DISCARD_TO_DRAW, false);
                $this->globals->set(RULE_5_DRAW_COUNT, 2);
                $this->globals->set(RULE_9_TARGET_ANY, false);
                break;
            case RULESET_BALANCED:
            default:
                $this->globals->set(RULE_USE_JOKERS, false);
                $this->globals->set(RULE_TARGET_KING_POINTS, [21, 14, 10, 5, 0]);
                $this->globals->set(RULE_HAND_LIMIT, 8);
                $this->globals->set(RULE_STARTING_HAND_SIZE_DEALER, 6);
                $this->globals->set(RULE_STARTING_HAND_SIZE_PLAYER, 5);
                $this->globals->set(RULE_DRAW_AND_PLAY_COUNT, 2);
                $this->globals->set(RULE_RETURNED_CARD_UNPLAYABLE, true);
                $this->globals->set(RULE_5_DISCARD_TO_DRAW, true);
                $this->globals->set(RULE_5_DRAW_COUNT, 3);
                $this->globals->set(RULE_9_TARGET_ANY, true);
                break;
        }

        /**
         * Setup game statistics
         * NOTE: statistics used in this file must be defined in your `stats.inc.php` file.
         */
        $this->initStat("table", "turns_count", 0);
        $this->initStat("table", "tied_rounds_count", 0);

        /**
         * Setup deck
         */
        $cards = array();
        // $suits = array( "club", "diamond", "heart", "spade" );
        for ($suit_id = 1; $suit_id <= 4; $suit_id++) {
            for ($value = 1; $value <= 13; $value++) {
                $cards[] = array('type_arg' => $suit_id, 'type' => $value, 'nbr' => 1);
            }
        }
        // only add jokers for alternative rule set
        if ($this->globals->get(RULE_USE_JOKERS)) {
            array_push($cards, array('type_arg' => 2, 'type' => 15, 'nbr' => 2));
        }

        $this->cards->createCards($cards, 'deck');

        $playerIds = $this->getPlayerIds();
        foreach ($playerIds as $playerId) {
            $this->initStat("player", "turns_count", 0, $playerId);
            $this->initStat("player", "turns_passed", 0, $playerId);
            $this->initStat("player", "drawn_from_deck", 0, $playerId);
            $this->initStat("player", "drawn_from_discard", 0, $playerId);
            $this->initStat("player", "played_for_points", 0, $playerId);
            $this->initStat("player", "played_for_scuttle", 0, $playerId);
            $this->initStat("player", "played_for_effect", 0, $playerId);
            $this->initStat("player", "played_for_discard", 0, $playerId);
        }

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
        $this->globals->set(K_DEALER_ID, $this->getNextPlayerTable()[0]);
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        //if ($state["type"] === "activeplayer") {
        switch ($state_name) {
            case "playerForcedDiscard":
                $cards = $this->getCards($this->cards->getCardsInLocation('hand', $active_player));
                if (count($cards) >= 2) {
                    $this->actPlayDiscard($cards[0]['id'], $cards[1]['id']);
                } else if (count($cards) >= 1) {
                    $this->actPlayDiscard($cards[0]['id'], null);
                }
                break;
            case "playersBlockOneOff":
                $this->actAllowOneOff();
                break;
            default:
                $this->actPass(true);
                break;
        }

        return;
        // }

        // Make sure player is in a non-blocking status for role turn.
        // if ($state["type"] === "multipleactiveplayer") {
        //     $this->gamestate->setPlayerNonMultiactive($active_player, 'zombiePass');
        //     return;
        // }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }
}
