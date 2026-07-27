<?php namespace ProcessWire;

/** The Tasks tab: a Plan/In progress/Done Kanban board. */
trait ProcessFolioTasks {

	public function ___executeTasks(): string {
		$input = $this->wire('input');
		$projectId = $input->get->int('project');
		$priority = $input->get->text('priority');

		return $this->screen('tasks',
			$this->taskAddButton($projectId)
			. $this->renderTaskFilters($projectId, $priority)
			. $this->renderTaskKanban($projectId, $priority)
		);
	}

	/**
	 * Status is no longer a filter option here: on a Kanban board the three
	 * columns already are the status split, so a fourth "Status" dropdown
	 * on top would just filter a board down to one of its own columns.
	 */
	protected function renderTaskFilters(int $projectId, ?string $priority): string {
		$projects = $this->filterPageSelect(
			'project', $this->_('Any project'),
			$this->wire('pages')->find('template=folio-project, sort=title'), $projectId ?: null
		);
		$priorityFilter = $this->filterOptionSelect('priority', $this->_('Any priority'), 'folio_priority', $priority);

		return $this->filterForm($projects . $priorityFilter);
	}

	/**
	 * A project must be picked before a task can be added, because a task
	 * always belongs to a specific sprint: there's no single "default"
	 * sprint across every project to land it in. Every project is
	 * guaranteed a backlog sprint (Folio::hookPageAdded), so that's always
	 * a safe target once a project is chosen.
	 */
	protected function taskAddButton(int $projectId): string {
		if (!$projectId) {
			return "<p class='uk-text-meta'>" . $this->_('Pick a project below to add a task.') . '</p>';
		}

		$project = $this->wire('pages')->get($projectId);
		$backlog = $project->id ? $project->children('template=folio-sprint, folio_is_backlog=1')->first() : null;
		if (!$backlog || !$backlog->id) return '';

		return "<a class='uk-button uk-button-default uk-margin-bottom' href='{$this->addUrl($backlog)}'>"
			. "<i class='fa fa-plus'></i> " . sprintf($this->_('Task in %s'), $this->entities($project->title)) . '</a>';
	}

	/** [status value => title], in whatever order the field itself defines them. */
	protected function statusOptionsMap(): array {
		$field = $this->wire('fields')->get('folio_status');
		if (!$field || !$field->id) return [];

		$manager = $this->wire('modules')->get('FieldtypeOptions')->manager;
		$map = [];
		foreach ($manager->getOptions($field) as $option) $map[(int) $option->value] = (string) $option->title;
		return $map;
	}

	/**
	 * Three columns, one per status — no drag-and-drop between them. Moving
	 * a card would mean an AJAX endpoint that changes status as a side
	 * effect of a drop, which is exactly the kind of thing the rest of this
	 * module deliberately avoids: editing stays in ProcessWire's own page
	 * editor, one click away via the card's title link.
	 */
	protected function renderTaskKanban(int $projectId, ?string $priority): string {
		$sanitizer = $this->wire('sanitizer');

		$selector = 'template=folio-task, sort=-created';
		if ($projectId) $selector .= ", has_parent={$projectId}";
		if ($priority !== '' && $priority !== null) $selector .= ', folio_priority.value=' . $sanitizer->int($priority);

		$columns = [Folio::STATUS_PLAN => [], Folio::STATUS_PROGRESS => [], Folio::STATUS_DONE => []];
		foreach ($this->wire('pages')->find($selector) as $task) {
			$value = (int) $task->folio_status?->value;
			if (isset($columns[$value])) $columns[$value][] = $task;
		}

		$labels = $this->statusOptionsMap();
		$labelClass = [
			Folio::STATUS_PLAN => 'uk-label',
			Folio::STATUS_PROGRESS => 'uk-label uk-label-warning',
			Folio::STATUS_DONE => 'uk-label uk-label-success',
		];

		$out = '';
		foreach ($columns as $value => $tasks) {
			$title = $this->entities($labels[$value] ?? '');
			$head = "<h3 class='uk-h5 uk-margin-small-bottom'><span class='{$labelClass[$value]}'>{$title}</span> "
				. "<span class='uk-badge'>" . count($tasks) . '</span></h3>';

			$cards = '';
			foreach ($tasks as $task) {
				$cards .= "<div class='uk-card uk-card-default uk-card-body uk-card-small uk-margin-small-bottom'>"
					. "<div>{$this->titleCell($task)}</div>"
					. "<div class='uk-text-meta'>" . $this->entities($task->parent->parent->title)
					. ' · ' . $this->entities($task->parent->title) . '</div>'
					. "<div class='uk-margin-small-top'>{$this->priorityLabel($task)}</div>"
					. '</div>';
			}
			if (!$cards) $cards = "<p class='uk-text-muted uk-text-small'>" . $this->_('No tasks.') . '</p>';

			$out .= "<div>{$head}{$cards}</div>";
		}

		return "<div class='uk-grid-medium uk-child-width-1-3@m' uk-grid>{$out}</div>";
	}
}
