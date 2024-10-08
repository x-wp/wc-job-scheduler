<?php

namespace XWC\Scheduler\Job;

use XWC\Scheduler\Action\Dispatched_Action;

trait Dispatchable {
    protected \DateTimeInterface|\DateInterval|array|int|null $delay = null;

    protected ?array $middleware = null;

    public static function dispatch( ...$args ): Dispatched_Action {
        return new Dispatched_Action( new static( ...$args ) );
    }

    public static function dispatch_async( ...$args ) {
        return ( new Dispatched_Action( new static( ...$args ) ) )->async();
    }

    /**
     * Set the desired delay in seconds for the job.
     *
     * @param  \DateTimeInterface|\DateInterval|array|int|null  $delay The delay in seconds
     * @return $this
     */
    public function delay( \DateTimeInterface|\DateInterval|array|int|null $delay ): static {
        $this->delay = $delay;

        return $this;
    }

    /**
     * Remove the delay from the job.
     *
     * @return $this
     */
    public function no_delay(): static {
        $this->delay;

        return $this;
    }

    public function with_middleware( array $middleware ): static {
        $this->middleware = $middleware;

        return $this;
    }

    public function get_middleware(): array {
        return \array_merge(
            \method_exists( $this, 'middleware' ) ? $this->middleware() : array(),
            $this->middleware ?? array(),
        );
    }
}
