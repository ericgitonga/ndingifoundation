<?php
/**
 * Large centred logo shown at the top of Home and About only — every other
 * page keeps the logo in the header navbar alone. Same asset as header.php.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<img
	class="prominent-logo"
	src="<?php echo esc_url( get_theme_file_uri( '/assets/images/logo.png' ) ); ?>"
	alt="R S Ndingi Mwana 'a Nzeki Foundation"
	width="560"
	height="175"
/>
