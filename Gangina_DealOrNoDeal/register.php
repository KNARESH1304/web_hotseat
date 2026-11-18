<?php
// register.php - creates an account (saved to data/users.json)
require_once 'includes/functions.php';

if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$errors   = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
    if (!csrf_valid()) {
        $errors['form'] = 'Your form expired. Please try again.';
    } else {
        $errors = user_register($username, (string)($_POST['password'] ?? ''), (string)($_POST['confirm'] ?? ''));
        if (!$errors) {
            flash_set('ok', 'Account created. Please log in.');
            redirect('login.php');
        }
    }
}

page_header('Register', 'Every player at the table needs an account.', 'auth');
?>
<form method="post" class="card form-card" novalidate>
    <h2>New account</h2>
    <?php if (isset($errors['form'])): ?>
        <p class="field-error" role="alert"><?php echo e($errors['form']); ?></p>
    <?php endif; ?>
    <?php echo csrf_field(); ?>
    <div class="field">
        <label for="username">Username (3 to 20 letters, digits or _)</label>
        <input type="text" id="username" name="username" value="<?php echo e($username); ?>"
               autocomplete="username" maxlength="20" required>
        <?php if (isset($errors['username'])): ?><p class="field-error"><?php echo e($errors['username']); ?></p><?php endif; ?>
    </div>
    <div class="field">
        <label for="password">Password (at least 8 characters)</label>
        <input type="password" id="password" name="password" autocomplete="new-password" required>
        <?php if (isset($errors['password'])): ?><p class="field-error"><?php echo e($errors['password']); ?></p><?php endif; ?>
    </div>
    <div class="field">
        <label for="confirm">Confirm password</label>
        <input type="password" id="confirm" name="confirm" autocomplete="new-password" required>
        <?php if (isset($errors['confirm'])): ?><p class="field-error"><?php echo e($errors['confirm']); ?></p><?php endif; ?>
    </div>
    <div class="actions">
        <button type="submit" class="btn primary">Register</button>
        <a href="login.php" class="btn secondary">Back to login</a>
    </div>
</form>
<?php page_footer(); ?>
