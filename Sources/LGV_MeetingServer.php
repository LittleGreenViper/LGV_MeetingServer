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

// MARK: - Internal Functions -

/*******************************************************************/
/**
    \brief Uses the Vincenty calculation to determine the distance (in Kilometers) between the two given lat/long pairs (in Degrees).
    
    The Vincenty calculation is more accurate than the Haversine calculation, as it takes into account the "un-spherical" shape of the Earth, but is more computationally intense.
    We use this calculation to refine the Haversine "triage" in SQL.
    
    \returns a Float with the distance, in Kilometers.
*/
function _get_accurate_distance (	$lat1,  ///< This is the first point latitude (degrees).
                                    $lon1,  ///< This is the first point longitude (degrees).
                                    $lat2,  ///< This is the second point latitude (degrees).
                                    $lon2   ///< This is the second point longitude (degrees).
                                    )
{
    if (($lat1 == $lat2) && ($lon1 == $lon2)) { // Just a quick shortcut.
        return 0;
    }
    
    $a = 6378137;
    $b = 6356752.3142;
    $f = 1/298.257223563;  // WGS-84 ellipsiod
    $L = ($lon2-$lon1)/57.2957795131;
    $U1 = atan((1.0-$f) * tan($lat1/57.2957795131));
    $U2 = atan((1.0-$f) * tan($lat2/57.2957795131));
    $sinU1 = sin($U1);
    $cosU1 = cos($U1);
    $sinU2 = sin($U2);
    $cosU2 = cos($U2);
      
    $lambda = $L;
    $lambdaP = $L;
    $iterLimit = 100;
    
    do {
        $sinLambda = sin($lambda);
        $cosLambda = cos($lambda);
        $sinSigma = sqrt(($cosU2*$sinLambda) * ($cosU2*$sinLambda) + ($cosU1*$sinU2-$sinU1*$cosU2*$cosLambda) * ($cosU1*$sinU2-$sinU1*$cosU2*$cosLambda));
        if ($sinSigma==0) {
            return 0;  // co-incident points
        }
        
        $cosSigma = $sinU1*$sinU2 + ($cosU1*$cosU2*$cosLambda);
        $sigma = atan2($sinSigma, $cosSigma);
        $sinAlpha = ($cosU1 * $cosU2 * $sinLambda) / $sinSigma;
        $cosSqAlpha = 1.0 - $sinAlpha*$sinAlpha;
        
        if (0 == $cosSqAlpha) {
            return 0;
        }
        
        $cos2SigmaM = $cosSigma - 2.0*$sinU1*$sinU2/$cosSqAlpha;
        
        $divisor = (16.0*$cosSqAlpha*(4.0+$f*(4.0-3.0*$cosSqAlpha)));
        
        if (0 == $divisor) {
            return 0;
        }
        
        $C = $f/$divisor;
        
        $lambdaP = $lambda;
        $lambda = $L + (1.0-$C) * $f * $sinAlpha * ($sigma + $C*$sinSigma*($cos2SigmaM+$C*$cosSigma*(-1.0+2.0*$cos2SigmaM*$cos2SigmaM)));
    } while (abs($lambda-$lambdaP) > 1e-12 && --$iterLimit>0);

    $uSq = $cosSqAlpha * ($a*$a - $b*$b) / ($b*$b);
    $A = 1.0 + $uSq/16384.0*(4096.0+$uSq*(-768.0+$uSq*(320.0-175.0*$uSq)));
    $B = $uSq/1024.0 * (256.0+$uSq*(-128.0+$uSq*(74.0-47.0*$uSq)));
    $deltaSigma = $B*$sinSigma*($cos2SigmaM+$B/4.0*($cosSigma*(-1.0+2.0*$cos2SigmaM*$cos2SigmaM)-$B/6.0*$cos2SigmaM*(-3.0+4.0*$sinSigma*$sinSigma)*(-3.0+4.0*$cos2SigmaM*$cos2SigmaM)));
    $s = $b*$A*($sigma-$deltaSigma);
    
    return ( abs ( round ( $s ) / 1000.0 ) ); 
}

