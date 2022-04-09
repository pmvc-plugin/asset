<?php

$path = __DIR__ . '/../vendor/autoload.php';
include $path;

\PMVC\Load::plug(
    [
        'unit' => null,
    ],
    [__DIR__ . '/../../']
);
\PMVC\l(__DIR__.'/resources/FakeView.php');
