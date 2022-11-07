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
    define( 'LGV_DB_CATCHER', 1 );
    
    $sql_init = file_get_contents(dirname(__FILE__).'/config/LGV_MeetingServer-SQL.sql');
    require_once(dirname(__FILE__).'/config/LGV_MeetingServer-Config.php');
    require_once(dirname(dirname(__FILE__)).'/Sources/LGV_MeetingServer_PDO.class.php');

    $g_PDOInstance = NULL;    
    
?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>LGV_MeetingServer Test Host</title>
    </head>
    <body>
        <h1>LGV_MeetingServer Test Host</h1>
        <pre><?php
            echo('<h2>Initializing to fresh database.</h2>');

            try {
                $g_PDOInstance = new LGV_MeetingServer_PDO($_dbName, $_dbLogin, $_dbPassword, $_dbType, $_dbHost, $_dbPort);
                $g_PDOInstance->preparedExec($sql_init);
                echo('<h3>WOOT!</h3>');
            } catch (Exception $exception) {
                echo('<h3 style="color:red">ERROR WHILE TRYING TO INITIALIZE THE DATABASES</h3>');
                echo('<pre>'.htmlspecialchars(print_r($exception->getMessage(), true)).'</pre>');
            }
        ?></pre>
    </body>
</html>
