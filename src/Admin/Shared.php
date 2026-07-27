<?php namespace ProcessWire;

/**
 * View helpers every other trait relies on: entity escaping, URLs, date/status
 * formatting, the card/list/row building blocks, filter-form controls, and a
 * couple of small formatters (tags, page links, "in N days"). Nothing here is
 * specific to one tab.
 */
trait ProcessFolioShared {

	protected function entities(?string $value): string {
		return $this->wire('sanitizer')->entities((string) $value);
	}

	protected function addUrl(Page $parent): string {
		return $this->wire('config')->urls->admin . "page/add/?parent_id={$parent->id}";
	}

	protected function editUrl(Page $page): string {
		return $this->wire('config')->urls->admin . "page/edit/?id={$page->id}";
	}

	protected function container(string $template): Page {
		return $this->wire('pages')->get("template={$template}, include=all");
	}

	protected function addButton(string $containerTemplate, string $label): string {
		$container = $this->container($containerTemplate);
		if (!$container->id) return '';

		return "<a class='uk-button uk-button-default uk-margin-small-right uk-margin-bottom' "
			. "href='{$this->addUrl($container)}'><i class='fa fa-plus'></i> " . $this->entities($label) . '</a>';
	}

	/**
	 * $config->dateFormat is PW's system format (default 'Y-m-d H:i:s', includes
	 * time) — meant for admin listers, not for date-only fields like birthdays
	 * or event/recurrence dates, so this hardcodes a date-only format instead.
	 */
	protected function date($value): string {
		$value = (int) $value;
		if (!$value) return '';
		return date('Y-m-d', $value);
	}

	protected function icon(string $name, string $class = ''): string {
		$class = trim("fa fa-{$name} fa-fw {$class}");
		return "<i class='{$class}'></i>";
	}

	/**
	 * Plain coloured text, not `uk-text-danger`: Konkat's admin.css redefines
	 * that class as a solid-fill highlight (background-color, not just
	 * color) for use as an inline badge, which reads as an alarming block
	 * of red rather than a subtly overdue date.
	 */
	protected function dangerText(string $html): string {
		return "<span style='color: var(--pw-error-inline-text-color)'>{$html}</span>";
	}

	/** Status and priority rendered as colour-coded labels rather than bare words. */
	protected function statusLabel(?Page $page): string {
		$value = (int) $page?->folio_status?->value;
		$title = $this->entities((string) $page?->folio_status?->title);
		if (!$title) return '';

		$class = match ($value) {
			Folio::STATUS_DONE => 'uk-label uk-label-success',
			Folio::STATUS_PROGRESS => 'uk-label uk-label-warning',
			default => 'uk-label',
		};
		return "<span class='{$class}'>{$title}</span>";
	}

	protected function priorityLabel(?Page $page): string {
		$value = (int) $page?->folio_priority?->value;
		$title = $this->entities((string) $page?->folio_priority?->title);
		if (!$title) return '';

		$class = $value === Folio::PRIORITY_HIGH ? 'uk-label uk-label-danger' : 'uk-label';
		return "<span class='{$class}'>{$title}</span>";
	}

	protected function titleCell(Page $page): string {
		return "<a href='{$this->editUrl($page)}'>" . $this->entities($page->title) . '</a>';
	}

	protected function card(string $icon, string $title, string $body, string $moreSegment = '', string $moreLabel = ''): string {
		$more = $moreSegment
			? "<p class='uk-margin-small-top uk-text-small'><a href='{$this->wire('page')->url}{$moreSegment}'>"
				. $this->entities($moreLabel) . ' →</a></p>'
			: '';

		return "<div><div class='uk-card uk-card-default uk-card-body uk-card-small'>"
			. "<h3 class='uk-h5 uk-margin-small-bottom uk-flex uk-flex-middle'>"
			. "<span class='pw-card-icon'>{$this->icon($icon)}</span>" . $this->entities($title) . '</h3>'
			. "{$body}{$more}</div></div>";
	}

	protected function list(array $items): string {
		return "<ul class='uk-list uk-list-divider uk-margin-remove'><li>"
			. implode('</li><li>', $items) . '</li></ul>';
	}

	/**
	 * One line of a dashboard card list: primary content (with its meta
	 * underneath) on the left, a status/priority badge on the right — the
	 * same left/right pairing the tables and Timeline already use, just as
	 * a flex row instead of table cells.
	 */
	protected function row(string $main, string $meta = '', string $aside = ''): string {
		$metaHtml = $meta ? "<div class='pw-row-meta'>{$meta}</div>" : '';
		$asideHtml = $aside ? "<div class='pw-row-aside'>{$aside}</div>" : '';

		return "<div class='pw-row'><div class='pw-row-main'>{$main}{$metaHtml}</div>{$asideHtml}</div>";
	}

