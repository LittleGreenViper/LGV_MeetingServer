# ``LGV_MeetingServer``

## Overview

![The Project Icon](icon.png)

This is a general-purpose aggregator server for various meeting lists.

[The source code is available at this GitHub repo.](https://github.com/LittleGreenViper/LGV_MeetingServer)

## How It Works

The server is designed to allow you to write "modules," that can connect to multiple servers, and reformat their meeting data, into a common format, which can then be read as a "unified" set, in a common format.

The aggregator maintains a very simple SQL database, with a common format (regardless of the format of its origins). Queries are then executed against that data.

### Reading the Data Into the System

Most of the "work" happens in the "gathering" service, which is triggered at regular intervals. Each "module" reads the servers that it is responsible for, and conditions that server's unique format, to the common format for the aggregator.

### Querying the Data

Most of the "action," happens when the database is queried. There are a number of table columns that can be queried (more on that, in a bit), and the response is always returned as optimized JSON (also, more on that, in a bit). This is designed as a simple semantic data source, and is not meant for immediate display to users.

## What Is the Data?

Glad you asked. This has been designed specifically, as an aggregator and façade, for regularly-occurring (weekly), repeating events. It supports both geographically-centered events ("in-person"), and "virtual" events (or combinations of both).

The events, in the initial phase of this project, are 12-Step Recovery organization meetings; notably, [NA](https://na.org) meetings. It has been designed to allow other types of meetings to be added to the database, as well.

The database is queried using the default language for every server, so expect servers based in France, to have their data in French.

### Meeting Time Data

Each meeting has columns that represent a start time (given in seconds, since midnight), a duration (also in seconds), and a weekday (1-7, with 1 always Sunday).

### Meeting Location Data

If the meeting is an "in-person" meeting, there is a "physical location," associated with it. This has fairly standard address components. Also, the main table row has a longitude column, and a latitude column, for geographic searches. These columns are NULL, if the meeting is virtual-only.

### Virtual Meeting Information

If the meeting has a virtual component (or is a virtual-only meeting), there will be an array associated, with the information for that virtual meeting, like a URL, phone number, meeting ID, password, etc.

### Meeting Format Data

Each meeting has an atomic "formats" array, containing a simple breakdown of the formats for the meeting.

## Query types

The query format is not a "comprehensive" search. It is designed to give a "quick and dirty" triage of the data, and we expect the query consumer to apply a more refined filtering and organization of the data. The main purpose of this server, is to provide a manageable found set, for more specific, and application-dependent processing.

There are three basic ways to query the database:

### Direct (Raw) Queries.

These have no implicit filtering, and an empty direct query will return the entire database (which can be quite large).

### Geographic Queries

These are queries that are focused on a specific location, given by a longitude/latitude (in degrees) pair. It is possible to have a fixed radius, where all the meetings that are geolocated within the circle around the center, are returned, or an "auto" radius, where an expanding radius is repeatedly executed, until a specified number of results has been found.

### Individual ID Queries

These queries provide a set of meeting IDs (which consist of a server ID, and a local meeting ID -more on that, later). Further filtering can be done on the set, but only meetings specifically referenced will be available for the search.

It is possible to have limited "wildcard" ID searches, where all the meetings provided by a particular source server, are provided as the initial found set. More on that, in a bit.

IDs are added to the search, in an "OR" fashion. Most of the filters are applied in an "AND" fashion.

## Query Filtering

Each of the above queries can have filters applied, which will affect/narrow the found set of meetings.

### Geofence 

As mentioned above, a geofence can be applied to a query, restricting the found set to a radius around a center. This will not return "pure virtual" meetings, which do not have a location.

### Organization 

Each meeting is designated to be part of an "organization," which is applied by the module that read the meeting from its origin server. Currently, there are only two "organizations": "na," and "virtual-na," with the former being in-person NA meetings, and the latter being "pure virtual" (no physical location) meetings.

Organizations are not specific to any single source server. Multiple servers can provide data that is tagged as belonging to an organization.

### Weekday

Each meeting is designated as gathering weekly, on a specific day. 1 is always Sunday (regardless of the locale week start), and 7 is always Saturday. Multiple weekdays can be applied, in an "OR" fashion.

### Start Time Window

You can filter for meetings that start on or after a certain time, or that start before, or at, a certain time. Both can be applied, so you can have a "time window" for meeting starts.

## Installation

### Server Technology Stack.

The server is written in very basic [PHP](https://php.net). Its initial implementaion is designed for a [MySQL](https://mysql.com) database server, but the server uses [PDO](https://www.php.net/manual/en/book.pdo.php), and modifying to use other types of servers (like [Postgres](https://postgresql.org)), should be fairly straightforward.

The database, itself, is absurdly simple. It just has one single data table, with each row being an atomic entity, representing one meeting. There are no relations. There is also a "meta" table, that is used to track the last update time, so that updates occur regularly.

### Security Considerations

There's very little, in the way of security risks, with the server. The most sensitive information, is the database login information. No user PID is kept.

The configuration file, which contains the databse information, can be stored outside the HTTP directory. It is referenced by an [`include(...)`](https://www.php.net/manual/en/function.include.php) function.

### Setting Up the Server 

You'll need to have about the same kind of "raw materials" as you would need, to set up a [WordPress](https://wordpress.org/support/article/before-you-install/) server: A MySQL database, and a PHP server that is running late-version (8.0 or greater is recommended, but it will probably work fine with 7.4) PHP.

Most of the server files can be stored outside the HTTP path. They are all included. The only file that needs to be in the HTTP path, is the entrypoint file that you write. An example, is the [`entrypoint.php`](https://github.com/LittleGreenViper/LGV_MeetingServer/blob/master/Tests/entrypoint.php) file, in the testing directory of the project.

In your entrypoint file, you'll need to declare `$config_file_path` as a global variable, and set it to reference the configuration file:

    global $config_file_path;
    $config_file_path = dirname(__FILE__).'/config/LGV_MeetingServer-Config.php';

You will then need to include the `LGV_MeetingServer_Entrypoint.php` file of the server, from the source directory:

    define( 'LGV_MeetingServer_Files', 1 );
    require_once(dirname(dirname(__FILE__)).'/Sources/LGV_MeetingServer_Entrypoint.php');

That's all you need to do. If the configuration file is set up correctly, the server will set itself up, the first time an update is run. It should have one successful update, before applying queries.

Note that `LGV_MeetingServer_Files` macro is declared (and set to 1). This is a simple macro that prevents individual files from being run, unless they are part of the hierarchy, defined by the entrypoint.

## Server API

This is the explicit server API. The server is not a traditional "CRUD" server. It's a very simple "call and response" server, with calls being GET HTTP requests, and responses being simple JSON (or HTTP headers, if there's an issue).

### The Calling URI

Each call to the server will have a structure like so (this is just an example):

    https://meetingserver/entrypoint.php?query&page_size=100&page=3&weekdays=2,3,4,5,6
    
    https://meetingserver/entrypoint.php   ?  query     &page_size=100&page=3&weekdays=2,3,4,5,6
    ↑                                  ↑      ↑   ↑     ↑                                      ↑
                The Base URL                 Function                Query Parameters

#### The Base URL

This will be the hostname and path, directly to the PHP file that will act as the entry point to the server. It will have the structure prescribed, above.

After that, will be the question mark delimiter, and the Function will be assigned.

#### The Function

This indicates the server function that we are invoking. It can be:

- **query**
  This will be the function invoked most often. It queries the database, and expects meeting data to be returned as JSON. There are a number of possible parameters, that will be defined, below.

- **info**
  This asks the server to return a JSON object that defines some basic server structure, like the number of meetings, organizations, and even module servers. There are no parameters to this call.

- **update**
  This gives the server execution thread, to perform an update of its data. Unless forced, calls with this function will usually end with a "204 No Content" success code (a JSON object is returned, if an update was performed). There are only three parameters to this call:
  
   `https://meetingserver/entrypoint.php?update&force&physical_only&separate_virtual`
  
   - *force*
   This means that the server should run the update, even if the elapsed time, specified in the config file, has not passed. The last update time will be changed to reflect this update.
  
   - *physical_only*
   This means that "pure virtual" meetings will be ignored, and only meetings with a physical presence will be added. If `separate_virtual` is defined, then this is ignored.
  
   - *separate_virtual*
   This means that an "pure virtual" meetings encountered, will be read, but assigned a different organization key (in this case, "virtual-na").
  
#### Query Response

Each query will result in a JSON response, consisting of two parts: `meta`, and `meetings`. `meta` is a JSON object, with some basic information about the query and the response, and `meetings` is an array of JSON objects, representing each meeting.

#### Query Parameters

These are the refinements to the command requested by the `"query"` function.

- **Paging**
  It is possible to "page" large query responses. This is the process of breaking up a very large response, into discrete "chunks." For example, if a query returned 3,000 meetings, it could be a lot of memory overhead to parse that much JSON, and you could also tie up the connection for a long time. That could be a problem, if there was "spotty" Internet service.
  
  Instead, what we do, is ask the server to break the response into "pages," and send us only the portion of the whole that is on a given "page."
  
  For example, we could break the 3,000 meeting response into 30 pages of 100 meetings, then ask for Page 0 (0 ... 299), Page 1 (300 ... 399), etc.
  
  - *page_size*
  
  This is the size of each page. It is 1-based. In the above example, this would be 100.
  
  - *page*
  
  This is which page to send. It is 0-based, so page 0, is 0 -> `page_size` - 1 meetings.
  
  **Just Getting the Paging Metrics**
  
  If you specify a `page_size` of 0, then only the "metrics" of the query response will be returned (how many meetings).
 
## License

Copyright 2022 [Little Green Viper Software Development LLC](https://littlegreenviper.com)

The SDK is provided as [MIT](https://opensource.org/licenses/MIT)-licensed code.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


