<?php namespace ProcessWire;

/** Reverse of the three install traits: pages, then templates, then fields. */
trait FolioUninstall {

	protected function uninstallPages(): void {
		$pages = $this->wire('pages');

		$root = $pages->get('template=folio-index, include=all');
		if ($root->id) $pages->delete($root, true);

		// Anything left over — most likely pages sitting in the trash — would
		// otherwise block the template deletions below. TEMPLATE_NAMES runs
		// child templates first, so no page is deleted before its children.
		foreach (self::TEMPLATE_NAMES as $name) {
			foreach ($pages->find("template={$name}, include=all") as $page) {
				$pages->delete($page, true);
			}
		}
	}

	protected function uninstallTemplates(): void {
		$templates = $this->wire('templates');
		$fieldgroups = $this->wire('fieldgroups');
		$names = self::TEMPLATE_NAMES;

		foreach ($names as $name) {
			$template = $templates->get($name);
			if (!$template || !$template->id) continue;
			$fieldgroup = $template->fieldgroup;
			$templates->delete($template);
			if ($fieldgroup && $fieldgroup->id && $fieldgroup->name === $name) {
				$fieldgroups->delete($fieldgroup);
			}
		}
	}

	protected function uninstallFields(): void {
		$fields = $this->wire('fields');
		foreach (self::FIELD_NAMES as $name) {
			$field = $fields->get($name);
			if ($field && $field->id) $fields->delete($field);
		}
	}
}
