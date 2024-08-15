<?php

namespace XWC\Queue\Traits;

use XWC\Queue\Job_Callback;
use XWC\Queue\Scheduler;

trait Schedulable {
    protected bool $unique = false;

    public function unique(): bool {
        return $this->unique;
    }

    public static function enqueue( ...$args ): Job_Callback {
        return static::schedule( ...$args )->now()->save();
    }

    public static function schedule( ...$args ): Job_Callback {
        $job = new static( ...$args );
        return Scheduler::job( $job )->schedule();
    }

    public function args(): array {
        //phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys
        return array(
            'job'  => static::class,
            'args' => $this->params(),
        );
        //phpcs:enable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys
    }

    public function data(): array {
        return array(
            'args'   => $this->args(),
            'group'  => $this->group(),
            'hook'   => $this->hook(),
            'unique' => $this->unique(),
        );
    }
}
