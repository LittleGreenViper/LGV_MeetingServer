**1.4.12** *February 21, 2024*

- The 24-hour fix went the wrong way. That has been fixed.

**1.4.11** *February 11, 2024*

- Some meetings are listed as having durations of 86400 (24 hours). I had to make these 86399.

**1.4.10** *January 24, 2024*

- The temp table should be getting deleted properly, now.
- The "TC" format now removes a physical location, for BMLT meetings.

**1.4.9** *January 17, 2024*

- The temp table was not being deleted, after a run. It is, now. TODO: This only happens after a successful run, but the temp table would be empty after an unsuccessful run. It may be a good idea to add a "garbage collector."

**1.4.8** *October 3, 2023*

- Code cleanup, and improved error trapping.

**1.4.7** *October 3, 2023*

- Changed the UA, to hopefully discourage bounces.

**1.4.6** *September 30, 2023*

- Just a tiny addition, to allow the server to detect no query string, and, instead of puking with an error, ir returns a teapot (CLI only).

**1.4.4** *August 2, 2023*

- The meta table now sets the time at the start of processing. This helps to avoid collisions.

**1.4.3** *July 31, 2023*

- The temp table now uses a random string, to avoid the table being deleted by overruns.

**1.4.2** *July 30, 2023*

- Bumped up the timeout.

**1.4.1** *June 19, 2023*

- Some code cleanup and documentation.

**1.4.0** *June 19, 2023*

- Improved the meeting type filter.

**1.3.0** *June 19, 2023*

- Added a filter for venue type.

**1.2.0** *June 18, 2023*

- Added a connection to the LGV_TZ_Lookup server, so all meetings get a timezone. This considerably increases the time it takes to do an update.

**1.1.5** *March 20, 2023*

- Some virtual meetings only provide a phone number (no URI), so I just look for that, if no URI is found.

**1.1.4** *January 6, 2023*

- Small change to the user agent, to make it a generic Linux Firefox one, in a pathetic effort to avoid being put on naughty lists.

**1.1.3** *December 9, 2022*

- Added a user agent to the cURL call, as it was being blocked.

**1.1.2** *December 1, 2022*

- Cleaned up the `venue_type` field parsing.

**1.1.1** *December 1, 2022*

- If the `venue_type` field is returned from a BMLT server, that is used to filter for in-person vs. virtual.

**1.1.0** *November 17, 2022*

- I needed to add the ID to each format. This is a slight API change.

**1.0.5** *November 16, 2022*

- The help text wasn't exactly right, for the update.

**1.0.4** *November 16, 2022*

- Added the server version to the server info response.

**1.0.3** *November 16, 2022*

- No API change. Just corrected some documentation.

**1.0.2** *November 16, 2022*

- Further Improved Server Info, by adding meeting breakdowns for each server's organizations.

**1.0.1** *November 15, 2022*

- Improved Server Info, by adding organization breakdowns for each server.

**1.0.0** *November 15, 2022*

- Initial Release
