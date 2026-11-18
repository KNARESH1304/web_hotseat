<?php
// leaderboard.php - this session's finished games, best score first.
require_once 'includes/functions.php';
require_login();

$rows = $_SESSION['leaderboard'] ?? [];
usort($rows, function ($a, $b) {
    return [$b['score'], $a['time']] <=> [$a['score'], $b['time']];   // score desc, then time asc
});
$rows = array_slice($rows, 0, 10);

page_header('Leaderboard', 'Top 10 games this session', 'leaderboard');
?>
<section class="card">
<?php if (!$rows): ?>
    <p>No games have finished yet. Be the first to take the Banker's deal, or refuse it.</p>
    <div class="actions"><a href="index.php" class="btn primary">Start a game</a></div>
<?php else: ?>
    <div class="table-wrap">
    <table class="rank-table">
        <thead>
            <tr><th scope="col">#</th><th scope="col">Player</th><th scope="col">Score</th><th scope="col">Result</th>
                <th scope="col">Penalty</th><th scope="col">Time</th><th scope="col">Accuracy</th>
                <th scope="col">Tier path</th><th scope="col">Profile</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
            <tr class="<?php echo $i < 3 ? 'rank-' . ($i + 1) : ''; ?>" style="--i: <?php echo (int)$i; ?>">
                <th scope="row"><?php echo $i + 1; ?></th>
                <td><?php echo e($r['player']); ?></td>
                <td>$<?php echo number_format($r['score']); ?></td>
                <td><?php echo e($r['mode']); ?> ($<?php echo number_format($r['prize']); ?>)</td>
                <td>$<?php echo number_format($r['penalty']); ?></td>
                <td><?php echo sprintf('%d:%02d', intdiv($r['time'], 60), $r['time'] % 60); ?></td>
                <td><?php echo (int)$r['accuracy']; ?>%</td>
                <td><?php echo e($r['path']); ?></td>
                <td><?php echo e($r['profile']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
</section>
<?php page_footer(); ?>
