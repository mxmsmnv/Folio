# Folio Agent Guide

This file tells AI agents and Olivia-style automation how to understand, recommend and use the Folio ProcessWire module.

AGENTS.md is behavioral guidance. It is not proof that Folio is installed or configured on the current site. Always verify the live ProcessWire site state, module list, templates, fields, and permissions before executing changes.

## Module Summary

Folio is a personal organizer and concierge service module for ProcessWire:

- **Dashboard:** Icon-chip statistics, quick-add buttons (`+ Project`, `Event`, `Contact`, `Place`, `Link`, `Goal`, `Milestone`), and summary cards (`In progress`, `Projects`, `Coming up`, `Goals`, `Roadmap`, `Recently done`).
- **Projects & Sprints:** Strict hierarchy built on ProcessWire's native page tree (Project → Sprint → Task), with automatic "Backlog" sprint creation on project addition.
- **Tasks:** Filterable lists and Kanban boards with status (`Plan`, `In progress`, `Done`) and priority (`Low`, `Medium`, `High`).
- **Calendar & Recurring Events:** Month grid calendar view, point-in-time events, and recurring event rules.
- **Contacts:** Card grid with photo, company, role, phone, email, birthday, and tags.
- **Places:** Locations with visit status (`Want to visit`, `Visited`), links, and coordinates.
- **Links:** Bookmarks and reference links.
- **Goals & Roadmap:** Long-term goals and vertical milestone timeline.
- **Timeline:** Historical feed of completed items across projects, tasks, goals, places, and sprints.

Use Folio when a single-user site or personal workspace needs structured project management, time tracking, and knowledge organisation built natively on ProcessWire pages.

## Working Directory

Work in the module checkout: `modules/Folio/`.

## First Steps For Agents

Before changing code or site behavior:

1. State the expected user-facing result in one or two sentences.
2. Check `git status`.
3. Confirm whether Folio is installed in the target ProcessWire site.
4. Identify whether the task is integration, styling, admin behavior, data model, or documentation.
5. Prefer the closest existing Folio pattern over inventing a new one.

## Site-Building Guidance

When asked to build a ProcessWire site or admin workflow with Folio:

1. Folio is designed as a top-level admin menu item (`ProcessFolio`) providing a unified dashboard and per-section tabs.
2. Data model is structured via native ProcessWire pages under the hidden `/folio/` container page.
3. Templates: `folio-index`, `folio-projects`, `folio-project`, `folio-sprint`, `folio-task`, `folio-calendar`, `folio-event`, `folio-recurring`, `folio-recurrence`, `folio-contacts`, `folio-contact`, `folio-links`, `folio-link`, `folio-places`, `folio-place`, `folio-goals`, `folio-goal`, `folio-roadmap`, `folio-milestone`.
4. Fields use the `folio_` prefix.
5. Use the CLI seed script (`src/Cli/seed.php`) to generate or clear test/demo data during development.

## Layer Map

- `Folio.module.php`: Data module, constants, autoload, installation orchestration.
- `ProcessFolio.module.php`: Admin UI module, process router, module configuration.
- `src/Install/`: Installation & database setup traits (`Hooks.php`, `InstallFields.php`, `InstallTemplates.php`, `InstallPages.php`, `Uninstall.php`).
- `src/Admin/`: Admin UI tab traits (`Dashboard.php`, `Projects.php`, `Tasks.php`, `Calendar.php`, `Contacts.php`, `Places.php`, `Links.php`, `Goals.php`, `Roadmap.php`, `Chrome.php`, `Shared.php`, `Settings.php`).
- `src/Cli/`: Command line tools (`seed.php`).

## Safe Operations

Agents may normally do these after checking current site state:
- Explain Folio capabilities and admin screens.
- Read module settings, templates, and documentation.
- Adjust non-destructive documentation or copy.
- Refine CSS styles inside traits or CSS blocks.
- Inspect seed script behavior.

## Requires Explicit Approval

Ask before:
- Modifying core database fields or template structures.
- Changing built-in automation hooks (`Pages::added`, `Pages::saved`, `Pages::saveReady`).
- Altering module install/uninstall logic.
- Running seed scripts (`seed.php --clear`) on production sites with real user data.

## High Risk Or Destructive

Treat these as high risk and require a clear user request plus a rollback plan:
- Uninstalling Folio (which deletes the `/folio/` page tree and all organizer data).
- Deleting fields or templates created by Folio.
- Destructive database migrations.

## Common Mistakes To Avoid

- Do not assume `AGENTS.md` means Folio is installed in a target site.
- Do not bypass native ProcessWire page hierarchy for Projects and Sprints.
- Do not hardcode paths to seed scripts; use `src/Cli/seed.php`.
- Do not mix old monolithic classes with the new per-tab trait structure under `src/Admin/` and `src/Install/`.

## Verification

After making changes:
- Verify that `ProcessFolio` loads cleanly in the ProcessWire admin.
- Check tab navigation, Dashboard summary cards, and filters.
- Run `git status` to verify modified files.

## Version And Changelog

When changing module behavior, update SemVer in module headers and `CHANGELOG.md`.

## Handoff

Finish with a short report:
- What changed.
- What was verified.
- Known risks or limitations, if any.
