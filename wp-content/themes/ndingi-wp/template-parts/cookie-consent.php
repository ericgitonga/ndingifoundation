<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="cookie-banner" data-cookie-banner hidden>
	<div class="cookie-banner__box">
		<div class="cookie-banner__row">
			<p style="font-size:0.875rem;">
				We use cookies. Necessary cookies keep the site running; everything else is
				optional and off by default.
			</p>
			<button type="button" class="dialog-close" data-consent="rejected" aria-label="Close">&times;</button>
		</div>
		<div class="cookie-banner__actions">
			<button type="button" class="btn-ghost" data-consent="rejected">Reject</button>
			<button type="button" class="btn btn-primary" data-consent="accepted">Accept</button>
		</div>
	</div>
</div>
<script>
	// Unhide only when JS confirms no stored decision yet — avoids a flash
	// of the banner for a returning visitor who already chose (matches the
	// original's useSyncExternalStore-driven conditional render).
	(function () {
		try {
			if (!localStorage.getItem('ndingi-cookie-consent')) {
				document.currentScript.previousElementSibling.hidden = false;
			}
		} catch (e) {
			document.currentScript.previousElementSibling.hidden = false;
		}
	})();
</script>
