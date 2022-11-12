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
?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Test Aggregator</title>
    </head>
    <body>
        <ul>
            <li><h1>Empty Request</h1><ul>
                <li><a href="./entrypoint.php" target="_blank">I&apos;m A Teapot</a></li>
            </ul></li>
            <li><h1>Update</h1><ul>
                <li><a href="./entrypoint.php?update" target="_blank">Regular Update</a></li>
                <li><a href="./entrypoint.php?update&physical_only" target="_blank">Regular Update (Physical Only)</a></li>
                <li><a href="./entrypoint.php?update&force" target="_blank">Forced Update</a></li>
                <li><a href="./entrypoint.php?update&force&physical_only" target="_blank">Forced Update (Physical Only)</a></li>
            </ul></li>
            <li><h1>Query</h1><ul>
                <li><h2>Open Searches</h2><ul>
                    <li><h3>All Meetings <em>(No Paging)</em></h3><ul>
                        <li><a href="./entrypoint.php?query&page_size=0" target="_blank">Just Get the Metrics</a></li>
                        <li><a href="./entrypoint.php" target="_blank">Get All of It</a> <em>(The Whole Nine Yards. Find Something to Do. This Will Take a While to Render.)</em></li>
                        <li><h4>Weekday Filtering</h4><ul>
                            <li><a href="./entrypoint.php?query&weekdays=7,3,5,7,3" target="_blank">Tuesday, Thursday, and Saturday</a></li>
                        </ul></li>
                    </ul></li>
                    <li><h3>All Meetings <em>(Page Tests)</em></h3><ul>
                        <li><a href="./entrypoint.php?query&page_size=10" target="_blank">10-Meeting Pages, Page 0</a> <em>(Implicit first page)</em></li>
                        <li><a href="./entrypoint.php?query&page_size=10&page=0" target="_blank">10-Meeting Pages, Page 0</a> <em>(Explicit first page)</em></li>
                        <li><a href="./entrypoint.php?query&page_size=10&page=100" target="_blank">10-Meeting Pages, Page 100</a></li>
                        <li><a href="./entrypoint.php?query&page_size=100" target="_blank">100-Meeting Pages, Page 0</a> <em>(Implicit first page)</em></li>
                        <li><a href="./entrypoint.php?query&page_size=100&page=0" target="_blank">100-Meeting Pages, Page 0</a> <em>(Explicit first page)</em></li>
                        <li><a href="./entrypoint.php?query&page_size=100&page=8" target="_blank">100-Meeting Pages, Page 8</a></li>
                        <li><a href="./entrypoint.php?query&page_size=1000" target="_blank">1000-Meeting Pages, Page 0</a> <em>(Implicit first page)</em></li>
                        <li><a href="./entrypoint.php?query&page_size=1000&page=0" target="_blank">1000-Meeting Pages, Page 0</a> <em>(Explicit first page)</em></li>
                        <li><a href="./entrypoint.php?query&page_size=1000&page=-1" target="_blank">1000-Meeting Pages, Page -1</a> <em>(Should come up as Page 0)</em></li>
                        <li><a href="./entrypoint.php?query&page_size=1000&page=8" target="_blank">1000-Meeting Pages, Page 8</a></li>
                        <li><a href="./entrypoint.php?query&page_size=1000&page=27" target="_blank">1000-Meeting Pages, Page 27</a></li>
                        <li><h4>Weekday Filtering</h4><ul>
                            <li><a href="./entrypoint.php?query&page_size=100&page=0&weekdays=7,3,5,7,3" target="_blank">100-Meeting Pages, Page 0, Tuesday, Thursday, and Saturday</a></li>
                        </ul></li>
                    </ul></li>
                </ul></li>
                <li><h2>Geographic Searches</h2><ul>
                    <li><h3>Fixed-Radius, No Paging</h3><ul>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.3432&geocenter_lat=40.9009&geo_radius=10" target="_blank">Small Fixed-Radius Search, North Shore Long Island</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.3432&geocenter_lat=40.9009&geo_radius=50" target="_blank">Medium Fixed-Radius Search, North Shore Long Island</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.3432&geocenter_lat=40.9009&geo_radius=100" target="_blank">Large Fixed-Radius Search, North Shore Long Island</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.9665&geocenter_lat=40.7812&geo_radius=10" target="_blank">Small Fixed-Radius Search, Manhattan</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.9665&geocenter_lat=40.7812&geo_radius=50" target="_blank">Medium Fixed-Radius Search, Manhattan</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.9665&geocenter_lat=40.7812&geo_radius=100" target="_blank">Large Fixed-Radius Search, Manhattan</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=151.2093&geocenter_lat=-33.8688&geo_radius=10" target="_blank">Small Fixed-Radius Search, Sydney, Australia</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=151.2093&geocenter_lat=-33.8688&geo_radius=50" target="_blank">Medium Fixed-Radius Search, Sydney, Australia</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=151.2093&geocenter_lat=-33.8688&geo_radius=100" target="_blank">Large Fixed-Radius Search, Sydney, Australia</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=18.4241&geocenter_lat=-33.9249&geo_radius=10" target="_blank">Small Fixed-Radius Search, Cape Town, South Africa</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=18.4241&geocenter_lat=-33.9249&geo_radius=50" target="_blank">Medium Fixed-Radius Search, Cape Town, South Africa</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=18.4241&geocenter_lat=-33.9249&geo_radius=100" target="_blank">Large Fixed-Radius Search, Cape Town, South Africa</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-67.7452&geocenter_lat=-54.3084&geo_radius=1000" target="_blank">Large Fixed-Radius Search, Tierra Del Fuego</a></li>
                    </ul></li>
                    <li><h3>Fixed-Radius, Paging</h3><ul>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.9665&geocenter_lat=40.7812&geo_radius=100&page_size=100" target="_blank">Large Fixed-Radius Search, Manhattan, Page 0, of 100-Meeting Pages</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.9665&geocenter_lat=40.7812&geo_radius=100&page_size=100&page=3" target="_blank">Large Fixed-Radius Search, Manhattan, Page 3, of 100-Meeting Pages</a></li>
                    </ul></li>
                    <li><h3>Auto-Radius, No Paging</h3><ul>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.3432&geocenter_lat=40.9009&minimum_found=5" target="_blank">Search for about 5 meetings, North Shore Long Island (No Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-67.7452&geocenter_lat=-54.3084&minimum_found=5" target="_blank">Search for about 5 meetings, Tierra Del Fuego (No Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-67.7452&geocenter_lat=-54.3084&minimum_found=10" target="_blank">Search for about 10 meetings, Tierra Del Fuego (No Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-67.7452&geocenter_lat=-54.3084&minimum_found=100" target="_blank">Search for about 100 meetings, Tierra Del Fuego (No Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-67.7452&geocenter_lat=-54.3084&minimum_found=1000" target="_blank">Search for about 1000 meetings, Tierra Del Fuego (No Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.3432&geocenter_lat=40.9009&geo_radius=100&minimum_found=5" target="_blank">Search for about 5 meetings, North Shore Long Island (100Km Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.3432&geocenter_lat=40.9009&geo_radius=100&minimum_found=10" target="_blank">Search for about 10 meetings, North Shore Long Island</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.3432&geocenter_lat=40.9009&geo_radius=100&minimum_found=100" target="_blank">Search for about 100 meetings, North Shore Long Island</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.9442&geocenter_lat=40.6782&minimum_found=5" target="_blank">Search for about 5 meetings, Brooklyn (No Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.9442&geocenter_lat=40.6782&geo_radius=10&minimum_found=5" target="_blank">Search for about 5 meetings, Brooklyn (10Km Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.9442&geocenter_lat=40.6782&geo_radius=10&minimum_found=10" target="_blank">Search for about 10 meetings, Brooklyn (2Km Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.9442&geocenter_lat=40.6782&geo_radius=1&minimum_found=1" target="_blank">Search for about 10 meetings, Brooklyn (1Km Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-108.5007&geocenter_lat=45.7833&minimum_found=5" target="_blank">Search for about 5 meetings, Montana (No Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-108.5007&geocenter_lat=45.7833&minimum_found=25" target="_blank">Search for about 25 meetings, Montana (No Limit)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-108.5007&geocenter_lat=45.7833&geo_radius=200&minimum_found=25" target="_blank">Search for about 25 meetings, Montana (200Km Limit)</a></li>
                    </ul></li>
                    <li><h3>Auto-Radius, Paging</h3><ul>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-67.7452&geocenter_lat=-54.3084&minimum_found=1000&page_size=100" target="_blank">Search for about 1000 meetings, Tierra Del Fuego (No Limit, Page Size: 100)</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-73.9442&geocenter_lat=40.6782&minimum_found=1000&page_size=100&page=3" target="_blank">Search for about 1000 meetings, Brooklyn (No Limit, Page Size: 100, Page 3)</a></li>
                    </ul></li>
                </ul></li>
                <li><h2>Individual IDs</h2><ul>
                    <li><a href="./entrypoint.php?query&ids=(152,16573)" target="_blank">1 Meeting</a></li>
                    <li><a href="./entrypoint.php?query&ids=(153,1),(153,2),(153,3)" target="_blank">3 Invalid Meetings</a></li>
                    <li><a href="./entrypoint.php?query&ids=(153,1),(152,16573),(153,2),(153,3),(99,9)" target="_blank">2 Valid Meetings, 3 Invalid Meetings</a></li>
                    <li><a href="./entrypoint.php?query&ids=(99,2),(100,5912),(152,16573)" target="_blank">3 Meetings, From 3 Servers</a></li>
                    <li><a href="./entrypoint.php?query&ids=(99,2),(99,9),(100,5901),(100,5912),(100,8206),(100,8272),(152,16573),(152,16592),(152,16605),(152,16627)" target="_blank">10 Meetings, From 3 Servers</a></li>
                    <li><a href="./entrypoint.php?query&ids=(99,2),(99,9),(100,5901),(100,5912),(100,8206),(100,8272),(120,1520),(120,1824),(120,1855),(134,940),(146,10228),(149,291),(134,996),(134,1077),(137,5897),(137,5908),(137,5925),(137,9341),(99,2),(141,1132),(141,1140),(144,624),(144,639),(144,642),(144,649),(145,2573),(145,2581),(146,2926),(146,2938),(146,2943),(146,2947),(146,2950),(146,10219),(146,10220),(149,294),(149,302),(151,41),(151,54),(151,58),(152,16573),(152,16592),(152,16605),(152,16627)" target="_blank">42 Meetings, From 12 Servers</a></li>
                    <li><h3>Geofencing</h3><ul>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-47.2694&geocenter_lat=-22.8222&geo_radius=1000&ids=(99,2),(99,9),(100,5901),(100,5912),(100,8206),(100,8272),(120,1520),(120,1824),(120,1855),(134,940),(134,996),(134,1077),(137,5897),(137,5908),(137,5925),(137,9341),(141,1132),(141,1140),(144,624),(144,639),(144,642),(144,649),(145,2573),(145,2581),(146,2926),(146,2938),(146,2943),(146,2947),(146,2950),(146,10219),(146,10220),(146,10228),(149,291),(149,294),(149,302),(151,41),(151,54),(151,58),(152,16573),(152,16592),(152,16605),(152,16627)" target="_blank">42 Meetings, From 12 Servers, But Geofenced For Brazil</a></li>
                    </ul></li>
                    <li><h3>Weekday Filtering</h3><ul>
                        <li><a href="./entrypoint.php?query&ids=(99,2),(99,9),(100,5901),(100,5912),(100,8206),(100,8272),(152,16573),(152,16592),(152,16605),(152,16627)&weekdays=1" target="_blank">10 Meetings, From 3 Servers, But Filtered For Only Sunday</a></li>
                        <li><a href="./entrypoint.php?query&ids=(100,8272),(99,9),(100,5901),(100,5912),(100,8206),(152,16573),(152,16592),(152,16605),(152,16627)&weekdays=7,1" target="_blank">10 Meetings, From 3 Servers, But Filtered For Only Sunday and Saturday</a></li>
                        <li><a href="./entrypoint.php?query&geocenter_lng=-47.2694&geocenter_lat=-22.8222&geo_radius=1000&weekdays=4&ids=(99,2),(99,9),(100,5901),(100,5912),(100,8206),(100,8272),(120,1520),(120,1824),(120,1855),(134,940),(134,996),(134,1077),(137,5897),(137,5908),(137,5925),(137,9341),(141,1132),(141,1140),(144,624),(144,639),(144,642),(144,649),(145,2573),(145,2581),(146,2926),(146,2938),(146,2943),(146,2947),(146,2950),(146,10219),(146,10220),(146,10228),(149,291),(149,294),(149,302),(151,41),(151,54),(151,58),(152,16573),(152,16592),(152,16605),(152,16627)" target="_blank">42 Meetings, From 12 Servers, But Filtered For Brazil, and Thursday</a></li>
                    </ul></li>
                </ul></li>
            </ul></li>
        </ul>
    </body>
</html>
