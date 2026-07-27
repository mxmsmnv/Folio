#!/usr/bin/env php
<?php namespace ProcessWire;

/**
 * Seeds (or clears) demo content for the Folio module, so every admin
 * screen has something to look at.
 *
 * Usage:
 *   php src/Cli/seed.php                    seed the site at ./ or a parent PW install
 *   php src/Cli/seed.php --root=/path/to/site seed a specific ProcessWire root
 *   php src/Cli/seed.php --clear            delete all content under /folio/, then seed
 *   php src/Cli/seed.php --clear --no-seed  delete only, seed nothing back
 */

$argv = $_SERVER["argv"] ?? [];
$root = null;
$clear = false;
$seed = true;

foreach ($argv as $index => $arg) {
    $arg = (string) $arg;
    if (strpos($arg, "--root=") === 0) {
        $root = substr($arg, 7);
        unset($argv[$index]);
    } elseif ($arg === "--clear") {
        $clear = true;
        unset($argv[$index]);
    } elseif ($arg === "--no-seed") {
        $seed = false;
        unset($argv[$index]);
    }
}

$root = seed_find_root($root ?: getcwd());
if (!$root) {
    fwrite(
        STDERR,
        "Error: ProcessWire root not found. Pass --root=/path/to/processwire.\n",
    );
    exit(1);
}

$_SERVER["HTTP_HOST"] = $_SERVER["HTTP_HOST"] ?? "localhost";
$_SERVER["REQUEST_URI"] = "/";
require $root . "/index.php";

if (!wire("modules")->isInstalled("Folio")) {
    fwrite(STDERR, "Error: the Folio module is not installed on this site.\n");
    exit(1);
}

if ($clear) {
    seed_clear();
}

if ($seed) {
    seed_run();
}

/* ------------------------------------------------------------------ */

function seed_find_root(string $path): ?string
{
    $candidates = [$path, getcwd(), dirname(__DIR__, 3), dirname(__DIR__, 4)];
    foreach ($candidates as $candidate) {
        if (
            $candidate &&
            is_file("$candidate/index.php") &&
            is_dir("$candidate/wire")
        ) {
            return $candidate;
        }
    }
    return null;
}

/** Removes every content page under each Folio container, keeping the containers themselves. */
function seed_clear(): void
{
    $pages = wire("pages");
    $containers = [
        "folio-projects",
        "folio-calendar",
        "folio-contacts",
        "folio-recurring",
        "folio-links",
        "folio-places",
        "folio-goals",
        "folio-roadmap",
    ];

    $count = 0;
    foreach ($containers as $template) {
        $container = $pages->get("template={$template}, include=all");
        if (!$container->id) {
            continue;
        }
        foreach ($container->children("include=all") as $child) {
            $pages->delete($child, true);
            $count++;
        }
    }
    echo "Cleared {$count} top-level page(s) (and their children) under /folio/.\n";
}

/**
 * A flat-colour placeholder with a label, generated locally with GD rather
 * than fetched from anywhere — demo data shouldn't depend on the network,
 * and downloading real photos isn't something a seed script should do.
 */
function seed_placeholder_image(string $label, string $hex): string
{
    $width = 640;
    $height = 420;
    $image = imagecreatetruecolor($width, $height);
    [$r, $g, $b] = sscanf($hex, "%02x%02x%02x");
    imagefill($image, 0, 0, imagecolorallocate($image, $r, $g, $b));

    $white = imagecolorallocate($image, 255, 255, 255);
    $font = 5; // GD's built-in fonts are numbered 1-5; no font file dependency needed
    $textWidth = imagefontwidth($font) * strlen($label);
    imagestring(
        $image,
        $font,
        (int) (($width - $textWidth) / 2),
        (int) ($height / 2 - imagefontheight($font) / 2),
        $label,
        $white,
    );

    $path =
        sys_get_temp_dir() .
        "/folio-seed-" .
        preg_replace("/[^a-z0-9]+/i", "-", strtolower($label)) .
        ".jpg";
    imagejpeg($image, $path, 85);
    return $path;
}

function seed_option(string $fieldName, int $value)
{
    $field = wire("fields")->get($fieldName);
    if (!$field || !$field->id) {
        return null;
    }
    $manager = wire("modules")->get("FieldtypeOptions")->manager;
    return $manager->getOptions($field, ["value" => $value])->first();
}

