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
\returns true, if successful.
*/
function initialize_meta_database(  $pdo_instance   ///< REQUIRED: The PDO instance for this transaction.
                                ) {
    $sql_init = file_get_contents(dirname(__FILE__).'/config/sql/LGV_MeetingServer-Meta-MySQL.sql');

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
function initialize_main_database(  $pdo_instance,  ///< REQUIRED: The PDO instance for this transaction.
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

/*******************************************************************/
/**
This actually fetches all the meetings, converts them to our local format, and stores them into a temporary table.
Once that is done, it deletes the current data table, and replaces it with the newly initialized temp table.
 */
function update_database(   $physical_only = false  ///< REQUIRED: If true (default is false), then only meetings that have a physical location will be returned.
                        ) {
    global $config_file_path;
    include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.

    try {
        $pdoInstance = new LGV_MeetingServer_PDO($_dbName, $_dbLogin, $_dbPassword, $_dbType, $_dbHost, $_dbPort);
        $lastupdate_response = $pdoInstance->preparedStatement("SELECT `last_update` FROM `$_dbMetaTableName`", [], true)[0]["last_update"];
        $lapsed_time = time() - intval($lastupdate_response);
        if ( $_updateIntervalInSeconds < $lapsed_time ) {
            $number_of_meetings = process_all_bmlt_server_meetings($pdoInstance, $_dbTempTableName, $physical_only);
            $rename_sql = "DROP TABLE IF EXISTS `$_dbTableName`;RENAME TABLE `$_dbTempTableName` TO `$_dbTableName`;UPDATE `$_dbMetaTableName` SET `last_update`=? WHERE 1;";
            $pdoInstance->preparedStatement($rename_sql, [time()]);
        }
        return $number_of_meetings;
    } catch (Exception $exception) {
        return NULL;
    }
}

/*******************************************************************/
/**
 */
function geo_query_database($geo_center_lng,    ///< REQUIRED FLOAT: The longitude (in degrees) of the center of the search
                            $geo_center_lat,    ///< REQUIRED FLOAT: The latitude (in degrees) of the center of the search
                            $geo_radius,        ///< REQUIRED UNSIGNED FLOAT: The maximum radius (in Kilometers) of the search.
                            $minimum_found = 0, ///< OPTIONAL UNSIGNED INT: If nonzero, then the search will be an "auto-radius" search, starting from the center, and expanding in steps (each step is 1/20 of the total radius). Once this many meetings are found (or the maximum radius is reached), the search stops, and the found metings are returned.
                            $weekdays = [],     ///< OPTIONAL ARRAY[UNSIGNED INT (1 - 7)]: Any weekdays. Each integer is 1-7 (1 is always Sunday). There are a maximum of 7 elements. An empty Array (default), means all weekdays. If any values are present, ONLY those days are found.
                            $start_time = 0,    ///< OPTIONAL UNSIGNED INT: A minimum start time, in seconds (0 -> 86400, with 86399 being "One minute before midnight tonight," and 0 being "midnight, this morning"). Default is 0. This is inclusive (25200 is 7AM, or later).
                            $org_key = NULL     ///< OPTIONAL STRING: The key for a particular organization. If not provided, all organizations are searched.
                            ) {
    $step_size_in_km = $geo_radius;
    $current_step = $geo_radius;
    if ( 0 < $minimum_found) {
        $step_size_in_km /= 20;
        $current_step = $step_size_in_km;
    }
    
    $predicate = "";
    $params = [];
    
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
    
    $sql = _location_predicate($geo_center_lng, $geo_center_lat, $current_step, $predicate, false);
   
    global $config_file_path;
    include($config_file_path);    // Config file path is defined in the calling context. This won't work, without it.
    $pdoInstance = new LGV_MeetingServer_PDO($_dbName, $_dbLogin, $_dbPassword, $_dbType, $_dbHost, $_dbPort);
    $response = $pdoInstance->preparedStatement($sql, $params, true);
    
    return $response;
}