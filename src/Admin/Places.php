<?php namespace ProcessWire;

/** The Places tab: a photo card grid with a visit-status filter. */
trait ProcessFolioPlaces {

	public function ___executePlaces(): string {
		$visit = $this->wire('input')->get->text('visit');

		return $this->screen('places',
			$this->addButton('folio-places', $this->_('Place'))
			. $this->renderPlacesFilters($visit)
			. $this->renderPlaces($visit)
		);
	}

	protected function visitStatusLabel(Page $place): string {
		$title = $this->entities((string) $place->folio_visit_status?->title);
		if (!$title) return '';
		$class = (int) $place->folio_visit_status?->value === Folio::VISIT_DONE
			? 'uk-label uk-label-success' : 'uk-label';
		return "<span class='{$class}'>{$title}</span>";
	}

	protected function renderPlacesFilters(?string $visitStatus): string {
		return $this->filterForm($this->filterOptionSelect('visit', $this->_('Any visit status'), 'folio_visit_status', $visitStatus));
	}

	/**
	 * A photo grid rather than a data table: Places is a "things to look at
	 * and want to go to" list, and rows of text don't sell that the way a
	 * thumbnail does. Every box is a native uk-card — no custom border or
	 * radius of our own, same as the rest of this pass.
	 */
	protected function renderPlaces(?string $visitStatus = null): string {
		$selector = 'template=folio-place, sort=title';
		if ($visitStatus !== '' && $visitStatus !== null) {
			$selector .= ', folio_visit_status.value=' . $this->wire('sanitizer')->int($visitStatus);
		}
		$places = $this->wire('pages')->find($selector);
		if (!$places->count()) return "<p class='uk-text-muted'>" . $this->_('No places yet.') . '</p>';

		$out = '';
		foreach ($places as $place) {
			$photo = $place->folio_images->first();
			$media = $photo
				? "<div class='uk-height-small uk-background-cover' style=\"background-image:url('"
					. $this->entities($photo->size(600, 300)->url) . "')\"></div>"
				: "<div class='uk-height-small uk-flex uk-flex-middle uk-flex-center uk-text-muted' style='background:var(--pw-blocks-background)'>"
					. $this->icon('picture-o', 'fa-2x') . '</div>';

			$address = $place->folio_address ? "<p class='uk-text-meta uk-margin-remove'>" . $this->entities($place->folio_address) . '</p>' : '';

			$links = '';
			if ($place->folio_links->count()) {
				$links = "<div class='uk-margin-small-top uk-text-small'>{$this->icon('external-link')} " . $this->pageLinks($place->folio_links) . '</div>';
			}

			$out .= "<div><div class='uk-card uk-card-default'>{$media}"
				. "<div class='uk-card-body uk-card-small'>"
				. "<h3 class='uk-card-title uk-h5 uk-margin-remove-bottom'>" . $this->titleCell($place) . '</h3>'
				. "{$address}<div class='uk-margin-small-top'>{$this->visitStatusLabel($place)}</div>{$links}"
				. '</div></div></div>';
		}

		return "<div class='uk-grid-medium uk-child-width-1-2@s uk-child-width-1-3@m uk-child-width-1-4@l' uk-grid>{$out}</div>";
	}
}
