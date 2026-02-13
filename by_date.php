<?php

$packages = json_decode(file_get_contents(__DIR__ . '/composer.lock'), true)['packages'];

foreach ($packages as &$p) {
    $p['time_obj'] = new DateTimeImmutable($p['time']);
}
unset($p);

usort($packages, fn ($a, $b) => $a['time_obj'] <=> $b['time_obj']);

foreach ($packages as $p) {
    if (
        !str_starts_with($p['name'], 'arokettu/') && !str_starts_with($p['name'], 'peso/') ||
        $p['name'] === 'arokettu/random-polyfill'
    ) {
        continue;
    }
    echo sprintf("%s    %-34s %s\n", $p['time_obj']->format('Y-m-d'), $p['name'], $p['version']);
}
