<?php

namespace XWC\Queue\Traits;

trait Queueable {
    abstract public static function get_action(): string;

    abstract public function get_params(): array;

    abstract public function handle();
}
