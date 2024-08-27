<?php

namespace XWC\Queue\Error;

class DependencyError extends JobExecutionError {
    protected function formatMessage( string $message, string $hook ): string {
        return \sprintf( 'Job %s is blocked by %s. Rescheduling in 5 minutes.', $hook, $message );
    }

    public function getLogLevel(): string {
		return 'notice';
	}
}
