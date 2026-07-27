# Folio

![Playful hand-drawn illustration of people organizing projects, tasks and a calendar](assets/folio-doodle.png)

Personal organizer for ProcessWire — dashboard, projects, tasks, calendar, contacts, places, links, goals, roadmap and timeline in one place.

**Author:** Maxim Semenov  
**Website:** [smnv.org](https://smnv.org)  
**Email:** [maxim@smnv.org](mailto:maxim@smnv.org)

If this project helps your work, consider supporting future development: [GitHub Sponsors](https://github.com/sponsors/mxmsmnv) or [smnv.org/sponsor](https://smnv.org/sponsor/).

## Features

### Dashboard & Navigation
- Top-level admin menu item with a comprehensive tabbed interface.
- Dashboard with icon-chip statistics, quick-add buttons, and summary cards.
- Clean UIKit-based admin UI integrated with ProcessWire themes.

### Projects & Tasks
- Strict hierarchy built on ProcessWire's native page tree (Project → Sprint → Task).
- Every new Project automatically gets a "Backlog" sprint.
- Kanban board view for Tasks with filtering by project and priority.

### Schedule & Calendar
- Month grid calendar view for point-in-time events and recurring events.
- Upcoming window for birthdays and recurring tasks.

### Personal Knowledge & Life Management
- **Contacts:** Cards with photo, company, role, and details.
- **Places:** Locations with visits tracking, links, and card grid.
- **Links:** Useful bookmarks and references.
- **Goals & Roadmap:** Long-term planning and vertical milestone timeline.
- **Timeline:** Historical feed of completed items and activity.

## Installation

1. Copy the `Folio` folder to `/site/modules/Folio/`
2. In the ProcessWire admin go to **Modules → Refresh**
3. Install **Folio** — the companion `ProcessFolio` module installs automatically

On install the module creates its fields (`folio_*`), templates (`folio-*`) and the hidden `/folio/` page tree that holds the data.

## Development & Seeding

- Use the CLI seed script to generate or reset test data:
  ```bash
  php src/Cli/seed.php --clear
  ```

## Requirements

- ProcessWire >= 3.0.200
- PHP >= 8.0

## Documentation

See [CHANGELOG.md](CHANGELOG.md) for the release notes.

## Author

Maxim Semenov  
[smnv.org](https://smnv.org)  
[maxim@smnv.org](mailto:maxim@smnv.org)

## License

MIT
