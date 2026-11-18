<?php
// about.php - rules, Banker strategy, AI rules and team (public page)
require_once 'includes/functions.php';

page_header('Rules & About', 'How the game, the Banker and the AI work', 'about');
?>
<section class="card">
    <h2>How to play</h2>
    <ol>
        <li>Register, log in, and add one or two more registered players in the lobby.</li>
        <li>Each player gets their own shuffled board of 26 briefcases ($1 to $1,000,000) and keeps one case.</li>
        <li>On your turn you face a trivia question. Answer correctly and you choose which cases to open; miss it and the vault opens them for you.</li>
        <li>After each round the Banker offers a sum. Take the Deal, or say No Deal and play the next round.</li>
        <li>Refuse every offer and you win whatever is in your own case.</li>
        <li>Your leaderboard score is your prize minus lifeline penalties (never below $0).</li>
    </ol>
</section>

<section class="card">
    <h2>Lifelines</h2>
    <ul>
        <li><strong>50:50 Lifeline AI</strong> (-$2,000, once per round): removes two wrong options and shows why.</li>
        <li><strong>Hint</strong> (-$1,000): shows a clue for the current question.</li>
        <li><strong>Pass</strong> (-$3,000, once per game): swaps the question for a new one.</li>
    </ul>
</section>

<section class="card">
    <h2>The Banker</h2>
    <p>The offer is the average of the values still in play multiplied by a factor that rises each round. The factor drops when big prizes remain, when the player has been risk-taking, and late in the game. Good trivia accuracy earns a small bonus. About one offer in four is changed by a random market event, and every offer carries a little random volatility.</p>
</section>

<section class="card">
    <h2>The AI question selector</h2>
    <p>The AI is a readable PHP rule, not a hidden model. After each answer it looks at your last three results: if two or more were correct the next question is one tier harder (max Hard); otherwise it is one tier easier (min Easy). It then picks an unused question from that tier. Every third question favours your weakest category, using a confidence score that rises faster for quick, hard answers. After the game the Weak Spot Analyzer finds your lowest-accuracy category and shows review tips.</p>
    <p><strong>Limits:</strong> three answers are a small sample, so the difficulty can swing; the 15-second "fast" cut-off is our own estimate; and the bank has only 36 questions.</p>
</section>

<section class="card">
    <h2>Team</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th scope="col">Name</th><th scope="col">Student ID</th><th scope="col">Role</th></tr></thead>
        <tbody>
            <tr><th scope="row">Sri Krishna Teja Gangina</th><td>002923905</td><td>Project Leader, game logic and AI</td></tr>
            <tr><th scope="row">Kothoju Naresh</th><td>002923882</td><td>Login, sessions, security, leaderboard</td></tr>
            <tr><th scope="row">Sattwik Bhattacharjee</th><td>002960354</td><td>Layout, CSS, accessibility, testing</td></tr>
        </tbody>
    </table>
    </div>
    <p class="hint">Team: Web Warriors &middot; CSC 6370</p>
</section>
<?php page_footer(); ?>
