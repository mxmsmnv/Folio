<?php namespace ProcessWire;

/** The Roadmap and Timeline tabs: they share one vertical-timeline component. */
trait ProcessFolioRoadmap {

	public function ___executeRoadmap(): string {
		return $this->screen('roadmap',
			$this->addButton('folio-roadmap', $this->_('Milestone'))
			. $this->renderRoadmap()
		);
	}

	public function ___executeTimeline(): string {
		return $this->screen('timeline', $this->renderTimeline());
	}

	/**
	 * A vertical timeline rather than a table: milestones are inherently
	 * points in time, and a "Today" marker makes it obvious at a glance
	 * what's already behind schedule versus what's still ahead.
	 */
	protected function renderRoadmap(): string {
		$milestones = $this->wire('pages')->find('template=folio-milestone, sort=folio_target_date');
		if (!$milestones->count()) return "<p class='uk-text-muted'>" . $this->_('No milestones yet.') . '</p>';

		$now = time();
		$entries = [];
		$todayInserted = false;

		foreach ($milestones as $milestone) {
			$due = (int) $milestone->getUnformatted('folio_target_date');
			$status = (int) $milestone->folio_status?->value;
			$done = $status === Folio::STATUS_DONE;
			$overdue = $due && $due < $now && !$done;

			if (!$todayInserted && $due && $due >= $now) {
				$entries[] = ['type' => 'today'];
				$todayInserted = true;
			}

			$dot = match (true) {
				$done => 'is-success',
				$overdue => 'is-danger',
				$status === Folio::STATUS_PROGRESS => 'is-warning',
				default => '',
			};

			$when = $this->date($due);
			$whenHtml = $overdue
				? $this->dangerText($when)
				: "<span class='pw-timeline-meta'>{$when}</span>";

			$refs = array_filter([$this->pageLinks($milestone->folio_projects), $this->pageLinks($milestone->folio_goals)]);
			$refsHtml = $refs ? "<div class='pw-timeline-meta'>" . implode(' · ', $refs) . '</div>' : '';

			$entries[] = [
				'dot' => $dot,
				'body' => "{$this->titleCell($milestone)} {$this->statusLabel($milestone)}<br>{$whenHtml}{$refsHtml}",
			];
		}

		if (!$todayInserted) $entries[] = ['type' => 'today'];

		return $this->timelineStyles() . $this->timelineList($entries);
	}

	/**
	 * Scoped once per render rather than shipped as a separate asset file —
	 * the module has no other CSS, so a second request for a stylesheet
	 * would cost more than the handful of rules below.
	 *
	 * Colours are the same --pw-alert-success/warning and border/muted
	 * tokens the uk-label overrides already use elsewhere, so a dot and
	 * its matching status label are always the same colour.
	 */
	protected function timelineStyles(): string {
		return <<<CSS
			<style>
			.ProcessFolio .pw-timeline { list-style: none; margin: 0; padding: 0; position: relative; }
			.ProcessFolio .pw-timeline::before {
				content: ''; position: absolute; left: 6px; top: 2px; bottom: 2px;
				width: 2px; background: var(--pw-border-color);
			}
			.ProcessFolio .pw-timeline-item { position: relative; padding: 0 0 12px 30px; }
			.ProcessFolio .pw-timeline-item:last-child { padding-bottom: 0; }
			.ProcessFolio .pw-timeline-dot {
				position: absolute; left: 0; top: 12px; width: 14px; height: 14px;
				border-radius: 50%; background: var(--pw-muted-color);
				box-shadow: 0 0 0 3px var(--pw-main-background);
			}
			.ProcessFolio .pw-timeline-dot.is-success { background: var(--pw-alert-success); }
			.ProcessFolio .pw-timeline-dot.is-warning { background: var(--pw-alert-warning); }
			.ProcessFolio .pw-timeline-dot.is-danger { background: var(--pw-error-inline-text-color); }
			.ProcessFolio .pw-timeline-dot.is-today {
				background: var(--pw-main-color); width: 16px; height: 16px; left: -1px; top: 11px;
			}
			.ProcessFolio .pw-timeline-heading {
				font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
				color: var(--pw-muted-color); margin: 0; padding-top: 6px;
			}
			.ProcessFolio .pw-timeline-body {
				line-height: 1.5; background: var(--pw-blocks-background);
				border-radius: 6px; padding: 10px 14px;
			}
			.ProcessFolio .pw-timeline-meta { font-size: 12px; color: var(--pw-muted-color); }
			.ProcessFolio .pw-timeline-today-label {
				display: inline-block; padding: 2px 10px; border-radius: 999px; line-height: 1.6;
				background: var(--pw-main-color); color: var(--pw-main-background);
				font-size: 11px; font-weight: 700; text-transform: uppercase;
			}
			</style>
			CSS;
	}

