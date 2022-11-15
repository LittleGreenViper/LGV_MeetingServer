<?php
/***************************************************************************************************************************/
/**
    This file handles the BMLT aspect of the meeting server.
    
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
    \brief This file implements the BMLT-specific "writer" for the server.
    
    It reads in the current server list (from the [TOMATO Project](https://github.com/bmlt-enabled/tomato/blob/master/rootServerList.json)), and then reads in every server, using the default localization.
    It processes the server data into our local format, and stores it.
 */
defined( 'LGV_MeetingServer_Files' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.


/***************************************************************************************************************************/
/**
This is a "service module" class, for the Basic Meeting List Toolbox (BMLT), a service for NA meetings.
It uses [a list](https://github.com/bmlt-enabled/tomato/blob/master/rootServerList.json) that is published in
[the TOMATO project](https://github.com/bmlt-enabled/tomato), an aggregator service for the BMLT.
 */
class BMLTServerInteraction extends AServiceInteraction {
    /***********************************************************************************************************************/
    /**
    This reads the BMLT Root Server JSON list, from its GitHub home.

    \returns a JSON-decoded PHP object, with the list as an Array. Each element has an ID (Integer), name (String), and Root Server entrypoint URI (String).
     */
    protected static function _read_bmlt_server_list() {
        /// This is the "master list" of all the BMLT servers that TOMATO (the BMLT aggregator) uses. It is a JSON file.
        return json_decode(self::_call_URL("https://raw.githubusercontent.com/bmlt-enabled/tomato/master/rootServerList.json"));
    }

    /***********************************************************************************************************************/
    /**
    This is a simple sort closure, for the resultant meeting array. We sort on the server internal IDs.
    This allows us to maintain a consistent order to each server's meetings.

    \returns 0, if the IDs are equal (should never happen), or -1 is $a is less than $b, or 1, otherwise.
     */
    protected static function   _sort_bmlt_meetings(   $a, ///< REQUIRED: The first meeting to check.
                                                        $b  ///< REQUIRED: The second meeting to check.
                                                    ) {
        $aVal = $a["meeting_id"];
        $bVal = $b["meeting_id"];
    
        return ($aVal == $bVal) ? 0 : (($aVal < $bVal) ? -1 : 1);
    }

    /***********************************************************************************************************************/
    /**
    This queries a single server for all of its meeting data, and converts it to our internal representation.

    \returns the meetings from the server, as our own representation, and sorted by server ID.
     */
    protected static function _read_bmlt_server_meetings(   $url,                       ///< REQIRED:   This is the base URL for the server's API.
                                                            $server_id,                 ///< REQUIRED: The integer ID of the server.
                                                            $physical_only = false,     ///< OPTIONAL BOOLEAN: If true (default is false), then only meetings that have a physical location will be returned.
                                                            $separate_virtual = false   ///< OPTIONAL BOOLEAN: If true (default is false), then virtual-only meetings will be counted, but will be assigned a "virtual-%s" (with "%s" being the org key) org key.
                                                        ) {
        $json_data = self::_call_URL("$url/client_interface/json/?switcher=GetSearchResults&get_used_formats=1");
        $decoded_json = json_decode($json_data);
        $meeting_objects = isset($decoded_json->meetings) ? $decoded_json->meetings : NULL;
        $format_objects = isset($decoded_json->formats) ? $decoded_json->formats : NULL;
        $meetings = [];
        if ( isset($decoded_json->meetings) ) {
            foreach($meeting_objects as $meeting_object) {
                $meeting = [];
                // These 3 are required.
                $meeting["server_id"] = $server_id;
                $meeting["meeting_id"] = intval($meeting_object->id_bigint);
                $meeting["organization_key"] = "na";
        
                if ( isset($meeting_object->meeting_name) && trim($meeting_object->meeting_name) ) {
                    $meeting["name"] = trim($meeting_object->meeting_name);
                }
        
                if ( isset($meeting_object->weekday_tinyint) && trim($meeting_object->weekday_tinyint) ) {
                    $meeting["weekday"] = intval($meeting_object->weekday_tinyint);
                }
        
                if ( isset($meeting_object->start_time) && trim($meeting_object->start_time) ) {
                    $meeting["start_time"] = trim($meeting_object->start_time);
                }
        
                if ( isset($meeting_object->duration_time) && trim($meeting_object->duration_time) ) {
                    $duration_array = explode(":", trim($meeting_object->duration_time));
                    $duration = (intval($duration_array[0]) * (60 * 60)) + (intval($duration_array[1]) * 60) + intval($duration_array[2]);
                    $meeting["duration"] = $duration;
                }
            
                if ( isset($meeting_object->comments) && trim($meeting_object->comments) ) {
                    $meeting["comments"] = trim($meeting_object->comments);
                }
        
                if ( isset($meeting_object->location_street) && trim($meeting_object->location_street) && isset($meeting_object->longitude) && isset($meeting_object->latitude) ) {
                    $meeting["physical_location"]["longitude"] = floatval($meeting_object->longitude);
                    $meeting["physical_location"]["latitude"] = floatval($meeting_object->latitude);
                    $meeting["physical_location"]["street"] = trim($meeting_object->location_street);
                    if ( isset($meeting_object->location_text) && trim($meeting_object->location_text) ) {
                        $meeting["physical_location"]["name"] = trim($meeting_object->location_text);
                    }
                    if ( isset($meeting_object->location_neighborhood) && trim($meeting_object->location_neighborhood) ) {
                        $meeting["physical_location"]["neighborhood"] = trim($meeting_object->location_neighborhood);
                    }
                    if ( isset($meeting_object->location_city_subsection) && trim($meeting_object->location_city_subsection) ) {
                        $meeting["physical_location"]["city_subsection"] = trim($meeting_object->location_city_subsection);
                    }
                    if ( isset($meeting_object->location_municipality) && trim($meeting_object->location_municipality) ) {
                        $meeting["physical_location"]["city"] = trim($meeting_object->location_municipality);
                    }
                    if ( isset($meeting_object->location_sub_province) && trim($meeting_object->location_sub_province) ) {
                        $meeting["physical_location"]["county"] = trim($meeting_object->location_sub_province);
                    }
                    if ( isset($meeting_object->location_province) && trim($meeting_object->location_province) ) {
                        $meeting["physical_location"]["province"] = trim($meeting_object->location_province);
                    }
                    if ( isset($meeting_object->location_postal_code_1) && trim($meeting_object->location_postal_code_1) ) {
                        $meeting["physical_location"]["postal_code"] = trim($meeting_object->location_postal_code_1);
                    }
                    if ( isset($meeting_object->location_nation) && trim($meeting_object->location_nation) ) {
                        $meeting["physical_location"]["nation"] = trim($meeting_object->location_nation);
                    }
                    if ( isset($meeting_object->location_info) && trim($meeting_object->location_info) ) {
                        $meeting["physical_location"]["info"] = trim($meeting_object->location_info);
                    }
                }
        
                if ( isset($meeting_object->virtual_meeting_link) && trim($meeting_object->virtual_meeting_link) ) {
                    $meeting["virtual_meeting_info"]["url"] = trim($meeting_object->virtual_meeting_link);
                    if ( isset($meeting_object->virtual_meeting_additional_info) && trim($meeting_object->virtual_meeting_additional_info) ) {
                        $meeting["virtual_meeting_info"]["info"] = trim($meeting_object->virtual_meeting_additional_info);
                    }
            
                    if ( isset($meeting_object->phone_meeting_number) && trim($meeting_object->phone_meeting_number) ) {
                        $meeting["virtual_meeting_info"]["phone_number"] = trim($meeting_object->phone_meeting_number);
                    }
                }
        
                if ( isset($format_objects) && !empty($format_objects) && isset($meeting_object->format_shared_id_list) && !empty($meeting_object->format_shared_id_list) ) {
                    $id_list = array_map('intval', explode(",", $meeting_object->format_shared_id_list));
                    if ( !empty($id_list) ) {
                        $meeting["formats"] = [];
                        foreach($format_objects as $format) {
                            if ( in_array($format->id, $id_list) ) {
                                $format_ar["key"] = trim($format->key_string);
                                $format_ar["name"] = trim($format->name_string);
                                $format_ar["description"] = trim($format->description_string);
                                if ( isset($format->lang) && trim($format->lang) ) {
                                    $format_ar["language"] = strtolower(trim($format->lang));
                                } else {
                                    $format_ar["language"] = "en";
                                }
                                array_push($meeting["formats"], $format_ar);
                            }
                        }
                    }
                }
            
                if ( isset($meeting["physical_location"]) || !$physical_only || $separate_virtual ) {
                    if ( $separate_virtual && !isset($meeting["physical_location"]) ) {
                        $meeting["organization_key"] = "virtual-na";
                    }
                    array_push($meetings, $meeting);
                }
            }
    
            usort($meetings, "self::_sort_bmlt_meetings");
        }
    
        return $meetings;
    }

    /***********************************************************************************************************************/
    /**
    This saves a set of meetings into the database.

    \returns the number of meetings saved.
     */
    protected static function _save_bmlt_meetings_into_db(  $pdo_instance,  ///< REQUIRED: The initialized PDO instance that will be used to store the data.
                                                            $table_name,    ///< REQUIRED: The name of the table to be used.
                                                            $meetings       ///< REQUIRED: The array of meeting objects to be saved.
                                                        ) {
        $counted_meetings = 0;
    
        $sql = "INSERT INTO `$table_name` (`server_id`, `meeting_id`, `organization_key`, `name`, `start_time`, `weekday`, `single_occurrence_date`, `duration`, `longitude`, `latitude`, `tag0`, `tag1`, `tag2`, `tag3`, `tag4`, `tag5`, `tag6`, `tag7`, `tag8`, `tag9`, `comments`, `formats`, `physical_address`, `virtual_information`) VALUES\n";
        $params = [];
        $sql_rows = [];
        foreach ( $meetings as $meeting ) {
            $counted_meetings++;
            array_push($sql_rows, "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            array_push($params, $meeting["server_id"]);
            array_push($params, $meeting["meeting_id"]);
            array_push($params, $meeting["organization_key"]);
            array_push($params, (isset($meeting["name"]) ? $meeting["name"] : "NA Meeting"));
            array_push($params, (isset($meeting["start_time"]) ? $meeting["start_time"] : NULL));
            array_push($params, (isset($meeting["weekday"]) ? $meeting["weekday"] : NULL));
            array_push($params, (isset($meeting["single_occurrence_date"]) ? $meeting["single_occurrence_date"] : NULL));
            array_push($params, (isset($meeting["duration"]) ? $meeting["duration"] : NULL));
            array_push($params, (isset($meeting["physical_location"]["longitude"]) ? $meeting["physical_location"]["longitude"] : NULL));
            array_push($params, (isset($meeting["physical_location"]["latitude"]) ? $meeting["physical_location"]["latitude"] : NULL));
            array_push($params, (isset($meeting["tag0"]) ? $meeting["tag0"] : NULL));
            array_push($params, (isset($meeting["tag1"]) ? $meeting["tag1"] : NULL));
            array_push($params, (isset($meeting["tag2"]) ? $meeting["tag2"] : NULL));
            array_push($params, (isset($meeting["tag3"]) ? $meeting["tag3"] : NULL));
            array_push($params, (isset($meeting["tag4"]) ? $meeting["tag4"] : NULL));
            array_push($params, (isset($meeting["tag5"]) ? $meeting["tag5"] : NULL));
            array_push($params, (isset($meeting["tag6"]) ? $meeting["tag6"] : NULL));
            array_push($params, (isset($meeting["tag7"]) ? $meeting["tag7"] : NULL));
            array_push($params, (isset($meeting["tag8"]) ? $meeting["tag8"] : NULL));
            array_push($params, (isset($meeting["tag9"]) ? $meeting["tag9"] : NULL));
            array_push($params, (isset($meeting["comments"]) ? $meeting["comments"] : NULL));
            if ( isset($meeting["formats"]) ) {
                $_json = serialize($meeting["formats"]);
                array_push($params, $_json);
            } else {
                array_push($params, "");
            }
            if ( isset($meeting["physical_location"]) && !empty($meeting["physical_location"]) ) {
                $_json = serialize($meeting["physical_location"]);
                array_push($params, $_json);
            } else {
                array_push($params, "");
            }
            if ( isset($meeting["virtual_meeting_info"]) && !empty($meeting["virtual_meeting_info"]) ) {
                $_json = serialize($meeting["virtual_meeting_info"]);
                array_push($params, $_json);
            } else {
                array_push($params, "");
            }
        }
    
        if ( !empty($sql_rows) ) {
            $sql .= (implode(",\n", $sql_rows) . ";\n");
            $pdo_instance->preparedStatement($sql, $params);
        }
    
        return $counted_meetings;
    }

    /***********************************************************************************************************************/
    /**
    This processes all the BMLT meetings, using the TOMATO list. It reads the server list, reads all the meetings in each server, then saves them into the given table.

    \returns the number of meetings that were processed.
     */
    function process_all_meetings(  $pdo_instance,              ///< REQUIRED: The initialized PDO instance that will be used to store the data.
                                    $table_name,                ///< REQUIRED: The name of the table to be used. This will not be cleared or initialized.
                                    $physical_only = false,     ///< OPTIONAL BOOLEAN: If true (default is false), then only meetings that have a physical location will be returned.
                                    $separate_virtual = false   ///< OPTIONAL BOOLEAN: If true (default is false), then virtual-only meetings will be counted, but will be assigned a "virtual-%s" (with "%s" being the org key) org key.
                                ) {
        $all_meetings = 0;
        $server_list = self::_read_bmlt_server_list();
        foreach ( $server_list as $server ) {
            $dataURL = $server->rootURL."client_interface/json/?switcher=GetSearchResults&get_used_formats=1";
            $meetings = self::_read_bmlt_server_meetings($dataURL, intval($server->id), $physical_only, $separate_virtual);
            $all_meetings += self::_save_bmlt_meetings_into_db($pdo_instance, $table_name, $meetings);
        }
    
        return $all_meetings;
    }
                                
    /***********************************************************************************************************************/
    /**
    This returns information about the service.

    \returns an array, with information about the service. The key for each element of the "servers" array, is the server ID.
     */
    function service_info() {
        $servers = [];
    
        $rawServerList = self::_read_bmlt_server_list();
        
        foreach ( $rawServerList as $server ) {
            $key = intval($server->id);
            $name = $server->name;
            $url = $server->rootURL;
            
            $servers[$key] = ["name" => $name, "url" => $url];
        }
        
        return ["service_name" => "BMLT", "servers" => $servers];
    }
}