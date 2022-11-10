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
    set_time_limit(300);
    
    global $config_file_path;
    $config_file_path = dirname(__FILE__).'/config/LGV_MeetingServer-Config.php';
    include($config_file_path);

    define( 'LGV_MeetingServer_Files', 1 );
    require_once(dirname(dirname(__FILE__)).'/Sources/LGV_MeetingServer.php');

    $query = explode("&", strtolower($_SERVER["QUERY_STRING"]));
    
    header('Content-Type: application/json');

    if ( in_array("initialize", $query) ) {
        $pdo_instance = new LGV_MeetingServer_PDO($_dbName, $_dbLogin, $_dbPassword, $_dbType, $_dbHost, $_dbPort);
        if ( $pdo_instance && initialize_meta_database($pdo_instance) ) {
            echo('1');
        } else {
            echo('0');
        }
    } elseif ( in_array("update", $query) ) {
        set_time_limit(300);
        $start = microtime(true);
        $number_of_meetings = update_database(true);
        $exchange_time = microtime(true) - $start;
        echo("{\"number_of_meetings\": $number_of_meetings,\"time_in_seconds\":$exchange_time}");
    } else {
        $geocenter_lng = NULL;
        $geocenter_lat = NULL;
        $geo_radius = NULL;
        $geo_min = NULL;
        $geo_weekdays = NULL;
        $geo_start_time = NULL;
        $geo_org = NULL;
        $ids = NULL;
        
        foreach ( $query as $parameter ) {
            [$key, $value] = explode("=", $parameter);
            if ( "geocenter_lng" == $key && !empty($value) ) {
                $geocenter_lng = floatval($value);
            } elseif ( "geocenter_lat" == $key && !empty($value) ) {
                $geocenter_lat = floatval($value);
            } elseif ( "geo_radius" == $key && !empty($value) ) {
                $geo_radius = floatval($value);
            } elseif ( "geo_min" == $key && !empty($value) ) {
                $geo_min = intval($value);
            } elseif ( "geo_weekdays" == $key && !empty($value) ) {
                $weekdays = explode(",", $value);
                
                if ( !empty($weekdays) ) {
                    $geo_weekdays = array_map('intval', $weekdays);
                }
            } elseif ( "geo_start_time" == $key && !empty($value) ) {
                $geo_start_time = intval($value);
            } elseif ( "geo_org" == $key && !empty($value) ) {
                $geo_org = $value;
            } elseif ( "ids" == $key && !empty($value) ) {
                $id_temp = explode("),(", trim($value, "()"));
                
                if ( !empty($id_temp) ) {
                    $ids = [];
                    foreach ( $id_temp as $id_pair ) {
                        $id_pair_temp = explode(",", $id_pair);
                        if ( 2 == count($id_pair_temp) ) {
                            $server_id = intval($id_pair_temp[0]);
                            $meeting_id = intval($id_pair_temp[1]);
                            
                            array_push($ids, [$server_id, $meeting_id]);
                        }
                    }
                }
            }
        }
        
        echo(query_database($geocenter_lng, $geocenter_lat, $geo_radius, $geo_min, $geo_weekdays, $geo_start_time, $geo_org, $ids));
    }
