<?php

namespace XWC\Queue\Middleware;

use XWC\Queue\Dispatcher;
use XWC\Queue\Error\DependencyError;
use XWC\Queue\Scheduler\Pending_Action;

class Check_Blockers {
    public function handle( Pending_Action $action, \Closure $next ) {
        if ( Dispatcher::queue()->get_blocker( $action ) ) {
            throw new DependencyError( \esc_html( $action->get_hook() ), \esc_html( $action->get_blocker() ) );
        }

        return $next( $action );
    }
}
