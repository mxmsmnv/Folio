<?php namespace ProcessWire;

/** Template creation, family (parent/child) rules, column widths, and field-reference wiring. */
trait FolioInstallTemplates {

	protected function installTemplates(): void {
		$this->addTemplate('folio-index', [], 'Folio (root)', 'sitemap');
		$this->addTemplate('folio-projects', [], 'Projects (container)', 'folder-open-o');
		$this->addTemplate('folio-project', [
			'folio_status', 'folio_completed_at', 'folio_description',
		], 'Project', 'briefcase');
		$this->addTemplate('folio-sprint', [
			'folio_status', 'folio_start_date', 'folio_end_date', 'folio_is_backlog', 'folio_completed_at',
		], 'Sprint', 'flag-checkered');
		$this->addTemplate('folio-task', [
			'folio_status', 'folio_priority', 'folio_completed_at',
		], 'Task', 'check-square-o');
		$this->addTemplate('folio-calendar', [], 'Calendar (container)', 'folder-open-o');
		$this->addTemplate('folio-event', [
			'folio_datetime', 'folio_duration', 'folio_description',
		], 'Event', 'calendar');
		$this->addTemplate('folio-contacts', [], 'Contacts (container)', 'folder-open-o');
		$this->addTemplate('folio-contact', [
			'folio_photo', 'folio_company', 'folio_role',
			'folio_phone', 'folio_email', 'folio_birthday',
			'folio_phone_2', 'folio_email_2',
			'folio_address', 'folio_website',
			'folio_tags', 'folio_description',
		], 'Contact', 'user');
		$this->addTemplate('folio-recurring', [], 'Recurring events (container)', 'folder-open-o');
		$this->addTemplate('folio-recurrence', [
			'folio_datetime', 'folio_interval', 'folio_interval_unit', 'folio_contact', 'folio_description',
		], 'Recurring event', 'refresh');
		$this->addTemplate('folio-links', [], 'Links (container)', 'folder-open-o');
		$this->addTemplate('folio-link', [
			'folio_url', 'folio_project', 'folio_tags', 'folio_description',
		], 'Link', 'external-link');
		$this->addTemplate('folio-places', [], 'Places (container)', 'folder-open-o');
		$this->addTemplate('folio-place', [
			'folio_address', 'folio_visit_status', 'folio_images', 'folio_lat', 'folio_lng',
			'folio_links', 'folio_description', 'folio_completed_at',
		], 'Place', 'map-marker');
		$this->addTemplate('folio-goals', [], 'Goals (container)', 'folder-open-o');
		$this->addTemplate('folio-goal', [
			'folio_status', 'folio_period', 'folio_completed_at', 'folio_projects', 'folio_description',
		], 'Goal', 'bullseye');
		$this->addTemplate('folio-roadmap', [], 'Roadmap (container)', 'folder-open-o');
		$this->addTemplate('folio-milestone', [
			'folio_status', 'folio_target_date', 'folio_projects', 'folio_goals', 'folio_description',
		], 'Milestone', 'map-signs');

		$this->setFamily('folio-index', [
			'folio-projects', 'folio-calendar', 'folio-contacts', 'folio-recurring',
			'folio-links', 'folio-places', 'folio-goals', 'folio-roadmap',
		]);
		$this->setFamily('folio-projects', ['folio-project']);
		$this->setFamily('folio-project', ['folio-sprint']);
		$this->setFamily('folio-sprint', ['folio-task']);
		$this->setFamily('folio-calendar', ['folio-event']);
		$this->setFamily('folio-contacts', ['folio-contact']);
		$this->setFamily('folio-recurring', ['folio-recurrence']);
		$this->setFamily('folio-links', ['folio-link']);
		$this->setFamily('folio-places', ['folio-place']);
		$this->setFamily('folio-goals', ['folio-goal']);
		$this->setFamily('folio-roadmap', ['folio-milestone']);

		$this->applyColumnWidths();
	}

