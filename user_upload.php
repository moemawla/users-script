<?php

error_reporting(E_ERROR | E_PARSE);

const DATABASE_NAME = 'Catalyst';

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
    logMessage('Please provide the credentials for connecting to the database');
    exit;
}

// create and check database connection
$dbConnection = new mysqli($argumentList['h'], $argumentList['u'], $argumentList['p'], DATABASE_NAME);

if ($dbConnection->connect_error) {
    logMessage('Connection failed: ' . $dbConnection->connect_error);
    exit;
}

// create the users table if it doesnt exist
if (array_key_exists('create_table', $argumentList)) {
    createTable($dbConnection);
    closeAndExit($dbConnection);
}

// parse the CSV file
if (array_key_exists('file', $argumentList)) {
    parseCSV($argumentList['file']);
    closeAndExit($dbConnection);
}


/**
 * declare helper functions
 */

 function closeAndExit(mysqli $connection) {
    $connection->close();
    exit;
 }

function logMessage(string $message) {
    fwrite(STDOUT, $message);
}

function createTable(mysqli $connection) {
    $query = <<<SQL
    CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL,
    surname VARCHAR(30) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE
    )
SQL;

    if (true === $connection->query($query)) {
        logMessage('Users table created successfully');
        return;
    }
    logMessage('Failed creating the users table');
}

function parseCSV(string $fileName) {
    $handle = fopen($fileName, 'r');

    if (!$handle) {
        logMessage('Failed opening the CSV file');
        return;
    }

    // skip the fields row
    fgetcsv($handle);

    // start processing data
    while (($data = fgetcsv($handle)) !== FALSE) {
        // TODO: handle record
    }

    fclose($handle);
}
