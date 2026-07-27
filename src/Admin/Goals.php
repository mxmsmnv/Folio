<?php namespace ProcessWire;

/** The Goals tab: a card grid showing linked-project progress toward each goal. */
trait ProcessFolioGoals {

	public function ___executeGoals(): string {
		$status = $this->wire('input')->get->text('status');

		return $this->screen('goals',
			$this->addButton('folio-goals', $this->_('Goal'))
			. $this->renderGoalFilters($status)
			. $this->renderGoals($status)
		);
	}

	protected function renderGoalFilters(?string $status): string {
		return $this->filterForm($this->filterOptionSelect('status', $this->_('Any status'), 'folio_status', $status));
	}

	/**
	 * A card grid, same family as Projects/Places: each goal leads with its
	 * period and status, then which projects are actually working toward it
	 * and how many of those are done — the point of linking Goals to
	 * Projects is to answer "is this moving," which a bare title list
	 * doesn't show at a glance.
	 */
	protected function renderGoals(?string $status = null): string {
		$selector = 'template=folio-goal, sort=folio_period, sort=title';
		if ($status !== '' && $status !== null) {
			$selector .= ', folio_status.value=' . $this->wire('sanitizer')->int($status);
		}
		$goals = $this->wire('pages')->find($selector);
		if (!$goals->count()) return "<p class='uk-text-muted'>" . $this->_('No goals yet.') . '</p>';

		$out = '';
		foreach ($goals as $goal) {
			$projects = $goal->folio_projects;
			$done = 0;
			foreach ($projects as $project) {
				if ((int) $project->folio_status?->value === Folio::STATUS_DONE) $done++;
			}
			$projectsHtml = $projects->count()
				? "<div class='uk-margin-small-top uk-text-small'>{$this->icon('briefcase')} " . $this->pageLinks($projects)
					. " <span class='uk-text-meta'>(" . sprintf($this->_('%d / %d done'), $done, $projects->count()) . ')</span></div>'
				: '';

			$period = $goal->folio_period ? " · <span class='uk-text-meta'>" . $this->entities($goal->folio_period) . '</span>' : '';

			$out .= "<div><div class='uk-card uk-card-default uk-card-body uk-card-small'>"
				. $this->row($this->titleCell($goal) . $period, '', $this->statusLabel($goal))
				. "{$projectsHtml}</div></div>";
		}

		return $this->cardStyles()
			. "<div class='uk-grid-medium uk-child-width-1-2@s uk-child-width-1-3@l' uk-grid>{$out}</div>";
	}
}
