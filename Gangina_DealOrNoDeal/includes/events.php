<?php
// includes/events.php - random "market events" that nudge the Banker's offer.

function dond_event_list(): array {
    return [
        ['name' => 'Market Surge',  'multiplier' => 1.10, 'message' => 'Markets are up. The Banker feels generous.'],
        ['name' => 'Recession',     'multiplier' => 0.90, 'message' => 'A recession hits. The Banker tightens the purse.'],
        ['name' => 'Banker Bonus',  'multiplier' => 1.05, 'message' => 'The Banker had a good quarter and adds a little extra.'],
        ['name' => 'Audit',         'multiplier' => 0.95, 'message' => 'An audit is under way. The Banker plays it safe.'],
    ];
}

// About one offer in four is affected by an event.
function dond_get_random_event(): ?array {
    if (random_int(1, 100) > 25) {
        return null;
    }
    $events = dond_event_list();
    return $events[random_int(0, count($events) - 1)];
}

function dond_apply_event_effect(float $average, array $event): float {
    return $average * $event['multiplier'];
}
