<?php namespace ProcessWire;

/** Tab navigation and the panel every screen renders inside. */
trait ProcessFolioChrome {

	/**
	 * [icon, label, url segment] per tab. Icons match the FontAwesome name
	 * (without the fa- prefix) each tab's own template uses in the page
	 * tree, so the same icon means the same thing everywhere in the module.
	 */
	protected function tabs(): array {
		return [
			'dashboard' => ['tachometer', $this->_('Dashboard'), ''],
			'projects' => ['briefcase', $this->_('Projects'), 'projects/'],
			'tasks' => ['check-square-o', $this->_('Tasks'), 'tasks/'],
			'calendar' => ['calendar', $this->_('Calendar'), 'calendar/'],
			'contacts' => ['user', $this->_('Contacts'), 'contacts/'],
			'places' => ['map-marker', $this->_('Places'), 'places/'],
			'links' => ['external-link', $this->_('Links'), 'links/'],
			'goals' => ['bullseye', $this->_('Goals'), 'goals/'],
			'roadmap' => ['map-signs', $this->_('Roadmap'), 'roadmap/'],
			'timeline' => ['history', $this->_('Timeline'), 'timeline/'],
		];
	}

	/** Wraps a screen's body in the tab bar and the themed panel. */
	protected function screen(string $current, string $body): string {
		$tabs = $this->tabs();
		$url = $this->wire('page')->url;

		// The dashboard IS "Folio", so its own headline/breadcrumb would be
		// redundant; every other tab gets a proper trail back to it plus a
		// browser tab title that actually tells tabs apart.
		if ($current !== 'dashboard' && isset($tabs[$current])) {
			$label = $tabs[$current][1];
			$this->headline($label);
			$this->browserTitle(sprintf('%s • %s', $label, $this->_('Folio')));
			$this->breadcrumb($url, $this->_('Folio'));
		}

		$nav = '';
		foreach ($tabs as $key => $tab) {
			list($icon, $label, $segment) = $tab;
			$class = $key === $current ? " class='uk-active'" : '';
			$nav .= "<li{$class}><a href='{$url}{$segment}'>{$this->icon($icon)} "
				. $this->entities($label) . '</a></li>';
		}

		return "<div class='pw-wrap ProcessFolio'>"
			. "<ul class='uk-tab uk-margin-bottom'>{$nav}</ul>{$body}</div>";
	}
}
