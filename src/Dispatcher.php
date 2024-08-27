<?php

namespace XWC\Queue;

use Carbon\Carbon;
use Closure;
use XWC\Queue\Interfaces\Can_Dispatch;
use XWC\Queue\Interfaces\Can_Schedule;
use XWC_Queue_Definition;
use XWP\Helper\Traits\Singleton;

final class Dispatcher {
    use Singleton;

    private static ?XWC_Queue_Definition $queue = null;

    private Pipeline $pipeline;

    /**
     * Queued jobs.
     *
     * @var array<Can_Dispatch>
     */
    private array $async_jobs = array();

    public static function queue(): XWC_Queue_Definition {
        return self::$queue ??= \WC()->queue();
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

    public function dispatch_to_schedule( Can_Schedule $job, array $params = array() ): void {
        [ $params, $method ] = $this->parse_schedule_params( $job, $params );

        $id = self::queue()->$method( ...$params );
    }

    public function dispatch_to_shutdown( $job ): void {
        $this->async_jobs[] = $job;
    }

    public function dispatch_to_executor( Can_Dispatch $job, $handler = null ): mixed {
        $callback = $this->resolve_callback( $job, $handler );

        return $this->pipeline
            ->send( $job )
            ->through( $job->get_middleware() )
            ->then( $callback );
    }

    private function dispatch_jobs(): void {
        foreach ( $this->async_jobs as $job ) {
            $this->dispatch_to_executor( $job );
        }
    }

    private function parse_schedule_params( Can_Schedule $job, array $params ): array {
        $method = $params['method'] ?? 'schedule_single';

        $params['group']     ??= $job->get_group();
        $params['hook']      ??= $job->get_hook();
        $params['priority']  ??= 10;
        $params['timestamp'] ??= Carbon::now( 'UTC' )->getTimestamp();
        $params['unique']    ??= $job->is_unique();

        $remove = match ( $method ) {
            'schedule_single' => array( 'expression', 'method' ),
            'schedule_cron'   => array( 'method' ),
            'unschedule'      => array( 'priority', 'unique', 'timestamp', 'method', 'expression' ),
            default           => array(),
        };

        return array( \xwp_array_diff_assoc( $params, ...$remove ), $method );
    }

    private function resolve_callback( Can_Dispatch $job, $handler ): Closure {
        $handler ??= $this->get_handler( $job );

        return $handler
            ? static function ( $j ) use ( $handler ) {
                $method = \method_exists( $handler, 'handle' ) ? 'handle' : '__invoke';

                return $handler->$method( $j );
            } : static function ( $j ) {
                $method = \method_exists( $j, 'handle' ) ? 'handle' : '__invoke';

                return $j->$method();
            };
    }

    private function get_handler( Can_Dispatch $job ): object|false {
        if ( ! isset( $job->handler ) ) {
            return false;
        }

        return \class_exists( $job->handler ) ? new ( $job->handler )() : false;
    }
}
