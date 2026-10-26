<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<dialog id="ndingi-contact-modal" class="ndingi-dialog">
	<button type="button" class="dialog-close" data-close-modal aria-label="Close">&times;</button>
	<h2 class="section-title">Contact</h2>
	<div style="margin-top:1rem;">
		<?php get_template_part( 'template-parts/contact-info' ); ?>
	</div>
	<?php get_template_part( 'template-parts/contact-form' ); ?>
</dialog>
