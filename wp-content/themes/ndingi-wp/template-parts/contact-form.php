<?php
/**
 * Native contact form — posts to admin-post.php, handled by the
 * ndingi-wp-content plugin's contact-form-handler.php via wp_mail().
 * Replaces ndingi's client-side-only Web3Forms submission
 * (src/components/ContactForm.tsx) with a form that also works with JS off.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ndingi_contact_status = isset( $_GET['ndingi_contact'] ) ? sanitize_key( $_GET['ndingi_contact'] ) : '';
?>

<?php if ( 'success' === $ndingi_contact_status ) : ?>
	<p class="form-message">Thanks for reaching out &mdash; we&rsquo;ll get back to you soon.</p>
<?php else : ?>
	<form class="contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ndingi_contact" />
		<?php wp_nonce_field( 'ndingi_contact_form', 'ndingi_contact_nonce' ); ?>

		<div class="field">
			<label for="contact-name">Name</label>
			<input id="contact-name" name="name" type="text" required />
		</div>
		<div class="field">
			<label for="contact-email">Email</label>
			<input id="contact-email" name="email" type="email" required />
		</div>
		<div class="field">
			<label for="contact-subject">Subject</label>
			<input id="contact-subject" name="subject" type="text" />
		</div>
		<div class="field">
			<label for="contact-message">Message</label>
			<textarea id="contact-message" name="message" rows="5" required></textarea>
		</div>

		<!-- Honeypot: hidden from real visitors, only a bot fills this in. -->
		<input class="form-honeypot" type="checkbox" name="botcheck" tabindex="-1" autocomplete="off" aria-hidden="true" />

		<?php if ( 'error' === $ndingi_contact_status ) : ?>
			<p class="form-message form-message--error">
				Something went wrong sending your message &mdash; please email
				<a href="mailto:<?php echo esc_attr( ndingi_contact_email() ); ?>"><?php echo esc_html( ndingi_contact_email() ); ?></a>
				directly instead.
			</p>
		<?php endif; ?>

		<button type="submit" class="btn btn-primary" style="align-self:flex-start;">Send message</button>
	</form>
<?php endif; ?>
