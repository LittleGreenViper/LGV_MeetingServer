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
\returns true, if successful.
*/
function initialize_database(   $pdo_instance,  ///< REQUIRED: The PDO instance for this transaction.
                                $dbTableName    ///< REQUIRED: The name of the table to receive the initialization
                            ) {
    $sql_init = file_get_contents(dirname(dirname(__FILE__)).'/Sources/config/sql/LGV_MeetingServer-MySQL.sql');
    
    global $config_file_path;
    include($config_file_path);

    try {
        echo("<h3>Creating the $dbTableName table.</h3>");
        $sql_init = str_replace("`TABLE-NAME`", "`$dbTableName`", $sql_init);
        if ( $pdo_instance->preparedStatement($sql_init) ) {
            $data = $pdo_instance->preparedStatement("SELECT * FROM `$dbTableName`", [], true);
            if ( isset($data) && is_array($data) && 0 == count($data) ) {
                echo('<h3 style="color:green">SUCCESSFUL DATABASE INIT:</h3>');
                return true;
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
    
    return false;
}
?>