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
/***************************************************************************************************************************/
/**
    \brief This file implements the lions' share of basic entrypoint duties for the server. It should be included into whatever index file you are using.
    
    NOTE: Your index file needs to declare a path to the config file, in the global variable $config_file_path.
 */
defined( 'LGV_MeetingServer_Files' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

require_once(dirname(__FILE__).'/LGV_MeetingServer.php');

// See if this is an update call.
if ( 'cli' == php_sapi_name() ) { // A call from the CLI means just do an update (for cron jobs).
    set_time_limit(600);    // We give ourselves a ridiculous amount of time, as this may take a while.
    $args = array_map('strtolower', $argv);
    if ( 2 == count($args) && '-h' == $args[1] ) {
        echo("Updates the LGV_MeetingServer Database.\n\tUsage:\t-h: Help (This display)\n\t\t-f: Force (Perform update, even if not scheduled)\n\t\t-p: Physical Meetings Only (Virtual-only meetings are ignored)\n\t\t-sv: Separate Organization for Virtual (Virtual meetings are stored, but given a different organization key. The -p flag is ignored)\n\t\tIf no arguments given, waits until the specified time has passed, and performs a -sv update of the database.\n");
    } else {
        $physical_only = in_array('-p', $args);
        $forced = in_array('-f', $args);
        $separate_virtual = 1 == count($args) || in_array('-sv', $args);
        echo intval(update_database($physical_only, $forced, $separate_virtual));
    }
} else {
    $query = explode("&", strtolower($_SERVER["QUERY_STRING"]));

    if ( in_array("update", $query) ) {
        $force = in_array("force", $query);
        $physical_only = in_array("physical_only", $query);
        $separate_virtual = in_array("separate_virtual", $query);

        set_time_limit(600);    // We give ourselves a ridiculous amount of time, as this may take a while.
        $start = microtime(true);
        $number_of_meetings = update_database($physical_only, $force, $separate_virtual);
        $exchange_time = microtime(true) - $start;
        if ( 0 < $number_of_meetings ) {
            header('Content-Type: application/json');
            echo("{\"number_of_meetings\": $number_of_meetings,\"time_in_seconds\":$exchange_time}");
        } else {
            header("HTTP/1.1 204 No Content");
        }
     // If we are a search, then we also see if we need a query key.
    } elseif ( !in_array("update", $query) && in_array("query", $query) ) { 
        set_time_limit(60);    // We give ourselves a minute, as some searches may take a while.
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
    
        // Build up the list of arguments that we'll be sending to the query function.
        foreach ( $query as $parameter ) {
            $splodie = explode("=", $parameter);
            if ( is_array($splodie) && 1 < count($splodie) ) {
                $key = $splodie[0];
                $value = $splodie[1];
                if ( isset($value) ) {
                    // This allows us to "scrub" the various values.
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
                            $weekdays_temp = explode(",", $value);
            
                            if ( !empty($weekdays) ) {
                                $weekdays_temp = array_map('abs', array_map('intval', $weekdays_temp));
                            }
                        
                            foreach ( $weekdays_temp as $weekday ) {
                                if ( !in_array($weekday, $weekdays) && (0 < $weekday) && (8 > $weekday) ) {
                                    array_push($weekdays, $weekday);
                                }
                            }
                        
                            asort($weekdays);
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
                            $page = max(0, intval($value));
                        break;
                        
                        case "page_size":
                            $page_size = max(-1, intval($value));
                        break;
                    }
                }
            }
        }
    
        // Compress the response.
        header('Content-Type: application/json');
        ob_start('ob_gzhandler');
        echo(query_database($geocenter_lng, $geocenter_lat, $geo_radius, $minimum_found, $weekdays, $start_time, $org_key, $ids, $page, $page_size));
        ob_end_flush();
    } else {
        header("HTTP/1.1 418 I'm a teapot");
        echo("🫖");
    }
}