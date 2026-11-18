# Test plan

Run each test on the live CODD URL in a private window. Fill in **Actual result** and tick Pass.

| # | Test | Expected | Actual result | Pass |
|---|---|---|---|---|
| 1 | Register with username `ab` | Field error, nothing saved | | |
| 2 | Register an existing username | "already taken", other fields kept | | |
| 3 | Log in with a wrong password | One general error, username kept | | |
| 4 | Open `game.php` while logged out | Redirect to login | | |
| 5 | Wait 30 min idle (or lower `IDLE_LIMIT`) | "session timed out" on login page | | |
| 6 | Log out, press browser Back | Protected page redirects to login | | |
| 7 | Start a game with only 1 player | Start button disabled / error | | |
| 8 | Add a player with a wrong password | Error, no seat taken | | |
| 9 | Submit a form with the token removed | "form expired", nothing changes | | |
| 10 | Two players: choose cases, play rounds | Turns alternate; only the active player's board is shown | | |
| 11 | Submit the trivia form with no option chosen | Error, no attempt or penalty counted | | |
| 12 | Answer correctly | Player may open the round's cases | | |
| 13 | Answer wrong | Right answer shown, random cases opened, Banker calls | | |
| 14 | Refresh after an answer | Nothing is repeated (PRG) | | |
| 15 | Two correct answers then one wrong (2 of 3) | Next tier one higher, badge text updated | | |
| 16 | Two wrong answers then one correct | Next tier one lower | | |
| 17 | Fewer than 3 answers | Tier stays Medium, "still calibrating" | | |
| 18 | Use 50:50 | Two options removed, reason shown, -$2,000, second use in same round refused | | |
| 19 | Use Hint, then try again | Hint shown, -$1,000, second use refused | | |
| 20 | Use Pass twice | First gives a new question (-$3,000); second refused | | |
| 21 | Take a Deal | Player finished, score = prize - penalties, added to leaderboard once | | |
| 22 | Refuse every offer | Player wins their own case; game ends for them | | |
| 23 | Both players finish | Redirect to analytics; leaderboard shows both | | |
| 24 | Refresh analytics / leaderboard | No duplicate leaderboard rows | | |
| 25 | Analytics after a few answers | Category bars, weak spot, 2 tips, profile | | |
| 26 | Set cookie `dond_theme` to `hacker` | Falls back to dark theme | | |
| 27 | Leaderboard before any game | Friendly empty message + button | | |
| 28 | Game page at 360 px width | No horizontal page scroll; case grid 2 columns | | |
| 29 | Keyboard only | All controls reachable with a visible focus ring | | |
| 30 | `data/users.json` URL | 403 Forbidden | | |
