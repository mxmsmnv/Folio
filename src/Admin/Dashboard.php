<?php namespace ProcessWire;

/** The landing screen: quick-add buttons, stat tiles, and the six summary cards. */
trait ProcessFolioDashboard {

	public function ___execute(): string {
		return $this->screen('dashboard', $this->renderAddButtons() . $this->renderDashboard());
	}

	protected function renderAddButtons(): string {
		$types = [
			'folio-projects' => $this->_('Project'),
			'folio-calendar' => $this->_('Event'),
			'folio-contacts' => $this->_('Contact'),
			'folio-places' => $this->_('Place'),
			'folio-links' => $this->_('Link'),
			'folio-goals' => $this->_('Goal'),
			'folio-roadmap' => $this->_('Milestone'),
		];

		$out = '';
		foreach ($types as $template => $label) $out .= $this->addButton($template, $label);

		return $out ? "<div>{$out}</div>" : '';
	}

	protected function renderDashboard(): string {
		$tiles = $this->renderTiles();
		$cards = array_filter([
			$this->cardTasksInProgress(),
			$this->cardProjects(),
			$this->cardComingUp(),
			$this->cardGoals(),
			$this->cardRoadmap(),
			$this->cardRecentlyDone(),
		]);

		if (!$cards) {
			return $this->cardStyles() . $tiles . "<div class='pw-empty'>{$this->icon('inbox')}<p>"
				. $this->_('Nothing here yet — add a project or an event with the buttons above.')
				. '</p></div>';
		}

		return $this->cardStyles() . $tiles
			. "<div class='uk-grid-medium uk-child-width-1-2@m uk-child-width-1-3@l' uk-grid>"
			. implode('', $cards) . '</div>';
	}

	/** A counts strip, so the state of things is legible before any reading. */
	protected function renderTiles(): string {
		$pages = $this->wire('pages');
		$now = time();

		$tiles = [
			['check-square-o', $this->_('Open tasks'), $pages->count('template=folio-task, folio_status.value!=' . Folio::STATUS_DONE), 'tasks/'],
			['briefcase', $this->_('Projects'), $pages->count('template=folio-project'), 'projects/'],
			['calendar', $this->_('Upcoming events'), $pages->count("template=folio-event, folio_datetime>={$now}"), 'calendar/'],
			['user', $this->_('Contacts'), $pages->count('template=folio-contact'), 'contacts/'],
			['map-marker', $this->_('To visit'), $pages->count('template=folio-place, folio_visit_status.value=' . Folio::VISIT_WANT), 'places/'],
			['bullseye', $this->_('Goals open'), $pages->count('template=folio-goal, folio_status.value!=' . Folio::STATUS_DONE), 'goals/'],
		];

		$url = $this->wire('page')->url;
		$out = '';
		foreach ($tiles as $tile) {
			list($icon, $label, $count, $segment) = $tile;
			// uk-tile + uk-padding-small is the same recipe the old plain
			// tiles used (uk-tile's own padding is a tall 40px, meant for
			// section-sized blocks; uk-padding-small overrides it to 15px
			// on all sides) — no border or radius, same as every other
			// native uk-tile in the admin.
			$out .= "<div><a class='uk-link-reset' href='{$url}{$segment}'>"
				. "<div class='uk-tile uk-tile-muted uk-padding-small uk-flex uk-flex-middle'>"
				. "<div class='pw-stat-icon uk-margin-small-right'>{$this->icon($icon)}</div>"
				. "<div><div class='pw-stat-value'>{$count}</div>"
				. "<div class='pw-stat-label'>" . $this->entities($label) . '</div></div>'
				. '</div></a></div>';
		}

		return "<div class='uk-grid-medium uk-child-width-1-3@s uk-child-width-1-6@m uk-margin-bottom' uk-grid>{$out}</div>";
	}

	protected function cardTasksInProgress(): string {
		$tasks = $this->wire('pages')->find(
			'template=folio-task, folio_status.value=' . Folio::STATUS_PROGRESS
			. ", sort=-modified, limit={$this->cardLimit()}"
		);
		if (!$tasks->count()) return '';

		$items = [];
		foreach ($tasks as $task) {
			$items[] = $this->row(
				$this->titleCell($task),
				$this->entities($task->parent->parent->title),
				$this->priorityLabel($task)
			);
		}

		return $this->card('check-square-o', $this->_('In progress'), $this->list($items), 'tasks/', $this->_('All tasks'));
	}

	protected function cardProjects(): string {
		$projects = $this->wire('pages')->find('template=folio-project, sort=title, limit=' . $this->cardLimit());
		if (!$projects->count()) return '';

		$items = [];
		foreach ($projects as $project) {
			$all = $project->find('template=folio-task')->count();
			$done = $project->find('template=folio-task, folio_status.value=' . Folio::STATUS_DONE)->count();
			$percent = $all ? round($done / $all * 100) : 0;

			// The backlog is the one sprint every project is guaranteed to
			// have, so it is where a quick "add task" can safely land.
			$backlog = $project->children('template=folio-sprint, folio_is_backlog=1')->first();
			$add = $backlog && $backlog->id
				? " <a class='pw-icon-btn' href='{$this->addUrl($backlog)}' title='" . $this->_('Add task') . "'><i class='fa fa-plus'></i></a>"
				: '';

			$head = $this->row($this->titleCell($project) . $add, '', $this->statusLabel($project));
			$bar = "<div class='pw-progress-row'>"
				. "<progress class='uk-progress' value='{$done}' max='" . max($all, 1) . "'></progress>"
				. "<span class='pw-progress-label'>{$percent}%</span></div>"
				. "<div class='pw-row-meta'>" . sprintf($this->_('%d / %d tasks'), $done, $all) . '</div>';

			$items[] = $head . $bar;
		}

		return $this->card('briefcase', $this->_('Projects'), $this->list($items), 'projects/', $this->_('All projects'));
	}

