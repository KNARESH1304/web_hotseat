<?php
// game.php - the game table. Only the active player's board is shown;
// POST requests are handled in one place (dond_handle_action) and then redirected
// (Post/Redirect/Get) so a refresh can never repeat a move.
require_once 'includes/functions.php';
require_login();

if (empty($_SESSION['game'])) {
    flash_set('error', 'Start a game from the lobby first.');
    redirect('index.php');
}
$g = $_SESSION['game'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash_set('error', 'Your form expired. Please try again.');
    } elseif (!$g['complete']) {
        dond_handle_action($g, (string)($_POST['action'] ?? ''));
        $_SESSION['game'] = $g;
    }
    redirect($g['complete'] ? 'analytics.php' : 'game.php');
}
if ($g['complete']) {
    redirect('analytics.php');
}

$name = $g['order'][$g['turn']];
$p    = $g['players'][$name];

// Questions are issued when the turn is shown, so the response timer starts then.
if ($p['phase'] === 'question' && $p['question'] === null) {
    ai_issue_question($p);
}
$eventMsg = $p['event_message'];
$p['event_message'] = null;
$g['players'][$name] = $p;
$_SESSION['game'] = $g;

$qitem     = $p['question'] ? ai_question($p['question']['id']) : null;
$remaining = dond_remaining_values($p);
$stats     = dond_get_game_statistics($p);
$lastOpened = $p['opened'] ? end($p['opened']) : null;   // newest case gets the reveal animation
$phases    = ['pick_case' => 'choosing a case', 'question' => 'answering a question',
              'choose' => 'opening cases', 'offer' => 'facing the Banker', 'done' => 'finished'];

page_header('Deal or No Deal', 'High-Stakes PHP Vault', 'game');
?>
<section class="turn-banner" aria-live="polite">
    <strong>Turn: <?php echo e($name); ?></strong>
    <span>Round <?php echo (int)$p['round']; ?></span>
    <span>Opened <?php echo (int)$p['total_opened']; ?> of <?php echo count($p['cases']) - 2; ?></span>
    <span>Penalties: $<?php echo number_format($p['ai_stats']['penalty']); ?></span>
</section>

