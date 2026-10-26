/**
 * Native <dialog> open/close wiring — any element with data-open-modal="id"
 * opens the <dialog id="id">, any [data-close-modal] inside a dialog closes
 * its closest dialog, and clicking the dialog's own backdrop area closes it.
 * Mirrors ndingi's ModalController/TeamGrid/ProgrammeGrid/DetailGrid dialog
 * behaviour without a JS framework.
 */
(function () {
	document.querySelectorAll('[data-open-modal]').forEach(function (trigger) {
		trigger.addEventListener('click', function () {
			var id = trigger.getAttribute('data-open-modal');
			var dialog = document.getElementById(id);
			if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
		});
	});

	document.querySelectorAll('dialog.ndingi-dialog').forEach(function (dialog) {
		dialog.addEventListener('click', function (e) {
			if (e.target === dialog) dialog.close();
		});
		dialog.querySelectorAll('[data-close-modal]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				dialog.close();
			});
		});
	});

	// Per-card modals (team members, programmes, detail items) share one
	// dialog per grid; each trigger button carries the content to show via
	// data attributes read by this handler instead of rendering N dialogs.
	document.querySelectorAll('[data-card-dialog]').forEach(function (dialog) {
		var grid = document.getElementById(dialog.getAttribute('data-card-dialog'));
		if (!grid) return;
		grid.querySelectorAll('[data-card-trigger]').forEach(function (card) {
			card.addEventListener('click', function () {
				var template = document.getElementById(card.getAttribute('data-card-trigger'));
				if (!template) return;
				dialog.querySelector('[data-dialog-content]').innerHTML = template.innerHTML;
				dialog.showModal();
			});
		});
	});
})();