/****************************************************************************************************************************/
/**
This is a simple sort closure, for the resultant meeting array. We sort on the distances from search center.
This allows us to maintain a consistent order to each server's meetings.

\returns 0, if the IDs are equal, or -1 is $a is less than $b, or 1, otherwise.
 */
function _sort_meetings_by_distance(    $a, ///< REQUIRED: The first meeting to check.
                                        $b  ///< REQUIRED: The second meeting to check.
                                    ) {
    $aVal = floatval($a["distance"]);
    $bVal = floatval($b["distance"]);
    
    return ($aVal == $bVal) ? _sort_meetings_by_id($a, $b) : (($aVal < $bVal) ? -1 : 1);
}

/****************************************************************************************************************************/
/**
This is a simple sort closure, for the resultant meeting array. We sort on the IDs of the servers and meetings.
This allows us to maintain a consistent order to each server's meetings.

\returns 0, if the IDs are equal, or -1 is $a is less than $b, or 1, otherwise.
 */
function _sort_meetings_by_id(  $a, ///< REQUIRED: The first meeting to check.
                                $b  ///< REQUIRED: The second meeting to check.
                            ) {
    if ( isset($a["organization_key"]) && isset($b["organization_key"]) ) {
        if ( $a["organization_key"] < $b["organization_key"] ) {
            return -1;
        } elseif ( $a["organization_key"] < $b["organization_key"] ) {
            return 1;
        }
    }
    
    $aVal_server = intval($a["server_id"]);
    $bVal_server = intval($b["server_id"]);
    
    if ($aVal_server == $bVal_server) {
        $aVal_meeting = intval($a["meeting_id"]);
        $bVal_meeting = intval($b["meeting_id"]);
        return ($aVal_meeting == $bVal_meeting) ? 0 : (($aVal_meeting < $bVal_meeting) ? -1 : 1);
    } else {
        return ($aVal_server < $bVal_server) ? -1 : 1;
    }
}


/****************************************************************************************************************************/
/**
This "scrubs" a meeting array, so that unused fields are removed. It will help to make transmission much faster.

\returns the "cleaned" meeting Array.
 */
function _clean_meeting($meeting    ///< REQUIRED: The meeting to be filtered (an Array).
                        ) {
    $keys = array_keys($meeting) ;
    $ret = Array();
    
    // Get rid of "housekeeping" stuff.
    if ( isset($meeting["id"]) ) {
        unset($meeting["id"]);
    }
    
    if ( isset($meeting["radius"]) ) {
        unset($meeting["radius"]);
    }
    
    if ( isset($meeting["last_modified"]) ) {
        unset($meeting["last_modified"]);
    }

    foreach ($keys as $key) {
        if ( !empty($meeting[$key])) {
            $value = $meeting[$key];
            if ( "formats" == $key || "virtual_information" == $key || "physical_address" == $key ) {
                $value = unserialize($value);
                if ( "physical_address" == $key ) {
                    if ( isset($value["latitude"]) ) {
                        unset($value["latitude"]);
                    }
                    if ( isset($value["longitude"]) ) {
                        unset($value["longitude"]);
                    }
                }
            }
        
            switch ( $key ) {
                case "server_id":
                case "meeting_id":
                case "weekday":
                case "duration":
                    $value = intval($value);
                
                case "latitude":
                case "longitude":
                    $value = floatval($value);
            }
        
            $ret[$key] = $value;
        }
    }
    
    return $ret;
}

/*******************************************************************/
/**
This method creates a special SQL header that has an embedded Haversine formula. You use this in place of the security predicate.

The Haversine formula is not as accurate as the Vincenty Calculation, but is a lot less computationally intense, so we use this in SQL for a "triage."

\returns an SQL query that will specify a Haversine search. It will include any given WHERE predicate. This adds no placeholders to the predicate.
 */
