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