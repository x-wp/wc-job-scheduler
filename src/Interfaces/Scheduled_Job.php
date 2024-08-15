<?php

namespace XWC\Queue\Interfaces;

use XWC\Queue\Job_Callback;

interface Scheduled_Job {
    public static function enqueue( ...$args ): Job_Callback;

    public static function schedule( ...$args ): Job_Callback;

    public function args(): array;

    public function data(): array;

    public function hook(): string;

    public function group(): string;

    public function params(): array;
}
