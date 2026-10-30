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
$ndingi_form_uid       = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'contact-' ) : 'contact-' . wp_rand( 1000, 9999 );
$ndingi_ts             = (string) time();
$ndingi_seed           = wp_generate_password( 16, false, false );
$ndingi_sig            = function_exists( 'ndingi_contact_sign_token' )
	? ndingi_contact_sign_token( $ndingi_ts, $ndingi_seed )
	: hash_hmac( 'sha256', $ndingi_ts . '|' . $ndingi_seed, wp_salt( 'nonce' ) );
?>

<?php if ( 'success' === $ndingi_contact_status ) : ?>
	<p class="form-message">Thanks for reaching out &mdash; we&rsquo;ll get back to you soon.</p>
<?php else : ?>
	<form class="contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-contact-seed="<?php echo esc_attr( $ndingi_seed ); ?>">
		<input type="hidden" name="action" value="ndingi_contact" />
		<input type="hidden" name="ndingi_ts" value="<?php echo esc_attr( $ndingi_ts ); ?>" />
		<input type="hidden" name="ndingi_seed" value="<?php echo esc_attr( $ndingi_seed ); ?>" />
		<input type="hidden" name="ndingi_sig" value="<?php echo esc_attr( $ndingi_sig ); ?>" />
		<input type="hidden" name="ndingi_js_token" value="" />
		<?php wp_nonce_field( 'ndingi_contact_form', 'ndingi_contact_nonce' ); ?>

		<div class="field">
			<label for="<?php echo esc_attr( $ndingi_form_uid . '-name' ); ?>">Name</label>
			<input id="<?php echo esc_attr( $ndingi_form_uid . '-name' ); ?>" name="name" type="text" maxlength="100" required />
		</div>
		<div class="field">
			<label for="<?php echo esc_attr( $ndingi_form_uid . '-email' ); ?>">Email</label>
			<input id="<?php echo esc_attr( $ndingi_form_uid . '-email' ); ?>" name="email" type="email" maxlength="150" required />
		</div>
		<div class="field">
			<label for="<?php echo esc_attr( $ndingi_form_uid . '-subject' ); ?>">Subject</label>
			<input id="<?php echo esc_attr( $ndingi_form_uid . '-subject' ); ?>" name="subject" type="text" maxlength="160" />
		</div>
		<div class="field">
			<label for="<?php echo esc_attr( $ndingi_form_uid . '-message' ); ?>">Message</label>
			<textarea id="<?php echo esc_attr( $ndingi_form_uid . '-message' ); ?>" name="message" rows="5" maxlength="5000" required></textarea>
		</div>

		<div class="contact-form__aux" aria-hidden="true">
			<label for="<?php echo esc_attr( $ndingi_form_uid . '-website' ); ?>">Website</label>
			<input id="<?php echo esc_attr( $ndingi_form_uid . '-website' ); ?>" type="text" name="website" value="" tabindex="-1" autocomplete="nope" />
			<input class="form-honeypot" type="checkbox" name="botcheck" value="1" tabindex="-1" autocomplete="off" />
		</div>

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
