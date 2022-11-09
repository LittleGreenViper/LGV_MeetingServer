<?php
/***************************************************************************************************************************/
/**
    This is the main entrypoint file for the LGV_MeetingServer server.
    
    © Copyright 2022, <a href="https://littlegreenviper.com">Little Green Viper Software Development LLC</a>
    
    LICENSE:
    
    MIT License
    
    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
    files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
    modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
    IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
    CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

    The Great Rift Valley Software Company: https://riftvalleysoftware.com
*/
defined( 'LGV_MeetingServer_Files' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

require_once(dirname(__FILE__).'/LGV_MeetingServer_BMLT.php');

defined( 'LGV_DB_CATCHER' ) or define( 'LGV_DB_CATCHER', 1 );

require_once(dirname(__FILE__).'/LGV_MeetingServer_PDO.class.php');

/****************************************************************************************************************************/
/**
 */
function update_database(   $dbFromTableName,       ///< The temporary table that we will be moving into place.
                            $dbToTableName,         ///< The original table that will be replaced.
                            $physical_only = false  ///< If true (default is false), then only meetings that have a physical location will be returned.
                        ) {
    global $config_file_path;
    include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.

    try {
        $pdoInstance = new LGV_MeetingServer_PDO($_dbName, $_dbLogin, $_dbPassword, $_dbType, $_dbHost, $_dbPort);
        $number_of_meetings = process_all_bmlt_server_meetings($pdoInstance, $dbFromTableName, $physical_only);
        $rename_sql = "DROP TABLE IF EXISTS `$dbToTableName`;RENAME TABLE `$dbFromTableName` TO `$dbToTableName`;";
        $pdoInstance->preparedStatement($rename_sql);
    
        return $number_of_meetings;
    } catch (Exception $exception) {
        return NULL;
    }
}