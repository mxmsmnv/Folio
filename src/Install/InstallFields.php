<?php namespace ProcessWire;

/** Field creation: every folio_* field, plus the datepicker top-up. */
trait FolioInstallFields {

	protected function installFields(): void {
		$status = $this->addField('folio_status', 'FieldtypeOptions', ['label' => 'Status']);
		$this->addFieldOptions($status, [
			self::STATUS_PLAN => 'Plan',
			self::STATUS_PROGRESS => 'In progress',
			self::STATUS_DONE => 'Done',
		]);

		$priority = $this->addField('folio_priority', 'FieldtypeOptions', ['label' => 'Priority']);
		$this->addFieldOptions($priority, [
			self::PRIORITY_LOW => 'Low',
			self::PRIORITY_MEDIUM => 'Medium',
			self::PRIORITY_HIGH => 'High',
		]);

		$unit = $this->addField('folio_interval_unit', 'FieldtypeOptions', ['label' => 'Repeat unit']);
		$this->addFieldOptions($unit, [
			self::UNIT_DAYS => 'Days',
			self::UNIT_WEEKS => 'Weeks',
			self::UNIT_MONTHS => 'Months',
			self::UNIT_YEARS => 'Years',
		]);

		$this->addField('folio_description', 'FieldtypeTextarea', ['label' => 'Description']);
		$this->addField('folio_datetime', 'FieldtypeDatetime', ['label' => 'Date/time']);
		$this->addField('folio_duration', 'FieldtypeInteger', ['label' => 'Duration (minutes)']);
		$this->addField('folio_start_date', 'FieldtypeDatetime', ['label' => 'Start date']);
		$this->addField('folio_end_date', 'FieldtypeDatetime', ['label' => 'End date']);
		// Both of these are written by hooks, never by hand: shown so their
		// state is visible, locked so editing them cannot fight the hook.
		$this->addField('folio_is_backlog', 'FieldtypeCheckbox', [
			'label' => 'Is backlog sprint',
			'collapsed' => Inputfield::collapsedNoLocked,
		]);
		$this->addField('folio_phone', 'FieldtypeText', ['label' => 'Phone']);
		$this->addField('folio_email', 'FieldtypeEmail', ['label' => 'Email']);
		$this->addField('folio_birthday', 'FieldtypeDatetime', ['label' => 'Birthday']);
		$this->addField('folio_tags', 'FieldtypeText', ['label' => 'Tags']);
		$this->addField('folio_photo', 'FieldtypeImage', [
			'label' => 'Photo',
			'extensions' => 'jpg jpeg png gif webp',
			// A contact card wants one portrait, not a gallery like Places.
			'maxFiles' => 1,
		]);
		$this->addField('folio_company', 'FieldtypeText', ['label' => 'Company']);
		$this->addField('folio_role', 'FieldtypeText', ['label' => 'Role']);
		$this->addField('folio_website', 'FieldtypeText', [
			'label' => 'Website / social',
			// Plain text, not FieldtypeURL: a Telegram handle like "@username"
			// isn't a valid URL and shouldn't fail validation.
			'description' => 'A URL, or a handle like @username.',
		]);
		$this->addField('folio_phone_2', 'FieldtypeText', ['label' => 'Phone 2']);
		$this->addField('folio_email_2', 'FieldtypeEmail', ['label' => 'Email 2']);
		$this->addField('folio_interval', 'FieldtypeInteger', ['label' => 'Repeat every']);
		$this->addField('folio_contact', 'FieldtypePage', [
			'label' => 'Contact',
			'inputfield' => 'InputfieldSelect',
			// Empty value yields a NullPage rather than false, so reading
			// ->title off an unset reference stays safe.
			'derefAsPage' => FieldtypePage::derefAsPageOrNullPage,
		]);
		$this->addField('folio_url', 'FieldtypeURL', ['label' => 'URL']);
		$this->addField('folio_project', 'FieldtypePage', [
			'label' => 'Project',
			'inputfield' => 'InputfieldSelect',
			'derefAsPage' => FieldtypePage::derefAsPageOrNullPage,
		]);
		$this->addField('folio_address', 'FieldtypeText', ['label' => 'Address']);
		// colType double: a single-precision float cannot hold a coordinate
		// like -122.419416 without dropping digits.
		$this->addField('folio_lat', 'FieldtypeFloat', ['label' => 'Latitude', 'precision' => 6, 'colType' => 'double']);
		$this->addField('folio_lng', 'FieldtypeFloat', ['label' => 'Longitude', 'precision' => 6, 'colType' => 'double']);

		$visit = $this->addField('folio_visit_status', 'FieldtypeOptions', ['label' => 'Visit status']);
		$this->addFieldOptions($visit, [
			self::VISIT_WANT => 'Want to visit',
			self::VISIT_DONE => 'Visited',
		]);

		$this->addField('folio_period', 'FieldtypeText', ['label' => 'Period (e.g. 2026 Q3)']);
		$this->addField('folio_target_date', 'FieldtypeDatetime', ['label' => 'Target date']);
		$this->addField('folio_completed_at', 'FieldtypeDatetime', [
			'label' => 'Completed at',
			'collapsed' => Inputfield::collapsedNoLocked,
		]);
		$this->configureDateFields();

		// Multi-value references: derefAsPageArray is the default, but spelled
		// out here to contrast with the single-page fields above.
		$this->addField('folio_projects', 'FieldtypePage', [
			'label' => 'Projects',
			'inputfield' => 'InputfieldAsmSelect',
			'derefAsPage' => FieldtypePage::derefAsPageArray,
		]);
		$this->addField('folio_goals', 'FieldtypePage', [
			'label' => 'Goals',
			'inputfield' => 'InputfieldAsmSelect',
			'derefAsPage' => FieldtypePage::derefAsPageArray,
		]);
		$this->addField('folio_images', 'FieldtypeImage', [
			'label' => 'Photos',
			// Without an explicit extensions list the field shows a
			// "not yet ready to use" notice until someone opens and
			// re-saves its settings by hand.
			'extensions' => 'jpg jpeg png gif webp',
			'maxFiles' => 0,
		]);
		$this->addField('folio_links', 'FieldtypePage', [
			'label' => 'Links',
			'inputfield' => 'InputfieldAsmSelect',
			'derefAsPage' => FieldtypePage::derefAsPageArray,
		]);
	}