	/**
	 * Scoped once per render, same as timelineStyles(): a stat-tile chip
	 * layout, a shared main/meta/aside row for every card list, an inline
	 * progress row, a small icon button, and an empty state. color-mix()
	 * against the live --pw-main-color token keeps every tint correct in
	 * both themes without a second dark-mode rule to maintain.
	 */
	protected function cardStyles(): string {
		return <<<CSS
			<style>
			.ProcessFolio .pw-stat-icon {
				display: flex; align-items: center; justify-content: center;
				width: 38px; height: 38px; border-radius: 9px; flex-shrink: 0;
				background: color-mix(in srgb, var(--pw-main-color) 14%, transparent);
				color: var(--pw-main-color); font-size: 15px;
			}
			.ProcessFolio .pw-stat-value { font-size: 22px; font-weight: 700; line-height: 1.15; }
			.ProcessFolio .pw-stat-label { font-size: 12px; color: var(--pw-muted-color); margin-top: 1px; }

			.ProcessFolio .pw-card-icon {
				display: inline-flex; align-items: center; justify-content: center;
				width: 26px; height: 26px; border-radius: 7px; margin-right: 6px;
				background: color-mix(in srgb, var(--pw-main-color) 14%, transparent);
				color: var(--pw-main-color); font-size: 12px; vertical-align: middle;
			}

			.ProcessFolio .pw-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
			.ProcessFolio .pw-row-main { min-width: 0; }
			.ProcessFolio .pw-row-main a { font-weight: 500; }
			.ProcessFolio .pw-row-meta { font-size: 12px; color: var(--pw-muted-color); margin-top: 2px; }
			.ProcessFolio .pw-row-aside { flex-shrink: 0; padding-top: 1px; }

			.ProcessFolio .pw-progress-row { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
			.ProcessFolio .pw-progress-row .uk-progress { margin: 0; flex: 1; }
			.ProcessFolio .pw-progress-label { font-size: 11px; color: var(--pw-muted-color); flex-shrink: 0; width: 30px; text-align: right; }

			.ProcessFolio .pw-icon-btn {
				display: inline-flex; align-items: center; justify-content: center;
				width: 20px; height: 20px; border-radius: 5px; margin-left: 2px;
				background: color-mix(in srgb, var(--pw-main-color) 10%, transparent);
				color: var(--pw-main-color); vertical-align: middle;
				/* Konkat underlines every link inside a .uk-card (.uk-card
				   :is(a, .uk-link)); correct for a card's title link, wrong
				   for an icon-only button that happens to sit in one. */
				text-decoration: none;
			}

			.ProcessFolio .pw-empty {
				text-align: center; padding: 48px 20px; color: var(--pw-muted-color);
				border: 1px dashed var(--pw-border-color); border-radius: 8px;
			}
			.ProcessFolio .pw-empty .fa { font-size: 28px; margin-bottom: 10px; display: block; opacity: .5; }
			</style>
			CSS;
	}

	/** Comma-separated free text (folio_tags) rendered as individual uk-label chips. */
	protected function tagChips(?string $tags): string {
		$parts = array_filter(array_map('trim', explode(',', (string) $tags)));
		if (!$parts) return '';

		$chips = array_map(
			fn($t) => "<span class='uk-label uk-margin-small-right uk-margin-small-top'>" . $this->entities($t) . '</span>',
			$parts
		);
		return "<div>" . implode('', $chips) . '</div>';
	}

	protected function pageLinks($value): string {
		if (!$value instanceof PageArray || !$value->count()) return '';

		$links = [];
		foreach ($value as $page) $links[] = $this->titleCell($page);
		return implode(', ', $links);
	}

	protected function daysFromNow(int $days): string {
		if ($days === 0) return $this->_('today');
		if ($days === 1) return $this->_('tomorrow');
		return sprintf($this->_('in %d days'), $days);
	}

	/**
	 * A single-line filter bar: plain native <select>s that auto-submit on
	 * change, not InputfieldForm/InputfieldSelect — their fieldset+legend
	 * markup renders a tall boxed panel that dwarfs what is really just a
	 * couple of dropdowns.
	 */
	protected function filterForm(string $selects): string {
		$style = "<style>.ProcessFolio .pw-filter-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }"
			. ' .ProcessFolio .pw-filter-row select { min-width: 160px; }</style>';
		return "{$style}<form method='get' action='./' class='pw-filter-row'>{$selects}</form>";
	}

	/** Filter choices come from the field's own options, never a second copy. */
	protected function filterOptionSelect(string $name, string $emptyLabel, string $fieldName, ?string $value): string {
		$options = "<option value=''>" . $this->entities($emptyLabel) . '</option>';

		$field = $this->wire('fields')->get($fieldName);
		if ($field && $field->id) {
			$manager = $this->wire('modules')->get('FieldtypeOptions')->manager;
			foreach ($manager->getOptions($field) as $option) {
				$selected = (string) $option->value === (string) $value ? " selected='selected'" : '';
				$options .= "<option value='{$option->value}'{$selected}>" . $this->entities($option->title) . '</option>';
			}
		}

		return "<select class='uk-select uk-form-small' name='{$name}' onchange='this.form.submit()'>{$options}</select>";
	}

	/** Same idea as filterOptionSelect(), but the choices are page titles rather than field options. */
	protected function filterPageSelect(string $name, string $emptyLabel, PageArray $pages, ?int $value): string {
		$options = "<option value=''>" . $this->entities($emptyLabel) . '</option>';

		foreach ($pages as $page) {
			$selected = $page->id === $value ? " selected='selected'" : '';
			$options .= "<option value='{$page->id}'{$selected}>" . $this->entities($page->title) . '</option>';
		}

		return "<select class='uk-select uk-form-small' name='{$name}' onchange='this.form.submit()'>{$options}</select>";
	}
}
