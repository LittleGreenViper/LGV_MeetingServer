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

require_once(dirname(__FILE__).'/LGV_MeetingServer_PDO.class.php');

    
/****************************************************************************************************************************/
/**
This is the function that is used by the BASALT testing facility to make REST calls to the Greater Rift Valley BAOBAB server.

It is provided as an example of making REST calls to BAOBAB, and to provide guidance for programmers creating their own REST clients.

\returns the resulting transfer from the server, as a string of bytes.
 */
function call_URL(  $url    ///< REQIRED:   This is the base URL for the call. It should include the entire URI, including query arguments.
                    ) {
    
    // Initialize function local variables.
    $file = NULL;               // This will be a file handle, for uploads.
    $content_type = NULL;       // This is used to signal the content-type for uploaded files.
    $file_size = 0;             // This is the size, in bytes, of uploaded files.
    $temp_file_name = NULL;     // This is a temporary file that is used to hold files before they are sent to the server.

    $curl = curl_init();                    // Initialize the cURL handle.
    curl_setopt($curl, CURLOPT_URL, $url);  // This is the URL we are calling.
    curl_setopt($curl, CURLOPT_HEADER, false);          // Do not return any headers, please.
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);   // Please return to sender as a function response.
    curl_setopt($curl, CURLOPT_VERBOSE, false);         // Let's keep this thing simple.
    
    $result = curl_exec($curl); // Do it to it.

    curl_close($curl);  // Bye, now.

    return $result;
}

/****************************************************************************************************************************/
/**
 */
function read_bmlt_server_list() {
    require_once(dirname(__FILE__).'/config/LGV_MeetingServer-Config.php');
    $json_data = call_URL($_tomato_server_list_file_uri);
    return json_decode($json_data);
}

/****************************************************************************************************************************/
/**
 */
function read_bmlt_server_meetings( $url,                   ///< REQIRED:   This is the base URL for the server's API.
                                    $server_id,             ///< REQUIRED: The integer ID of the server.
                                    $physical_only = false  ///< If true (default is false), then only meetings that have a physical location will be returned.
                                    ) {
    $json_data = call_URL("$url/client_interface/json/?switcher=GetSearchResults&get_used_formats=1");
    $decoded_json = json_decode($json_data);
    $meeting_objects = $decoded_json->meetings;
    $format_objects = $decoded_json->formats;
    $meetings = [];
    foreach($meeting_objects as $meeting_object) {
        $meeting = [];
        $meeting["server_id"] = $server_id;
        $meeting["meeting_id"] = $meeting_object->id_bigint;
        if ( trim($meeting_object->meeting_name) ) {
            $meeting["name"] = trim($meeting_object->meeting_name);
        }
        $meeting["weekday"] = intval($meeting_object->weekday_tinyint);
        $meeting["start_time"] = $meeting_object->start_time;
        $meeting["duration"] = $meeting_object->duration_time;
        $meeting["language"] = strtolower($meeting_object->lang_enum);
        if ( $meeting_object->comments ) {
            $meeting["comments"] = $meeting_object->comments;
        }
        if ( trim($meeting_object->location_street) && isset($meeting_object->longitude) && isset($meeting_object->latitude) ) {
            $meeting["physical_location"]["longitude"] = $meeting_object->longitude;
            $meeting["physical_location"]["latitude"] = $meeting_object->latitude;
            $meeting["physical_location"]["street"] = $meeting_object->location_street;
            if ( trim($meeting_object->location_text) ) {
                $meeting["physical_location"]["name"] = trim($meeting_object->location_text);
            }
            if ( trim($meeting_object->location_neighborhood) ) {
                $meeting["physical_location"]["neighborhood"] = trim($meeting_object->location_neighborhood);
            }
            if ( trim($meeting_object->location_city_subsection) ) {
                $meeting["physical_location"]["city_subsection"] = trim($meeting_object->location_city_subsection);
            }
            if ( trim($meeting_object->location_municipality) ) {
                $meeting["physical_location"]["city"] = trim($meeting_object->location_municipality);
            }
            if ( trim($meeting_object->location_sub_province) ) {
                $meeting["physical_location"]["county"] = trim($meeting_object->location_sub_province);
            }
            if ( trim($meeting_object->location_province) ) {
                $meeting["physical_location"]["province"] = trim($meeting_object->location_province);
            }
            if ( trim($meeting_object->location_postal_code_1) ) {
                $meeting["physical_location"]["postal_code"] = trim($meeting_object->location_postal_code_1);
            }
            if ( trim($meeting_object->location_nation) ) {
                $meeting["physical_location"]["nation"] = trim($meeting_object->location_nation);
            }
            if ( trim($meeting_object->location_info) ) {
                $meeting["physical_location"]["info"] = trim($meeting_object->location_info);
            }
        }
        
        if ( trim($meeting_object->virtual_meeting_link) ) {
            $meeting["virtual_meeting_info"]["url"] = trim($meeting_object->virtual_meeting_link);
            if ( trim($meeting_object->virtual_meeting_additional_info) ) {
                $meeting["virtual_meeting_info"]["info"] = trim($meeting_object->virtual_meeting_additional_info);
            }
            if ( trim($meeting_object->phone_meeting_number) ) {
                $meeting["virtual_meeting_info"]["phone_number"] = trim($meeting_object->phone_meeting_number);
            }
        }
        
        $id_list = array_map('intval', explode(",", $meeting_object->format_shared_id_list));
        if ( !empty($id_list) ) {
            $meeting["formats"] = [];
            foreach($format_objects as $format) {
                if ( in_array($format->id, $id_list)) {
                    $format_ar["key"] = $format->key_string;
                    $format_ar["name"] = $format->name_string;
                    $format_ar["description"] = $format->description_string;
                    $format_ar["language"] = strtolower($format->lang);
                    array_push($meeting["formats"], $format_ar);
                }
            }
        }
        if ( !$physical_only || isset($meeting["physical_location"]) ) {
            array_push($meetings, $meeting);
        }
    }
    
    return $meetings;
}

/****************************************************************************************************************************/
/**
 */
function read_all_bmlt_server_meetings( $physical_only = false  ///< If true (default is false), then only meetings that have a physical location will be returned.
                                        ) {
    $all_meetings = [];
    $server_list = read_bmlt_server_list();
    foreach ( $server_list as $server ) {
        $id = $server->id;
        $name = $server->name;
        $rootURL = $server->rootURL;
        $dataURL = $rootURL."client_interface/json/?switcher=GetSearchResults&get_used_formats=1";
        $semanticURL = $rootURL."semantic";
        $meetings = read_bmlt_server_meetings($dataURL, $id, true);
        array_push($all_meetings, $meetings);
    }
    
    return $all_meetings;
}