	/**
	 * FieldtypeDatetime defaults to a plain text input — no calendar UI at
	 * all — unless `datepicker` is set explicitly. addField() only applies
	 * its $props at creation time, so an already-installed field (e.g. on an
	 * upgrade rather than a fresh install) would never pick this up from
	 * installFields() alone; this re-applies it unconditionally every time,
	 * the same top-up addTemplate() already does for icons.
	 */
	protected function configureDateFields(): void {
		$fields = $this->wire('fields');

		foreach (['folio_birthday', 'folio_start_date', 'folio_end_date', 'folio_target_date'] as $name) {
			$field = $fields->get($name);
			if (!$field || !$field->id) continue;
			$field->set('datepicker', InputfieldDatetime::datepickerFocus);
			$field->set('dateInputFormat', 'Y-m-d');
			$field->save();
		}

		// folio_datetime is the only one with a meaningful time-of-day
		// (event start times, duration in minutes) — the rest are date-only.
		$datetime = $fields->get('folio_datetime');
		if ($datetime && $datetime->id) {
			$datetime->set('datepicker', InputfieldDatetime::datepickerFocus);
			$datetime->set('dateInputFormat', 'Y-m-d');
			$datetime->set('timeInputFormat', 'H:i');
			$datetime->save();
		}
	}

	protected function addField(string $name, string $type, array $props = []): Field {
		$fields = $this->wire('fields');
		$field = $fields->get($name);
		if ($field && $field->id) return $field;

		$field = $this->wire(new Field());
		$field->type = $this->wire('modules')->get($type);
		$field->name = $name;
		foreach ($props as $key => $value) $field->set($key, $value);
		$field->save();
		return $field;
	}

	/**
	 * Populate a FieldtypeOptions field from a [value => title] map, but only
	 * while it has no options yet, so re-installing never clobbers options
	 * that were edited by hand.
	 *
	 * One line per option, in ProcessWire's "value|title" syntax. Note the
	 * pipe: a line written "10=Plan" would set the option *ID* to 10 and
	 * leave its value empty, which quietly breaks every `.value=` selector.
	 */
	protected function addFieldOptions(Field $field, array $options): void {
		$manager = $this->wire('modules')->get('FieldtypeOptions')->manager;
		if ($manager->getOptions($field)->count()) return;

		$lines = [];
		foreach ($options as $value => $title) $lines[] = "{$value}|{$title}";
		$manager->setOptionsString($field, implode("\n", $lines), false);
	}
}
