<?php

namespace XWC\Scheduler\Action;

use XWC\Scheduler\Dispatcher;
use XWC\Scheduler\Interfaces\Can_Dispatch;

class Dispatched_Action {
    /**
     * Indicates if the job should be dispatched immediately after sending the response.
     *
     * @var bool
     */
    protected $is_async = false;

    public function __construct(
        protected readonly Can_Dispatch $job,
	) {
    }

    /**
     * Indicate that the job should be dispatched after the response is sent to the browser.
     *
     * @return static
     */
    public function async(): static {
        $this->is_async = true;

        return $this;
    }

    public function with_middleware( array $middleware ): static {
        $this->job->with_middleware( $middleware );

        return $this;
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct() {
        $this->is_async
            ? Dispatcher::instance()->dispatch_to_shutdown( $this->job )
            : Dispatcher::instance()->dispatch_to_executor( $this->job );
    }
}
