<?php
// includes/ai.php - the "AI" in this project is a set of readable PHP rules.
// It never answers for the player; it only chooses questions and explains itself.
//
// Per-player state used here (kept inside $_SESSION['game']['players'][name]):
//   ai_diff   current target difficulty 1..3 (starts at 2)
//   seen_ids  question ids already shown to this player
//   recent    results (true/false) of the last answers
//   answers   every answered question with category, time and result
//   ai_stats  confidence per category, tier path, lifelines, penalty, notes

function ai_label(int $diff): string {
    return [1 => 'Easy', 2 => 'Medium', 3 => 'Hard'][$diff] ?? 'Medium';
}

function ai_accuracy(array $p): float {
    $total = count($p['answers']);
    if ($total === 0) {
        return 0.5;
    }
    $right = count(array_filter($p['answers'], fn($a) => $a['correct']));
    return $right / $total;
}

// Category with the lowest confidence score so far (null if nothing answered).
function ai_weakest_category(array $p): ?string {
    $conf = $p['ai_stats']['conf'];
    if (!$conf) {
        return null;
    }
    asort($conf);
    return array_key_first($conf);
}

// Selector: unused question at the current target tier. Every third question
// prefers the player's weakest category. If the tier is empty, fall back to the
// nearest tier (easier first), then to recycling the tier.
function ai_pick(array &$p): array {
    $bank   = ai_bank();
    $unused = function (int $tier, array $seen) use ($bank): array {
        return array_values(array_filter($bank, function ($q) use ($tier, $seen) {
            return $q['d'] === $tier && !in_array($q['id'], $seen, true);
        }));
    };

    $diff = $p['ai_diff'];
    $pool = $unused($diff, $p['seen_ids']);
    if (!$pool) {
        $p['ai_stats']['fallbacks']++;
        foreach ([$diff - 1, $diff + 1, $diff - 2, $diff + 2] as $tier) {
            if ($tier >= 1 && $tier <= 3) {
                $pool = $unused($tier, $p['seen_ids']);
                if ($pool) {
                    break;
                }
            }
        }
    }
    if (!$pool) {
        $p['seen_ids'] = [];            // bank exhausted: start over
        $pool = $unused($diff, $p['seen_ids']);
    }

    if (count($p['seen_ids']) % 3 === 2) {
        $weak = ai_weakest_category($p);
        if ($weak !== null) {
            $focused = array_values(array_filter($pool, fn($q) => $q['cat'] === $weak));
            if ($focused) {
                $pool = $focused;
            }
        }
    }
    return $pool[random_int(0, count($pool) - 1)];
}

// Puts a new question on the player's table. Answers are shuffled, and only the
// shuffled order is stored so the correct position is never sent to the browser.
function ai_issue_question(array &$p): void {
    $item  = ai_pick($p);
    $order = [0, 1, 2, 3];
    shuffle($order);
    $p['seen_ids'][] = $item['id'];
    $p['question'] = [
        'id'         => $item['id'],
        'order'      => $order,
        'issued'     => microtime(true),
        'token'      => bin2hex(random_bytes(8)),
        'elim'       => [],
        'hint_shown' => false,
        'fifty_msg'  => null,
    ];
}

// Smart Shuffle (last three answers) + confidence model.
function ai_record_answer(array &$p, array $item, bool $correct, float $secs): void {
    $p['answers'][] = [
        'id' => $item['id'], 'cat' => $item['cat'], 'd' => $item['d'], 'w' => $item['w'],
        'correct' => $correct, 'secs' => round($secs, 1), 'round' => $p['round'],
    ];

    // Undergraduate rule: look at the last three answers.
    $p['recent'][] = $correct;
    $p['recent']   = array_slice($p['recent'], -3);
    $old = $p['ai_diff'];
    if (count($p['recent']) === 3) {
        $right = count(array_filter($p['recent']));
        if ($right >= 2) {
            $p['ai_diff'] = min(3, $old + 1);
            $p['ai_stats']['note'] = "Host AI raised the difficulty: you got $right of your last 3 right.";
        } else {
            $p['ai_diff'] = max(1, $old - 1);
            $p['ai_stats']['note'] = 'Host AI lowered the difficulty: you got ' . (3 - $right) . ' of your last 3 wrong.';
        }
        if ($p['ai_diff'] === $old) {
            $p['ai_stats']['note'] .= ' (already at the limit, so it stays ' . ai_label($old) . ')';
        }
    } else {
        $p['ai_stats']['note'] = 'Host AI is still calibrating: it needs three answers.';
    }
    $p['ai_stats']['tier_path'][] = $p['ai_diff'];

    // Graduate rule: per-category confidence, weighted by question weight and speed.
    $cat = $item['cat'];
    $c   = $p['ai_stats']['conf'][$cat] ?? 50;
    if ($correct) {
        $c += ($secs <= 15) ? 2 * $item['w'] : $item['w'];
    } else {
        $c -= 2 * $item['w'];
    }
    $p['ai_stats']['conf'][$cat] = max(0, min(100, $c));
}

