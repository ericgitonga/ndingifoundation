(function () {
	var toggle = document.querySelector('[data-nav-toggle]');
	var nav = document.getElementById('main-navigation');
	if (!toggle || !nav) return;

	function close() {
		nav.classList.remove('is-open');
		toggle.setAttribute('aria-expanded', 'false');
		toggle.setAttribute('aria-label', 'Open menu');
	}

	function open() {
		nav.classList.add('is-open');
		toggle.setAttribute('aria-expanded', 'true');
		toggle.setAttribute('aria-label', 'Close menu');
	}

	toggle.addEventListener('click', function () {
		nav.classList.contains('is-open') ? close() : open();
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') close();
	});

	document.addEventListener('click', function (e) {
		if (!nav.classList.contains('is-open')) return;
		if (nav.contains(e.target) || toggle.contains(e.target)) return;
		close();
	});

	nav.querySelectorAll('a, button').forEach(function (el) {
		el.addEventListener('click', close);
	});
})();
