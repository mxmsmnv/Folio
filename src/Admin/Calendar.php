<?php namespace ProcessWire;

/** The Calendar tab: a real month grid, plus the Recurring card list below it. */
trait ProcessFolioCalendar {

	public function ___executeCalendar(): string {
		return $this->screen('calendar',
			$this->addButton('folio-calendar', $this->_('Event'))
			. $this->addButton('folio-recurring', $this->_('Recurring event'))
			. $this->renderCalendarGrid()
			. $this->renderRecurringCards()
		);
	}

	/**
	 * Scoped once per render, same pattern as timelineStyles()/cardStyles().
	 * No hover states, per standing instruction — every element that needs to
	 * read as interactive (nav arrows, event pills) gets a permanent tint
	 * instead of one that only appears on mouseover.
	 */
	protected function calendarStyles(): string {
		return <<<CSS
			<style>
			.ProcessFolio .pw-cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
			.ProcessFolio .pw-cal-title { font-size: 18px; font-weight: 700; margin: 0; }
			.ProcessFolio .pw-cal-nav { display: flex; gap: 6px; align-items: center; }
			.ProcessFolio .pw-cal-nav a {
				display: inline-flex; align-items: center; justify-content: center;
				height: 30px; padding: 0 10px; border-radius: 6px;
				background: color-mix(in srgb, var(--pw-main-color) 10%, transparent);
				color: var(--pw-text-color); font-size: 12px;
			}
			.ProcessFolio .pw-cal-grid {
				display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px;
				background: var(--pw-border-color); border: 1px solid var(--pw-border-color);
				border-radius: 8px; overflow: hidden;
			}
			.ProcessFolio .pw-cal-weekday {
				background: var(--pw-blocks-background); padding: 8px 6px;
				font-size: 11px; font-weight: 700; text-transform: uppercase;
				color: var(--pw-muted-color); text-align: center;
			}
			.ProcessFolio .pw-cal-day { background: var(--pw-main-background); min-height: 92px; padding: 6px; }
			.ProcessFolio .pw-cal-day.is-other { background: var(--pw-blocks-background); }
			.ProcessFolio .pw-cal-day.is-other .pw-cal-daynum { color: var(--pw-muted-color); opacity: .6; }
			.ProcessFolio .pw-cal-day.is-today { background: color-mix(in srgb, var(--pw-main-color) 7%, var(--pw-main-background)); }
			.ProcessFolio .pw-cal-daynum { font-size: 12px; font-weight: 600; margin-bottom: 4px; }
			.ProcessFolio .pw-cal-day.is-today .pw-cal-daynum {
				display: inline-flex; align-items: center; justify-content: center;
				width: 20px; height: 20px; border-radius: 50%;
				background: var(--pw-main-color); color: var(--pw-main-background);
			}
			.ProcessFolio .pw-cal-event {
				display: block; font-size: 11px; padding: 2px 5px; margin-bottom: 2px; border-radius: 4px;
				background: color-mix(in srgb, var(--pw-main-color) 14%, transparent);
				color: var(--pw-text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
			}
			.ProcessFolio .pw-cal-more { font-size: 11px; color: var(--pw-muted-color); }
			@media (max-width: 800px) { .ProcessFolio .pw-cal-day { min-height: 56px; } }
			</style>
			CSS;
	}

	/**
	 * A real month grid rather than an "Upcoming"/"Past" pair of tables: it
	 * shows every event for the viewed month regardless of whether that
	 * month is behind or ahead of today, which a date-filtered table can't
	 * do without becoming two tables. Recurring events are deliberately not
	 * plotted here — projecting an annual/monthly rule onto a specific
	 * six-week grid is its own can of worms, and a flat "Repeats every N"
	 * list (below the grid) already answers the only question that matters
	 * for those: when do they next happen.
	 */
	protected function renderCalendarGrid(): string {
		$input = $this->wire('input');
		$today = getdate();

		$year = $input->get->int('y') ?: (int) $today['year'];
		$month = $input->get->int('m') ?: (int) $today['mon'];
		if ($month < 1) { $month = 12; $year--; }
		if ($month > 12) { $month = 1; $year++; }

		$first = mktime(0, 0, 0, $month, 1, $year);
		// ISO weekday of the 1st (1=Mon..7=Sun) tells us how many days of the
		// prior month need to lead the grid so weeks start on Monday.
		$startWeekday = (int) date('N', $first);
		$rangeStart = mktime(0, 0, 0, $month, 1 - ($startWeekday - 1), $year);
		$totalCells = 42; // a fixed 6 weeks keeps the grid height stable month to month
		$rangeEnd = strtotime("+{$totalCells} days", $rangeStart);

		$byDay = [];
		foreach ($this->wire('pages')->find("template=folio-event, folio_datetime>={$rangeStart}, folio_datetime<{$rangeEnd}, sort=folio_datetime") as $event) {
			$byDay[date('Y-m-d', (int) $event->getUnformatted('folio_datetime'))][] = $event;
		}

		$todayKey = date('Y-m-d');
		$head = '';
		foreach ([$this->_('Mon'), $this->_('Tue'), $this->_('Wed'), $this->_('Thu'), $this->_('Fri'), $this->_('Sat'), $this->_('Sun')] as $label) {
			$head .= "<div class='pw-cal-weekday'>" . $this->entities($label) . '</div>';
		}

		$cells = '';
		for ($i = 0; $i < $totalCells; $i++) {
			$stamp = strtotime("+{$i} days", $rangeStart);
			$key = date('Y-m-d', $stamp);
			$class = 'pw-cal-day';
			if ((int) date('n', $stamp) !== $month) $class .= ' is-other';
			if ($key === $todayKey) $class .= ' is-today';

			$dayEvents = $byDay[$key] ?? [];
			$eventsHtml = '';
			$shown = 0;
			foreach ($dayEvents as $event) {
				if ($shown >= 3) break;
				$eventsHtml .= "<a class='pw-cal-event' href='{$this->editUrl($event)}'>" . $this->entities($event->title) . '</a>';
				$shown++;
			}
			if (count($dayEvents) > $shown) {
				$eventsHtml .= "<div class='pw-cal-more'>" . sprintf($this->_('+%d more'), count($dayEvents) - $shown) . '</div>';
			}

			$cells .= "<div class='{$class}'><div class='pw-cal-daynum'>" . date('j', $stamp) . "</div>{$eventsHtml}</div>";
		}

		$prevYear = $month === 1 ? $year - 1 : $year;
		$prevMonth = $month === 1 ? 12 : $month - 1;
		$nextYear = $month === 12 ? $year + 1 : $year;
		$nextMonth = $month === 12 ? 1 : $month + 1;
		$base = $this->wire('page')->url . 'calendar/';

		$nav = "<div class='pw-cal-nav'>"
			. "<a href='{$base}?y={$prevYear}&m={$prevMonth}'>{$this->icon('chevron-left')}</a>"
			. "<a href='{$base}'>" . $this->_('Today') . '</a>'
			. "<a href='{$base}?y={$nextYear}&m={$nextMonth}'>{$this->icon('chevron-right')}</a>"
			. '</div>';

		$header = "<div class='pw-cal-header'><h3 class='pw-cal-title'>" . $this->entities(date('F Y', $first)) . "</h3>{$nav}</div>";

		return $this->calendarStyles() . $header . "<div class='pw-cal-grid'>{$head}{$cells}</div>";
	}

	/**
	 * A card grid rather than a table, same reasoning as Places: this is a
	 * short, glanceable list, not a dataset worth spreadsheet columns for.
	 * Cards sort by next occurrence so the soonest recurrence leads — for
	 * daily/weekly rules that don't project forward (see nextOccurrence()),
	 * that's just their stored start date.
	 */
	protected function renderRecurringCards(): string {
		$recurrences = $this->wire('pages')->find('template=folio-recurrence, sort=title');
		if (!$recurrences->count()) {
			return "<h3 class='uk-h5 uk-margin-top'>" . $this->_('Recurring') . "</h3>"
				. "<p class='uk-text-muted'>" . $this->_('No recurring events.') . '</p>';
		}

		$now = time();
		$rows = [];
		foreach ($recurrences as $rec) $rows[] = ['page' => $rec, 'next' => $this->nextOccurrence($rec, $now)];
		usort($rows, fn($a, $b) => $a['next'] <=> $b['next']);

		$out = '';
		foreach ($rows as $row) {
			$rec = $row['page'];
			$next = $row['next'];
			$isFuture = $next && $next >= $now;
			$whenLabel = $isFuture ? $this->_('Next') : $this->_('Starts');
			$when = $next ? $this->entities($this->date($next)) : '';
			if ($isFuture) {
				$days = (int) floor(($next - $now) / 86400);
				$when .= " · " . $this->entities($this->daysFromNow($days));
			}

			$contact = $rec->folio_contact?->id
				? "<div class='uk-margin-small-top uk-text-small'>{$this->icon('user')} " . $this->titleCell($rec->folio_contact) . '</div>'
				: '';

			$out .= "<div><div class='uk-card uk-card-default uk-card-body uk-card-small'>"
				. "<h3 class='uk-h5 uk-margin-remove-bottom'>" . $this->titleCell($rec) . '</h3>'
				. "<div class='uk-margin-small-top'><span class='uk-label'>" . sprintf($this->_('Every %s'), $this->entities($this->repeatLabel($rec))) . '</span></div>'
				. ($when ? "<p class='uk-text-meta uk-margin-small-top uk-margin-remove-bottom'>{$whenLabel}: {$when}</p>" : '')
				. "{$contact}</div></div>";
		}

		return "<h3 class='uk-h5 uk-margin-top'>" . $this->_('Recurring') . "</h3>"
			. "<div class='uk-grid-medium uk-child-width-1-2@s uk-child-width-1-3@m uk-child-width-1-4@l uk-margin-small-top' uk-grid>{$out}</div>";
	}

	/** "1 Year" / "3 Months", not the field's raw plural label. */
	protected function repeatLabel(Page $recurrence): string {
		$interval = (int) $recurrence->folio_interval;
		if (!$interval) return '';

		$unit = strtolower((string) $recurrence->folio_interval_unit?->title);
		$unit = $interval === 1 ? rtrim($unit, 's') : $unit;

		return "{$interval} {$unit}";
	}
}
