<?php
// includes/layout.php - shared page header and footer.
//
// $hero picks the banner photo for the page (images/hero-<key>.jpg). If the
// photo is missing the banner still shows a gold-and-navy gradient, so the
// page never looks broken.

const HERO_KEYS = ['lobby', 'auth', 'game', 'leaderboard', 'analytics', 'about'];

function page_header(string $title, string $subtitle = '', string $hero = 'lobby'): void {
    $theme = dond_get_theme();
    $user  = $_SESSION['user'] ?? null;
    $flash = flash_get();
    $game  = !empty($_SESSION['game']);
    $hero  = in_array($hero, HERO_KEYS, true) ? $hero : 'lobby';
    $cur   = basename($_SERVER['SCRIPT_NAME'] ?? '');
    // Marks the link of the page you are on (used for the gold underline).
    $here  = fn(string $file): string => $cur === $file ? ' aria-current="page"' : '';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title); ?> - Deal or No Deal</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%92%BC%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="style.css">
</head>
<body class="theme-<?php echo e($theme); ?> page-<?php echo e($hero); ?>">
<a class="skip-link" href="#main">Skip to content</a>
<div class="container">
    <header class="site-header">
        <div class="topbar">
            <a class="brand" href="<?php echo $user ? 'index.php' : 'login.php'; ?>" aria-label="Deal or No Deal home">
                <svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                    <path class="bm-handle" d="M17 14v-3a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3v3" fill="none" stroke-width="3" stroke-linecap="round"/>
                    <rect class="bm-body" x="5" y="14" width="38" height="27" rx="5"/>
                    <path class="bm-line" d="M5 26h38" fill="none" stroke-width="2.5"/>
                    <rect class="bm-lock" x="21" y="23" width="6" height="7" rx="1.5"/>
                </svg>
                <span class="brand-text">Deal <em>or</em> No Deal</span>
            </a>
            <nav class="main-nav" aria-label="Main">
                <?php if ($user): ?>
                    <a href="index.php"<?php echo $here('index.php'); ?>>Lobby</a>
                    <?php if ($game): ?><a href="game.php"<?php echo $here('game.php'); ?>>Game</a><a href="analytics.php"<?php echo $here('analytics.php'); ?>>Analytics</a><?php endif; ?>
                    <a href="leaderboard.php"<?php echo $here('leaderboard.php'); ?>>Leaderboard</a>
                    <a href="about.php"<?php echo $here('about.php'); ?>>About</a>
                    <form method="post" action="logout.php" class="inline-form">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn tiny secondary">Log out (<?php echo e($user); ?>)</button>
                    </form>
                <?php else: ?>
                    <a href="login.php"<?php echo $here('login.php'); ?>>Log in</a>
                    <a href="register.php"<?php echo $here('register.php'); ?>>Register</a>
                    <a href="about.php"<?php echo $here('about.php'); ?>>About</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="hero hero-<?php echo e($hero); ?>">
            <div class="hero-text">
                <h1><?php echo e($title); ?></h1>
                <?php if ($subtitle !== ''): ?><p class="subtitle"><?php echo e($subtitle); ?></p><?php endif; ?>
            </div>
        </div>
    </header>
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo e($flash['type']); ?>" role="status"><?php echo e($flash['msg']); ?></div>
    <?php endif; ?>
    <main id="main">
<?php
}

function page_footer(): void {
    ?>
    </main>
    <footer class="site-footer">
        <p>CSC 6370 Project 2 &middot; PHP, HTML and CSS only &middot; no JavaScript, no database</p>
        <p class="credit">Banner artwork created for this project (original SVG illustrations).</p>
    </footer>
</div>
</body>
</html>
<?php
}
