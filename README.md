# Kimai - Compact week report

[![CI Status](https://github.com/cloudchristoph/kimai-plugin-compact-week-report/workflows/CI/badge.svg)](https://github.com/cloudchristoph/kimai-plugin-compact-week-report/actions)

A [Kimai](https://www.kimai.org) plugin that adds a compact weekly report: **one row per customer/project**, without the activity breakdown of the core weekly view.

Built to make transferring hours into external systems (e.g. ServiceNow time tracking) as easy as possible.

## Features

- New report under *Reporting → Compact week (customer – project)*
- One row per customer/project combination, columns Monday–Sunday plus weekly total
- Radio toggle between decimal hours (default, ideal for copy-over) and `h:mm` format
- Rounding dropdown with two groups — "Nearest" (commercial rounding) and "Round up" —
  for quarter, half and full hours. Rounding is applied per cell (project × day) after
  summing all activities; day and week totals are calculated from the rounded values,
  so the table is always consistent in itself.
- Every value links to the filtered timesheet view (project + day/week), showing the
  underlying unrounded entries
- Week navigation (previous / current / next)
- Respects the core permissions `view_reporting` and `report:user`
- German and English translations

## Installation

This plugin is compatible with the following Kimai releases:

| Bundle version | Minimum Kimai version |
|----------------|-----------------------|
| 1.0            | 2.61.0                |

You find the most notable changes between the versions in the file [CHANGELOG.md](CHANGELOG.md).

Download and extract the [compatible release](https://github.com/cloudchristoph/kimai-plugin-compact-week-report/releases) in `var/plugins/` (see [plugin docs](https://www.kimai.org/documentation/plugin-management.html)).

The file structure needs to look like this afterwards:

```bash
var/plugins/
├── CompactWeekBundle
│   ├── CompactWeekBundle.php
|   └ ... more files and directories follow here ...
```

Then rebuild the cache:

```bash
bin/console kimai:reload --env=prod
```

The new report shows up under *Reporting → Compact week (customer – project)*.

## License

MIT — see [LICENSE](LICENSE).
