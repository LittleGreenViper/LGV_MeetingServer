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
    global $config_file_path;
    $config_file_path = dirname(__FILE__).'/config/LGV_MeetingServer-Config.php';
    include($config_file_path);

    define( 'LGV_MeetingServer_Files', 1 );
    require_once(dirname(dirname(__FILE__)).'/Sources/LGV_MeetingServer.php');
    
?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>LGV_MeetingServer Test Host</title>
    </head>
    <body>
        <h1>LGV_MeetingServer Test Host</h1>
        <?php 
            echo("<h2>Initializing meta table.</h2>");
            $pdo_instance = new LGV_MeetingServer_PDO($_dbName, $_dbLogin, $_dbPassword, $_dbType, $_dbHost, $_dbPort);
            if ( $pdo_instance ) {
                if ( initialize_meta_database($pdo_instance) ) {
                    echo("<h2>Initializing to fresh database.</h2>");
                    if ( initialize_main_database($pdo_instance, $_dbTempTableName) ) {
                        echo("<h2>Reading BMLT Server List (Physical-Only).</h2>");
                        set_time_limit(300);
                        $start = microtime(true);
                        $number_of_meetings = update_database(true);
                        $exchange_time = microtime(true) - $start;
                        echo("<h4>$number_of_meetings meetings processed in $exchange_time seconds.</h4>");
                    }
            
                    echo("<h2>Geo query Test</h2>");
                    $response = geo_query_database(-73.3432, 40.9009, 10);
                    echo("Search Results:<pre>".htmlspecialchars(print_r($response, true))."</pre>");

                } else {
                    echo('<h3 style="color:red">TEMP DATABASE INIT FAILED!</h3>');
                }
            } else {
                echo('<h3 style="color:red">META DATABASE INIT FAILED!</h3>');
            }
        ?>
        </ul>
    </body>
</html>
