<?php

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

if (array_key_exists('create_table', $argumentList)) {
    createTable($dbConnection);
    exit;
}

// close database connection
$dbConnection->close();

/**
 * declare helper functions
 */

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
