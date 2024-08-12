<?php

function xwc_register_job( string $classname ) {
    XWC_Job_Manager::instance()->register_job( $classname );
}

function xwc_add_job( string $classname ) {
    XWC_Job_Scheduler::instance()->add_job( $classname );
}

function xwc_schedule_job( string $name, array $opts = array() ) {
    $defs = array(
        'args'      => array(),
        'enabled'   => true,
        'interval'  => 15 * MINUTE_IN_SECONDS,
        'timestamp' => \wc_string_to_timestamp( '+ 15 minutes' ),
    );

    $opts = wp_parse_args( $opts, $defs );

    XWC_Job_Manager::instance()->schedule_job( $name, $opts );
}