	/**
	 * columnWidth is stored per-template (Fieldgroup "context"), not on the
	 * Field itself, since the same field can sit at a different width in
	 * different templates. A field must be fetched in fieldgroup context
	 * (the `true` below) before Fields::saveFieldgroupContext() will accept
	 * it — that's what flags it as belonging to this specific fieldgroup.
	 *
	 * Fields left out of a template's map keep width 100 (their default) and
	 * fall on their own row: mainly the textareas and the multi-page
	 * (asmSelect) references, which read poorly squeezed into a column.
	 */
	protected function applyColumnWidths(): void {
		$layouts = [
			'folio-project' => ['folio_status' => 50, 'folio_completed_at' => 50],
			'folio-sprint' => [
				'folio_status' => 25, 'folio_start_date' => 25,
				'folio_end_date' => 25, 'folio_is_backlog' => 25,
			],
			'folio-task' => ['folio_status' => 33, 'folio_priority' => 33, 'folio_completed_at' => 34],
			'folio-event' => ['folio_datetime' => 50, 'folio_duration' => 50],
			'folio-contact' => [
				'folio_company' => 50, 'folio_role' => 50,
				'folio_phone' => 33, 'folio_email' => 33, 'folio_birthday' => 34,
				'folio_phone_2' => 50, 'folio_email_2' => 50,
				'folio_address' => 50, 'folio_website' => 50,
				'folio_tags' => 30, 'folio_description' => 70,
			],
			'folio-recurrence' => ['folio_datetime' => 40, 'folio_interval' => 20, 'folio_interval_unit' => 40],
			'folio-link' => ['folio_url' => 60, 'folio_project' => 40, 'folio_tags' => 30, 'folio_description' => 70],
			'folio-place' => [
				'folio_address' => 70, 'folio_visit_status' => 30,
				'folio_lat' => 50, 'folio_lng' => 50,
			],
			'folio-goal' => ['folio_status' => 33, 'folio_period' => 33, 'folio_completed_at' => 34],
			'folio-milestone' => [
				'folio_status' => 50, 'folio_target_date' => 50,
				'folio_projects' => 50, 'folio_goals' => 50,
			],
		];

		$fields = $this->wire('fields');
		$templates = $this->wire('templates');

		foreach ($layouts as $templateName => $widths) {
			$template = $templates->get($templateName);
			if (!$template || !$template->id) continue;

			foreach ($widths as $fieldName => $width) {
				$field = $template->fieldgroup->getField($fieldName, true);
				if (!$field) continue;
				$field->columnWidth = $width;
				$fields->saveFieldgroupContext($field, $template->fieldgroup);
			}
		}
	}

	protected function addTemplate(string $name, array $fieldNames, string $label = '', string $icon = ''): Template {
		$templates = $this->wire('templates');
		$template = $templates->get($name);

		// A later stage may add fields to a template an earlier stage created,
		// or change its icon, so an existing template is topped up too.
		if ($template && $template->id) {
			if ($this->addFieldsTo($template->fieldgroup, $fieldNames)) {
				$template->fieldgroup->save();
			}
			if ($icon && $template->icon !== $icon) {
				$template->icon = $icon;
				$template->save();
			}
			return $template;
		}

		$fieldgroup = $this->wire(new Fieldgroup());
		$fieldgroup->name = $name;
		$fieldgroup->add($this->wire('fields')->get('title'));
		$this->addFieldsTo($fieldgroup, $fieldNames);
		$fieldgroup->save();

		$template = $this->wire(new Template());
		$template->name = $name;
		$template->fieldgroup = $fieldgroup;
		if ($label) $template->label = $label;
		if ($icon) $template->icon = $icon;
		$template->save();
		return $template;
	}

	/** @return bool Whether anything was actually added. */
	protected function addFieldsTo(Fieldgroup $fieldgroup, array $fieldNames): bool {
		$fields = $this->wire('fields');
		$added = false;

		foreach ($fieldNames as $fieldName) {
			$field = $fields->get($fieldName);
			if (!$field || !$field->id) continue;
			if ($fieldgroup->hasField($field)) continue;
			$fieldgroup->add($field);
			$added = true;
		}

		return $added;
	}

	/**
	 * Restrict which templates may be used as parent/child of one another,
	 * so the "Add new page" UI in the admin only offers sensible options.
	 */
	protected function setFamily(string $parentName, array $childNames): void {
		$templates = $this->wire('templates');
		$parent = $templates->get($parentName);
		if (!$parent || !$parent->id) return;

		$childIds = [];
		foreach ($childNames as $childName) {
			$child = $templates->get($childName);
			if (!$child || !$child->id) continue;
			$childIds[] = $child->id;

			$parentIds = $child->parentTemplates;
			if (!in_array($parent->id, $parentIds)) {
				$parentIds[] = $parent->id;
				$child->parentTemplates = $parentIds;
				$child->save();
			}
		}
		$parent->childTemplates = $childIds;
		$parent->save();
	}

	/**
	 * Point page-reference fields at their templates. Runs after
	 * installTemplates(), since the templates must exist to be referenced.
	 */
	protected function configureReferenceFields(): void {
		$this->setReferenceTemplate('folio_contact', 'folio-contact');
		$this->setReferenceTemplate('folio_project', 'folio-project');
		$this->setReferenceTemplate('folio_projects', 'folio-project');
		$this->setReferenceTemplate('folio_goals', 'folio-goal');
		$this->setReferenceTemplate('folio_links', 'folio-link');

		// Re-applied here rather than only at field creation, so installs made
		// by an earlier version pick the lock up on upgrade.
		$this->lockField('folio_completed_at');
		$this->lockField('folio_is_backlog');
	}

	protected function lockField(string $fieldName): void {
		$field = $this->wire('fields')->get($fieldName);
		if (!$field || !$field->id) return;
		if ($field->get('collapsed') == Inputfield::collapsedNoLocked) return;

		$field->set('collapsed', Inputfield::collapsedNoLocked);
		$field->save();
	}

	protected function setReferenceTemplate(string $fieldName, string $templateName): void {
		$field = $this->wire('fields')->get($fieldName);
		$template = $this->wire('templates')->get($templateName);
		if (!$field || !$field->id || !$template || !$template->id) return;
		if ($field->get('template_id') == $template->id) return;

		$field->set('template_id', $template->id);
		$field->save();
	}
}
