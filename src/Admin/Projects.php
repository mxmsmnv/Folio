<?php namespace ProcessWire;

/** The Projects tab: a card grid with per-project progress and a status filter. */
trait ProcessFolioProjects {

	public function ___executeProjects(): string {
		$status = $this->wire('input')->get->text('status');

		return $this->screen('projects',
			$this->addButton('folio-projects', $this->_('Project'))
			. $this->renderProjectFilters($status)
			. $this->renderProjects($status)
		);
	}

	protected function renderProjectFilters(?string $status): string {
		return $this->filterForm($this->filterOptionSelect('status', $this->_('Any status'), 'folio_status', $status));
	}

	/**
	 * A card grid instead of a table: each project is a small dashboard of
	 * its own progress (bar + done/total, same building blocks cardProjects()
	 * uses on the main Dashboard), not just a row of numbers, and links
	 * straight into the Kanban board pre-filtered to that project — one
	 * click to "show me this project's tasks" instead of picking it again
	 * from the Tasks filter.
	 */
	protected function renderProjects(?string $status = null): string {
		$selector = 'template=folio-project, sort=title';
		if ($status !== '' && $status !== null) {
			$selector .= ', folio_status.value=' . $this->wire('sanitizer')->int($status);
		}
		$projects = $this->wire('pages')->find($selector);
		if (!$projects->count()) return $this->cardStyles() . "<p class='uk-text-muted'>" . $this->_('No projects yet.') . '</p>';

		$tasksUrl = $this->wire('page')->url . 'tasks/';
		$out = '';
		foreach ($projects as $project) {
			$all = $project->find('template=folio-task')->count();
			$done = $project->find('template=folio-task, folio_status.value=' . Folio::STATUS_DONE)->count();
			$percent = $all ? round($done / $all * 100) : 0;
			$sprints = $project->numChildren('template=folio-sprint');

			$bar = "<div class='pw-progress-row'>"
				. "<progress class='uk-progress' value='{$done}' max='" . max($all, 1) . "'></progress>"
				. "<span class='pw-progress-label'>{$percent}%</span></div>";

			$meta = "<div class='pw-row-meta'>" . sprintf($this->_('%d / %d tasks'), $done, $all)
				. ' · ' . sprintf($this->_('%d sprints'), $sprints) . '</div>';

			$out .= "<div><div class='uk-card uk-card-default uk-card-body uk-card-small'>"
				. $this->row($this->titleCell($project), '', $this->statusLabel($project))
				. "{$bar}{$meta}"
				. "<p class='uk-margin-small-top uk-margin-remove-bottom uk-text-small'>"
				. "<a href='{$tasksUrl}?project={$project->id}'>" . $this->_('View tasks') . ' →</a></p>'
				. '</div></div>';
		}

		return $this->cardStyles()
			. "<div class='uk-grid-medium uk-child-width-1-2@s uk-child-width-1-3@l' uk-grid>{$out}</div>";
	}
}
