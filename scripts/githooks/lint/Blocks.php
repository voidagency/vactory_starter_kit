<?php

use PHP_CodeSniffer\Runner;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Reporter;
use PHP_CodeSniffer\Files\DummyFile;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

require_once dirname(dirname(dirname(__DIR__))) . '/vendor/squizlabs/php_codesniffer/autoload.php';

$finder = new Finder();
$finder->files()
    ->ignoreUnreadableDirs()
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->filter(function (SplFileInfo $file) {
        return $file->isReadable();
    })
    ->name(sprintf('/\\.(%s)$/', implode('|', ['php'])))
    ->in(dirname(dirname(dirname(__DIR__))) . '/modules/*/src/Plugin/Block'); // todo: add profile

if (!$finder->hasResults()) {
    return 0;
}

$files = [];
foreach ($finder as $file) {
    $absoluteFilePath = $file->getRealPath();
    $files[] = $absoluteFilePath;
}

if (empty($files)) {
    return 0;
}

// Set phpcs standard to Drupal you can change it to vendor/drupal/coder/coder_sniffer/DrupalPractice to stric validation.
$config = [
    'standard' => __DIR__ . '/BlocksStandard',
];
$runner = new Runner();
Config::setConfigData('installed_paths', __DIR__ . '/BlocksStandard');
$runner->config = new Config(['dummy'], false);
$runner->config->explain = TRUE;

$runner->config->standards = array($config['standard']);
$runner->init();

$runner->config->reports      = array('summary' => null, 'full' => null);
$runner->config->verbosity    = 0;
$runner->config->showProgress = false;
$runner->config->interactive  = false;
$runner->config->cache        = false;
$runner->config->showSources  = true;

// Create the reporter, using the hard-coded settings from above.
$runner->reporter = new Reporter($runner->config);

foreach ($files as $file_path) {
    $file = new DummyFile(file_get_contents($file_path), $runner->ruleset, $runner->config);
    // var_dump(file_get_contents($file_path));
    $file->path = $file_path;
    $runner->processFile($file);
}

if ($runner->reporter->totalErrors !== 0 || $runner->reporter->totalWarnings !== 0) {
    $runner->reporter->printReports();
    die(1);
}
