#!/usr/bin/env php
<?php

use Symfony\Component\Filesystem\Filesystem;

try {

    require __DIR__ . '/vendor/autoload.php';

    $fs = new Filesystem();

    chdir(__DIR__);

    $composerJson = json_decode(file_get_contents('composer.json'), true);
    $composerLock = json_decode(file_get_contents('composer.lock'), true);

    $packageData = array_reduce($composerLock['packages'], function ($pkgs, $p) {
        $pkgs[$p['name']] = $p;
        return $pkgs;
    }, []);

    $packages = array_keys($composerJson['require']);

    $skip = [
        'arokettu/composer-license-manager', // skip because of platform check
        'arokettu/random-polyfill',
    ];

    $packages = array_diff($packages);

    foreach ($packages as $p) {
        chdir(__DIR__);

        echo "Testing ", $p, '... ';

        $url = $packageData[$p]['source']['url'];

        if (is_dir('run_tests/' . $p)) {
            chdir('run_tests/' . $p);

            run("git pull 2>&1", $output) === 0 || die("git error\n" . implode("\n", $output));
            echo 'updated... ';
        } else {
            $fs->mkdir('run_tests/' . $p);

            chdir('run_tests/' . $p);

            $urlSh = escapeshellarg($url);

            run("git clone $urlSh . 2>&1") === 0 || die("git error\n");
            echo 'cloned... ';
        }

        run('composer update --no-interaction 2>&1') === 0 || die("composer error\n");
        echo 'installed... ';

        if (in_array($p, $skip, true)) {
            echo 'skipped!', PHP_EOL;
            continue;
        }

        $testExec = $p === 'arokettu/is-resource' ? 'tests/tests.sh' : 'vendor/bin/phpunit';

        if (!file_exists($testExec)) {
            echo 'no tests!', PHP_EOL;
            continue;
        }

        if (run('/usr/bin/env php ' . $testExec, $output) === 0) {
            echo 'successful!', PHP_EOL;
            continue;
        }

        echo 'failure:', PHP_EOL;
        echo implode(PHP_EOL, $output);
        die("\n");
    }
} catch (Exception $e) {
    echo strval($e);
} catch (Throwable $e) {
    echo strval($e);
}

function run($command, &$output = null) {
    $output = null;
    ob_start();
    exec($command, $output, $ret);
    ob_end_clean();
    return $ret;
}