	protected function cardComingUp(): string {
		$pages = $this->wire('pages');
		$now = time();
		$events = $pages->find("template=folio-event, folio_datetime>={$now}, sort=folio_datetime, limit={$this->cardLimit()}");
		$birthdays = $this->upcomingRecurrences($this->cardLimit(), $this->upcomingDays());

		if (!$events->count() && !$birthdays) return '';

		$items = [];
		foreach ($events as $event) {
			$items[] = $this->row(
				$this->titleCell($event),
				$this->date($event->getUnformatted('folio_datetime')),
				"<span class='uk-label'>" . $this->_('Event') . '</span>'
			);
		}
		foreach ($birthdays as $birthday) {
			$items[] = $this->row(
				$this->titleCell($birthday['page']),
				$this->date($birthday['next']) . ' · ' . $this->daysFromNow($birthday['days']),
				"<span class='uk-label'>" . $this->_('Birthday') . '</span>'
			);
		}

		return $this->card('calendar', $this->_('Coming up'), $this->list($items), 'calendar/', $this->_('Calendar'));
	}

	protected function cardGoals(): string {
		$goals = $this->wire('pages')->find(
			'template=folio-goal, folio_status.value!=' . Folio::STATUS_DONE . ", sort=title, limit={$this->cardLimit()}"
		);
		if (!$goals->count()) return '';

		$items = [];
		foreach ($goals as $goal) {
			$items[] = $this->row($this->titleCell($goal), $this->entities($goal->folio_period), $this->statusLabel($goal));
		}

		return $this->card('bullseye', $this->_('Goals'), $this->list($items), 'goals/', $this->_('All goals'));
	}

	protected function cardRoadmap(): string {
		$milestones = $this->wire('pages')->find(
			'template=folio-milestone, folio_status.value!=' . Folio::STATUS_DONE
			. ", sort=folio_target_date, limit={$this->cardLimit()}"
		);
		if (!$milestones->count()) return '';

		$items = [];
		foreach ($milestones as $milestone) {
			$due = (int) $milestone->getUnformatted('folio_target_date');
			$overdue = $due && $due < time();
			$when = $this->date($due);
			$meta = $overdue ? $this->dangerText($when) : $when;

			$items[] = $this->row($this->titleCell($milestone), $meta, $this->statusLabel($milestone));
		}

		return $this->card('map-signs', $this->_('Roadmap'), $this->list($items), 'roadmap/', $this->_('Full roadmap'));
	}

	protected function cardRecentlyDone(): string {
		$templates = implode('|', Folio::COMPLETABLE_TEMPLATES);
		$done = $this->wire('pages')->find(
			"template={$templates}, folio_completed_at>0, sort=-folio_completed_at, limit={$this->cardLimit()}"
		);
		if (!$done->count()) return '';

		$items = [];
		foreach ($done as $page) {
			$icon = $page->template->getIcon();
			$type = ($icon ? $this->icon($icon) . ' ' : '') . $this->entities($page->template->getLabel());
			$items[] = $this->row(
				$this->titleCell($page),
				$this->date($page->getUnformatted('folio_completed_at')),
				"<span class='uk-label'>{$type}</span>"
			);
		}

		return $this->card('history', $this->_('Recently done'), $this->list($items), 'timeline/', $this->_('Timeline'));
	}

	/**
	 * Recurrences repeat forever, so their next occurrence is computed rather
	 * than stored. Only yearly and monthly rules are projected here; the rest
	 * fall back to their start date (0 if the recurrence has none).
	 */
	protected function nextOccurrence(Page $rec, int $now): int {
		$start = (int) $rec->getUnformatted('folio_datetime');
		if (!$start) return 0;

		$unit = (int) $rec->folio_interval_unit?->value;
		$next = $start;
		if ($unit === Folio::UNIT_YEARS) {
			$next = strtotime(date('Y', $now) . date('-m-d', $start));
			if ($next < $now) $next = strtotime('+1 year', $next);
		} else if ($unit === Folio::UNIT_MONTHS) {
			$next = strtotime(date('Y-m', $now) . date('-d', $start));
			if ($next < $now) $next = strtotime('+1 month', $next);
		}

		return $next;
	}

	protected function upcomingRecurrences(int $limit, int $withinDays = 60): array {
		$rows = [];
		$now = time();

		foreach ($this->wire('pages')->find('template=folio-recurrence') as $rec) {
			$next = $this->nextOccurrence($rec, $now);
			if (!$next || $next < $now) continue;
			$days = (int) floor(($next - $now) / 86400);
			if ($days > $withinDays) continue;

			$rows[] = ['page' => $rec, 'next' => $next, 'days' => $days];
		}

		usort($rows, fn($a, $b) => $a['next'] <=> $b['next']);
		return array_slice($rows, 0, $limit);
	}
}
