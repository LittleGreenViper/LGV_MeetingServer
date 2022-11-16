<?php
/***************************************************************************************************************************/
/**
    This is the main entrypoint of the meeting server.
    
    © Copyright 2022, [Little Green Viper Software Development LLC](https://littlegreenviper.com)
    
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

    [Little Green Viper Software Development LLC](https://littlegreenviper.com)
*/
/***************************************************************************************************************************/
/**
    \brief This file implements the lions' share of basic entrypoint duties for the server. It should be included into whatever index file you are using.
    
    NOTE: Your index file needs to declare a path to the config file, in the global variable $config_file_path.
 */
defined( 'LGV_MeetingServer_Files' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

require_once(dirname(__FILE__).'/LGV_MeetingServer.php');

if ( isset($argv) ) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

if ( isset($_GET["cli"]) ) { // A call from the CLI means just do an update (for cron jobs).
    if ( isset($_GET['-h']) ) {
        echo("Updates the LGV_MeetingServer Database.\n\tUsage:\t-h: Help (This display)\n\t\t-f: Force (Perform update, even if not scheduled)\n\t\t-p: Physical Meetings Only (Virtual-only meetings are ignored)\n\t\t-sv: Separate Organization for Virtual (Virtual meetings are stored, but given a different organization key. The -p flag is ignored)\n\t\tIf no arguments given, waits until the specified time has passed, and performs a -p update of the database.\n");
    } else {
        $forced = isset($_GET['-f']);
        $physical_only = isset($_GET['-p']);
        $separate_virtual = isset($_GET['-sv']);
        echo intval(update_database($physical_only, $forced, $separate_virtual));
    }
} else if ( !isset($_GET["cli"]) ) {    // This is "stupid securiity." People can still force an update by mimicking the CLI parameters, but it prevents the casual idiots from messing us up, too much. It's not the end of the world, if they succeed, anyway.
    $query = explode("&", $_SERVER["QUERY_STRING"]);
    
    if ( isset($query) && is_array($query) && (0 < count($query)) ) {
        if ( "query" == strtolower($query[0]) ) { 
            $geocenter_lng = NULL;
            $geocenter_lat = NULL;
            $geo_radius = NULL;
            $minimum_found = 0;
            $weekdays = [];
            $start_time = 0;
            $end_time = 0;
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
                        
                            case "end_time":
                                $end_time = abs(intval($value));
                            break;
                        
                            case "org_key":
                                $value = trim($value);
                                if ( !empty($value) ) {
                                    $org_key = array_map('trim', explode(",", $value));
                                }
                            break;
                        
                            case "ids":
                                if ( !empty(trim($value)) ) {
                                    $id_temp = explode("),(", trim($value, "()"));
            
                                    if ( !empty($id_temp) ) {
                                        foreach ( $id_temp as $id_pair ) {
                                            $id_pair_temp = explode(",", $id_pair);
                                            if ( 1 <= count($id_pair_temp) ) {
                                                $comp = [intval($id_pair_temp[0]), isset($id_pair_temp[1]) ? intval($id_pair_temp[1]) : 0];
                                                if ( 0 < $comp[0] ) {
                                                    array_push($ids, $comp);
                                                }
                                            }
                                        }
                                        if ( empty($ids) ) {
                                            header("HTTP/1.1 400 Bad Parameters");
                                            exit("INVALID IDS");
                                        }
                                    } else {
                                        header("HTTP/1.1 400 Bad Parameters");
                                        exit("INVALID IDS");
                                    }
                                } else {
                                    header("HTTP/1.1 400 Bad Parameters");
                                    exit("INVALID IDS");
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
            if (extension_loaded('zlib')) {
                ob_start();
            } else {
                ob_start('ob_gzhandler');
            }
            echo(query_database($geocenter_lng, $geocenter_lat, $geo_radius, $minimum_found, $weekdays, $start_time, $end_time, $org_key, $ids, $page, $page_size));
            ob_end_flush();
            exit;
        } elseif ( "info" == strtolower($query[0]) ) {
            header('Content-Type: application/json');
            echo(get_server_info());
            exit;
        } elseif ( "update" == strtolower($query[0]) ) {
            global $config_file_path;
            include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.
            if ( !(isset($_use_cli_only_for_update) && $_use_cli_only_for_update) ) {
                $force = in_array("force", $query);
                $physical_only = in_array("physical_only", $query);
                $separate_virtual = in_array("separate_virtual", $query);

                $start = microtime(true);
                $number_of_meetings = update_database($physical_only, $force, $separate_virtual);
                $exchange_time = microtime(true) - $start;
                if ( 0 < $number_of_meetings ) {
                    header('Content-Type: application/json');
                    echo("{\"number_of_meetings\": $number_of_meetings,\"time_in_seconds\":$exchange_time}");
                } else {
                    header("HTTP/1.1 204 No Content");
                }
            } else {
                header("HTTP/1.1 403 Not Authorized");
                echo("NOT AUTHORIZED");
            }
            
            exit;
        }
    }

    header("HTTP/1.1 418 I'm a teapot");
    echo("🫖");
}
