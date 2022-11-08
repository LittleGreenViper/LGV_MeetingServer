<?php
/***************************************************************************************************************************/
/**
    This is the main entrypoint file for the LGV_MeetingServer basic server-level unit tests.
    
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
defined( 'LGV_TEST' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.
define( 'LGV_DB_CATCHER', 1 );

require_once(dirname(dirname(__FILE__)).'/Sources/LGV_MeetingServer_PDO.class.php');

/***********************/
/**
*/
function initialize_database() {
    $g_PDOInstance = NULL;    
    require_once(dirname(__FILE__).'/config/LGV_MeetingServer-Config.php');

    try {
        $sql_init = file_get_contents(dirname(dirname(__FILE__)).'/Sources/sql/LGV_MeetingServer-SQL.sql');
        $sql_data = file_get_contents(dirname(__FILE__).'/config/LGV_MeetingServer-SQL-Rows.sql');

        $g_PDOInstance = new LGV_MeetingServer_PDO($_dbName, $_dbLogin, $_dbPassword, $_dbType, $_dbHost, $_dbPort);
        if ( $g_PDOInstance->preparedStatement($sql_init) ) {
            $data = $g_PDOInstance->preparedStatement("SELECT * FROM lgv_ms_meetings", [], true);
            if ( isset($data) && is_array($data) && 0 == count($data) ) {
                echo('<h3 style="color:green">SUCCESSFUL DATABASE INIT:</h3>');
                echo('<pre>'.htmlspecialchars(print_r($data, true)).'</pre>');
                if ( $g_PDOInstance->preparedStatement($sql_data) ) {
                    $data = $g_PDOInstance->preparedStatement("SELECT * FROM lgv_ms_meetings", [], true);
    
                    if ( isset($data) && is_array($data) && count($data) ) {
                        echo('<h3 style="color:green">SUCCESSFUL DATA INIT:</h3>');
                        echo('<pre>'.htmlspecialchars(print_r($data, true)).'</pre>');
                    } else {
                        echo('<h3 style="color:red">DATA INIT CHECK FAILED!</h3>');
                    }
                } else {
                    echo('<h3 style="color:red">DATA INIT FAILED!</h3>');
                }
            } else {
                echo('<h3 style="color:red">DATABASE INIT CHECK FAILED!</h3>');
            }
        } else {
            echo('<h3 style="color:red">DATABASE INIT FAILED!</h3>');
        }
    } catch (Exception $exception) {
        echo('<h3 style="color:red">ERROR WHILE TRYING TO INITIALIZE THE DATABASES</h3>');
        echo('<pre>'.htmlspecialchars(print_r($exception->getMessage(), true)).'</pre>');
    }
}
?>