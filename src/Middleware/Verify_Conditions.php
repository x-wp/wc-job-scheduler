<?php

namespace XWC\Queue\Middleware;

use XWC\Queue\Error\ConstraintError;
use XWC\Queue\Error\ConstraintInvalid;
use XWC\Queue\Scheduler\Pending_Action;

class Verify_Conditions {
    public function handle( Pending_Action $action, \Closure $next ) {
        if ( ! $this->verify( $action->get_filters(), false ) ) {
            $this->fail_condition( $action, 'filter' );
        }

        if ( ! $this->verify( $action->get_rejects(), true ) ) {
            $this->fail_condition( $action, 'reject' );
        }

        return $next( $action );
    }

    protected function verify( array $callables, bool $test ): bool {
        foreach ( $callables as $cb ) {
            if ( $cb() === $test ) {
                return false;
            }
        }

        return true;
    }

    protected function fail_condition( Pending_Action $action, string $why ): void {
        $exc_class = $action->is_strict() ? ConstraintError::class : ConstraintInvalid::class;
        throw new $exc_class( \esc_html( $action->get_hook() ), \esc_html( $why ) );
    }
}
