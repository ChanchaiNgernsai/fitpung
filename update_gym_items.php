<?php
use App\Models\GymLayout;

$gym = GymLayout::find(4);
if (!$gym) {
    echo "Gym not found.\n";
    exit;
}

$items = collect($gym->items);

// 1. Remove broken items (RecumbentCycle, LatPulldown)
$items = $items->filter(function ($item) {
    if (str_contains($item['src'], 'RecumbentCycle'))
        return false;
    if (str_contains($item['src'], 'LatPulldown'))
        return false;
    // Also remove if type is cycle since we deleted it from config
    if (isset($item['type']) && $item['type'] === 'cycle')
        return false;
    return true;
});

// 2. Define new items
$newItems = [
    [
        'id' => (int) (microtime(true) * 1000) + 1,
        'type' => 'incline_bench',
        'name' => 'Incline Bench Press',
        'src' => '/images/equipment/DeclineBenchPress.svg',
        'x' => 450,
        'y' => 650,
        'width' => 150,
        'height' => 180,
        'rotation' => 0
    ],
    [
        'id' => (int) (microtime(true) * 1000) + 2,
        'type' => 'incline_bench',
        'name' => 'Incline Bench Press',
        'src' => '/images/equipment/DeclineBenchPress.svg',
        'x' => 580,
        'y' => 650,
        'width' => 150,
        'height' => 180,
        'rotation' => 0
    ],
    [
        'id' => (int) (microtime(true) * 1000) + 3,
        'type' => 'leg_press',
        'name' => 'Leg Press',
        'src' => '/images/equipment/LegPress.svg',
        'x' => 840,
        'y' => 550,
        'width' => 120,
        'height' => 220,
        'rotation' => 0
    ]
];

// Add new items
foreach ($newItems as $item) {
    $items->push($item);
}

$gym->items = $items->values()->all();
$gym->save();

echo "Updated gym layout: Removed broken items, added 2 DeclineBenches and 1 LegPress.";
