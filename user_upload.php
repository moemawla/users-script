<?php

error_reporting(E_ERROR | E_PARSE);
mysqli_report(MYSQLI_REPORT_ALL);

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
    logMessage(sprintf('Connection failed: %s', $dbConnection->connect_error));
    exit;
}

// create the users table if it doesnt exist
if (array_key_exists('create_table', $argumentList)) {
    createTable($dbConnection);
    closeAndExit($dbConnection);
}

// parse the CSV file
if (array_key_exists('file', $argumentList)) {
    parseCSV(
        $dbConnection,
        $argumentList['file'],
        array_key_exists('dry_run', $argumentList)
    );
    closeAndExit($dbConnection);
}


/**
 * declare helper functions
 */

 function closeAndExit(mysqli $connection): void {
    $connection->close();
    exit;
 }

function logMessage(string $message): void {
    fwrite(STDOUT, $message.PHP_EOL);
}

function createTable(mysqli $connection): void {
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

function parseCSV(mysqli $connection, string $fileName, bool $dryRun): void {
    $handle = fopen($fileName, 'r');

    if (!$handle) {
        logMessage('Failed opening the CSV file');
        return;
    }

    // skip the fields row
    fgetcsv($handle);

    // start processing data
    while (($data = fgetcsv($handle)) !== FALSE) {
        handleSingleUser(
            $data[0],
            $data[1],
            $data[2],
            $dryRun ? null : $connection,
        );
    }

    fclose($handle);
}

function handleSingleUser(
    string $name,
    string $surname,
    string $email,
    ? mysqli $connection
): void {
    $name = ucfirst(strtolower(trim($name)));
    $surname = ucfirst(strtolower(trim($surname)));
    $email = strtolower(trim($email));

    // validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logMessage(sprintf('Invalid email address: %s', $email));
        return;
    }

    if (!$connection) {
        return;
    }

    // prepare SQL statement to prevent SQL injections
    $statement = $connection->prepare('INSERT INTO users (name, surname, email) VALUES (?, ?, ?)');
    $statement->bind_param('sss', $name, $surname, $email);

    // execute SQL statement
    try {
        $statement->execute();
    } catch (mysqli_sql_exception $e) {
        logMessage(sprintf('Database insert error: %s', $e->getMessage()));
    }
}
