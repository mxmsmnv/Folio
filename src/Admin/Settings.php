<?php namespace ProcessWire;

/**
 * These are read/written only through WireData's magic __get()/__set(),
 * never declared as real properties: Modules::setModuleConfigData()
 * assigns saved config with `$module->$key = $value` from outside the
 * class, which PHP routes to __set() only because no same-named native
 * property exists to shadow it. Declaring `protected int $timelineLimit`
 * here would silently keep reading its compile-time default forever.
 */
trait ProcessFolioSettings {

	protected function timelineLimit(): int {
		$value = (int) $this->timeline_limit;
		return $value > 0 ? $value : 100;
	}

	protected function cardLimit(): int {
		$value = (int) $this->dashboard_limit;
		return $value > 0 ? $value : 6;
	}

	protected function upcomingDays(): int {
		$value = (int) $this->upcoming_days;
		return $value > 0 ? $value : 60;
	}

	public function getModuleConfigInputfields(): InputfieldWrapper {
		$modules = $this->wire('modules');
		$wrapper = $this->wire(new InputfieldWrapper());

		$f = $modules->get('InputfieldInteger');
		$f->name = 'timeline_limit';
		$f->label = $this->_('Timeline entries');
		$f->description = $this->_('How many entries the Timeline tab shows before it stops looking back.');
		$f->min = 10;
		$f->max = 1000;
		$f->value = $this->timelineLimit();
		$f->columnWidth = 34;
		$wrapper->add($f);

		$f = $modules->get('InputfieldInteger');
		$f->name = 'dashboard_limit';
		$f->label = $this->_('Dashboard card size');
		$f->description = $this->_('How many rows each Dashboard card lists before pointing to its own tab.');
		$f->min = 1;
		$f->max = 50;
		$f->value = $this->cardLimit();
		$f->columnWidth = 33;
		$wrapper->add($f);

		$f = $modules->get('InputfieldInteger');
		$f->name = 'upcoming_days';
		$f->label = $this->_('Upcoming window (days)');
		$f->description = $this->_('How many days ahead "Coming up" looks for birthdays and recurring events.');
		$f->min = 1;
		$f->max = 365;
		$f->value = $this->upcomingDays();
		$f->columnWidth = 33;
		$wrapper->add($f);

		return $wrapper;
	}
}
