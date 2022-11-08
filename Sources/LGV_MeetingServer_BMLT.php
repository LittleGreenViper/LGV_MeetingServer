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
function read_bmlt_server(  $url    ///< REQIRED:   This is the base URL for the server's API.
                            ) {
    $json_data = call_URL("$url/client_interface/json/?switcher=GetSearchResults&get_used_formats=1");
    return json_decode($json_data);
}
