<?php namespace ProcessWire;

require_once __DIR__ . "/src/Admin/Shared.php";
require_once __DIR__ . "/src/Admin/Chrome.php";
require_once __DIR__ . "/src/Admin/Settings.php";
require_once __DIR__ . "/src/Admin/Dashboard.php";
require_once __DIR__ . "/src/Admin/Projects.php";
require_once __DIR__ . "/src/Admin/Tasks.php";
require_once __DIR__ . "/src/Admin/Calendar.php";
require_once __DIR__ . "/src/Admin/Contacts.php";
require_once __DIR__ . "/src/Admin/Places.php";
require_once __DIR__ . "/src/Admin/Links.php";
require_once __DIR__ . "/src/Admin/Goals.php";
require_once __DIR__ . "/src/Admin/Roadmap.php";

/**
 * Folio — admin UI.
 *
 * A top-level admin menu page with one tab per part of the organizer:
 * a Dashboard landing screen, then Projects, Tasks, Calendar, Contacts,
 * Places, Links, Goals, Roadmap and a Timeline. Every list screen can
 * create its own records; editing itself is left to ProcessWire's page
 * editor.
 *
 * Data model / install lives in the companion Folio module.
 *
 * Implementation is split into traits under src/Admin/, one per tab
 * plus Shared (cross-tab view helpers), Chrome (tab bar/panel) and Settings
 * (ConfigurableModule fields) — this file just wires them together.
 *
 * @author  Maxim Semenov <maxim@smnv.org> (smnv.org)
 * @license MIT
 * @version 1.0.2
 */
class ProcessFolio extends Process implements Module, ConfigurableModule
{
    use ProcessFolioShared, ProcessFolioChrome, ProcessFolioSettings;
    use ProcessFolioDashboard,
        ProcessFolioProjects,
        ProcessFolioTasks,
        ProcessFolioCalendar;
    use ProcessFolioContacts,
        ProcessFolioPlaces,
        ProcessFolioLinks,
        ProcessFolioGoals,
        ProcessFolioRoadmap;

    public static function getModuleInfo(): array
    {
        return [
            "title" => "Folio",
            "version" => 102,
            "summary" =>
                "Dashboard and per-section screens for the Folio organizer.",
            "author" => "Maxim Semenov",
            "icon" => "calendar-check-o",
            "requires" => ["Folio"],
            // No 'parent' key: Process::___installPage() puts the page at the
            // admin root by default, which is the top-level menu item we want.
            "page" => [
                "name" => "folio",
                "title" => "Folio",
            ],
            "autoload" => false,
            "singular" => true,
        ];
    }
}
