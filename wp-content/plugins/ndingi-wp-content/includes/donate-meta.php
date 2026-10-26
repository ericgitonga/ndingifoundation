<?php
/**
 * "Donation Settings" meta box for the Donate page — allows editing the
 * lede/appeal copy and M-Pesa Paybill / account details from wp-admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndingi_donate_page_metabox() {
	global $post;
	if ( ! $post || 'page' !== $post->post_type ) {
		return;
	}
	$template = get_post_meta( $post->ID, '_wp_page_template', true );
	if ( 'page-donate.php' !== $template && 'donate' !== $post->post_name ) {
		return;
	}

	add_meta_box(
		'ndingi_donate_settings',
		'Donation Settings',
		'ndingi_render_donate_metabox',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ndingi_donate_page_metabox' );

function ndingi_render_donate_metabox( $post ) {
	wp_nonce_field( 'ndingi_save_donate_meta', 'ndingi_donate_nonce' );

	$lede        = get_post_meta( $post->ID, 'ndingi_donate_lede', true );
	$paybill_num = get_post_meta( $post->ID, 'ndingi_paybill_number', true );
	$account_num = get_post_meta( $post->ID, 'ndingi_paybill_account', true );
	$bank_name   = get_post_meta( $post->ID, 'ndingi_bank_name', true );
	$bank_acc_nm = get_post_meta( $post->ID, 'ndingi_bank_account_name', true );
	$bank_acc_no = get_post_meta( $post->ID, 'ndingi_bank_account_number', true );
	$bank_branch = get_post_meta( $post->ID, 'ndingi_bank_branch', true );
	$bank_swift  = get_post_meta( $post->ID, 'ndingi_bank_swift', true );
	?>
	<p>
		<label for="ndingi_donate_lede"><strong>Donation Lede / Appeal Text</strong></label><br />
		<textarea id="ndingi_donate_lede" name="ndingi_donate_lede" rows="3" style="width:100%;"><?php echo esc_textarea( $lede ); ?></textarea>
		<span class="description">Leave blank to use default Foundation appeal text.</span>
	</p>
	<h3 style="margin-top:1.5rem;margin-bottom:0.5rem;">M-Pesa Details</h3>
	<p>
		<label for="ndingi_paybill_number"><strong>M-Pesa Paybill Number</strong></label><br />
		<input type="text" id="ndingi_paybill_number" name="ndingi_paybill_number" value="<?php echo esc_attr( $paybill_num ); ?>" placeholder="127767" style="width:100%;" />
	</p>
	<p>
		<label for="ndingi_paybill_account"><strong>M-Pesa Account Name/Number</strong></label><br />
		<input type="text" id="ndingi_paybill_account" name="ndingi_paybill_account" value="<?php echo esc_attr( $account_num ); ?>" placeholder="Donation" style="width:100%;" />
	</p>
	<h3 style="margin-top:1.5rem;margin-bottom:0.5rem;">Bank Transfer Details (Optional)</h3>
	<p>
		<label for="ndingi_bank_name"><strong>Bank Name</strong></label><br />
		<input type="text" id="ndingi_bank_name" name="ndingi_bank_name" value="<?php echo esc_attr( $bank_name ); ?>" placeholder="e.g. Caritas Microfinance Bank" style="width:100%;" />
	</p>
	<p>
		<label for="ndingi_bank_account_name"><strong>Account Name</strong></label><br />
		<input type="text" id="ndingi_bank_account_name" name="ndingi_bank_account_name" value="<?php echo esc_attr( $bank_acc_nm ); ?>" placeholder="e.g. Archbishop Ndingi Mwana'a Nzeki Foundation" style="width:100%;" />
	</p>
	<p>
		<label for="ndingi_bank_account_number"><strong>Account Number</strong></label><br />
		<input type="text" id="ndingi_bank_account_number" name="ndingi_bank_account_number" value="<?php echo esc_attr( $bank_acc_no ); ?>" placeholder="e.g. 1000123456" style="width:100%;" />
	</p>
	<p>
		<label for="ndingi_bank_branch"><strong>Branch</strong></label><br />
		<input type="text" id="ndingi_bank_branch" name="ndingi_bank_branch" value="<?php echo esc_attr( $bank_branch ); ?>" placeholder="e.g. Cardinal Otunga Plaza Branch, Nairobi" style="width:100%;" />
	</p>
	<p>
		<label for="ndingi_bank_swift"><strong>SWIFT / BIC Code</strong></label><br />
		<input type="text" id="ndingi_bank_swift" name="ndingi_bank_swift" value="<?php echo esc_attr( $bank_swift ); ?>" placeholder="Optional" style="width:100%;" />
	</p>
	<?php
}

function ndingi_save_donate_meta( $post_id ) {
	if ( ! isset( $_POST['ndingi_donate_nonce'] ) || ! wp_verify_nonce( $_POST['ndingi_donate_nonce'], 'ndingi_save_donate_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['ndingi_donate_lede'] ) ) {
		update_post_meta( $post_id, 'ndingi_donate_lede', sanitize_textarea_field( wp_unslash( $_POST['ndingi_donate_lede'] ) ) );
	}
	if ( isset( $_POST['ndingi_paybill_number'] ) ) {
		update_post_meta( $post_id, 'ndingi_paybill_number', sanitize_text_field( wp_unslash( $_POST['ndingi_paybill_number'] ) ) );
	}
	if ( isset( $_POST['ndingi_paybill_account'] ) ) {
		update_post_meta( $post_id, 'ndingi_paybill_account', sanitize_text_field( wp_unslash( $_POST['ndingi_paybill_account'] ) ) );
	}
	if ( isset( $_POST['ndingi_bank_name'] ) ) {
		update_post_meta( $post_id, 'ndingi_bank_name', sanitize_text_field( wp_unslash( $_POST['ndingi_bank_name'] ) ) );
	}
	if ( isset( $_POST['ndingi_bank_account_name'] ) ) {
		update_post_meta( $post_id, 'ndingi_bank_account_name', sanitize_text_field( wp_unslash( $_POST['ndingi_bank_account_name'] ) ) );
	}
	if ( isset( $_POST['ndingi_bank_account_number'] ) ) {
		update_post_meta( $post_id, 'ndingi_bank_account_number', sanitize_text_field( wp_unslash( $_POST['ndingi_bank_account_number'] ) ) );
	}
	if ( isset( $_POST['ndingi_bank_branch'] ) ) {
		update_post_meta( $post_id, 'ndingi_bank_branch', sanitize_text_field( wp_unslash( $_POST['ndingi_bank_branch'] ) ) );
	}
	if ( isset( $_POST['ndingi_bank_swift'] ) ) {
		update_post_meta( $post_id, 'ndingi_bank_swift', sanitize_text_field( wp_unslash( $_POST['ndingi_bank_swift'] ) ) );
	}
}
add_action( 'save_post_page', 'ndingi_save_donate_meta' );