<div class="layout">
    <div class="main-col">
        <?php if ($p['phase'] === 'question' && $qitem): ?>
        <section class="card question-card" aria-labelledby="q-title">
            <h2 id="q-title">Trivia round</h2>
            <p class="ai-badge">AI Difficulty: <strong><?php echo e(ai_label($p['ai_diff'])); ?></strong>
                &middot; <?php echo e($p['ai_stats']['note']); ?></p>
            <p class="hint">Category: <?php echo e($qitem['cat']); ?>. Answer correctly to choose
                your own <?php echo dond_round_size($p); ?> case(s); miss it and the vault opens them for you.</p>
            <form method="post" class="question-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="answer">
                <input type="hidden" name="qtoken" value="<?php echo e($p['question']['token']); ?>">
                <fieldset>
                    <legend class="question-text"><?php echo e($qitem['q']); ?></legend>
                    <?php foreach ($p['question']['order'] as $pos => $orig):
                        $off = in_array($pos, $p['question']['elim'], true); ?>
                        <label class="option<?php echo $off ? ' removed' : ''; ?>">
                            <input type="radio" name="choice" value="<?php echo (int)$pos; ?>"
                                <?php echo $off ? 'disabled' : ''; ?>>
                            <span><?php echo e($qitem['o'][$orig]); ?><?php echo $off ? ' (removed)' : ''; ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <div class="actions"><button type="submit" class="btn primary">Lock in answer</button></div>
            </form>
            <?php if ($p['question']['fifty_msg']): ?>
                <p class="event-notice"><small><?php echo e($p['question']['fifty_msg']); ?></small></p>
            <?php endif; ?>
            <?php if ($p['question']['hint_shown']): ?>
                <p class="event-notice"><small>Hint: <?php echo e($qitem['hint']); ?></small></p>
            <?php endif; ?>

            <h3>Lifelines</h3>
            <form method="post" class="lifelines">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="lifeline">
                <button type="submit" name="kind" value="fifty" class="btn secondary"
                    <?php echo $p['lifelines']['fifty_round'] === $p['round'] ? 'disabled' : ''; ?>>
                    50:50 Lifeline AI (-$<?php echo number_format(LIFELINE_COST['fifty']); ?>, once a round)</button>
                <button type="submit" name="kind" value="hint" class="btn secondary"
                    <?php echo $p['question']['hint_shown'] ? 'disabled' : ''; ?>>
                    Hint (-$<?php echo number_format(LIFELINE_COST['hint']); ?>)</button>
                <button type="submit" name="kind" value="pass" class="btn secondary"
                    <?php echo $p['lifelines']['pass_used'] ? 'disabled' : ''; ?>>
                    Pass (-$<?php echo number_format(LIFELINE_COST['pass']); ?>, once a game)</button>
            </form>
        </section>
        <?php endif; ?>

        <section class="cases-panel card">
            <h2>Briefcases</h2>
            <p class="hint">
                <?php if ($p['phase'] === 'pick_case'): ?>Choose your main case to keep.
                <?php elseif ($p['phase'] === 'choose'): ?>Open <?php echo (int)$p['picks_left']; ?> more case(s).
                <?php elseif ($p['phase'] === 'offer'): ?>The Banker is calling. Deal or No Deal?
                <?php else: ?>Answer the question to continue.<?php endif; ?>
            </p>
            <div class="cases-grid<?php echo $p['phase'] === 'pick_case' ? ' deal-in' : ''; ?>">
                <?php $ci = 0; foreach ($p['cases'] as $id => $val):
                    $isChosen = ($id === $p['chosen_case']);
                    $isOpened = in_array($id, $p['opened'], true);
                    $class = 'case' . ($isChosen ? ' chosen' : '') . ($isOpened ? ' opened' : '')
                           . ($isOpened && $id === $lastOpened ? ' just-opened' : '')
                           . ($isOpened ? ' v-' . ($val >= 100000 ? 'high' : ($val >= 5000 ? 'mid' : 'low')) : ''); ?>
                    <div class="<?php echo $class; ?>" style="--i: <?php echo $ci++; ?>">
                        <span><?php echo $isOpened ? '$' . number_format($val) : (int)$id; ?></span>
                        <?php if ($isChosen): ?><small>Yours</small><?php endif; ?>
                        <?php if ($p['phase'] === 'pick_case'): ?>
                            <form method="post">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="case_id" value="<?php echo (int)$id; ?>">
                                <button type="submit" name="action" value="choose_case" class="btn tiny"
                                    aria-label="Keep case <?php echo (int)$id; ?>">Keep</button>
                            </form>
                        <?php elseif ($p['phase'] === 'choose' && !$isChosen && !$isOpened): ?>
                            <form method="post">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="case_id" value="<?php echo (int)$id; ?>">
                                <button type="submit" name="action" value="open_case" class="btn tiny"
                                    aria-label="Open case <?php echo (int)$id; ?>">Open</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <aside class="side-panel" aria-label="Game information">
        <section class="card">
            <h2>Remaining values</h2>
            <ul class="values-list">
                <?php foreach ($remaining as $vi => $val): ?><li style="--i: <?php echo (int)$vi; ?>">$<?php echo number_format($val); ?></li><?php endforeach; ?>
            </ul>
        </section>

        <section class="card banker-card <?php echo $p['current_offer'] !== null ? 'has-offer' : ''; ?>">
            <div class="banker-photo" role="img" aria-label="The Banker, a man in a suit holding a briefcase">
                <?php if ($p['phase'] === 'offer'): ?><span class="calling"><i></i>Incoming offer</span><?php endif; ?>
            </div>
            <h2>Banker</h2>
            <?php if ($eventMsg): ?>
                <div class="event-notice"><small>Market event: <?php echo e($eventMsg); ?></small></div>
            <?php endif; ?>
            <?php if ($p['phase'] === 'offer'): ?>
                <p>The Banker offers:</p>
                <p class="big-amount">$<?php echo number_format($p['current_offer']); ?></p>
                <p class="offer-context">Offer #<?php echo count($p['banker_offers']); ?> (round <?php echo (int)$p['round']; ?>)</p>
                <form method="post" class="deal-form">
                    <?php echo csrf_field(); ?>
                    <button type="submit" name="action" value="deal" class="btn primary">Deal</button>
                    <button type="submit" name="action" value="no_deal" class="btn danger">No Deal</button>
                </form>
            <?php else: ?>
                <p>No offer yet.</p>
                <p class="game-progress"><?php
                    $next = null;
                    foreach (dond_round_breaks() as $b) { if ($b > $p['total_opened']) { $next = $b; break; } }
                    echo $next !== null ? 'Next offer after ' . ($next - $p['total_opened']) . ' more cases.' : 'Final round.';
                ?></p>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Live standings</h2>
            <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Player</th><th scope="col">Status</th><th scope="col">Last offer</th></tr></thead>
                <tbody>
                <?php foreach ($g['order'] as $n): $q = $g['players'][$n]; ?>
                    <tr<?php echo $n === $name ? ' class="current-row"' : ''; ?>>
                        <th scope="row"><?php echo e($n); ?></th>
                        <td><?php echo $q['game_over']
                            ? e($q['mode']) . ': $' . number_format($q['score'])
                            : 'Round ' . (int)$q['round'] . ', ' . e($phases[$q['phase']]); ?></td>
                        <td><?php echo $q['banker_offers'] ? '$' . number_format(end($q['banker_offers'])) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </section>

        <section class="card stats-card">
            <h2>Your stats</h2>
            <div class="stats-grid">
                <div class="stat"><label>Risk level</label><span><?php echo (int)$stats['player_risk_tendency']; ?>%</span></div>
                <div class="stat"><label>Offers</label><span><?php echo (int)$stats['total_offers_received']; ?></span></div>
                <div class="stat"><label>Events</label><span><?php echo (int)$stats['market_events']; ?></span></div>
            </div>
        </section>
    </aside>
</div>
<?php page_footer(); ?>
