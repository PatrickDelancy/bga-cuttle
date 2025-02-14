<?php

namespace Bga\Games\cuttle;

// class CardPlayability
// {
//     public int $id;
//     public bool $playableForPoints = false;
//     public bool $playableForEffect = false;
//     public ?string $unplayableReason = null;

//     public function __construct($card)
//     {
//         $this->id = $card->id;
//         $this->playableForPoints = $card->points > 0;

//         //$gamedata = $game->getAllGameData();

//         $isPointCardInOpponentField = false;
//         $isPointCardInOwnField = false;
//         $isEffectCardInOpponentField = false;
//         $isEffectCardInOwnField = false;
//         $isCardsInOpponentHand = true;
//         //$isCardsInOwnHand = false;
//         $discardIsEmpty = true; //count($gamedata['discard']) === 0;
//         $drawPileIsEmpty = false; //$gamedata['deckCount'] === 0;

//         // foreach ($gamedata['players'] as $playerId => $player) {
//         //     if (count($player['hand']) > 0) {
//         //         $isCardsInOpponentHand = $isCardsInOpponentHand || $playerId !== $gamedata['current_player_id'];;
//         //         $isCardsInOwnHand = $isCardsInOwnHand || $playerId === $gamedata['current_player_id'];
//         //     }
//         //     foreach ($player['field'] as $cardInField) {
//         //         if ($cardInField->points > 0) {
//         //             $isPointCardInOpponentField = $isPointCardInOpponentField || $playerId !== $gamedata['current_player_id'];
//         //             $isPointCardInOwnField = $isPointCardInOwnField || $playerId === $gamedata['current_player_id'];
//         //         }
//         //         if ($cardInField->effect_type === 'Permanent') {
//         //             $isEffectCardInOpponentField = $isEffectCardInOpponentField || $playerId !== $gamedata['current_player_id'];
//         //             $isEffectCardInOwnField = $isEffectCardInOwnField || $playerId === $gamedata['current_player_id'];
//         //         }
//         //     }
//         // }

//         switch ($card->type) {
//             case 1: // Ace - if there are any point cards on the table
//                 if ($isPointCardInOpponentField || $isPointCardInOwnField) {
//                     $this->playableForEffect = true;
//                 } else {
//                     $this->unplayableReason = clienttranslate('No point cards on the table');
//                 }
//                 break;
//             case 2: // if there are any permanent effects on the table
//                 if ($isEffectCardInOpponentField || $isEffectCardInOwnField) {
//                     $this->playableForPoints = true;
//                 } else {
//                     $this->unplayableReason = clienttranslate('No permanent effects on the table');
//                 }
//                 break;
//             case 3: // if discard pile has any cards
//                 if ($discardIsEmpty) {
//                     $this->unplayableReason = clienttranslate('Discard pile is empty');
//                 } else {
//                     $this->playableForEffect = true;
//                 }
//                 break;
//             case 4: // if opponent has any cards
//                 if ($isCardsInOpponentHand) {
//                     $this->playableForPoints = true;
//                 } else {
//                     $this->unplayableReason = clienttranslate('Opponent has no cards in hand');
//                 }
//                 break;
//             case 5: // if draw pile has any cards
//                 if ($drawPileIsEmpty) {
//                     $this->unplayableReason = clienttranslate('Draw pile is empty');
//                 } else {
//                     $this->playableForPoints = true;
//                 }
//                 break;
//             case 6: // if there are any permanent effects on the table
//                 if ($isEffectCardInOpponentField || $isEffectCardInOwnField) {
//                     $this->playableForEffect = true;
//                 } else {
//                     $this->unplayableReason = clienttranslate('No permanent effects on the table');
//                 }
//                 break;
//             case 7: // if the the draw pile has any cards
//                 if ($drawPileIsEmpty) {
//                     $this->unplayableReason = clienttranslate('Draw pile is empty');
//                 } else {
//                     $this->playableForEffect = true;
//                 }
//                 break;
//             case 8: // always an option
//                 $this->playableForEffect = true;
//                 break;
//             case 9: // if there are any permanent effects on the table
//                 if ($isEffectCardInOpponentField || $isEffectCardInOwnField) {
//                     $this->playableForPoints = true;
//                 } else {
//                     $this->unplayableReason = clienttranslate('No permanent effects on the table');
//                 }
//                 break;
//             case 11: // Jack - if opponent has any point cards on the table
//                 if ($isPointCardInOpponentField) {
//                     $this->playableForEffect = true;
//                 } else {
//                     $this->unplayableReason = clienttranslate('Opponent has no active point cards');
//                 }
//                 break;
//             case 12: // Queen - always an option
//             case 13: // King - always an option
//                 $this->playableForEffect = true;
//                 break;
//             case 15: // Joker - if opponent has any permanent effects on the table
//                 if ($isEffectCardInOpponentField) {
//                     $this->playableForEffect = true;
//                 } else {
//                     $this->unplayableReason = clienttranslate('Opponent has no permanent effects on the table');
//                 }
//                 break;
//         }
//     }
// }

// class CuttleCard extends Card
// {
//     public ?string $name = null;
//     public int $points = 0;
//     public float $strength = 0.0;
//     public ?string $effect = null;
//     public ?string $effect_type = null;

//     public function __construct($dbCard)
//     {
//         parent::__construct($dbCard);
//         if ($this->type > 0) {
//             $this->name = CUTTLE_CARD_NAMES[$this->type . '_' . $this->type_arg];

//             $cardData = CUTTLE_CARD_DATA[$this->type];
//             if ($cardData) {
//                 $this->points = $cardData['points'];
//                 if ($this->points > 0)
//                     $this->strength = $this->points + ($this->type_arg / 10);

//                 $this->effect = $cardData['effect'];
//                 $this->effect_type = $cardData['effect_type'];
//             }
//         }
//     }
// }

class Card
{
    public int $id;
    public string $location;
    public int $location_arg;
    public int $type_arg;
    public int $type;

    public function __construct($dbCard)
    {
        $this->id = intval($dbCard['id']);
        $this->location = $dbCard['location'];
        $this->location_arg = intval($dbCard['location_arg']);
        $this->type_arg = intval($dbCard['type_arg']);
        $this->type = intval($dbCard['type']);
    }

    // public static function toDbCard($card) {
    //     return new Card([
    //         'id' => $card->id,
    //         'location' => $card->location,
    //         'location_arg' => $card->location_arg,
    //         'type_arg' => $card->type_arg,
    //         'type' => $card->type,
    //     ]);
    // }

    // public static function toDbCards(array $cards)
    // {
    //     return array_map(fn($card) => self::toDbCard($card), $cards);
    // }

    public static function onlyId($card)
    {
        if ($card == null) {
            return null;
        }
        return [
            'id' => $card['id'],
            'location' => $card['location'],
            'location_arg' => $card['location_arg'],
            'type_arg' => null,
            'type' => null,
        ];
    }

    public static function onlyIds(array $cards)
    {
        return array_values(array_map(fn($card) => self::onlyId($card), $cards));
    }
}