/**
 * @param Page $parent
 * @param array $fields Plain field values; 'folio_status'/'folio_priority'/
 *   'folio_visit_status'/'folio_interval_unit' are resolved through
 *   seed_option() automatically since assigning a bare int would be read
 *   as an option ID rather than its value.
 */
function seed_page(
    string $template,
    Page $parent,
    string $title,
    array $fields = [],
): Page {
    $optionFields = [
        "folio_status",
        "folio_priority",
        "folio_visit_status",
        "folio_interval_unit",
    ];
    $imageFields = ["folio_images", "folio_photo"];

    $page = wire(new Page());
    $page->template = $template;
    $page->parent = $parent;
    $page->title = $title;

    $images = [];
    foreach ($imageFields as $name) {
        if (!isset($fields[$name])) {
            continue;
        }
        $images[$name] = $fields[$name];
        unset($fields[$name]);
    }

    foreach ($fields as $name => $value) {
        if (in_array($name, $optionFields, true) && is_int($value)) {
            $value = seed_option($name, $value);
        }
        $page->set($name, $value);
    }

    $page->save();

    if ($images) {
        foreach ($images as $name => $paths) {
            foreach ((array) $paths as $path) {
                $page->$name->add($path);
            }
        }
        $page->save();
    }

    return $page;
}

