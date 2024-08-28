<?php

namespace XWC\Scheduler\Interfaces;

/**
 * Describes a job that can be scheduled.
 */
interface Can_Schedule extends Can_Dispatch {
    public function with_hook( ?string $hook ): static;

    public function with_group( ?string $group ): static;

    public function get_hook(): string;

    public function get_group(): string;

    public function get_args(): array;

    public function is_unique(): bool;
}
