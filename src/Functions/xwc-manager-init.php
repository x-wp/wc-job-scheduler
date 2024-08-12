<?php
/**
 * Integration utility functions
 *
 * @package WC KursEvra
 * @subpackage Utils
 */

use XWC\Queue\Initializer;

if ( ! function_exists( 'xwc_queue_init' ) && function_exists( 'add_action' ) ) :


    function xwc_queue_init(): void {
        Initializer::run();
    }

    add_action( 'woocommerce_loaded', xwc_queue_init( ... ), 10 );

    if ( did_action( 'woocommerce_loaded' ) &&
        ! doing_action( 'woocommerce_loaded' ) &&
        ! Initializer::initialized()
    ) {
        xwc_queue_init();
    }

endif;
