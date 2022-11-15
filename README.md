# ``LGV_MeetingServer``

## Overview

![The Project Icon](icon.png)

This is a general-purpose aggregator server for various meeting lists.

[The source code is available at this GitHub repo.](https://github.com/LittleGreenViper/LGV_MeetingServer)

[This is a Live Example (Test Harness)](https://littlegreenviper.com/LGV_MeetingServer/Tests/)

## How It Works

The server is designed to allow you to write "modules," that can connect to multiple servers, and reformat their meeting data, into a common format, which can then be read as a "unified" set, in a common format.

The aggregator maintains a very simple SQL database, with a defined format (regardless of the format of its origins). Queries are then executed against that data.

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
    $config_file_path = '< PATH TO CONFIG FILE >';

You will then need to include the `LGV_MeetingServer_Entrypoint.php` file of the server, from the source directory:

    define( 'LGV_MeetingServer_Files', 1 );
    require_once('< PATH TO SERVER DIRECTORY >/Sources/LGV_MeetingServer_Entrypoint.php');

That's all you need to do. If the configuration file is set up correctly, the server will set itself up, the first time an update is run. You should have one successful update performed, before applying queries.

[Here is a sample config file.](https://github.com/LittleGreenViper/LGV_MeetingServer/blob/master/Tests/config/LGV_MeetingServer-Config.php)

[Here is a sample entrypoint file.](https://github.com/LittleGreenViper/LGV_MeetingServer/blob/master/Tests/entrypoint.php)

Note that the `LGV_MeetingServer_Files` macro is declared (and set to 1). This is a simple macro that prevents individual files from being run, unless they are part of the hierarchy, defined by the entrypoint.

## Server API

This is the explicit server API. The server is not a traditional "CRUD" server. It's a very simple "call and response" server, with calls being GET HTTP requests, and responses being simple JSON (or HTTP headers, if there's an issue).

### The Calling URI

Each call to the server will have a structure like so (this is just an example):

    https://meetingserver/entrypoint.php?query&page_size=100&page=3&weekdays=2,3,4,5,6

Here's a simple breakdown:

    https://meetingserver/entrypoint.php   ?   query     &page_size=100&page=3&weekdays=2,3,4,5,6
    ↖                                  ↗       ↖   ↗     ↖                                      ↗
                The Base URL                  Function                Query Parameters

#### The Base URL

This will be the hostname and path, directly to the PHP file that will act as the entry point to the server. You will create this file, and it will have the structure prescribed, above.

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
   This means that any "pure virtual" meetings encountered, will be read, but assigned a different organization key (in this case, "virtual-na").
  
#### Query Response

Each query will result in a JSON response, consisting of two parts: `meta`, and `meetings`. `meta` is a JSON object, with some basic information about the query and the response, and `meetings` is an array of JSON objects, representing each meeting.

#### Query Parameters

These are the refinements to the command requested by the `"query"` function.

[The Live Test Page has examples of these.](https://littlegreenviper.com/LGV_MeetingServer/Tests/)

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
  
- **Organization Parameters**

  We can search for meetings that have certain organization keys.
  
  - *org_key*
  
  This is a string, and specifies the organization we are filtering for. We can specify multiple values, separated by commas (,). Examples might be `org_key=na` (in-person NA meetings only), `org_key=virtual-na` (virtual NA meetings only), or `org_key=na,virtual-na` (both in-person, and virtual, NA meetings).
 
- **Geographic Parameters**

  You can search the database, based on a geographic center, and a prescribed (or implied) radius, around that center.
  
  - *geocenter_lng*
  
  This is the longitude of the geographic center. It is in degrees, as a floating-point number (-180.0 -> 180.0). If provided, you must also provide `geocenter_lat`.
  
  - *geocenter_lat*
  
  This is the latitude of the geographic center. It is in degrees, as a floating-point number (-90.0 -> 90.0). If provided, you must also provide `geocenter_lng`.
  
  Specifying these two coordinates will define a geographic search, but you still need to provide **at least one** of the following two parameters, in order to complete the geographic search specification:
  
  - *geo_radius*
  
  This is the radius of the search, as a fixed value, so every meeting that has its long/lat coordinates within this circle, will be returned. It is in kilometers, specified as a floating-point number.
  
  >**NOTE:** If you specify this, along with `minimum_found`, then this will be the maximum radius possible for an auto-radius search. If not provided, the maximum radius will be 10,000 Km.
  
  - *minimum_found*
  
  This will be a positive integer value, and will specify a "target" number of meetings to be found, by increasing the radius around the center, in steps, until at least this many meetings are found. The final radius will be returned in the "meta" object, in the search results.
  
  >**NOTE:** In most cases, this will be ***at least*** the number of meetings found, but, occasionally, there may be a few meetings not included. That is because there is a small amount of "slop" in the radius calculations.
  
  >**NOTE:** This minimum number will be applied ***after*** the other filter parameters, so, for example, if you are looking only for meetings on the weekend, the final radius is likely to be larger, than if you search for meetings that gather on any day.
  
- **Time And Day Parameters**

  We have the ability to filter for meetings that occur only on certain weekdays, or that begin at certain times of the day. Times are integers (0 - 86399), given in seconds from midnight, this morning (00:00:00), and weekdays are integers (1-7) always 1 = Sunday, and 7 = Saturday, regardless of when the week starts, locally. You can specify more than one weekday (they are used in an "OR" fashion), and times are always *start* times (duration of a meeting is not taken into account).
  
  - *weekdays*
  
  This is one or more integers (1-7), separated by commas (,), if more than one weeekday is specified. An example of Sunday, Tuesday, and Thursday would be `weekdays=1,3,5`. In that case, any meeting that gathered on any of these days would be included, and meetings that gathered on other days, would be excluded.
  
  - *start_time*
  
  This means that meetings that start at, or after, the given time (seconds from midnight, this morning 0 - 86399), will be included in the found set.
  
  >**NOTE:** If `end_time` is specified, `end_time` must be greater than `start_time`, or it will be ignored (`start_time` is dominant).
  
  - *end_time*
  
  This means that meetings that start at, or before, the given time (seconds from midnight, this morning 0 - 86399), will be included in the found set.
  
  >**NOTE:** If `start_time` is specified, `end_time` must be greater than `start_time`, or it will be ignored (`start_time` is dominant).
  
- **Individual (And Server) Meeting IDs**

  It is possible to filter out an explicit (or "wildcard") IDs for meetings.
  
  - *ids*
  
  This will have integer pairs, given as "`(<SERVER ID>,<MEETING ID>)`". The parentheses are required, as is the `<SERVER ID>`. The `<MEETING ID>` is optional. If not provided, or set to 0 (or a non-integer chracter, such as "*"), then all meetings on that server are included in the found set. If provided, but the meeting is not available at that ID, the ID is considered invalid, and does not apply.
  
  These can be specified as multiple values, separated by commas (,). For example: `ids=(99,10),(99,11)` will target two meetings, on one server. `ids=(99,10),(100,10)` will target two meetings, on two different servers.
  
  **Server IDs and Meeting IDs**
  
  Every server that is queried for meetings, has a `server_id` column, which is a 1-based, positive integer ID, that is unique (but repeated for every meeting table row for that server). Every meeting has a `meeting_id` table column, which is unique, on the server. Between the two values, each meeting has a unique identifier on the server, and specifying "`(<SERVER ID>,<MEETING ID>)`" will target exactly one meeting row.
  
  **Wildcards**
  
  It is possible to specify "all the meetings provided by this server", by specifying only the `server_id` value. For exaple: `ids=(99)`,  `ids=(99,0)`,  `ids=(99,*)` all specify every meeting on the server identified by an ID of 99. `ids=(99),(100)` Will specify all the IDs in servers 99, and 100. Any servers and/or meetings, not mentioned in the `ids` parameter, will not be included in the found set.

### The Response Data

Data will always be returned as an optimized [JSON](https://json.org) object, with various formats, depending upon the request. If a field has no value, it is generally not included.

Here are the schemas for the various responses, assigned to the function:

#### `update`

  >**NOTE:** It is possible to prevent the `update` function from working from the HTTP invocation (only available via command line). This is so that we can regulate the updates, via things like [`cron`](https://en.wikipedia.org/wiki/Cron) tasks. This is done by setting the `$_use_cli_only_for_update` variable to `true`, in [the configuration file](https://github.com/LittleGreenViper/LGV_MeetingServer/blob/master/Tests/config/LGV_MeetingServer-Config.php).
  
  If an update is successful, it will return a respoonse like so:
  
```
{
  "number_of_meetings": 32790,
  "time_in_seconds": 55.877379179001
}
```

  The `number_of_meetings` value is the total number of meetings process, from all servers, in all services, and available for searching.
  
  The `time_in_seconds` value, is how long it took, to run the operation.
  
  If the update is ignored (like the elapsed time period, specified by the variable `$_updateIntervalInSeconds`, in [the configuration file](https://github.com/LittleGreenViper/LGV_MeetingServer/blob/master/Tests/config/LGV_MeetingServer-Config.php), has not passed, no output is returned (You get [an HTTP 204](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/204) response).

#### `Info`

  The `info` function returns a JSON object that looks (more or less) like this:
```
{
    "last_update_timestamp": 1668520598,
    "organizations": {
        "total_meetings": 32689,
        "na": 27908,
        "virtual-na": 4781
    },
    "server_ids": [
        99,
         •
         •
         •
        153
    ],
    "services": {
        "BMLT": {
          "service_name": "BMLT",
          "servers": {
            "99": {
              "name": "Aotearoa New Zealand Region",
              "url": "https://bmlt.nzna.org/bmlt/main_server/",
              "num_meetings": 215
            },
                •
                •
                •
            "153": {
              "name": "Tri-State Region",
              "url": "https://tsrscna.org/bmlt_dev/main_server/",
              "num_meetings": 498
            }
          }
        }
    }
}
```

  Here are the fields:

- `last_update_timestamp`
  This is the [UNIX Timestamp](https://www.unixtimestamp.com) of the time that the last successful update of the database completed.

- `organizations`
  This is a list of objects, reflecting the "organizations," within the server.
  
  `total_meetings` has the total number of meetings, between all organizations.

  Under that, each organization is listed as a key (the `org_key` value), and the number of meetings in that organization.

- `server_ids`
  This has the actual `server_id` values, amongst all the database table rows.
  
- `services`
  This lists each of the "reader module" services (currently, we only have [the BMLT](https://bmlt.app) supported). These list the service name, and each of the servers that it has accessed for meeting information, along with the server name, and how many meetings are assigned to that server. The key is the numerical server ID (as a string).
  
  - `service_name`
    This is the name of the service.
    
  - `servers`
    This has a list of servers.
    
    Each `server` is a JSON object, named as the ID (a string representation of the server's integer ID), with the following properties:
    
    - `name`
      The readable string name for the server.
      
    - `url`
      The API endpoint URL for the server.
      
    - `num_meetings`
      The number of meetings, in the dataset, covered by this server.

#### `query`

  The query, itself, returns a JSON object that has the following main structure:
  
```
{
  "meta": {
        •
        •
        •
  },
  "meetings": [
        •
        •
        •
  ]
}
```

  We'll examine each of the two main objects, in detail, below.
  
##### `meta`

This contains information about the query. The number of fields can vary, depending on what type of query has been sent.

Here is a sample of a "full-featured" `meta` object:

```
"meta": {
    "total": 832,
    "total_pages": 9,
    "page_size": 100,
    "page": 3,
    "starting_index": 300,
    "actual_size": 100,
    "search_time": 0.16288781166077,
    "center_lat": 40.7812,
    "center_lng": -73.9665,
    "radius_in_km": 100
}
```

###### Always Available

- `total`

  This is a positive integer, with the total number of meetings returned in the found set. The number of meetings in the `meetings` array may be less than this.

- `total_pages`

  This is a positive 0-based integer, integer, with the total number of pages, required to represent the entire found set, if `page_size` is less than `total`. If this is a "metrics-only" query, then this will be 0.

- `page_size`

  This is a positive 0-based integer, integer, with the number of meetings per page. If this is a "metrics-only" query, then this will be 0. If the query is not paged, then this will be equal to `total`.

- `page`

  This is a positive 0-based integer, with the total number of pages, required to represent the entire found set, if `page_size` is less than `total`. If this is a "metrics-only" query, then this will be 0. If the response is not paged, it will be 1.

- `starting_index`

  This is a positive 0-based integer, with the index of the first meeting, in this page. For example, if we have more than 300 meetings, and a `page_size` of 100, and this is `page` 3, the `starting_index` value will be 300.

- `actual_size`

  This is a positive 0-based integer, with the actual number of meetings in this page. It will be `page_size`, or less.
  
- `search_time`

  This is a positive floating point number, with the time, in seconds, that it took to perform the database query, and prepare the response. It does not include transmission time.

###### Only Available for Geographic Queries

- `center_lat`

  This is a floating point number, between -90, and 90, with the latitude of the georaphic search center. It is in degrees.

- `center_lng`

  This is a floating point number, between -180, and 180, with the longitude of the georaphic search center. It is in degrees.

- `radius_in_km`

  This is a positive floating point number, with the last radius selected (if auto-radius), or the fixed radius, supplied to the query. It is in kilometers.
  
##### `meetings`

This is an array of `meeting` JSON objects.

Here is a sample of a "full-featured" `meeting` object:

```
{
  "server_id": 122,
  "meeting_id": 292,
  "organization_key": "na",
  "name": "Easy Does It",
  "start_time": "20:00:00",
  "weekday": 4,
  "duration": 5400,
  "language": "en",
  "longitude": -83.4139,
  "latitude": 36.0168,
  "comments": "Zoom Meeting ID: 339 696 7992";
  "formats": [
    {
      "key": "O",
      "name": "Open",
      "description": "This meeting is open to addicts and non-addicts alike. All are welcome.",
      "language": "en"
    },
    {
      "key": "H",
      "name": "Handicapped accessible",
      "description": "Handicapped accessible",
      "language": "en"
    },
    {
      "key": "BK",
      "name": "Book Study",
      "description": "Approved N.A. Books",
      "language": "en"
    },
    {
      "key": "IW",
      "name": "It Works -How and Why",
      "description": "This meeting is focused on discussion of the It Works -How and Why text.",
      "language": "en"
    },
    {
      "key": "BT",
      "name": "Basic Text",
      "description": "This meeting is focused on discussion of the Basic Text of Narcotics Anonymous.",
      "language": "en"
    }
  ],
  "physical_address": {
    "street": "121 E. Meeting Street",
    "name": "First United Methodist Church",
    "neighborhood": "Other Side of the Tracks",
    "city_subsection": "East Side Borough",
    "city": "Dandridge",
    "county": "Jefferson",
    "province": "TN",
    "postal_code": "37725"
    "info": "Park across the street."
  },
  "virtual_information": {
    "url": "https://us02web.zoom.us/j/3396967992?pwd=UVBacGFjN3dMdVVjKzkwYnhraC9HUT09",
    "info": "Meeting ID: 339 696 7992";
    "phone_number": "+1 301 715 8592"
  }
}
```

###### Always Available

- `server_id`
  This is a positive, nonzero integer, with the numerical ID of the server, responsible for this meeting. It is not unique, in the found dataset, and must be combined with `meeting_id`, to specify a unique table row (meeting).
  
- `meeting_id`
  This is a positive, nonzero integer, with the numerical ID of the server, responsible for this meeting. It is unique, for the server, but not within the found dataset, and must be combined with `server_id`, to specify a unique table row (meeting).

- `organization_key`
  This is a string, with the organization that is assigned to this table row.
  
- `language`
    If specified, the language that the meeting information is provided in. This is an [ISO 639](https://www.loc.gov/standards/iso639-2/php/code_list.php) code.
  
- `formats`
  This is an array of format objects. It may be empty. Each format object is a simple JSON object, containing the following:
  
  - `key`
    This is the string "key" of the format. It should be unique, within the format object.
    
  - `name`
    This is a short name for the format.
    
  - `description`
    This is a longer desctription of the format.
    
  - `language`
    If specified, the language that the format information is provided in. This is an [ISO 639](https://www.loc.gov/standards/iso639-2/php/code_list.php) code.

###### Only Supplied, If Available

- `name`
  The meeting name.
  
- `start_time`
  The meeting start time, supplied as a string ("HH:MM:SS").
  
- `weekday`
  The meeting weekday, expressed as a 1-based, positive integer, with 1 being Sunday, and 7 being Saturday. Any other value is not considered valid.
  
- `duration`
  The meeting duration, expressed in seconds. This is a positive, 0-based integer, 0 - 86399.
  
- `longitude`
  This is a floating point number, between -180.0, and 180.0. It is the longitude, in degrees, of the meeting's physical location. This will not be avaialbal for virtual-only meetings.
  
- `latitude`
  This is a floating point number, between -90.0, and 90.0. It is the latitude, in degrees, of the meeting's physical location. This will not be avaialbal for virtual-only meetings.

## License

Copyright 2022 [Little Green Viper Software Development LLC](https://littlegreenviper.com)

The SDK is provided as [MIT](https://opensource.org/licenses/MIT)-licensed code.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


