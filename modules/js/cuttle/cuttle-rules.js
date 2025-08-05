
let CuttleRules = {

    rulesSetName: null,
    winningScore: 0,

    discardToDraw_5: true,
    drawCount_5: 3,
    drawAndPlayCount_7: 2,
    returnedCardUnplayable: true,
    targetKingPoints: [21, 14, 10, 7, 4],
    useJokers: false,
    foursRandom: false,
    deckNines: false,
    nineTargetAny: false,

    setup: function (gamedatas) {
        this.rulesSetName = gamedatas['rules_set_name'];
        this.winningScore = gamedatas['winning_score'];

        this.useJokers = gamedatas.globals['USE_JOKERS'] || false;
        this.discardToDraw_5 = gamedatas.globals['5_DISCARD_TO_DRAW'];
        this.drawCount_5 = gamedatas.globals['5_DRAW_COUNT'];
        this.drawAndPlayCount_7 = gamedatas.globals['DRAW_AND_PLAY_COUNT'];
        this.returnedCardUnplayable = gamedatas.globals['RETURNED_CARD_UNPLAYABLE'];
        this.foursRandom = gamedatas.globals['FOURS_RANDOM'];
        this.deckNines = gamedatas.globals['DECK_NINES'];
        this.targetKingPoints = gamedatas.globals['TARGET_KING_POINTS'];
        this.nineTargetAny = gamedatas.globals['9_TARGET_ANY'];
    }
};

define({
    CuttleRules: CuttleRules
});