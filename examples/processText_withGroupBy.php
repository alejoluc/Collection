<?php

/*
 * In this example, we want to turn a comma-separated text file into a Collection, grouped by one of
 * the values (continent), and at the end convert it into valid JSON
 * 
 * The JSON generated should be similar to the JSON generated in the "processText_withReduce.php" example, with the
 * difference being that we are not creating objects here (or an associative array, for that matter), to keep it as
 * simple as possible on purpose, so the values in this case are not going to be accompanied by a key or
 * field name (continent, country, language)
 */

require dirname(__DIR__) . '/src/Collection.php';

use alejoluc\Collection\Collection;

mb_internal_encoding('UTF-8');

$dataSource = new Collection(file(__DIR__ . '/processText_rawSource.txt'));

$continents = $dataSource->map('trim')
                         ->map(function($line){ return explode(',', $line); })
                         ->groupBy('0');


// Notice that the Collection object can be converted to JSON directly
$outputJSON = json_encode($continents, JSON_PRETTY_PRINT);
