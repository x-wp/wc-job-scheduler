<?php

use XWC\Queue\Scheduler;

function xwc_register_job( string $hook ) {
    Scheduler::register_job( $hook );
}