function seed_run(): void
{
    $pages = wire("pages");
    $now = time();
    $day = 86400;

    $existing = $pages->get("template=folio-project, title=Website Redesign");
    if ($existing->id) {
        fwrite(
            STDERR,
            "Demo data already present (found project 'Website Redesign'). Run with --clear first to reset.\n",
        );
        exit(1);
    }

    $projectsContainer = $pages->get("template=folio-projects");
    $calendarContainer = $pages->get("template=folio-calendar");
    $contactsContainer = $pages->get("template=folio-contacts");
    $recurringContainer = $pages->get("template=folio-recurring");
    $linksContainer = $pages->get("template=folio-links");
    $placesContainer = $pages->get("template=folio-places");
    $goalsContainer = $pages->get("template=folio-goals");
    $roadmapContainer = $pages->get("template=folio-roadmap");

    /* -- Projects, sprints, tasks --------------------------------------- */

    $website = seed_page(
        "folio-project",
        $projectsContainer,
        "Website Redesign",
        [
            "folio_description" =>
                "Refresh the marketing site with the new design system.",
            "folio_status" => Folio::STATUS_PROGRESS,
        ],
    );
    $websiteBacklog = $website
        ->children("template=folio-sprint, folio_is_backlog=1")
        ->first();
    $sprint1 = seed_page("folio-sprint", $website, "Sprint 1", [
        "folio_status" => Folio::STATUS_DONE,
        "folio_start_date" => $now - 14 * $day,
        "folio_end_date" => $now - 7 * $day,
    ]);
    $sprint2 = seed_page("folio-sprint", $website, "Sprint 2", [
        "folio_status" => Folio::STATUS_PROGRESS,
        "folio_start_date" => $now - 7 * $day,
        "folio_end_date" => $now + 7 * $day,
    ]);
    seed_page("folio-task", $sprint1, "Wireframe homepage", [
        "folio_status" => Folio::STATUS_DONE,
        "folio_priority" => Folio::PRIORITY_MEDIUM,
    ]);
    seed_page("folio-task", $sprint1, "Content audit", [
        "folio_status" => Folio::STATUS_DONE,
        "folio_priority" => Folio::PRIORITY_LOW,
    ]);
    seed_page("folio-task", $sprint2, "Build header component", [
        "folio_status" => Folio::STATUS_PROGRESS,
        "folio_priority" => Folio::PRIORITY_HIGH,
    ]);
    seed_page("folio-task", $sprint2, "Fix mobile nav", [
        "folio_status" => Folio::STATUS_PLAN,
        "folio_priority" => Folio::PRIORITY_HIGH,
    ]);
    seed_page("folio-task", $websiteBacklog, "Dark mode support", [
        "folio_status" => Folio::STATUS_PLAN,
        "folio_priority" => Folio::PRIORITY_LOW,
    ]);

    $app = seed_page("folio-project", $projectsContainer, "Mobile App MVP", [
        "folio_description" => "First release of the companion mobile app.",
        "folio_status" => Folio::STATUS_PROGRESS,
    ]);
    $appBacklog = $app
        ->children("template=folio-sprint, folio_is_backlog=1")
        ->first();
    $sprintA = seed_page("folio-sprint", $app, "Sprint A", [
        "folio_status" => Folio::STATUS_PROGRESS,
        "folio_start_date" => $now - 3 * $day,
        "folio_end_date" => $now + 11 * $day,
    ]);
    seed_page("folio-task", $sprintA, "Set up push notifications", [
        "folio_status" => Folio::STATUS_PROGRESS,
        "folio_priority" => Folio::PRIORITY_HIGH,
    ]);
    seed_page("folio-task", $sprintA, "Onboarding flow", [
        "folio_status" => Folio::STATUS_PLAN,
        "folio_priority" => Folio::PRIORITY_MEDIUM,
    ]);
    seed_page("folio-task", $appBacklog, "Offline mode", [
        "folio_status" => Folio::STATUS_PLAN,
        "folio_priority" => Folio::PRIORITY_LOW,
    ]);

    $apiProject = seed_page(
        "folio-project",
        $projectsContainer,
        "API Integration & Webhooks",
        [
            "folio_description" =>
                "Connect external billing and CRM webhooks securely.",
            "folio_status" => Folio::STATUS_PLAN,
        ],
    );
    $apiBacklog = $apiProject
        ->children("template=folio-sprint, folio_is_backlog=1")
        ->first();
    seed_page("folio-task", $apiBacklog, "Design webhook payload schema", [
        "folio_status" => Folio::STATUS_PLAN,
        "folio_priority" => Folio::PRIORITY_HIGH,
    ]);
    seed_page(
        "folio-task",
        $apiBacklog,
        "Implement HMAC signature verification",
        [
            "folio_status" => Folio::STATUS_PLAN,
            "folio_priority" => Folio::PRIORITY_HIGH,
        ],
    );

    $personal = seed_page(
        "folio-project",
        $projectsContainer,
        "Personal Website",
        [
            "folio_description" => "A small site for writing and a portfolio.",
            "folio_status" => Folio::STATUS_PLAN,
        ],
    );
    $personalBacklog = $personal
        ->children("template=folio-sprint, folio_is_backlog=1")
        ->first();
    seed_page("folio-task", $personalBacklog, "Write About page", [
        "folio_status" => Folio::STATUS_PLAN,
        "folio_priority" => Folio::PRIORITY_MEDIUM,
    ]);

    /* -- Contacts --------------------------------------------------------- */

    seed_page("folio-contact", $contactsContainer, "Anna Petrova", [
        "folio_phone" => "+1 415 555 0134",
        "folio_email" => "anna@example.com",
        "folio_birthday" => $now + 5 * $day,
        "folio_tags" => "friend",
        "folio_company" => "Bright Studio",
        "folio_role" => "Product Designer",
        "folio_address" => "120 Market St, San Francisco, CA",
        "folio_website" => "https://annapetrova.design",
        "folio_photo" => seed_placeholder_image("Anna Petrova", "8e44ad"),
    ]);
    seed_page("folio-contact", $contactsContainer, "Ivan Sokolov", [
        "folio_phone" => "+1 415 555 0177",
        "folio_email" => "ivan@example.com",
        "folio_birthday" => $now + 40 * $day,
        "folio_tags" => "work",
        "folio_company" => "Sokolov & Partners",
        "folio_role" => "Engineering Lead",
        "folio_phone_2" => "+1 415 555 0178",
        "folio_website" => "@ivansokolov",
        "folio_photo" => seed_placeholder_image("Ivan Sokolov", "2874a6"),
    ]);
    seed_page("folio-contact", $contactsContainer, "Maria Ivanova", [
        "folio_phone" => "+1 415 555 0199",
        "folio_email" => "maria@example.com",
        "folio_birthday" => $now + 200 * $day,
        "folio_tags" => "family",
        "folio_company" => "Ivanova Consulting",
        "folio_role" => "Account Manager",
        "folio_email_2" => "maria.work@example.com",
        "folio_website" => "https://ivanovaconsulting.com",
    ]);
    seed_page("folio-contact", $contactsContainer, "David Chen", [
        "folio_email" => "david@example.com",
        "folio_tags" => "client",
        "folio_company" => "Chen Logistics",
        "folio_role" => "Client",
        "folio_photo" => seed_placeholder_image("David Chen", "c0392b"),
    ]);
    seed_page("folio-contact", $contactsContainer, "Sophie Laurent", [
        "folio_phone" => "+1 415 555 0244",
        "folio_email" => "sophie@example.com",
        "folio_birthday" => $now + 12 * $day,
        "folio_tags" => "network",
        "folio_company" => "Apex Talent",
        "folio_role" => "Tech Recruiter",
        "folio_address" => "450 Sutter St, San Francisco, CA",
        "folio_photo" => seed_placeholder_image("Sophie Laurent", "d35400"),
    ]);
    seed_page("folio-contact", $contactsContainer, "Marcus Vance", [
        "folio_phone" => "+1 415 555 0288",
        "folio_email" => "marcus@example.com",
        "folio_birthday" => $now + 85 * $day,
        "folio_tags" => "work",
        "folio_company" => "Vance Design Lab",
        "folio_role" => "UX Researcher",
        "folio_website" => "https://marcusvance.ux",
        "folio_photo" => seed_placeholder_image("Marcus Vance", "16a085"),
    ]);

    /* -- Calendar --------------------------------------------------------- */

    seed_page("folio-event", $calendarContainer, "Kickoff call", [
        "folio_datetime" => $now - 5 * $day,
        "folio_duration" => 30,
    ]);
    seed_page("folio-event", $calendarContainer, "Design review", [
        "folio_datetime" => $now - 2 * $day,
        "folio_duration" => 45,
    ]);
    seed_page("folio-event", $calendarContainer, "Sprint planning", [
        "folio_datetime" => $now + 3 * $day,
        "folio_duration" => 60,
    ]);
    seed_page("folio-event", $calendarContainer, "Client demo", [
        "folio_datetime" => $now + 10 * $day,
        "folio_duration" => 60,
    ]);
    seed_page(
        "folio-event",
        $calendarContainer,
        "Architecture sync with Ivan",
        [
            "folio_datetime" => $now + 4 * $day,
            "folio_duration" => 90,
        ],
    );
    seed_page("folio-event", $calendarContainer, "Quarterly roadmap sync", [
        "folio_datetime" => $now + 15 * $day,
        "folio_duration" => 60,
    ]);

    seed_page("folio-recurrence", $recurringContainer, "Wedding anniversary", [
        "folio_datetime" => strtotime("2020-06-12"),
        "folio_interval" => 1,
        "folio_interval_unit" => Folio::UNIT_YEARS,
    ]);
    seed_page(
        "folio-recurrence",
        $recurringContainer,
        "Monthly team retrospective",
        [
            "folio_datetime" => strtotime("2026-01-01"),
            "folio_interval" => 1,
            "folio_interval_unit" => Folio::UNIT_MONTHS,
        ],
    );

    /* -- Links -------------------------------------------------------------- */

    seed_page("folio-link", $linksContainer, "ProcessWire Docs", [
        "folio_url" => "https://processwire.com/docs/",
        "folio_tags" => "reference",
        "folio_project" => $website,
    ]);
    seed_page("folio-link", $linksContainer, "UIkit Components", [
        "folio_url" => "https://getuikit.com/docs/introduction",
        "folio_tags" => "design",
    ]);
    seed_page("folio-link", $linksContainer, "Figma board", [
        "folio_url" => "https://www.figma.com/",
        "folio_tags" => "design",
        "folio_project" => $app,
    ]);
    $menuLink = seed_page(
        "folio-link",
        $linksContainer,
        "Blue Bottle Coffee — Menu",
        [
            "folio_url" => "https://bluebottlecoffee.com/menu",
            "folio_tags" => "places",
        ],
    );
    $ticketsLink = seed_page(
        "folio-link",
        $linksContainer,
        "National Museum — Tickets",
        [
            "folio_url" => "https://example.org/tickets",
            "folio_tags" => "places",
        ],
    );
    seed_page("folio-link", $linksContainer, "GitHub CLI Documentation", [
        "folio_url" => "https://cli.github.com/manual/",
        "folio_tags" => "dev",
        "folio_project" => $apiProject,
    ]);
    seed_page("folio-link", $linksContainer, "MDN Web Docs", [
        "folio_url" => "https://developer.mozilla.org/",
        "folio_tags" => "reference",
    ]);

    /* -- Places ------------------------------------------------------------- */

    seed_page("folio-place", $placesContainer, "Blue Bottle Coffee", [
        "folio_address" => "66 Mint St, San Francisco, CA",
        "folio_lat" => 37.7825,
        "folio_lng" => -122.4055,
        "folio_visit_status" => Folio::VISIT_WANT,
        "folio_images" => [
            seed_placeholder_image("Blue Bottle Coffee", "4a3728"),
        ],
        "folio_links" => $menuLink,
    ]);
    seed_page("folio-place", $placesContainer, "National Museum", [
        "folio_address" => "1000 Museum Way, San Francisco, CA",
        "folio_lat" => 37.7699,
        "folio_lng" => -122.4661,
        "folio_visit_status" => Folio::VISIT_DONE,
        "folio_images" => [seed_placeholder_image("National Museum", "2f4858")],
        "folio_links" => $ticketsLink,
    ]);
    seed_page("folio-place", $placesContainer, "Riverside Park", [
        "folio_address" => "210 Riverside Dr, San Francisco, CA",
        "folio_lat" => 37.8021,
        "folio_lng" => -122.4187,
        "folio_visit_status" => Folio::VISIT_WANT,
        "folio_images" => [seed_placeholder_image("Riverside Park", "2e7d32")],
    ]);
    seed_page("folio-place", $placesContainer, "Old Town Bakery", [
        "folio_address" => "45 Union St, San Francisco, CA",
        "folio_lat" => 37.8004,
        "folio_lng" => -122.4098,
        "folio_visit_status" => Folio::VISIT_DONE,
    ]);
    seed_page("folio-place", $placesContainer, "SF Public Library", [
        "folio_address" => "100 Larkin St, San Francisco, CA",
        "folio_lat" => 37.7782,
        "folio_lng" => -122.4174,
        "folio_visit_status" => Folio::VISIT_DONE,
        "folio_images" => [
            seed_placeholder_image("SF Public Library", "835375"),
        ],
    ]);

    /* -- Goals & Roadmap -------------------------------------------------- */

    $goalShip = seed_page("folio-goal", $goalsContainer, "Ship v1 of the app", [
        "folio_description" => "Get the MVP into public beta.",
        "folio_period" => "2026 Q3",
        "folio_status" => Folio::STATUS_PROGRESS,
        "folio_projects" => $app,
    ]);
    $goalBrand = seed_page(
        "folio-goal",
        $goalsContainer,
        "Grow personal brand",
        [
            "folio_period" => "2026",
            "folio_status" => Folio::STATUS_PLAN,
            "folio_projects" => $personal,
        ],
    );
    $goalApi = seed_page(
        "folio-goal",
        $goalsContainer,
        "Automate billing workflows",
        [
            "folio_description" =>
                "Integrate automated payment webhooks and invoices.",
            "folio_period" => "2026 Q3",
            "folio_status" => Folio::STATUS_PLAN,
            "folio_projects" => $apiProject,
        ],
    );
    seed_page("folio-goal", $goalsContainer, "Improve fitness", [
        "folio_period" => "2026 Q3",
        "folio_status" => Folio::STATUS_PROGRESS,
    ]);

    seed_page("folio-milestone", $roadmapContainer, "Public beta launch", [
        "folio_target_date" => $now + 20 * $day,
        "folio_status" => Folio::STATUS_PROGRESS,
        "folio_projects" => $app,
        "folio_goals" => $goalShip,
    ]);
    seed_page("folio-milestone", $roadmapContainer, "Website relaunch", [
        "folio_target_date" => $now + 45 * $day,
        "folio_status" => Folio::STATUS_PLAN,
        "folio_projects" => $website,
    ]);
    seed_page("folio-milestone", $roadmapContainer, "API v1 release", [
        "folio_target_date" => $now + 30 * $day,
        "folio_status" => Folio::STATUS_PLAN,
        "folio_projects" => $apiProject,
        "folio_goals" => $goalApi,
    ]);
    // Overdue on purpose, to exercise the red "overdue" styling.
    seed_page("folio-milestone", $roadmapContainer, "Q3 review", [
        "folio_target_date" => $now - 5 * $day,
        "folio_status" => Folio::STATUS_PROGRESS,
        "folio_goals" => $goalShip,
    ]);

    echo "Seeded demo data under /folio/.\n";
}
