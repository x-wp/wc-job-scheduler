<?php

namespace XWC\Scheduler\Error;

class ConstraintError extends JobExecutionError {
    protected function formatMessage( string $message, string $hook ): string {
        return \sprintf( 'Job %s failed. Condition %s not met.', $hook, $message );
    }

    public function getLogLevel(): string {
		return 'error';
	}
}
