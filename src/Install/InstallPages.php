<?php namespace ProcessWire;

/** The /folio/ page tree: root + one container per section. */
trait FolioInstallPages {

	protected function installPages(): void {
		$pages = $this->wire('pages');

		$root = $pages->get('template=folio-index, include=all');
		if (!$root->id) {
			$root = $this->wire(new Page());
			$root->template = 'folio-index';
			$root->parent = $pages->get('/');
			$root->name = 'folio';
			$root->title = 'Folio';
			$root->addStatus(Page::statusHidden);
			$root->save();
		}

		$this->addChildContainer($root, 'folio-projects', 'projects', 'Projects');
		$this->addChildContainer($root, 'folio-calendar', 'calendar', 'Calendar');
		$this->addChildContainer($root, 'folio-contacts', 'contacts', 'Contacts');
		$this->addChildContainer($root, 'folio-recurring', 'recurring', 'Recurring events');
		$this->addChildContainer($root, 'folio-links', 'links', 'Links');
		$this->addChildContainer($root, 'folio-places', 'places', 'Places');
		$this->addChildContainer($root, 'folio-goals', 'goals', 'Goals');
		$this->addChildContainer($root, 'folio-roadmap', 'roadmap', 'Roadmap');
	}

	protected function addChildContainer(Page $parent, string $template, string $name, string $title): Page {
		$pages = $this->wire('pages');
		$existing = $pages->get("parent={$parent->path}, template={$template}, include=all");
		if ($existing->id) return $existing;

		$page = $this->wire(new Page());
		$page->template = $template;
		$page->parent = $parent;
		$page->name = $name;
		$page->title = $title;
		$page->save();
		return $page;
	}
}
