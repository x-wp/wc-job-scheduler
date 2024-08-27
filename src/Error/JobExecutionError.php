<?php

namespace XWC\Queue\Error;

abstract class JobExecutionError extends \Exception {
    public function __construct( protected readonly string $hook, string $message, $code = 0, \Throwable $previous = null ) {
        parent::__construct( $this->formatMessage( $message, $hook ), $code, $previous );
    }

    public function getHook(): string {
        return $this->hook;
    }

    abstract public function getLogLevel(): string;

    abstract protected function formatMessage( string $message, string $hook ): string;
}
