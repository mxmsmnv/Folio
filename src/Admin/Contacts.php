<?php namespace ProcessWire;

/** The Contacts tab: a card grid with an inline avatar next to each name. */
trait ProcessFolioContacts {

	public function ___executeContacts(): string {
		return $this->screen('contacts',
			$this->addButton('folio-contacts', $this->_('Contact'))
			. $this->renderContacts()
		);
	}

	protected function contactStyles(): string {
		return "<style>"
			. ".ProcessFolio .pw-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }"
			. ".ProcessFolio .pw-avatar-placeholder { display: flex; align-items: center; justify-content: center;"
			. " background: var(--pw-blocks-background); color: var(--pw-muted-color); }"
			. '</style>';
	}

	/** A URL renders as a link; a bare handle like "@username" doesn't get forced into one. */
	protected function websiteLink(string $value): string {
		if (!preg_match('~^https?://~i', $value)) return $this->entities($value);
		return "<a href='" . $this->entities($value) . "' target='_blank' rel='noopener'>" . $this->entities($value) . '</a>';
	}

	/**
	 * A card grid, not a table: Contact fields read more naturally as a small
	 * stack under a name than as columns that are mostly empty for any given
	 * row (not everyone has all of them). The avatar sits inline next to the
	 * name rather than as a Places-style photo strip — a contact card, not a
	 * place to browse thumbnails.
	 */
	protected function renderContacts(): string {
		$contacts = $this->wire('pages')->find('template=folio-contact, sort=title');
		if (!$contacts->count()) return "<p class='uk-text-muted'>" . $this->_('No contacts yet.') . '</p>';

		$out = '';
		foreach ($contacts as $contact) {
			$photo = $contact->folio_photo->first();
			$avatar = $photo
				? "<img class='pw-avatar' src='" . $this->entities($photo->size(88, 88)->url) . "' alt=''>"
				: "<div class='pw-avatar pw-avatar-placeholder'>{$this->icon('user')}</div>";

			$roleLine = implode(' · ', array_filter([$contact->folio_role, $contact->folio_company]));
			$roleHtml = $roleLine ? "<div class='uk-text-meta'>" . $this->entities($roleLine) . '</div>' : '';

			$head = "<div class='uk-flex uk-flex-middle'>{$avatar}"
				. "<div class='uk-margin-small-left'><h3 class='uk-h5 uk-margin-remove'>" . $this->titleCell($contact) . '</h3>'
				. "{$roleHtml}</div></div>";

			$lines = '';
			foreach ([
				['phone', $contact->folio_phone],
				['phone', $contact->folio_phone_2],
				['map-marker', $contact->folio_address],
			] as [$icon, $value]) {
				if ($value) $lines .= "<div class='uk-text-small'>{$this->icon($icon)} " . $this->entities($value) . '</div>';
			}
			foreach ([$contact->folio_email, $contact->folio_email_2] as $value) {
				if (!$value) continue;
				$lines .= "<div class='uk-text-small'>{$this->icon('envelope-o')} <a href='mailto:"
					. $this->entities($value) . "'>" . $this->entities($value) . '</a></div>';
			}
			if ($contact->folio_website) {
				$lines .= "<div class='uk-text-small'>{$this->icon('globe')} " . $this->websiteLink($contact->folio_website) . '</div>';
			}
			if ($contact->folio_birthday) {
				$lines .= "<div class='uk-text-small'>{$this->icon('birthday-cake')} " . $this->date($contact->getUnformatted('folio_birthday')) . '</div>';
			}

			$out .= "<div><div class='uk-card uk-card-default uk-card-body uk-card-small'>{$head}"
				. "<div class='uk-margin-small-top'>{$lines}</div>"
				. "<div class='uk-margin-small-top'>" . $this->tagChips($contact->folio_tags) . '</div>'
				. '</div></div>';
		}

		return $this->contactStyles()
			. "<div class='uk-grid-medium uk-child-width-1-2@s uk-child-width-1-3@m uk-child-width-1-4@l' uk-grid>{$out}</div>";
	}
}
