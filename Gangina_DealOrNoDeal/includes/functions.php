<?php
// includes/functions.php
// Game logic for Deal or No Deal. The server makes every decision: briefcase
// values, case opening, trivia checking, Banker offers, turns and scores.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/events.php';
require_once __DIR__ . '/questions.php';
require_once __DIR__ . '/ai.php';
require_once __DIR__ . '/layout.php';

// ---------- game setup ----------

// Every player gets their own shuffled board and their own AI state.
function dond_new_player(): array {
    $values = dond_get_case_values();
    shuffle($values);
    $cases = [];
    foreach ($values as $i => $value) {
        $cases[$i + 1] = $value;          // case ids 1..26
    }
    return [
        'cases' => $cases, 'chosen_case' => null, 'opened' => [], 'total_opened' => 0,
        'round' => 1, 'phase' => 'pick_case', 'picks_left' => 0,
        'current_offer' => null, 'banker_offers' => [], 'offer_decisions' => [],
        'game_over' => false, 'final_amount' => null, 'deal_amount' => null,
        'mode' => null, 'score' => 0, 'start_time' => time(),
        'risk_profile' => 0.5, 'player_aggression' => 0.5,
        'event_message' => null, 'event_history' => [], 'history' => [],
        // AI state
        'ai_diff' => 2, 'seen_ids' => [], 'recent' => [], 'answers' => [],
        'ai_stats' => ['conf' => [], 'tier_path' => [2], 'fallbacks' => 0,
                       'lifelines_used' => 0, 'penalty' => 0,
                       'note' => 'Host AI starts you at Medium.'],
        'lifelines' => ['fifty_round' => 0, 'pass_used' => false],
        'question' => null,
    ];
}

function dond_init_game(array $names): void {
    $players = [];
    foreach ($names as $name) {
        $players[$name] = dond_new_player();
    }
    $_SESSION['game'] = [
        'order' => array_values($names), 'players' => $players,
        'turn' => 0, 'complete' => false, 'started' => time(),
    ];
    // The cookie only says "a game may be resumable"; the session decides.
    setcookie('dond_can_resume', '1', time() + 7 * 24 * 3600, '/');
}

function dond_has_active_game(): bool {
    return isset($_SESSION['game']) && !$_SESSION['game']['complete'];
}

function dond_reset_game(): void {
    unset($_SESSION['game']);
    setcookie('dond_can_resume', '', time() - 3600, '/');
}

function dond_log(array &$p, string $action, $data = null): void {
    $p['history'][] = ['round' => $p['round'], 'action' => $action, 'data' => $data, 'time' => time()];
}

// ---------- board helpers ----------

function dond_remaining_case_ids(array $p): array {
    return array_values(array_diff(array_keys($p['cases']), $p['opened']));
}

function dond_remaining_values(array $p): array {
    $values = [];
    foreach ($p['cases'] as $id => $value) {
        if (!in_array($id, $p['opened'], true)) {
            $values[] = $value;
        }
    }
    sort($values);
    return $values;
}

// How many cases must be opened in the player's current round.
function dond_round_size(array $p): int {
    $breaks = dond_round_breaks();
    return $breaks[$p['round'] - 1] - $p['total_opened'];
}

function dond_open_case(array &$p, int $id): void {
    $p['opened'][] = $id;
    $p['total_opened']++;
    dond_log($p, 'open_case', $id);
}

// Wrong trivia answer: the server opens random cases for the player.
function dond_auto_open(array &$p, int $count): array {
    $ids = array_values(array_diff(dond_remaining_case_ids($p), [$p['chosen_case']]));
    shuffle($ids);
    $picked = array_slice($ids, 0, $count);
    foreach ($picked as $id) {
        dond_open_case($p, $id);
    }
    return $picked;
}

// After the last case of a round, the Banker calls.
function dond_after_open(array &$p): void {
    $p['picks_left'] = 0;
    if (in_array($p['total_opened'], dond_round_breaks(), true)) {
        $offer = dond_compute_banker_offer($p);
        $p['current_offer']    = $offer;
        $p['banker_offers'][]  = $offer;
        $p['phase']            = 'offer';
        dond_log($p, 'banker_offer', $offer);
    }
}

// ---------- Banker algorithm ----------

