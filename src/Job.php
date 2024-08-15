<?php

namespace XWC\Queue;

use XWC\Queue\Interfaces\Scheduled_Job;
use XWC\Queue\Traits\Schedulable;

abstract class Job implements Scheduled_Job {
    use Schedulable;

    protected ?string $hook = null;

    protected ?string $group = null;

    protected ?string $needs = null;

    protected ?string $chains = null;

    protected ?string $processor = null;

    protected function default_hook(): string {
        $classname = \strtolower( static::class );
        $classname = \str_replace( '\\', '_', $classname );

        return $classname;
    }

    protected function default_group(): string {
        return '';
    }

    protected function default_needs(): string {
        return '';
    }

    protected function default_chains(): string {
        return '';
    }

    public function hook(): string {
        return $this->hook ?? $this->default_hook();
    }

    public function group(): string {
        return $this->group ?? $this->default_group();
    }

    public function needs(): string {
        return $this->needs ?? $this->default_needs();
    }

    public function chains(): string {
        return $this->chains ?? $this->default_chains();
    }

    abstract public function params(): array;

    public function handler(): ?object {
        if ( ! $this->processor ) {
            return null;
        }

        return new $this->processor();
    }

    abstract public function handle(): void;

    public static function dispatch( ...$args ) {
        Scheduler::job( new static( ...$args ) )->dispatch();
    }
}
