<?php
// login.php - session-based login
require_once 'includes/functions.php';

if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$errors   = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors['form'] = 'Your form expired. Please try again.';
    } else {
        // Normalize username input before authentication.
        $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
        $password = (string)($_POST['password'] ?? '');
        $name = ($username !== '' && $password !== '') ? user_verify($username, $password) : null;
        if ($name === null) {
            // One general message: it never reveals which part was wrong.
            $errors['form'] = 'Username or password is incorrect.';
        } else {
            session_regenerate_id(true);               // new session ID at login
            $_SESSION['user']        = $name;
            $_SESSION['last_active'] = time();
            $_SESSION['csrf']        = bin2hex(random_bytes(32));
            flash_set('ok', "Welcome, $name.");
            redirect('index.php');
        }
    }
}

page_header('Log in', 'Sign in to face the Banker.', 'auth');
?>
<form method="post" class="card form-card" novalidate>
    <h2>Account</h2>
    <?php if (isset($errors['form'])): ?>
        <p class="field-error" role="alert"><?php echo e($errors['form']); ?></p>
    <?php endif; ?>
    <?php echo csrf_field(); ?>
    <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?php echo e($username); ?>"
               autocomplete="username" maxlength="20" required>
    </div>
    <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <div class="actions">
        <button type="submit" class="btn primary">Log in</button>
        <a href="register.php" class="btn secondary">Create account</a>
    </div>
</form>
<?php page_footer(); ?>
