# Users script

This script is used to parse a CSV file of user records and to insert them into the database table "users".

## Requirements
- PHP version 7.4 or higher must be installed, with the mysql package. On Ubuntu 20.04 you might need to run the following command after installing PHP 7.4: ```sudo apt-get install php7.4-mysql```.

- MYSQL 8.0 or higher must be installed, and a database schema named "Catalyst" is already created.

- A MYSQL user that has full permissions on the "Catalyst" database.

<br/>

## Running the script

The following are the available directives that can be used when running the script:

    -u – MySQL username [Required]

    -p – MySQL password [Required]

    -h – MySQL host [Required]

    --create_table – This will create the "users" table in the database if it doesn't exist.

    --file=[csv file name] – This is the name of the CSV to be parsed.

    --dry_run – This is used with the --file directive in case you want to run the script but not insert into the database. All other functions will be executed, but the database won't be altered.

    --help – Shows an explanation of the script and a list of available directives that can be used with it.

<br/>

## Assumptions and Notes

- The script assumes that the CSV file records have the following fields in the stated order: name, surname and email. The script only validates that each record has 3 fields of data and that the third field is a valid email.
- The script inserts the user records into the database one-by-one instead of doing bulk inserts. The reason this wasn't optimized is because a certain record may fail to be inserted as a result of a duplicate value for the "email" field, which will prevent the rest of the batch from being inserted. An optimization for this would be preferrable, but it is out of the scope of this task.
