<?php
// index.php - lobby (protected). Seats 2-3 verified players, then starts the game.
require_once 'includes/functions.php';
require_login();

$user = $_SESSION['user'];
if (empty($_SESSION['seats']) || $_SESSION['seats'][0] !== $user) {
    $_SESSION['seats'] = [$user];               // the logged-in user is always seat 1
}
$errors   = [];
$newName  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash_set('error', 'Your form expired. Please try again.');
        redirect('index.php');
    }
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'theme') {
        $theme = (($_POST['theme'] ?? '') === 'light') ? 'light' : 'dark';
        setcookie('dond_theme', $theme, time() + 30 * 24 * 3600, '/');
        flash_set('ok', 'Theme saved.');
        redirect('index.php');
    }

    if ($action === 'add_player') {
        $newName = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
        $pass    = (string)($_POST['password'] ?? '');
        $found   = ($newName !== '' && $pass !== '') ? user_verify($newName, $pass) : null;
        if (count($_SESSION['seats']) >= MAX_PLAYERS) {
            $errors['add'] = 'The table is full (' . MAX_PLAYERS . ' players).';
        } elseif ($found === null) {
            $errors['add'] = 'Those credentials are not valid. Every player must be registered.';
        } elseif (in_array(strtolower($found), array_map('strtolower', $_SESSION['seats']), true)) {
            $errors['add'] = "$found already has a seat.";
        } else {
            $_SESSION['seats'][] = $found;
            flash_set('ok', "$found joined the table.");
            redirect('index.php');
        }
    }

    if ($action === 'remove_player') {
        $idx = filter_input(INPUT_POST, 'seat', FILTER_VALIDATE_INT);
        if ($idx !== null && $idx !== false && $idx > 0 && isset($_SESSION['seats'][$idx])) {
            array_splice($_SESSION['seats'], $idx, 1);
        }
        redirect('index.php');
    }

    if ($action === 'start') {
        if (count($_SESSION['seats']) < MIN_PLAYERS) {
            flash_set('error', 'At least ' . MIN_PLAYERS . ' registered players are needed to start.');
            redirect('index.php');
        }
        dond_init_game($_SESSION['seats']);
        redirect('game.php');
    }

    if ($action === 'resume' && dond_has_active_game()) {
        redirect('game.php');
    }

    if ($action === 'abandon') {
        dond_reset_game();
        flash_set('ok', 'Game abandoned. Nothing was added to the leaderboard.');
        redirect('index.php');
    }
}

$can_resume = !empty($_COOKIE['dond_can_resume']) && dond_has_active_game();
$theme      = dond_get_theme();
$seats      = $_SESSION['seats'];

page_header('Deal or No Deal', 'Seat your players, then face the Banker.', 'lobby');
?>
<section class="card">
    <h2>Players at the table (<?php echo count($seats); ?> of <?php echo MAX_PLAYERS; ?>)</h2>
    <ol class="seat-list">
        <?php foreach ($seats as $i => $seat): ?>
            <li>
                <?php echo e($seat); ?><?php echo $i === 0 ? ' (host)' : ''; ?>
                <?php if ($i > 0): ?>
                    <form method="post" class="inline-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="remove_player">
                        <input type="hidden" name="seat" value="<?php echo (int)$i; ?>">
                        <button type="submit" class="btn tiny secondary">Remove</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>

    <?php if (count($seats) < MAX_PLAYERS): ?>
    <form method="post" class="form-grid" novalidate>
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="add_player">
        <h3>Add another registered player</h3>
        <?php if (isset($errors['add'])): ?><p class="field-error" role="alert"><?php echo e($errors['add']); ?></p><?php endif; ?>
        <div class="field">
            <label for="p_username">Player username</label>
            <input type="text" id="p_username" name="username" value="<?php echo e($newName); ?>" maxlength="20" required>
        </div>
        <div class="field">
            <label for="p_password">Player password</label>
            <input type="password" id="p_password" name="password" required>
        </div>
        <div class="actions"><button type="submit" class="btn secondary">Add player</button></div>
    </form>
    <p class="hint">Players who have not registered can <a href="register.php">create an account</a> first (log out of this one if needed).</p>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Game</h2>
    <form method="post">
        <?php echo csrf_field(); ?>
        <p>Players take turns. Each round starts with a trivia question: answer correctly and you choose which cases to open; miss it and the vault opens them for you.</p>
        <div class="actions">
            <button type="submit" name="action" value="start" class="btn primary"
                <?php echo count($seats) < MIN_PLAYERS ? 'disabled' : ''; ?>>Start new game</button>
            <?php if ($can_resume): ?>
                <button type="submit" name="action" value="resume" class="btn">Resume game</button>
                <button type="submit" name="action" value="abandon" class="btn danger">Abandon game</button>
            <?php endif; ?>
        </div>
        <?php if (count($seats) < MIN_PLAYERS): ?>
            <p class="hint">Add at least one more player to start.</p>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h2>Theme</h2>
    <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="theme">
        <fieldset class="radio-row">
            <legend class="sr-only">Theme</legend>
            <label><input type="radio" name="theme" value="dark" <?php echo $theme === 'dark' ? 'checked' : ''; ?>> Dark Casino</label>
            <label><input type="radio" name="theme" value="light" <?php echo $theme === 'light' ? 'checked' : ''; ?>> Light Minimal</label>
        </fieldset>
        <div class="actions"><button type="submit" class="btn secondary">Save theme</button></div>
    </form>
</section>

<section class="card">
    <h2>Learn the game</h2>
    <p>Rules, Banker strategy, market events and how the AI picks your questions.</p>
    <div class="actions">
        <a href="about.php" class="btn secondary">Rules &amp; About</a>
        <a href="leaderboard.php" class="btn secondary">Leaderboard</a>
    </div>
</section>
<?php page_footer(); ?>
