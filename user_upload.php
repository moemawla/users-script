<?php

$shortOptions = 'u:p:h:';

$longOptions = [
    'create_table',
    'file:',
    'dry_run',
    'help'
];

$argumentList = getopt($shortOptions, $longOptions);

// make sure the database credentials are passed
if (!array_key_exists('u', $argumentList) ||
    !array_key_exists('p', $argumentList) ||
    !array_key_exists('h', $argumentList)
) {
    die('Please provide the credentials for connecting to the database');
}

// create and check database connection
$conn = new mysqli($argumentList['h'], $argumentList['u'], $argumentList['p']);

if ($conn->connect_error) {
  die('Connection failed: ' . $conn->connect_error);
}
