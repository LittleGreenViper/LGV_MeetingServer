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
    define( 'LGV_TEST', 1 );
    require_once(dirname(__FILE__).'/InitializeDatabase.php');
    define( 'LGV_MeetingServer_Files', 1 );
    require_once(dirname(dirname(__FILE__)).'/Sources/LGV_MeetingServer_BMLT.php');
?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>LGV_MeetingServer Test Host</title>
    </head>
    <body>
        <h1>LGV_MeetingServer Test Host</h1>
        <h2>Initializing to fresh database.</h2>
        <?php 
            initialize_database();
        ?>
        <h2>Reading BMLT Server List.</h2>
        <?php 
            $server_list = read_bmlt_server_list();
            foreach ( $server_list as $server ) {
                $id = $server->id;
                $name = $server->name;
                $rootURL = $server->rootURL;
                $dataURL = $rootURL."client_interface/json/?switcher=GetSearchResults&get_used_formats=1";
                $semanticURL = $rootURL."semantic";
                echo("\n<li><h3>".htmlspecialchars($name)." ($id)</h3>".'<ul><li><h4><a href="'.htmlspecialchars($semanticURL).'" target="_blank">Open Semantic Workshop</a></h4></li><li><h4><a href="'.htmlspecialchars($dataURL).'" target="_blank">Get Data Dump</a></h4></li></ul></li>');
            }
        ?>
        </ul>
    </body>
</html>
