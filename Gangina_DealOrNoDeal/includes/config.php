<?php
// includes/config.php
// Shared setup. Every page loads this first (through functions.php), so
// session_start() always runs before any output.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

const APP_NAME      = 'Deal or No Deal: High-Stakes PHP Vault';
const IDLE_LIMIT    = 1800;   // 30 minutes of inactivity
const MIN_PLAYERS   = 2;
const MAX_PLAYERS   = 3;
const USERS_FILE    = __DIR__ . '/../data/users.json';
// Score penalty (in dollars) for each lifeline.
const LIFELINE_COST = ['fifty' => 2000, 'hint' => 1000, 'pass' => 3000];

// ---------- small helpers ----------

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// Theme lives in a cookie, but only the two allowed values are ever used.
function dond_get_theme(): string {
    $theme = $_COOKIE['dond_theme'] ?? 'dark';
    return in_array($theme, ['dark', 'light'], true) ? $theme : 'dark';
}

// The 26 classic briefcase values.
function dond_get_case_values(): array {
    return [1, 2, 5, 10, 25, 50, 75, 100, 200, 300, 400, 500, 750,
            1000, 5000, 10000, 25000, 50000, 75000,
            100000, 200000, 300000, 400000, 500000, 750000, 1000000];
}

// Cumulative number of cases opened when the Banker calls (9 rounds).
function dond_round_breaks(): array {
    return [6, 11, 15, 18, 20, 21, 22, 23, 24];
}

// ---------- flash messages ----------

function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
}

function flash_get(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ---------- CSRF tokens ----------

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool {
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

// ---------- login guard ----------

// Call at the top of every protected page.
function require_login(): void {
    if (empty($_SESSION['user'])) {
        flash_set('error', 'Please log in to continue.');
        redirect('login.php');
    }
    if (time() - (int)($_SESSION['last_active'] ?? 0) > IDLE_LIMIT) {
        $_SESSION = [];
        flash_set('error', 'Your session timed out. Please log in again.');
        redirect('login.php');
    }
    $_SESSION['last_active'] = time();
}
