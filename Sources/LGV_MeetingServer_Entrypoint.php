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
defined( 'LGV_MeetingServer_Files' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.
set_time_limit(300);

global $config_file_path;
include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.

require_once(dirname(__FILE__).'/LGV_MeetingServer.php');

$query = explode("&", strtolower($_SERVER["QUERY_STRING"]));

header('Content-Type: application/json');

set_time_limit($_query_time_limit_in_seconds);

$query = explode("&", strtolower($_SERVER["QUERY_STRING"]));

if ( in_array("update", $query) && in_array($_update_key, $query) ) {
    $start = microtime(true);
    $number_of_meetings = update_database(true, in_array("physical_only", $query));
    $exchange_time = microtime(true) - $start;
    if ( 0 < $number_of_meetings ) {
        echo("{\"number_of_meetings\": $number_of_meetings,\"time_in_seconds\":$exchange_time}");
    } else {
        echo("0");
    }
} elseif ( !in_array("update", $query) ) {
    $geocenter_lng = NULL;
    $geocenter_lat = NULL;
    $geo_radius = NULL;
    $minimum_found = 0;
    $weekdays = [];
    $start_time = 0;
    $org_key = NULL;
    $ids = [];
    $page = 0;
    $page_size = -1;
    
    foreach ( $query as $parameter ) {
        $splodie = explode("=", $parameter);
        if ( is_array($splodie) && 1 < count($splodie) ) {
            $key = $splodie[0];
            $value = $splodie[1];
            if ( isset($value) ) {
                switch ( $key ) {
                    case "geocenter_lng":
                        $geocenter_lng = floatval($value);
                    break;
                        
                    case "geocenter_lat":
                        $geocenter_lat = floatval($value);
                    break;
                        
                    case "geo_radius":
                        if ( empty($value) ) {
                            break;
                        }
                        $geo_radius = floatval($value);
                        break;
                        
                    case "minimum_found":
                        $minimum_found = abs(intval($value));
                    break;
                        
                    case "weekdays":
                        $weekdays = explode(",", $value);
            
                        if ( !empty($weekdays) ) {
                            $weekdays = array_map('intval', $weekdays);
                        }
                    break;
                        
                    case "start_time":
                        $start_time = abs(intval($value));
                    break;
                        
                    case "org_key":
                        if ( empty(trim($value)) ) {
                            break;
                        }
                        $org_key = trim($value);
                    break;
                        
                    case "ids":
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
                    break;
                        
                    case "page":
                        $page = abs(intval($value));
                    break;
                        
                    case "page_size":
                        $page_size = max(-1, intval($value));
                    break;
                }
            }
        }
    }
    
    ob_start('ob_gzhandler');
    echo(query_database($geocenter_lng, $geocenter_lat, $geo_radius, $minimum_found, $weekdays, $start_time, $org_key, $ids, $page, $page_size));
    ob_end_flush();
}
