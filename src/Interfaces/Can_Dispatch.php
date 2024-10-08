<?php

namespace XWC\Scheduler\Interfaces;

use XWC\Scheduler\Action\Dispatched_Action;

/**
 * @property-read class-string|null $handler The class name of the handler.
 */
interface Can_Dispatch {
    public static function dispatch( ...$args ): Dispatched_Action;

    public function handle();

    public function delay( \DateTimeInterface|\DateInterval|array|int|null $delay ): static;

    public function get_middleware(): array;

    public function with_middleware( array $middleware ): static;
}
