
let CuttlePlayers = {
    myId: 0,
    players: {},
    playerOrder: [],
    handLimit: 0,
    isSpectator: false,
    dealerId: null,

    setup: function (gamedatas) {
        this.myId = gamedatas.current_player_id;
        this.playerOrder = gamedatas.playerorder;
        this.players = gamedatas.players;
        this.handLimit = gamedatas.globals.HAND_LIMIT;
        this.isSpectator = !Object.keys(gamedatas.players).includes('' + this.myId);
        this.dealerId = gamedatas.dealer_id;
    },
    getPlayer: function (playerId) {
        return this.players[playerId];
    },
    getPlayers: function () {
        if (this.playerOrder.length == Object.values(this.players).length)
            return this.playerOrder.map(id => this.players[id]);
        return Object.values(this.players);
    },
    getPlayerIds: function () {
        return Object.keys(this.players);
    },
    setReturnedCardId: function (playerId, cardId) {
        this.players[playerId].returned_card_id = cardId;
    },
    getReturnedCardId: function (playerId) {
        return this.players[playerId]?.returned_card_id;
    },
    setPlayerShield: function (playerId, shielded) {
        this.players[playerId].shielded = shielded;
    },
    getPlayerShield: function (playerId) {
        return this.players[playerId]?.shielded;
    },
    getHandLimit: function (playerId) {
        return this.handLimit;
    },
    getPlayerHasSpyglass: function (playerId) {
        return CuttleCards.getCardStock('effects', playerId)?.getCards().find(c => c.type == 8) != null;
    }
};

define({
    CuttlePlayers: CuttlePlayers
});