// Lifelines: 'fifty' (Lifeline AI, once per round), 'hint' (once per question),
// 'pass' (once per game). Each costs points. Returns [ok, message].
function ai_use_lifeline(array &$p, string $kind): array {
    if (!array_key_exists($kind, LIFELINE_COST) || $p['question'] === null) {
        return [false, 'That lifeline is not available.'];
    }
    $item = ai_question($p['question']['id']);

    if ($kind === 'fifty') {
        if ($p['lifelines']['fifty_round'] === $p['round']) {
            return [false, 'The Lifeline AI was already used this round.'];
        }
        // Keep the right answer plus the most plausible wrong one; remove the other two.
        $wrong = array_values(array_diff([0, 1, 2, 3], [$item['a']]));
        $drop  = array_values(array_diff($wrong, [$item['near']]));
        $p['question']['elim'] = array_map(fn($i) => array_search($i, $p['question']['order'], true), $drop);
        $p['question']['fifty_msg'] = 'Host AI eliminated two options that contradict the clue: "' . $item['hint'] . '"';
        $p['lifelines']['fifty_round'] = $p['round'];
        $msg = 'Lifeline AI removed two wrong answers.';
    } elseif ($kind === 'hint') {
        if ($p['question']['hint_shown']) {
            return [false, 'The hint is already showing for this question.'];
        }
        $p['question']['hint_shown'] = true;
        $msg = 'Hint revealed.';
    } else { // pass
        if ($p['lifelines']['pass_used']) {
            return [false, 'You have already used your Pass.'];
        }
        $p['lifelines']['pass_used'] = true;
        ai_issue_question($p);
        $msg = 'Question passed. Here is a new one.';
    }

    $p['ai_stats']['penalty']       += LIFELINE_COST[$kind];
    $p['ai_stats']['lifelines_used'] += 1;
    return [true, $msg . ' Penalty: $' . number_format(LIFELINE_COST[$kind]) . '.'];
}

// Accuracy per category: category => [right, total, pct]
function ai_category_accuracy(array $p): array {
    $out = [];
    foreach ($p['answers'] as $a) {
        $out[$a['cat']]['total'] = ($out[$a['cat']]['total'] ?? 0) + 1;
        $out[$a['cat']]['right'] = ($out[$a['cat']]['right'] ?? 0) + ($a['correct'] ? 1 : 0);
    }
    foreach ($out as $cat => $row) {
        $out[$cat]['pct'] = (int)round(100 * $row['right'] / $row['total']);
    }
    ksort($out);
    return $out;
}

// Weak Spot Analyzer: lowest accuracy (ties go to lower confidence, then name).
function ai_weak_spot(array $p): ?array {
    $acc = ai_category_accuracy($p);
    if (!$acc) {
        return null;
    }
    $best = null;
    foreach ($acc as $cat => $row) {
        $conf = $p['ai_stats']['conf'][$cat] ?? 50;
        if ($best === null
            || $row['pct'] < $best['pct']
            || ($row['pct'] === $best['pct'] && $conf < $best['conf'])) {
            $best = ['cat' => $cat, 'pct' => $row['pct'], 'right' => $row['right'], 'total' => $row['total'], 'conf' => $conf];
        }
    }
    $best['tips'] = ai_tips($best['cat']);
    return $best;
}

function ai_avg_time(array $p): float {
    if (!$p['answers']) {
        return 0.0;
    }
    return round(array_sum(array_column($p['answers'], 'secs')) / count($p['answers']), 1);
}

// One-word profile. Lifeline use is checked first.
function ai_profile(array $p): string {
    if ($p['ai_stats']['lifelines_used'] >= 2) {
        return 'Lifeline Lover';
    }
    if ($p['risk_profile'] >= 0.7) {
        return 'Risk Taker';
    }
    return 'Steady Climber';
}
