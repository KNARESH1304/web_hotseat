# Deal or No Deal: High-Stakes PHP Vault

**CSC 6370, Project 2 (Popular Game Show) · Team Web Warriors**

A multi-player Deal or No Deal built with PHP 8, HTML5 and CSS3 only (no JavaScript, no database, no external API).
Two or three registered players sit at one table. Each has their own shuffled board of 26 briefcases. Every round starts with a trivia question chosen by a rule-based AI: answer correctly and you choose which cases to open, miss it and the vault opens them for you. Then the Banker makes an offer: Deal or No Deal?

- **Live CODD URL:** https://codd.cs.gsu.edu/~YOUR_GSU_ID/Gangina_DealOrNoDeal/
- **GitHub:** https://github.com/OWNER/REPO

## Setup (CODD)

1. Requires **PHP 8.0 or newer** (uses `fn` closures, `array_key_first`, `random_int`).
2. Upload the whole `Gangina_DealOrNoDeal` folder to `public_html` on CODD (keep the folder structure).
3. Make the data folder and file writable by PHP: `chmod 777 data` and `chmod 666 data/users.json`.
4. `data/.htaccess` blocks direct downloads of `users.json`. Check that https://codd.cs.gsu.edu/~YOUR_GSU_ID/Gangina_DealOrNoDeal/data/users.json returns *403 Forbidden*.
5. Open the live URL in a private window, register two accounts and play a full game.

## How to play this game

1. **Register** and **log in**.
2. In the **lobby** add one or two more registered players (their username and password are verified; they must already have accounts). Press *Start new game*.
3. Each player keeps one briefcase. After that, turns alternate: answer the trivia question, open cases (yours to choose if correct, random if not), then answer the Banker.
4. **Lifelines** (each costs score): 50:50 Lifeline AI (-$2,000, once per round), Hint (-$1,000), Pass (-$3,000, once per game).
5. Final score = prize won minus lifeline penalties (never below $0). The **leaderboard** shows the top ten finished games of the session; **analytics** shows each player's Banker history and AI Performance Report.

## File structure

```
index.php        lobby (seats, start/resume/abandon, theme cookie)     [protected]
login.php  register.php  logout.php                                     auth
game.php         the table: question, lifelines, cases, Banker          [protected]
analytics.php    Banker history + AI Performance Report                 [protected]
leaderboard.php  session leaderboard                                    [protected]
about.php        rules, AI rule, team                                   public
style.css        all styling (themes, responsive, focus, animations, reduced motion)
images/          7 original illustrations (banners + Banker), see docs/image_credits.md
includes/        config.php (session + helpers + CSRF + guard), auth.php (users.json),
                 functions.php (game logic), ai.php (AI rules), questions.php (36-question bank),
                 events.php (market events), layout.php (shared header/footer)
data/            users.json + .htaccess
docs/            test plan, sprint log, journal, time log, stand-up notes
```

## Look and feel (CSS only, no JavaScript)

- **Illustrated banners** on every page (`images/hero-*.jpg`, original artwork) with a slow zoom and a soft gold light sweep, plus a Banker picture on the game page. A gradient shows if an image is missing.
- **Classic game-show motion:** drifting spotlights and gold dust behind the page, cards that rise in, a gold underline on the nav, a shine on primary buttons, briefcases dealt in at the start, a flip and colour reveal (blue = low, red = big prize) when a case opens, a pulsing Banker call, bars that fill, and leaderboard rows that slide in with gold / silver / bronze markers.
- Pages cross-fade in browsers that support CSS view transitions.
- Everything stops for people who set *reduce motion* in their system (`prefers-reduced-motion`).

## The five core requirements

| Requirement | Where |
|---|---|
| Sessions & leaderboard | `$_SESSION['game']` holds each player's state; `$_SESSION['leaderboard']` is filled once per finished player. The only cookies are the theme (allow-listed) and a resume flag; scores and credentials never go in a cookie. |
| Form processing | Every form is POST with a CSRF token, `filter_input()` validation and visible messages; then Post/Redirect/Get. |
| Login, register, auth | `data/users.json` with `password_hash()` / `password_verify()`, file locking, `session_regenerate_id(true)` at login, 30-minute idle timeout, `require_login()` on protected pages, POST-only logout that wipes the session. |
| Logic | Briefcase shuffle, round breaks, Banker offer algorithm, market events, turn order, scoring, lifelines: all server-side in `includes/functions.php`. |
| Game flow | 26 cases, 9 Banker rounds, Deal / No Deal, final reveal, multi-player turns. |

## The AI (rules in `includes/ai.php`)

**Undergraduate (Smart Shuffle).** `ai_diff` starts at 2 (Medium). After each answer the last three results (`recent`) are checked: 2 or more correct raises the tier by 1 (max 3), otherwise it drops by 1 (min 1). The selector picks a random question in that tier that is not in `seen_ids`. A badge shows Easy / Medium / Hard and one sentence explaining the last change.

**Graduate (Skill-Curve Optimizer).**
- Each question has a weight (1-5). Per-category confidence starts at 50: correct and fast (15 s or less) `+2 x weight`, correct and slow `+weight`, wrong `-2 x weight` (clamped 0-100). Every third question favours the weakest category.
- Response time is measured on the server (the question's issue time is stored in the session; a hidden one-time token ties the answer to that question, so time cannot be forged from the form).
- **Weak Spot Analyzer:** groups `answers` by category, finds the lowest accuracy and shows two review tips.
- **Lifeline AI (50:50):** removes two wrong options (keeping the most plausible distractor) and shows a reason sentence.
- `ai_stats` tracks confidence per category, tier path, fallbacks, lifelines used and penalties; the report card shows them with a one-word profile (Lifeline Lover, Risk Taker or Steady Climber).
- **Bank exhausted?** The selector falls back to the nearest tier (easier first), then recycles the bank, and counts each fallback.

**Banker.** Offer = average of remaining values x a factor (rises each round; lowered for big prizes left, risk-taking, aggression and late game; raised slightly for good trivia accuracy) x difficulty modifier x random volatility. About 1 offer in 4 is changed by a market event.

*Limits:* the last-three rule is a small sample and can swing; the 15 s cut-off and weights are our own estimates; the bank has 36 questions; the AI only knows the categories and tips we wrote.

## Tests

See `docs/test_plan.md` (test cases with expected results and a column for actual results).

## Team

| Name | Student ID | Main contribution |
|---|---|---|
| Sri Krishna Teja Gangina | 002923905 | Project Leader, game logic and AI |
| Kothoju Naresh | 002923882 | Login, sessions, security, leaderboard |
| Sattwik Bhattacharjee | 002960354 | Layout, CSS, accessibility, testing |

## AI tools disclosure

> **Team: edit this before submitting so it matches what really happened.**
The original `index.php`, `game.php`, `functions.php` and `style.css` were written by the team. Claude (Anthropic) was used to generate the multi-player turn system, login/registration, the trivia question bank, the AI difficulty engine, the analytics and leaderboard pages, the CSS additions and this documentation, based on the team's original files and the course specification. The team reviewed, tested and can explain all code.
<!-- Deployment note: Keep the original folder structure intact when uploading to CODD so PHP includes, stylesheets, and image paths resolve correctly. -->
