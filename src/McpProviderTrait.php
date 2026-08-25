<?php namespace ProcessWire;

/** Aggregate-only MCP integration for private Folio data. */
trait FolioMcpProviderTrait {
    public function mcpProviderInfo(): array {
        return ['name' => 'folio', 'title' => 'Folio', 'version' => '1.0.1'];
    }

    public function mcpTools(): array {
        return [[
            'name' => 'folio_status',
            'title' => 'Folio organizer status',
            'description' => 'Return aggregate organizer counts without task titles, contact details, places, notes, URLs, images, or private page content.',
            'handler' => [$this, 'mcpFolioStatus'],
            'scope' => 'read', 'read_only' => true, 'destructive' => false,
            'idempotent' => true, 'open_world' => false,
            'input_schema' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => [], 'additionalProperties' => false],
        ]];
    }

    public function mcpFolioStatus(): array {
        $pages = $this->wire('pages');
        $counts = [];
        foreach(['task', 'sprint', 'project', 'event', 'contact', 'link', 'place', 'goal'] as $type) {
            $counts[$type . 's'] = (int)$pages->count('template=folio-' . $type . ', include=all');
        }
        return ['version' => '1.0.1', 'counts' => $counts, 'private_records_exposed' => false, 'write_tools' => false];
    }
}
