## Test Scenarios

### Aces
* Cannot play when there are no point cards on the table
* Discards stacked Jacks with point cards
* All players points are accurate

### Twos
* Cannot play when there are no active permanent effects on the table in front of other players
* Cannot play with points on own table, but not on other players tables
* Discard K increases target points
* Cannot discard K when protected by a Q
* Discard J moves point card to original owner
* Discard stacked J moves remaining stack to previous owner

### Threes
* Cannot play if discard pile is empty
* Cannot draw card from discard if at or over hand limit
* Cannot add the played 3 to hand (it is not in the discard pile until action is completed)

### Fours
* When only 2 players, does not ask to choose a target
* When 3 or more players, asked to choose target
* When target has 2 or more cards, they must discard 2
* When target has 1 card, they discard the 1 card
* When target has 0 cards, nothing happens (cannot target player without cards?)
* When RANDOM FOURS option is enabled, cards are chosen at random instead of waiting for player to select cards

### Fives
* Can play when hand is empty
* Balanced: Can play if at or over hand limit
* Balanced: Must choose card to discard before new cards are drawn
* Balanced: 3 cards are added to hand from deck, up to hand limit, no more
* Traditional: 2 cards are added to hand from the deck
* If deck has fewer than required number of cards to draw, then all remaining deck cards are added to hand

### Sixes
* Cannot play if there are no active permanent effects on the table
* If point card has a J attached, returns to original owner
* If point card has multiple J's attached, returns to original owner
* When Js are discarded, players points are accurate
* When K is discarded, player's target points return to normal
* When Q is discarded, player's cards are not blocked from 2/9/J attacks
* When 8 is discarded, other players' hand cards are hidden from player who had the 8

### Sevens
* Balanced: 7 after 7: Play 7 then play a revealed 7 from the staging line
* Balanced: When both revealed cards are unplayable, allow discard one, other returns to top of deck
* Balanced: When card is played, other is returned to top of deck
* Traditional: Only reveal 1 card to play, instead of 2
* Traditional: If revealed card is unplayable, allow discard

### Eights
* When playing 8 effect, card appears sideways on the table
* When played, opponents hands are immediately revealed to you
* When played, IF this causes ALL players to have active 8's, then spectators are immediately able to view all players hands
* When Deck is empty, IF there are only 2 players, then all players hands are immediately revealed to all players (including spectators)
* When deck is empty and there are 3 or more players, then player hands stay hidden to spectators, only visible to players with active 8s
* When using 9 to return card to players hand, players with active 8 see the card face-up, players WITHOUT active 8 see the card hidden
* When player draws card, players with active 8 see the card face-up, players WITHOUT active 8 see the card hidden

### Nines
* Cannot be played if no active permanent effects in from of other players, even if active effect in front of self
* Can be played if ANY other player has active permanent effect
* Balanced: returned card CANNOT be played (for points, scuttle, or effect) on that player's next turn. CAN be played on the turn after that
* Traditional: returned card CAN be played again on the player's next turn
* 3-Player: returned card CAN be discarded if next player plays a 4 before this player's turn

### Jacks
* Can be played when an opponent has at least one point card in play (even if protected by Q -- clearly shows user why target cards cannot be stolen)
* Cannot be played when no opponent has any point cards, even if current player does
* Can steal point card that has J already -- it stacks

### Blocking Cards
* When all players hands are revealed to the table, if NO player has a 2, skip prompt to block one-off action
* When One player had hidden hand, prompt to allow block
* When all players have hidden hands, prompt to allow block
* When playing one-off from 7 staging, complete the 7 action, prompt to block the new action

### Scuttling
* Can use point card to scuttle lower number point card
* Can use point card to scuttle lower suit of the same point value
* Scuttling point card with Js attached also discards the Js
* Can scuttle when player has active Q shield
