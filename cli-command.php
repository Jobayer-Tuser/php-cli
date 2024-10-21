#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use App\Controllers\CsvToJsonCommand;
use Symfony\Component\Console\Application;

/*
$longOptions = ['from:', 'to::'];
$options = getopt('', $longOptions);
$contents = file_get_contents($options['from']);
file_put_contents($options['to'], $contents);
*/


$application = new Application();

$application->add(new CsvToJsonCommand());

$application->run();