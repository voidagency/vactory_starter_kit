<?php

$composer = json_decode(file_get_contents('composer.json'), true);
// Add path repository at the beginning so it takes precedence
array_unshift($composer['repositories'], [
    'type' => 'path',
    'url' => '/var/www/html/profiles/contrib/vactory_starter_kit',
    'options' => ['symlink' => true],
    'canonical' => true
]);
$composer['require']['voidagency/vactory_starter_kit'] = '@dev';
file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));