function _location_predicate(   $geo_center_lng,    ///< REQUIRED FLOAT: The search center longitude, in degrees.
                                $geo_center_lat,    ///< REQUIRED FLOAT: The search center latitude, in degrees.
                                $geo_radius,        ///< REQUIRED FLOAT: The search radius, in Kilometers.
                                $predicate = "",    ///< OPTIONAL STRING: A WHERE predicate to be anded to the location predicate. Default is empty string. WARNING: Possible security issue! Make sure it's parameterized (or otherwise cleaned)!
                                $count_only = false ///< OPTIONAL BOOLEAN: If true (default is false), then only a single integer will be returned, with the count of items that fit the search.
                            ) {
    if ( $predicate ) {
        $predicate = "($predicate) AND";
    }
    
    global $config_file_path;
    include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.

    $ret =  "SELECT * FROM (
                SELECT z.*,
                    p.radius,
                    p.distance_unit
                             * DEGREES(ACOS(COS(RADIANS(p.latpoint))
                             * COS(RADIANS(z.latitude))
                             * COS(RADIANS(p.longpoint - z.longitude))
                             + SIN(RADIANS(p.latpoint))
                             * SIN(RADIANS(z.latitude)))) AS distance
                FROM `".$_dbTableName."` AS z
                JOIN (   /* these are the query parameters */
                    SELECT  ".floatval($geo_center_lat)." AS latpoint, ".floatval($geo_center_lng)." AS longpoint,".floatval($geo_radius)." AS radius, 111.045 AS distance_unit
                ) AS p ON 1=1
                WHERE z.latitude
                 BETWEEN p.latpoint  - (p.radius / p.distance_unit)
                     AND p.latpoint  + (p.radius / p.distance_unit)
                AND z.longitude
                 BETWEEN p.longpoint - (p.radius / (p.distance_unit * COS(RADIANS(p.latpoint))))
                     AND p.longpoint + (p.radius / (p.distance_unit * COS(RADIANS(p.latpoint))))
                ) AS d
                WHERE $predicate (distance <= radius)";
    
    if ( $count_only ) {
        $ret = "SELECT COUNT(*) FROM ($ret)";
    }
    
    return $ret;
}

/***********************/
/**
\returns true, if the meta table exists.
*/
function _meta_table_exists($pdo_instance   ///< REQUIRED: The PDO instance for this transaction.
                            ) {
    global $config_file_path;
    include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.

    $sql = "SELECT * FROM information_schema.tables WHERE table_schema='$_dbName' AND table_name='$_dbMetaTableName' LIMIT 1;";
    
    $response = $pdo_instance->preparedStatement($sql, [], true);

    return !empty($response);
}

/***********************/
/**
\returns true, if the data table exists.
*/
function _data_table_exists($pdo_instance   ///< REQUIRED: The PDO instance for this transaction.
                            ) {
    global $config_file_path;
    include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.

    $sql = "SELECT * FROM information_schema.tables WHERE table_schema='$_dbName' AND table_name='$_dbTableName' LIMIT 1;";
    
    $response = $pdo_instance->preparedStatement($sql, [], true);

    return !empty($response);
}

/***********************/
/**
\returns true, if successful.
*/
function _initialize_meta_database(  $pdo_instance   ///< REQUIRED: The PDO instance for this transaction.
                                ) {
    global $config_file_path;
    include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.

    $sql_init = file_get_contents(dirname(__FILE__).'/config/sql/LGV_MeetingServer-Meta-MySQL.sql') . ";DROP TABLE IF EXISTS `$_dbTableName`;DROP TABLE IF EXISTS `$_dbTempTableName`";;

    try {
        $pdo_instance->preparedStatement($sql_init);
        return true;
    } catch (Exception $exception) {
    }
    
    return false;
}

