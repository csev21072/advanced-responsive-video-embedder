<?php
namespace nextgenthemes\admin;

function activation_notices() {

	$products = get_products();

	foreach ( $products as $key => $value ) {

		if ( $value['active'] && ! $value['valid_key'] ) {

			$msg = sprintf(
				// Translators: First %1$s is product name.
				__( 'Hi there, thanks for your purchase. One last step, please activate your %1$s <a href="%2$s">here now</a>.', 'advanced-responsive-video-embedder' ),
				$value['name'],
				get_admin_url() . 'admin.php?page=nextgenthemes-licenses'
			);
			new \Nextgenthemes_Admin_Notice_Factory( $key . '-activation-notice', "<p>$msg</p>", HOUR_IN_SECONDS );
		}
	}
}

function php_below_56_notice() {

	$msg = sprintf(
		__( 'You use a PHP version below 5.6 ', 'advanced-responsive-video-embedder' ),
		PHP_VERSION
	);

	// new \Nextgenthemes_Admin_Notice_Factory( 'nextgenthemes-php-below-56-w', "<p>$msg</p>", false );
}
