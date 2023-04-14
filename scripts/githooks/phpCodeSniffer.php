<?php

use PHP_CodeSniffer\Runner;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Reporter;
use PHP_CodeSniffer\Files\DummyFile;
require_once dirname(__DIR__).'../../vendor/squizlabs/php_codesniffer/autoload.php';


$files = [];
if (isset($argc)) {
	for ($i = 1; $i < $argc; $i++) {
		$files[] = $argv[$i];
    }
    if (empty($files)) {
        return 0;
    }
    // Set phpcs standard to Drupal you can change it to vendor/drupal/coder/coder_sniffer/DrupalPractice to stric validation.
    $config = [
        'standard' => 'vendor/drupal/coder/coder_sniffer/Drupal',
    ];
    $runner = new Runner();
    $runner->config = new Config(['dummy'], false);
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

    foreach ( $files as $file_path ) {
        $file = new DummyFile( file_get_contents( $file_path ), $runner->ruleset, $runner->config );
        $file->path = $file_path;
        
        $runner->processFile( $file );
    }
    if ($runner->reporter->totalErrors !== 0 || $runner->reporter->totalWarnings !== 0) {
        $runner->reporter->printReports();
        die(1);
    }
}