/***********************/
/**
\returns true, if successful.
*/
function _initialize_main_database(  $pdo_instance,  ///< REQUIRED: The PDO instance for this transaction.
                                    $dbTableName    ///< REQUIRED: The name of the table to receive the initialization
                                ) {
    $sql_init = file_get_contents(dirname(__FILE__).'/config/sql/LGV_MeetingServer-MySQL.sql');

    try {
        $sql_init = str_replace("`TABLE-NAME`", "`$dbTableName`", $sql_init);
        $pdo_instance->preparedStatement($sql_init);
        return true;
    } catch (Exception $exception) {
    }
    
    return false;
}

// MARK: - Exposed Functions -

/*******************************************************************/
/**
This actually fetches all the meetings, converts them to our local format, and stores them into a temporary table.
Once that is done, it deletes the current data table, and replaces it with the newly initialized temp table.

This checks to see if the meta table exists. If it does not, then it creates it.
This checks if the data table exists. If it does, and if the elapsed time has passed, an update is forced. Otherwise, if it does not exist, an update is forced (which creates it).

\returns: the number of meetings updated. NULL, if no update.
 */
function update_database(   $physical_only = false  ///< REQUIRED: If true (default is false), then only meetings that have a physical location will be returned.
                        ) {
    global $config_file_path;
    include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.

    try {
        $pdo_instance = new LGV_MeetingServer_PDO($_dbName, $_dbLogin, $_dbPassword, $_dbType, $_dbHost, $_dbPort);
        if (!_meta_table_exists($pdo_instance) ) {
            _initialize_meta_database($pdo_instance);
        }
        $lastupdate_response = $pdo_instance->preparedStatement("SELECT `last_update` FROM `$_dbMetaTableName`", [], true)[0]["last_update"];
        $lapsed_time = time() - intval($lastupdate_response);
        if ( (!_data_table_exists($pdo_instance) || ($_updateIntervalInSeconds < $lapsed_time)) && _initialize_main_database($pdo_instance, $_dbTempTableName) ) {
            $number_of_meetings = process_all_bmlt_server_meetings($pdo_instance, $_dbTempTableName, $physical_only);
            $rename_sql = "DROP TABLE IF EXISTS `$_dbTableName`;RENAME TABLE `$_dbTempTableName` TO `$_dbTableName`;UPDATE `$_dbMetaTableName` SET `last_update`=? WHERE 1;";
            $pdo_instance->preparedStatement($rename_sql, [time()]);
            if ( 0 < $number_of_meetings ) {
                return $number_of_meetings;
            }
        }
    } catch (Exception $exception) {
    }
   
    return NULL;
}

/*******************************************************************/
/**
\returns a JSON object, with the meetings found, and the time it took for the search to be done (in seconds). NULL, if no meetings found.
 */
