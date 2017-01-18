<?php

/*
 * In this example, we want to turn a comma-separated text file into a valid PHP array of objects, grouped
 * by one of the values (continent), and at the end we want to convert it into valid JSON
 *
 * The JSON generated should be similar to the JSON generated in the "processText_withGroupBy.php" example, with
 * the difference being that we are creating objects here, so the values in this case are going to be accompanied
 * by a key or field name (continent, country, language)
 */

require dirname(__DIR__) . '/src/Collection.php';

use alejoluc\Collection\Collection;

mb_internal_encoding('UTF-8');

$dataSource = new Collection(file(__DIR__ . '/processText_rawSource.txt'));

$continents = $dataSource->map('trim')                                      // Using a native function by name
                 ->map(function($line){ return explode(',', $line); })      // Split each line by the commas
                 ->reduce(function($resultArray, $countryData){
                    list($continent, $country, $language) = $countryData;

                    $oCountry = new stdClass;
                    $oCountry->continent = $continent;
                    $oCountry->name      = $country;
                    $oCountry->language  = $language;

                    if (!array_key_exists($continent, $resultArray)) {
                        $resultArray[$continent] = [];
                    }

                    $resultArray[$continent][] = $oCountry;
                    return $resultArray;
                 }, []);


$outputJSON = json_encode($continents);