function dond_compute_banker_offer(array &$p): int {
    $remaining = dond_remaining_values($p);
    if (!$remaining) {
        return 0;
    }
    $avg = array_sum($remaining) / count($remaining);
    $min = min($remaining);

    $phase      = count($p['opened']) / count($p['cases']);
    $highRatio  = count(array_filter($remaining, fn($v) => $v >= 100000)) / count($remaining);

    $factor = 0.5 + ($p['round'] * 0.03);                 // offers grow each round
    if ($highRatio > 0.3) {
        $factor -= 0.1;                                    // big prizes still in play
    }
    $factor -= $p['risk_profile'] * 0.1;                   // risk takers get less
    $factor -= $p['player_aggression'] * 0.08;             // so do aggressive players
    if ($phase > 0.7) {
        $factor -= 0.05;                                   // late game is more cautious
    }
    $factor += (ai_accuracy($p) - 0.5) * 0.1;              // trivia goodwill

    $event = dond_get_random_event();
    if ($event) {
        $avg = dond_apply_event_effect($avg, $event);
        $p['event_message']   = $event['message'];
        $p['event_history'][] = $event;
    }

    $offer = $avg * $factor * dond_difficulty_modifier($p) * (random_int(85, 115) / 100);
    return (int)round(max($offer, $min * 0.5));
}

function dond_difficulty_modifier(array $p): float {
    $mod = 1.0;
    if ($p['risk_profile'] > 0.7) {
        $mod *= 0.9;                                       // player is gambling: offer less
    }
    if (count($p['banker_offers']) > 2 && max($p['banker_offers']) < max($p['cases']) * 0.3) {
        $mod *= 1.1;                                       // player is struggling: ease up
    }
    return $mod;
}

function dond_update_player_behavior(array &$p, string $decision): void {
    $noDeal = ($decision === 'no_deal');
    $p['risk_profile']      = max(0.1, min(0.9, $p['risk_profile'] + ($noDeal ? 0.1 : -0.1)));
    $p['player_aggression'] = max(0.1, min(0.9, $p['player_aggression'] + ($noDeal ? 0.15 : -0.15)));
}

// ---------- turns, finishing, leaderboard ----------

// Moves to the next player who is still playing; ends the game if nobody is.
function dond_next_turn(array &$g): void {
    $n = count($g['order']);
    for ($i = 1; $i <= $n; $i++) {
        $idx = ($g['turn'] + $i) % $n;
        if (!$g['players'][$g['order'][$idx]]['game_over']) {
            $g['turn'] = $idx;
            return;
        }
    }
    $g['complete'] = true;
}

function dond_finish_player(array &$g, string $name, string $mode): void {
    $p = &$g['players'][$name];
    $p['game_over']     = true;
    $p['phase']         = 'done';
    $p['mode']          = $mode;
    $p['current_offer'] = null;
    $p['score']         = max(0, $p['final_amount'] - $p['ai_stats']['penalty']);
    $_SESSION['leaderboard'][] = [
        'player'   => $name,
        'score'    => $p['score'],
        'prize'    => $p['final_amount'],
        'penalty'  => $p['ai_stats']['penalty'],
        'mode'     => $mode,
        'accuracy' => (int)round(ai_accuracy($p) * 100),
        'path'     => implode(', ', $p['ai_stats']['tier_path']),
        'time'     => time() - $p['start_time'],
        'profile'  => ai_profile($p),
    ];
    unset($p);
    dond_next_turn($g);
}

// ---------- the one place that handles every game POST ----------

