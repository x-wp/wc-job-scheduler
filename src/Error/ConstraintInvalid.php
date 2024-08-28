<?php

namespace XWC\Scheduler\Error;

class ConstraintInvalid extends JobExecutionError {
    protected function formatMessage( string $message, string $hook ): string {
        return \sprintf( 'Job %s stopped. Condition %s not met.', $hook, $message );
    }

    public function getLogLevel(): string {
		return 'warning';
	}
}
