<?php

namespace Bga\Games\cuttle;

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
