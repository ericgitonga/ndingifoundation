/**
 * Cookie consent banner — plain localStorage version of ndingi's
 * CookieConsent.tsx. No analytics script is wired up on "accept" here
 * (Vercel Analytics/Speed Insights don't apply off Vercel); the accepted
 * hook is left in place for whatever analytics the client chooses later.
 */
(function () {
	var STORAGE_KEY = 'ndingi-cookie-consent';
	var banner = document.querySelector('[data-cookie-banner]');
	if (!banner) return;

	function getConsent() {
		try {
			var value = localStorage.getItem(STORAGE_KEY);
			return value === 'accepted' || value === 'rejected' ? value : null;
		} catch (e) {
			return null;
		}
	}

	function setConsent(value) {
		try {
			localStorage.setItem(STORAGE_KEY, value);
		} catch (e) {
			/* private browsing / storage disabled — the choice still applies this visit */
		}
		banner.hidden = true;
		document.dispatchEvent(new CustomEvent('ndingi:cookie-consent', { detail: value }));
	}

	if (getConsent() !== null) {
		banner.hidden = true;
	}

	banner.querySelectorAll('[data-consent]').forEach(function (button) {
		button.addEventListener('click', function () {
			setConsent(button.getAttribute('data-consent'));
		});
	});
})();
