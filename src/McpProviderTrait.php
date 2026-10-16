<?php namespace ProcessWire;

/** Aggregate-only MCP integration for private Folio data. */
trait FolioMcpProviderTrait {
	public function pocketWireProviderInfo(): array {
		return ['id' => 'folio', 'title' => 'Folio', 'module' => 'Folio', 'moduleVersion' => '1.0.2'];
	}

	public function pocketWireManifest(User $actor): array {
		$ready = $this->pocketWireCanRead($actor);
		return [
			'id' => 'folio', 'title' => 'Folio', 'module' => 'Folio', 'moduleVersion' => '1.0.2',
			'status' => $ready ? 'native_read_only' : 'unsupported',
			'permissions' => ['scope' => 'providers.read', 'processWire' => ['superuser']],
			'presentation' => ['layout' => 'dashboard', 'icon' => 'calendar-outline', 'summary' => 'Private organizer totals without record content.', 'accentColor' => '#7C3AED'],
			'resources' => $ready ? [[
				'id' => 'overview', 'title' => 'Organizer overview', 'method' => 'GET',
				'endpoint' => '/api/pocketwire/v1/providers/folio/overview',
				'presentation' => ['layout' => 'metrics', 'metrics' => [
					['path' => 'counts.tasks', 'label' => 'Tasks', 'format' => 'integer'],
					['path' => 'counts.projects', 'label' => 'Projects', 'format' => 'integer'],
					['path' => 'counts.events', 'label' => 'Events', 'format' => 'integer'],
					['path' => 'counts.goals', 'label' => 'Goals', 'format' => 'integer'],
				]],
			]] : [],
			'actions' => [],
		];
	}

	public function pocketWireRead(string $resource, string $identifier, array $query, User $actor): array {
		if(!$this->pocketWireCanRead($actor)) throw new WirePermissionException('Superuser access is required for private Folio aggregates.');
		if($resource !== 'overview' || $identifier !== '') throw new Wire404Exception('Folio resource was not found.');
		return $this->mcpFolioStatus();
	}

	private function pocketWireCanRead(User $actor): bool {
		return (int)$this->wire('user')->id === (int)$actor->id && $actor->isSuperuser();
	}

    public function mcpProviderInfo(): array {
        return ['name' => 'folio', 'title' => 'Folio', 'version' => '1.0.2'];
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
        return ['version' => '1.0.2', 'counts' => $counts, 'private_records_exposed' => false, 'write_tools' => false];
    }
}
