<?php

namespace XWC\Scheduler\Middleware;

use XWC\Scheduler\Action\Pending_Action;
use XWC\Scheduler\Dispatcher;
use XWC\Scheduler\Error\DependencyError;

class Check_Blockers {
    public function handle( Pending_Action $action, \Closure $next ) {
        if ( Dispatcher::queue()->get_blocker( $action ) ) {
            throw new DependencyError(
                \esc_html( $action->get_hook() ),
                \esc_html( \implode( ', ', $action->get_blocker() ) ),
            );
        }

        return $next( $action );
    }
}