function query_database($geo_center_lng = NULL, ///< OPTIONAL FLOAT: The longitude (in degrees) of the center of the search
                        $geo_center_lat = NULL, ///< OPTIONAL FLOAT: The latitude (in degrees) of the center of the search
                        $geo_radius = NULL,     ///< OPTIONAL UNSIGNED FLOAT: The maximum radius (in Kilometers) of the search.
                        $minimum_found = 0,     ///< OPTIONAL UNSIGNED INT: If nonzero, then the search will be an "auto-radius" search, starting from the center, and expanding in steps (each step is 1/20 of the total radius). Once this many meetings are found (or the maximum radius is reached), the search stops, and the found metings are returned.
                        $weekdays = [],         ///< OPTIONAL ARRAY[UNSIGNED INT (1 - 7)]: Any weekdays. Each integer is 1-7 (1 is always Sunday). There are a maximum of 7 elements. An empty Array (default), means all weekdays. If any values are present, ONLY those days are found.
                        $start_time = 0,        ///< OPTIONAL UNSIGNED INT: A minimum start time, in seconds (0 -> 86400, with 86399 being "One minute before midnight tonight," and 0 being "midnight, this morning"). Default is 0. This is inclusive (25200 is 7AM, or later).
                        $org_key = NULL,        ///< OPTIONAL STRING: The key for a particular organization. If not provided, all organizations are searched.
                        $ids = NULL,            ///< OPTIONAL ARRAY[(UNSIGNED INT, UNSIGNED INT)]: This can be an array of tuples (each being a server ID, and a meeting ID, in that order, as integers). These represent individual meetings. If these are provided, then ONLY those meetings will be returned, but any other parameters will still be applied.
                        $page = 0,              ///< OPTIONAL UNSIGNED INTEGER: This is the 0-based page. Default is 0 (from the beginning).
                        $page_size = -1         ///< OPTIONAL INTEGER: The size of each page. 0, means return a count only. Negative values mean the whole search should be returned in one page, and $page is ignored (considered to be 0).
                        ) {
    // Practice good argument hygiene.
    $minimum_found = abs(intval($minimum_found));
    $start_time = abs(intval($start_time));
    $page = abs(intval($page));
    $page_size = max(-1, intval($page_size));
    $step_size_in_km = $geo_radius;
    
    
    
    $current_step = $step_size_in_km;
    
    $geo_search = NULL != $geo_center_lng && NULL != $geo_center_lat;
    
    $geo_center_lng = floatval($geo_center_lng);
    $geo_center_lat = floatval($geo_center_lat);
    $geo_radius = abs(floatval($geo_radius));

    if ( !$geo_search ) {
        $geo_radius = 0;
        $minimum_found = 0;
    }
    
    if ( 0 < $minimum_found && 0 < $geo_radius && $geo_search) {
        $step_size_in_km = 0.1;
        $current_step = $step_size_in_km;
    // Special case for specifying a minimum, without a radius. We set 5Km steps, and a max radius of 10000 Km.
    } elseif ( 0 < $minimum_found && 0 == $geo_radius && $geo_search) {
        $step_size_in_km = 0.1;
        $current_step = $step_size_in_km;
        $geo_radius = 10000;
    }
    
    $predicate = "";
    $params = [];
    
    if ( !empty($weekdays) ) {
        $weekday_predicate_array = [];
    
        foreach ( $weekdays as $weekday ) {
            $weekday = abs(intval($weekday));
            if ( (0 < $weekday) && (8 > $weekday) ) {
                $pred = "`weekday`=$weekday";
                if ( !in_array($pred, $weekday_predicate_array) ) {
                    array_push($weekday_predicate_array, $pred);
                }
            }
        }
    
        if ( !empty($weekday_predicate_array) ) {
            $predicate = "(".implode(") OR (",$weekday_predicate_array).")";
        }
    }
    
    $start_time = intval($start_time);
    
    if ( 0 < $start_time && 86400 > $start_time ) {
        $hour = intval($start_time / 3600);
        $minute = ($start_time - ($hour * 3600)) / 60;
        $second = $start_time - ($hour * 3600) - ($minute * 60);
        $comp_time = sprintf("%02d:%02d:00", $hour, $minute);
        if ( $predicate ) {
            $predicate .= " AND ";
        }
        $predicate .= "(`start_time`<=$comp_time)";
    }
    
    if ( isset($org_key) && trim($org_key) ) {
        $org_key = strtolower(trim($org_key));
        $params = [$org_key];
        if ( $predicate ) {
            $predicate .= " AND ";
        }
        $predicate .= "(?=`organization_key`)";
    }
    
    if ( !empty($ids) ) {
        $plist = [];
        foreach ( $ids as $id ) {
            if ( is_array($id) && (1 < count($id)) ) {
                $server_id = intval($id[0]);
                $meeting_id = intval($id[1]);
                
                array_push($plist, "(`server_id`=$server_id AND `meeting_id`=$meeting_id)");
            }
        }
        
        if ( !empty($plist) ) {
            if ( $predicate ) {
                $predicate .= " AND ";
            }
            
            $predicate .= implode(" OR ", $plist);
        }
    }
   
    global $config_file_path;
    include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.
    $pdo_instance = new LGV_MeetingServer_PDO($_dbName, $_dbLogin, $_dbPassword, $_dbType, $_dbHost, $_dbPort);
    
    $response = [];
    
    if ( _data_table_exists($pdo_instance) ) {
        if ( 0 < $minimum_found) {
            while ( ($current_step <= $geo_radius) && ($minimum_found > count($response)) ) {
                $sql = _location_predicate($geo_center_lng, $geo_center_lat, $current_step, $predicate, false);
                $response = $pdo_instance->preparedStatement($sql, $params, true);
                $current_step *= 1.05;
            }
            
            $current_step = $geo_radius;
        } else {
            $sql =  "";
        
            if ( $geo_search ) {
                $sql = _location_predicate($geo_center_lng, $geo_center_lat, $geo_radius * 1.05, $predicate, false);
            } else {
                $sql = "SELECT * FROM `".$_dbTableName."`";
                if ( !empty($predicate) ) {
                    $sql .= " WHERE $predicate";
                }
            
            }
            $response = $pdo_instance->preparedStatement($sql, $params, true);
        }
    }

    // NOTE ON PAGING: We don't use SQL to page, because our "cleaning," with the Vincenty algorithm, could shave off some meetings.
    // We do avoid a couple of the time-intensive tasks, though, if we are just looking for metrics.
    // 0 for a page size, means we are just looking for a total count. -1, means we want the whole found set at once.

    if ( !empty($response) && (!$geo_search || ($current_step == $geo_radius) || ((0 < $minimum_found) && (count($response) >= $minimum_found))) ) {
        $ret = Array();
    
        foreach ( $response as $meeting ) {
            if ( $geo_search && isset($meeting["latitude"]) && isset($meeting["longitude"]) ) {
                // We apply a Vincenty algorithm, to refine the distance. It is more accurate than the simple one we used for the main search.
                $distance = _get_accurate_distance($geo_center_lat, $geo_center_lng, floatVal($meeting["latitude"]), floatval($meeting["longitude"]));
                if ( $geo_radius >= $distance ) {
                    $meeting["distance"] = $distance;
                    if ( 0 != $page_size ) {
                        array_push($ret, _clean_meeting($meeting));
                    } else {
                        array_push($ret, $meeting);
                    }
                }
            } elseif ( 0 != $page_size ) {
                array_push($ret, _clean_meeting($meeting));
            } else {
                array_push($ret, $meeting);
            }
        }
        
        if ( 0 != $page_size ) {
            usort($ret, "_sort_meetings_by_".($geo_search ? "distance" : "id"));
        }
        
        $count = count($ret);
        $total_pages = intval((0 > $page_size) ? 1 : ((0 == $page_size) ? 0 : (($count + ($page_size - 1)) / $page_size)));
        
        $starting_index = max(0, $page * $page_size);

        if ( 0 > $page_size ) {
            $page_size = $count;
            $page = 0;
        }
        
        $page_end_index = min($count, ($page + 1) * $page_size);
        
        $slice_size = $page_end_index - $starting_index;
        $returned_meetings = array_slice($ret, $starting_index, $slice_size);
        
        return "{ \"meta\": {\"count\": $slice_size, \"total\": $count, \"starting_index\": $starting_index, \"page\": $page, \"total_pages\": $total_pages, \"page_size\": $page_size}, \"meetings\": ".json_encode( $returned_meetings )."}";
    }
    
    return "{ \"meta\": {\"count\": 0, \"total\": 0, \"page\": 0, \"total_pages\": 0, \"page_size\": 0}, \"meetings\": []}";
}
