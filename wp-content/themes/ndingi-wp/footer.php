<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
</main>

<footer class="site-footer">
	<div class="site-footer__support">
		<p>Support our work</p>
		<button type="button" class="btn btn-primary" data-open-modal="ndingi-donate-modal">Donate</button>
	</div>
	<div class="site-footer__bottom">
		<div class="site-footer__bottom-inner">
			<p class="site-footer__brand">Ndingi Foundation</p>
			<a href="mailto:<?php echo esc_attr( ndingi_contact_email() ); ?>">
				<?php echo esc_html( ndingi_contact_email() ); ?>
			</a>
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Ndingi Foundation. All rights reserved.</p>
		</div>
	</div>
</footer>

<?php get_template_part( 'template-parts/cookie-consent' ); ?>

<?php wp_footer(); ?>
</body>
</html>
