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

// check if no valid arguments are passed
if (!array_key_exists('help', $argumentList) &&
    !array_key_exists('file', $argumentList) &&
    !array_key_exists('create_table', $argumentList)
) {
    outputMessage('You need to specify a directive, call the script with "--help" for more explanation');
    exit;
}

// show help
if (array_key_exists('help', $argumentList)) {
    showHelp();
    exit;
}

// make sure the database credentials are passed
if (!array_key_exists('u', $argumentList) ||
    !array_key_exists('p', $argumentList) ||
    !array_key_exists('h', $argumentList)
) {
    outputMessage('Please provide the credentials for connecting to the database');
    exit;
}

// create and check database connection
$dbConnection = new mysqli($argumentList['h'], $argumentList['u'], $argumentList['p'], DATABASE_NAME);

if ($dbConnection->connect_error) {
    outputMessage(sprintf('Connection failed: %s', $dbConnection->connect_error));
    exit;
}

// create the users table
if (array_key_exists('create_table', $argumentList)) {
    createTable($dbConnection);
    $dbConnection->close();
    exit;
}

// parse the CSV file
if (array_key_exists('file', $argumentList)) {
    parseCSV(
        $dbConnection,
        $argumentList['file'],
        array_key_exists('dry_run', $argumentList) // check if dry_run was requested
    );
    $dbConnection->close();
    exit;
}

// *****************************
// declare the helper functions
// *****************************

/**
 * Outputs the message to STDOUT
 */
function outputMessage(string $message): void {
    fwrite(STDOUT, $message.PHP_EOL);
}

/**
 * Outputs the help text
 */
function showHelp(): void {
    $message = <<<MSG

This script is used to parse a CSV file of user records and to insert them into the database table "users".

The following are the available directives that can be used:

    -u – MySQL username [Required]

    -p – MySQL password [Required]

    -h – MySQL host [Required]

    --create_table – This will create the "users" table in the database if it doesn't exist.

    --file=[csv file name] – This is the name of the CSV to be parsed.

    --dry_run – This is used with the --file directive in case you want to run the script but not
    insert into the database. All other functions will be executed, but the database won't be altered.

    --help – Shows an explanation of the script and a list of available directives that can be used with it.

MSG;

    outputMessage($message);
}

/**
 * Creats the "users" table in the database if it doesn't exist
 */
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
        outputMessage('Users table created successfully');
        return;
    }
    outputMessage('Failed creating the users table');
}

/**
 * Opens and parses the CSV file provided by name.
 * 
 * The CSV file must have the fields in order: name, surname and email.
 * 
 * Outputs an error message if the file could not be opened.
 * 
 * For each record in the file, it calls the handleSingleUser function which will 
 * handle saving the user to the DB.
 */
function parseCSV(mysqli $connection, string $fileName, bool $dryRun): void {
    $handle = fopen($fileName, 'r');

    if (!$handle) {
        outputMessage('Failed opening the CSV file');
        return;
    }

    // skip the fields row
    fgetcsv($handle);

    // start processing data
    while (($data = fgetcsv($handle)) !== FALSE) {
        try {
            handleSingleUser(
                $data[0],
                $data[1],
                $data[2],
                $dryRun ? null : $connection,
            );
        } catch (mysqli_sql_exception $e) {
            outputMessage(sprintf('Database error: %s', $e->getMessage()));
            break;
        }
    }

    fclose($handle);
}

/**
 * Handles inserting the user to the DB table "users".
 * 
 * Sanitizes values by trimming whitespaces and applying proper casing.
 * 
 * Outputs an error message, and then halts execution, if the provided email is not valid.
 * 
 * If NULL is passed for $connection, inserting into the DB is skipped and only the validation
 * is performed.
 * 
 * @throws mysqli_sql_exception
 */
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
        outputMessage(sprintf('Invalid email address: %s', $email));
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
        outputMessage(sprintf('Database insert error: %s', $e->getMessage()));
    }
}
