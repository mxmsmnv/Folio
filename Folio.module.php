<?php namespace ProcessWire;

require_once __DIR__ . "/src/Install/Hooks.php";
require_once __DIR__ . "/src/Install/InstallFields.php";
require_once __DIR__ . "/src/Install/InstallTemplates.php";
require_once __DIR__ . "/src/Install/InstallPages.php";
require_once __DIR__ . "/src/Install/Uninstall.php";

/**
 * Folio — data module.
 *
 * Personal organizer built on the native page tree: Projects -> Sprints ->
 * Tasks, a Calendar, Contacts, recurring events, Links, Places, Goals and a
 * Roadmap. Installs the fields, templates and pages, and carries the three
 * automations — backlog sprints, birthday recurrences, and completion stamps
 * for the Timeline. Admin UI lives in the companion ProcessFolio module.
 *
 * Implementation is split into traits under src/Install/ — Hooks, InstallFields,
 * InstallTemplates, InstallPages, Uninstall — this file just wires them
 * together and holds the constants/orchestration every trait shares.
 *
 * @author  Maxim Semenov <maxim@smnv.org> (smnv.org)
 * @license MIT
 * @version 1.0.0
 */
class Folio extends WireData implements Module
{
    use FolioHooks,
        FolioInstallFields,
        FolioInstallTemplates,
        FolioInstallPages,
        FolioUninstall;

    public static function getModuleInfo(): array
    {
        return [
            "title" => "Folio",
            "version" => 100,
            "summary" =>
                "Personal organizer: Projects -> Sprints -> Tasks, Calendar, Contacts, Links, Places, Goals and Roadmap.",
            "author" => "Maxim Semenov",
            "icon" => "calendar-check-o",
            "requires" => ["ProcessWire>=3.0.200", "PHP>=8.0.0"],
            "installs" => ["ProcessFolio"],
            "autoload" => true,
            "singular" => true,
        ];
    }

    /** Child-before-parent order, so uninstall never orphans a page. */
    const TEMPLATE_NAMES = [
        "folio-task",
        "folio-sprint",
        "folio-project",
        "folio-projects",
        "folio-event",
        "folio-calendar",
        "folio-recurrence",
        "folio-recurring",
        "folio-contact",
        "folio-contacts",
        "folio-link",
        "folio-links",
        "folio-place",
        "folio-places",
        "folio-milestone",
        "folio-roadmap",
        "folio-goal",
        "folio-goals",
        "folio-index",
    ];

    const FIELD_NAMES = [
        "folio_status",
        "folio_priority",
        "folio_description",
        "folio_datetime",
        "folio_duration",
        "folio_start_date",
        "folio_end_date",
        "folio_is_backlog",
        "folio_phone",
        "folio_email",
        "folio_birthday",
        "folio_tags",
        "folio_interval",
        "folio_interval_unit",
        "folio_contact",
        "folio_url",
        "folio_project",
        "folio_address",
        "folio_lat",
        "folio_lng",
        "folio_visit_status",
        "folio_period",
        "folio_projects",
        "folio_goals",
        "folio_target_date",
        "folio_completed_at",
        "folio_images",
        "folio_links",
        "folio_photo",
        "folio_company",
        "folio_role",
        "folio_website",
        "folio_phone_2",
        "folio_email_2",
    ];

    /** Stored values of the folio_status options. */
    const STATUS_PLAN = 10;
    const STATUS_PROGRESS = 20;
    const STATUS_DONE = 30;

    /** Stored values of the folio_priority options. */
    const PRIORITY_LOW = 10;
    const PRIORITY_MEDIUM = 20;
    const PRIORITY_HIGH = 30;

    /** Templates whose completion the Timeline reports on. */
    const COMPLETABLE_TEMPLATES = [
        "folio-task",
        "folio-sprint",
        "folio-project",
        "folio-goal",
        "folio-place",
    ];

    /** Stored values of the folio_interval_unit options (not their option IDs). */
    const UNIT_DAYS = 10;
    const UNIT_WEEKS = 20;
    const UNIT_MONTHS = 30;
    const UNIT_YEARS = 40;

    /** Stored values of the folio_visit_status options. */
    const VISIT_WANT = 10;
    const VISIT_DONE = 20;

    public function init(): void
    {
        $this->addHookAfter("Pages::added", $this, "hookPageAdded");
        $this->addHookAfter("Pages::saved", $this, "hookPageSaved");
        $this->addHookBefore("Pages::saveReady", $this, "hookSaveReady");
    }

    public function ___install(): void
    {
        $this->installFields();
        $this->installTemplates();
        $this->configureReferenceFields();
        $this->installPages();
    }

    /**
     * Each stage adds fields, templates and pages; every install step is
     * idempotent, so replaying them is all an upgrade needs to do.
     */
    public function ___upgrade($fromVersion, $toVersion): void
    {
        $this->installFields();
        $this->installTemplates();
        $this->configureReferenceFields();
        $this->installPages();
    }

    public function ___uninstall(): void
    {
        $this->uninstallPages();
        $this->uninstallTemplates();
        $this->uninstallFields();
    }
}