	/**
	 * @param array $entries Each is either:
	 *   ['type' => 'heading', 'label' => string] — a day/section divider
	 *   ['type' => 'today']                      — the "Today" marker
	 *   ['dot' => string, 'body' => string]       — a normal entry
	 * A single <ul> holds everything so the connecting line never breaks;
	 * dot-less entries just don't draw one.
	 */
	protected function timelineList(array $entries): string {
		$out = '';
		foreach ($entries as $entry) {
			$type = $entry['type'] ?? 'item';
			if ($type === 'heading') {
				$out .= "<li class='pw-timeline-item'><div class='pw-timeline-heading'>{$entry['label']}</div></li>";
			} else if ($type === 'today') {
				$out .= "<li class='pw-timeline-item'><span class='pw-timeline-dot is-today'></span>"
					. "<span class='pw-timeline-today-label'>" . $this->_('Today') . '</span></li>';
			} else {
				$dot = $entry['dot'] ?? '';
				$out .= "<li class='pw-timeline-item'><span class='pw-timeline-dot {$dot}'></span>"
					. "<div class='pw-timeline-body'>{$entry['body']}</div></li>";
			}
		}

		return "<ul class='pw-timeline'>{$out}</ul>";
	}

	/**
	 * A read-only feed of what has already happened, gathered from the other
	 * modules rather than stored anywhere of its own.
	 */
	protected function renderTimeline(): string {
		$pages = $this->wire('pages');
		$rows = [];

		$completable = implode('|', Folio::COMPLETABLE_TEMPLATES);
		foreach ($pages->find("template={$completable}, folio_completed_at>0, sort=-folio_completed_at, limit={$this->timelineLimit()}") as $page) {
			$rows[] = ['date' => (int) $page->getUnformatted('folio_completed_at'), 'page' => $page, 'dot' => 'is-success'];
		}

		// Calendar entries earn their place by having happened, not by a status.
		foreach ($pages->find('template=folio-event, folio_datetime<' . time() . ", sort=-folio_datetime, limit={$this->timelineLimit()}") as $page) {
			$rows[] = ['date' => (int) $page->getUnformatted('folio_datetime'), 'page' => $page, 'dot' => ''];
		}

		if (!$rows) return "<p class='uk-text-muted'>" . $this->_('Nothing has happened yet.') . '</p>';

		usort($rows, fn($a, $b) => $b['date'] <=> $a['date']);
		$rows = array_slice($rows, 0, $this->timelineLimit());

		$entries = [];
		$lastDay = '';
		foreach ($rows as $row) {
			$day = date('Y-m-d', $row['date']);
			if ($day !== $lastDay) {
				$entries[] = ['type' => 'heading', 'label' => $this->date($row['date'])];
				$lastDay = $day;
			}
			$page = $row['page'];
			$icon = $page->template->getIcon();
			$entries[] = [
				'dot' => $row['dot'],
				'body' => "<span class='uk-label'>" . ($icon ? $this->icon($icon) . ' ' : '')
					. $this->entities($page->template->getLabel()) . "</span> {$this->titleCell($page)}",
			];
		}

		return $this->timelineStyles() . $this->timelineList($entries);
	}
}
