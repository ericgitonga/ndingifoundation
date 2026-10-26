<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$donate_page_id = ( 'page-donate.php' === get_page_template_slug() ) ? get_the_ID() : 0;
if ( ! $donate_page_id ) {
	$donate_page    = get_page_by_path( 'donate' );
	$donate_page_id = $donate_page ? $donate_page->ID : 0;
}

$lede        = $donate_page_id ? get_post_meta( $donate_page_id, 'ndingi_donate_lede', true ) : '';
$paybill_num = $donate_page_id ? get_post_meta( $donate_page_id, 'ndingi_paybill_number', true ) : '';
$account_num = $donate_page_id ? get_post_meta( $donate_page_id, 'ndingi_paybill_account', true ) : '';

$bank_name   = $donate_page_id ? get_post_meta( $donate_page_id, 'ndingi_bank_name', true ) : '';
$bank_acc_nm = $donate_page_id ? get_post_meta( $donate_page_id, 'ndingi_bank_account_name', true ) : '';
$bank_acc_no = $donate_page_id ? get_post_meta( $donate_page_id, 'ndingi_bank_account_number', true ) : '';
$bank_branch = $donate_page_id ? get_post_meta( $donate_page_id, 'ndingi_bank_branch', true ) : '';
$bank_swift  = $donate_page_id ? get_post_meta( $donate_page_id, 'ndingi_bank_swift', true ) : '';

$lede        = $lede ?: "Your support helps the Archbishop Ndingi Mwana\u{2019}a Nzeki Foundation continue its work in education, sustainable livelihoods, and water & ecosystem management across Kenya.";
$paybill_num = $paybill_num ?: '127767';
$account_num = $account_num ?: 'Donation';
?>
<p class="lede">
	<?php echo esc_html( $lede ); ?>
</p>

<div class="donate-box">
	<p class="donate-box__label">Give via M-Pesa Paybill</p>
	<dl>
		<div>
			<dt>Paybill Number</dt>
			<dd><?php echo esc_html( $paybill_num ); ?></dd>
		</div>
		<div>
			<dt>Account Number</dt>
			<dd><?php echo esc_html( $account_num ); ?></dd>
		</div>
	</dl>
</div>

<?php if ( $bank_name && $bank_acc_no ) : ?>
<div class="donate-box" style="margin-top:1.5rem;">
	<p class="donate-box__label">Give via Bank Transfer</p>
	<dl>
		<div>
			<dt>Bank Name</dt>
			<dd><?php echo esc_html( $bank_name ); ?></dd>
		</div>
		<?php if ( $bank_acc_nm ) : ?>
		<div>
			<dt>Account Name</dt>
			<dd><?php echo esc_html( $bank_acc_nm ); ?></dd>
		</div>
		<?php endif; ?>
		<div>
			<dt>Account Number</dt>
			<dd><?php echo esc_html( $bank_acc_no ); ?></dd>
		</div>
		<?php if ( $bank_branch ) : ?>
		<div>
			<dt>Branch</dt>
			<dd><?php echo esc_html( $bank_branch ); ?></dd>
		</div>
		<?php endif; ?>
		<?php if ( $bank_swift ) : ?>
		<div>
			<dt>SWIFT / BIC</dt>
			<dd><?php echo esc_html( $bank_swift ); ?></dd>
		</div>
		<?php endif; ?>
	</dl>
</div>
<?php endif; ?>
