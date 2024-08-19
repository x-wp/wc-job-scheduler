<?php

namespace XWC\Queue;

use XWC\Queue\Interfaces\Can_Dispatch;
use XWC\Queue\Interfaces\Can_Schedule;
use XWP\Helper\Traits\Singleton;

final class Dispatcher {
    use Singleton;

    private static ?Repository $repo = null;

    private Pipeline $pipeline;

    /**
     * Delayed jobs.
     *
     * @var array<object>
     */
    private array $rescheduled = array();

    /**
     * Queued jobs.
     *
     * @var array<Can_Dispatch>
     */
    private array $async_jobs = array();

    public static function repo(): Repository {
        return self::$repo ??= Repository::instance();
    }

    private function __construct() {
        $this->load_pipeline();
        $this->load_own_hooks();

        \do_action( 'xwc_dispatcher_init' );
	}

    private function load_pipeline(): void {
        $this->pipeline = new Pipeline();
        // Need to implement this method.
    }

    private function load_own_hooks(): void {
        \add_action( 'shutdown', $this->dispatch_jobs( ... ), 100, 0 );
    }

    public function dispatch_to_schedule( Can_Schedule $job, ?array $params = array() ): void {
        $id = self::repo()->save( $job, $params );
    }

    public function dispatch_to_shutdown( $job ): void {
        $this->async_jobs[] = $job;
    }

    public function dispatch_to_executor( Can_Dispatch $job, array $pipes = array() ): void {
        $job->handle();
        // $this->pipeline->send( $job )->through( $pipes )->then( static fn() => true );
    }

    private function needs_scheduling( $job ): bool {
        return $job instanceof Can_Schedule;
    }

    private function dispatch_jobs(): void {
        foreach ( $this->async_jobs as $job ) {
            $this->dispatch_to_executor( $job );
        }
    }
}
