<?php namespace ProcessWire;

/** The three automations: backlog sprints, birthday sync, and completion stamps. */
trait FolioHooks {

	/**
	 * Every new folio-project page automatically gets a "Backlog" sprint,
	 * so tasks always have a sprint to belong to even before real sprints exist.
	 */
	public function hookPageAdded(HookEvent $event): void {
		$page = $event->arguments(0);
		if ($page->template != 'folio-project') return;

		$existing = $page->children('template=folio-sprint, folio_is_backlog=1, include=all')->first();
		if ($existing && $existing->id) return;

		$sprint = $this->wire(new Page());
		$sprint->template = 'folio-sprint';
		$sprint->parent = $page;
		$sprint->title = 'Backlog';
		$sprint->folio_is_backlog = 1;
		$sprint->save();
	}

	/**
	 * A contact's birthday is not stored twice: it drives a folio-recurrence
	 * page that repeats yearly and points back at the contact.
	 */
	public function hookPageSaved(HookEvent $event): void {
		$page = $event->arguments(0);
		if ($page->template != 'folio-contact') return;

		$pages = $this->wire('pages');
		$recurrence = $pages->get("template=folio-recurrence, folio_contact={$page->id}, include=all");

		if (!$page->folio_birthday) {
			if ($recurrence->id) $pages->delete($recurrence, true);
			return;
		}

		if (!$recurrence->id) {
			$parent = $pages->get('template=folio-recurring, include=all');
			if (!$parent->id) return;

			$recurrence = $this->wire(new Page());
			$recurrence->template = 'folio-recurrence';
			$recurrence->parent = $parent;
			$recurrence->folio_contact = $page;
			$recurrence->folio_interval = 1;
			$recurrence->folio_interval_unit = $this->optionByValue('folio_interval_unit', self::UNIT_YEARS);
		}

		$recurrence->title = sprintf($this->_('%s — birthday'), $page->title);
		$recurrence->folio_datetime = $page->folio_birthday;
		$recurrence->save();
	}

	/**
	 * Stamp the moment something is completed. The Timeline orders by this,
	 * and a page's own modified date would not do: any later edit would move
	 * a long-finished item back to the top of the feed.
	 *
	 * Runs on saveReady so the stamp is written by the save already underway.
	 */
	public function hookSaveReady(HookEvent $event): void {
		$page = $event->arguments(0);
		if (!in_array((string) $page->template, self::COMPLETABLE_TEMPLATES)) return;

		$completed = $this->isCompleted($page);
		if ($completed && !$page->folio_completed_at) {
			$page->folio_completed_at = time();
		} else if (!$completed && $page->folio_completed_at) {
			$page->folio_completed_at = '';
		}
	}

	/** A place is done when visited; everything else when its status is Done. */
	protected function isCompleted(Page $page): bool {
		if ((string) $page->template === 'folio-place') {
			return (int) $page->folio_visit_status?->value === self::VISIT_DONE;
		}
		return (int) $page->folio_status?->value === self::STATUS_DONE;
	}

	/**
	 * Look up a selectable option by its stored value. Assigning a bare number
	 * to an options field would be read as an option ID instead.
	 */
	protected function optionByValue(string $fieldName, int $value): ?SelectableOption {
		$field = $this->wire('fields')->get($fieldName);
		if (!$field || !$field->id) return null;

		$manager = $this->wire('modules')->get('FieldtypeOptions')->manager;
		return $manager->getOptions($field, ['value' => $value])->first();
	}
}
