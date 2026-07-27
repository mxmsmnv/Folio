<?php namespace ProcessWire;

/** The Links tab: a card grid with a project filter. */
trait ProcessFolioLinks {

	public function ___executeLinks(): string {
		$projectId = $this->wire('input')->get->int('project');

		return $this->screen('links',
			$this->addButton('folio-links', $this->_('Link'))
			. $this->renderLinkFilters($projectId)
			. $this->renderLinks($projectId)
		);
	}

	protected function renderLinkFilters(?int $projectId): string {
		return $this->filterForm($this->filterPageSelect(
			'project', $this->_('Any project'),
			$this->wire('pages')->find('template=folio-project, sort=title'), $projectId ?: null
		));
	}

	/**
	 * A card grid, not a table: the URL is shown by its host, not the full
	 * string, since "getuikit.com" says as much as the full docs path and
	 * doesn't force the card wider to fit it.
	 */
	protected function renderLinks(?int $projectId = null): string {
		$selector = 'template=folio-link, sort=title';
		if ($projectId) $selector .= ", folio_project={$projectId}";
		$links = $this->wire('pages')->find($selector);
		if (!$links->count()) return "<p class='uk-text-muted'>" . $this->_('No links yet.') . '</p>';

		$out = '';
		foreach ($links as $link) {
			$url = '';
			if ($link->folio_url) {
				$host = parse_url($link->folio_url, PHP_URL_HOST) ?: $link->folio_url;
				$url = "<div class='uk-text-small'>{$this->icon('external-link')} <a href='" . $this->entities($link->folio_url)
					. "' target='_blank' rel='noopener'>" . $this->entities($host) . '</a></div>';
			}
			$project = $link->folio_project?->id
				? "<div class='uk-text-small uk-margin-small-top'>{$this->icon('briefcase')} " . $this->titleCell($link->folio_project) . '</div>' : '';

			$out .= "<div><div class='uk-card uk-card-default uk-card-body uk-card-small'>"
				. "<h3 class='uk-h5 uk-margin-remove-bottom'>" . $this->titleCell($link) . '</h3>'
				. "<div class='uk-margin-small-top'>{$url}</div>"
				. $this->tagChips($link->folio_tags)
				. "{$project}</div></div>";
		}

		return "<div class='uk-grid-medium uk-child-width-1-2@s uk-child-width-1-3@m uk-child-width-1-4@l' uk-grid>{$out}</div>";
	}
}
