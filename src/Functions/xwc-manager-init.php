<?php
/**
 * Integration utility functions
 *
 * @package eXtended WooCommerce
 * @subpackage Job Scheduler
 */

use Automattic\Jetpack\Constants;
use Laravel\SerializableClosure\SerializableClosure;

if ( ! function_exists( 'xwc_queue_init' ) && function_exists( 'add_action' ) ) :
    function xwc_queue_init(): void {
        SerializableClosure::setSecretKey( Constants::get_constant( 'NONCE_SALT' ) );
        XWC_Schedule::instance();
    }

    add_action( 'woocommerce_loaded', xwc_queue_init( ... ), 10 );

    if ( did_action( 'woocommerce_loaded' ) &&
        ! doing_action( 'woocommerce_loaded' ) &&
        ! XWC_Schedule::initialized()
    ) {
        xwc_queue_init();
    }

endif;
