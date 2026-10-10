<?php
// These are the values you are likely to change, for your installation.

$_dbLogin = < YOUR DB LOGIN ID >;
$_dbPassword = < YOUR DB LOGIN PASSWORD >;
$_dbName = < YOUR DB NAME >;

// This is a flag, that, if set to true, will force updates to ONLY occur through the command line (cron jobs are an example).
$_use_cli_only_for_update = < TRUE OR FALSE >;

// This is the period between when the update script simply returns, after doing nothing, and when it updates its servers.
// Default is 4 hours.
$_updateIntervalInSeconds = (4 * 3600);

// Use the existing LGV_TZ_Lookup instance's private config.php (database settings only).
// PHP must be able to read this file. Leave NULL to disable geographic timezone lookup.
// Composer dependencies belong at the meeting server root, beside Sources/.
$_timezoneConfigFile = NULL; // Example: '/var/www/lgv-tz-server/config.php';

// Alternatively, set this array and leave $_timezoneConfigFile as NULL.
// This database is independent of the meeting database: it can use PostgreSQL while meetings use MySQL.
// The existing timezones table must already be loaded. Only SELECT access is needed.
$_timezoneDatabase = NULL;
// $_timezoneDatabase = [
//     'database' => 'tz_database',
//     'user' => 'tz_reader',
//     'password' => 'your-tz-db-password',
//     'driver' => 'pgsql',
//     'host' => '127.0.0.1',
//     'port' => 5432
// ];

// The declarations below this line are unlikely to be changed.
// ------------------------------------------------------------

// These are the names of the tables.
// Usually, there should be no need to change them, unless you have a shared database (not recommended, on general principle).
$_dbTableName = "data";
$_dbTempTableName = "temp";
$_dbMetaTableName = "meta";

$_dbHost = "localhost";
$_dbType = "mysql";
$_dbPort = 3306;
?>