function dond_handle_action(array &$g, string $action): void {
    $name = $g['order'][$g['turn']];
    $p = &$g['players'][$name];
    if ($p['game_over']) {
        return;
    }

    switch ($action) {
        case 'choose_case':
            $id = filter_input(INPUT_POST, 'case_id', FILTER_VALIDATE_INT);
            if ($p['phase'] !== 'pick_case' || !$id || !isset($p['cases'][$id])) {
                flash_set('error', 'Pick one of the briefcases to keep.');
                return;
            }
            $p['chosen_case'] = $id;
            $p['phase'] = 'question';
            dond_log($p, 'choose_case', $id);
            flash_set('ok', "$name keeps case $id.");
            unset($p);
            dond_next_turn($g);
            return;

        case 'answer':
            if ($p['phase'] !== 'question' || $p['question'] === null) {
                return;
            }
            $question = $p['question'];
            $token    = $_POST['qtoken'] ?? '';
            $choice   = filter_input(INPUT_POST, 'choice', FILTER_VALIDATE_INT,
                                     ['options' => ['min_range' => 0, 'max_range' => 3]]);
            if (!is_string($token) || !hash_equals($question['token'], $token)) {
                flash_set('error', 'That question is no longer active.');
                return;
            }
            if ($choice === false || $choice === null) {
                flash_set('error', 'Choose one of the four answers first. Nothing was counted.');
                return;
            }
            if (in_array($choice, $question['elim'], true)) {
                flash_set('error', 'The Lifeline AI removed that option.');
                return;
            }
            $item    = ai_question($question['id']);
            $secs    = microtime(true) - $question['issued'];
            $correct = ($question['order'][$choice] === $item['a']);
            ai_record_answer($p, $item, $correct, $secs);
            $p['question'] = null;
            $size = dond_round_size($p);
            if ($correct) {
                $p['phase'] = 'choose';
                $p['picks_left'] = $size;
                flash_set('ok', "Correct! Open $size case" . ($size > 1 ? 's' : '') . '.');
            } else {
                dond_auto_open($p, $size);
                dond_after_open($p);
                flash_set('error', 'Not quite. The answer was "' . $item['o'][$item['a']]
                    . "\". The vault opened $size random case" . ($size > 1 ? 's' : '') . '.');
            }
            return;

        case 'lifeline':
            $kind = $_POST['kind'] ?? '';
            if ($p['phase'] !== 'question' || !is_string($kind)) {
                return;
            }
            [$ok, $msg] = ai_use_lifeline($p, $kind);
            flash_set($ok ? 'ok' : 'error', $msg);
            return;

        case 'open_case':
            $id = filter_input(INPUT_POST, 'case_id', FILTER_VALIDATE_INT);
            if ($p['phase'] !== 'choose' || !$id || !isset($p['cases'][$id])
                || $id === $p['chosen_case'] || in_array($id, $p['opened'], true)) {
                flash_set('error', 'That case cannot be opened right now.');
                return;
            }
            dond_open_case($p, $id);
            $p['picks_left']--;
            if ($p['picks_left'] <= 0) {
                dond_after_open($p);
            }
            return;

        case 'deal':
            if ($p['phase'] !== 'offer') {
                return;
            }
            dond_update_player_behavior($p, 'deal');
            $p['offer_decisions'][] = 'deal';
            $p['deal_amount']  = $p['current_offer'];
            $p['final_amount'] = $p['deal_amount'];
            dond_log($p, 'accept_deal', $p['deal_amount']);
            flash_set('ok', "$name took the deal for $" . number_format($p['deal_amount']) . '.');
            unset($p);
            dond_finish_player($g, $name, 'Deal');
            return;

        case 'no_deal':
            if ($p['phase'] !== 'offer') {
                return;
            }
            dond_update_player_behavior($p, 'no_deal');
            $p['offer_decisions'][] = 'no_deal';
            dond_log($p, 'reject_deal', $p['current_offer']);
            $breaks = dond_round_breaks();
            if ($p['total_opened'] >= end($breaks)) {
                // Last offer refused: the player keeps their own case.
                $p['final_amount'] = $p['cases'][$p['chosen_case']];
                flash_set('ok', "$name went all the way and wins $" . number_format($p['final_amount']) . '.');
                unset($p);
                dond_finish_player($g, $name, 'No Deal');
                return;
            }
            $p['round']++;
            $p['phase'] = 'question';
            $p['current_offer'] = null;
            flash_set('ok', "$name says No Deal.");
            unset($p);
            dond_next_turn($g);
            return;
    }
}

// ---------- statistics ----------

function dond_get_game_statistics(array $p): array {
    $best = $p['banker_offers'] ? max($p['banker_offers']) : 0;
    if ($p['game_over'] && $p['mode'] === 'Deal') {
        $optimal = ($p['deal_amount'] >= $p['cases'][$p['chosen_case']]) ? 100 : 50;
    } elseif ($p['game_over']) {
        $optimal = ($p['final_amount'] >= $best) ? 100 : 50;
    } else {
        $optimal = 75;                                     // game still running
    }
    $declined = count(array_filter($p['offer_decisions'], fn($d) => $d === 'no_deal'));
    return [
        'player_risk_tendency'  => (int)round($p['risk_profile'] * 100),
        'total_offers_received' => count($p['banker_offers']),
        'offers_declined'       => $declined,
        'highest_offer'         => $best,
        'market_events'         => count($p['event_history']),
        'duration_seconds'      => time() - $p['start_time'],
        'optimal_strategy_score' => $optimal,
    ];
}
