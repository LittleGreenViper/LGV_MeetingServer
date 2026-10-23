# Project server URLs

- Prerelease test server: https://recovrr.org/recovrr/testMeetings/entrypoint.php
  Use this as the default working URL for development and testing before production release.
- Production server: https://recovrr.org/recovrr/meetings/entrypoint.php
  Use this when the task specifically concerns production.

# Timezone lookup dependency

- LGV_TZ_Lookup is a separate project controlled by the user and used directly as a Composer module by the meeting server.
- Local working repository: `/Users/chrismarshall/Developer/LGV/LGV_TZ_Lookup/`
- GitHub repository: https://github.com/LittleGreenViper/LGV_TZ_Lookup
- Composer package: `littlegreenviper/lgv_tz_lookup` (currently required as `^1.4.2` via a VCS repository).
- Use the local timezone repository when investigating or changing the dependency; read its applicable project instructions before making changes.

# Meeting data sources

- Meeting data comes from BMLT (Basic Meeting List Toolbox): https://bmlt.app
- The user does not directly control BMLT, but communicates regularly with its maintainers.
- The list of servers providing meeting data comes from the BMLT aggregator project:
  https://raw.githubusercontent.com/bmlt-enabled/aggregator/refs/heads/main/serverList.json
