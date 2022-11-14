<?php
/***************************************************************************************************************************/
/**
    This is the main entrypoint file for the LGV_MeetingServer basic server-level unit tests.
    
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
global $config_file_path;
$config_file_path = dirname(dirname(dirname(__FILE__))).'/LGV_MeetingServer-Config/LGV_MeetingServer-Config.php';

define( 'LGV_MeetingServer_Files', 1 );
require_once(dirname(dirname(__FILE__)).'/Sources/LGV_MeetingServer_Entrypoint.php');
