<?php
// analytics.php - Banker history and the AI Performance Report for every player.
require_once 'includes/functions.php';
require_login();

$g = $_SESSION['game'] ?? null;
if (!$g) {
    flash_set('error', 'There is no game to analyse yet. Start one from the lobby.');
    redirect('index.php');
}

page_header('Game Analytics', $g['complete'] ? 'Final results and AI reports' : 'Game in progress', 'analytics');
?>
<?php if ($g['complete']): ?>
<section class="card">
    <h2>Game over</h2>
    <p>Every player has finished. Scores were added to the leaderboard.</p>
    <div class="actions">
        <a href="leaderboard.php" class="btn primary">Leaderboard</a>
        <a href="index.php" class="btn secondary">Play again</a>
    </div>
</section>
<?php endif; ?>

<?php foreach ($g['order'] as $name):
    $p     = $g['players'][$name];
    $stats = dond_get_game_statistics($p);
    $weak  = ai_weak_spot($p);
    $cats  = ai_category_accuracy($p); ?>
<section class="card report-card" aria-labelledby="r-<?php echo e(strtolower($name)); ?>">
    <h2 id="r-<?php echo e(strtolower($name)); ?>"><?php echo e($name); ?></h2>

    <?php if ($p['game_over']): ?>
        <p><strong><?php echo e($p['mode']); ?></strong> for $<?php echo number_format($p['final_amount']); ?>.
            Their case held $<?php echo number_format($p['cases'][$p['chosen_case']]); ?>.
            Lifeline penalties: $<?php echo number_format($p['ai_stats']['penalty']); ?>.
            Final score: <strong>$<?php echo number_format($p['score']); ?></strong>.</p>
        <p class="strategy-score">Strategy score: <?php echo (int)$stats['optimal_strategy_score']; ?>%</p>
    <?php else: ?>
        <p>Still playing: round <?php echo (int)$p['round']; ?>, <?php echo (int)$p['total_opened']; ?> cases opened.</p>
    <?php endif; ?>

    <div class="stats-grid large">
        <div class="stat"><label>Risk tendency</label><span class="big-number"><?php echo (int)$stats['player_risk_tendency']; ?>%</span></div>
        <div class="stat"><label>Offers declined</label><span class="big-number"><?php echo (int)$stats['offers_declined']; ?> of <?php echo (int)$stats['total_offers_received']; ?></span></div>
        <div class="stat"><label>Highest offer</label><span class="big-number">$<?php echo number_format($stats['highest_offer']); ?></span></div>
        <div class="stat"><label>Market events</label><span class="big-number"><?php echo (int)$stats['market_events']; ?></span></div>
    </div>

    <h3>Banker offers</h3>
    <?php if ($p['banker_offers']): ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th scope="col">Offer</th><th scope="col">Amount</th><th scope="col">Decision</th></tr></thead>
        <tbody>
        <?php foreach ($p['banker_offers'] as $i => $amount):
            $d = $p['offer_decisions'][$i] ?? null; ?>
            <tr>
                <th scope="row">#<?php echo $i + 1; ?></th>
                <td>$<?php echo number_format($amount); ?></td>
                <td><?php echo $d === 'deal' ? 'Accepted' : ($d === 'no_deal' ? 'Declined' : 'Pending'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?><p>No offers yet.</p><?php endif; ?>

    <h3>AI Performance Report</h3>
    <?php if ($p['answers']): ?>
        <p><strong>Profile: <?php echo e(ai_profile($p)); ?></strong></p>
        <p>Difficulty path: <?php echo e(implode(' → ', array_map('ai_label', $p['ai_stats']['tier_path']))); ?></p>
        <p>Answers: <?php echo count(array_filter($p['answers'], fn($a) => $a['correct'])); ?> of <?php echo count($p['answers']); ?> correct
            &middot; average time <?php echo e(ai_avg_time($p)); ?> s
            &middot; lifelines used <?php echo (int)$p['ai_stats']['lifelines_used']; ?>
            &middot; tier fallbacks <?php echo (int)$p['ai_stats']['fallbacks']; ?></p>

        <h4>Accuracy by category</h4>
        <?php foreach ($cats as $cat => $row): ?>
            <p class="bar-label"><?php echo e($cat); ?>: <?php echo (int)$row['right']; ?> of <?php echo (int)$row['total']; ?></p>
            <div class="progress-bar" role="img" aria-label="<?php echo e($cat); ?> accuracy <?php echo (int)$row['pct']; ?> percent">
                <div class="progress-fill" style="width: <?php echo (int)$row['pct']; ?>%"></div>
            </div>
        <?php endforeach; ?>

        <?php if ($weak): ?>
        <h4>Weak spot: <?php echo e($weak['cat']); ?></h4>
        <div class="strategy-tips">
            <?php foreach (array_slice($weak['tips'], 0, 2) as $tip): ?><p><?php echo e($tip); ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <p>No trivia answered yet, so the AI has nothing to report.</p>
    <?php endif; ?>

    <?php if ($p['event_history']): ?>
        <h4>Market events</h4>
        <div class="event-history">
            <?php foreach ($p['event_history'] as $ev): ?>
                <div class="event-item"><strong><?php echo e($ev['name']); ?></strong><span><?php echo e($ev['message']); ?></span></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endforeach; ?>
<?php page_footer(); ?>
