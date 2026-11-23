/**
 * Cookie consent banner — versioned localStorage with preferences reset.
 * No analytics script is wired up on "accept" here (Vercel Analytics/Speed
 * Insights don't apply off Vercel); the accepted hook is left in place
 * for whatever analytics the client chooses later.
 */
(function () {
	var STORAGE_KEY = 'ndingi-cookie-consent';
	var CURRENT_VERSION = 'v1';
	var banner = document.querySelector('[data-cookie-banner]');
	if (!banner) return;

	function getConsent() {
		try {
			var raw = localStorage.getItem(STORAGE_KEY);
			if (!raw) return null;
			// Backwards compatibility with legacy string 'accepted' | 'rejected'
			if (raw === 'accepted' || raw === 'rejected') {
				return { choice: raw, version: CURRENT_VERSION };
			}
			var parsed = JSON.parse(raw);
			if (parsed && (parsed.choice === 'accepted' || parsed.choice === 'rejected')) {
				if (parsed.version === CURRENT_VERSION) {
					return parsed;
				}
				// Outdated consent policy version -> re-prompt
				return null;
			}
			return null;
		} catch (e) {
			return null;
		}
	}

	function setConsent(value) {
		var record = {
			choice: value,
			version: CURRENT_VERSION,
			timestamp: new Date().toISOString()
		};
		try {
			localStorage.setItem(STORAGE_KEY, JSON.stringify(record));
		} catch (e) {
			/* private browsing / storage disabled — the choice still applies this visit */
		}
		banner.hidden = true;
		document.dispatchEvent(new CustomEvent('ndingi:cookie-consent', { detail: record }));
	}

	if (getConsent() !== null) {
		banner.hidden = true;
	}

	banner.querySelectorAll('[data-consent]').forEach(function (button) {
		button.addEventListener('click', function () {
			setConsent(button.getAttribute('data-consent'));
		});
	});

	// Re-open cookie preferences from footer link
	document.querySelectorAll('[data-open-cookie-preferences]').forEach(function (trigger) {
		trigger.addEventListener('click', function (e) {
			e.preventDefault();
			banner.hidden = false;
			banner.scrollIntoView({ behavior: 'smooth', block: 'end' });
		});
	});
})();
