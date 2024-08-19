<?php

use XWC\Queue\Callback_Action;
use XWC\Queue\Dispatcher;
use XWC\Queue\Interfaces\Can_Dispatch;
use XWC\Queue\Interfaces\Can_Schedule;
use XWP\Helper\Traits\Singleton;

/**
 * @method static Callback_Action job( Can_Dispatch $job, ?string $hook = null, ?string $group = null ) Schedule a job.
 */
final class XWC_Schedule {
    use Singleton;

    public const SUNDAY = 0;

    public const MONDAY = 1;

    public const TUESDAY = 2;

    public const WEDNESDAY = 3;

    public const THURSDAY = 4;

    public const FRIDAY = 5;

    public const SATURDAY = 6;

    private static ?Dispatcher $dispatcher = null;

    /**
     * Registered
     *
     * @var array<Callback_Action>
     */
    private array $actions = array();

    private static function dispatcher(): Dispatcher {
        return self::$dispatcher ??= Dispatcher::instance();
    }

    public function __call( string $name, array $args = array() ) {
        return self::__callStatic( $name, $args );
    }

    public static function __callStatic( string $name, array $args = array() ) {
        if ( ! method_exists( self::class, $name ) ) {
            throw new \BadMethodCallException( esc_html( "Method {$name} does not exist." ) );
        }

        return self::instance()->$name( ...$args );
    }

    private function __construct() {
        add_action( 'shutdown', $this->save_actions( ... ), 50, 0 );
    }

    private function job( Can_Dispatch $job, ?string $hook = null, ?string $group = null ): Callback_Action {
        $cb = function ( array $params = array() ) use ( $job, $hook, $group ) {
            $job instanceof Can_Schedule
                ? $this->schedule_job( $job->with_hook( $hook )->with_group( $group ), $params )
                : $this->dispatch_now( $job, $params );
        };

        return $this->call( $cb );
    }

    private function call( callable|Closure $cb ): Callback_Action {
        $action = new Callback_Action( $cb );

        $this->actions[] = $action;

        return $action;
    }

    private function schedule_job( Can_Schedule $job, array $params = array() ) {
        self::dispatcher()->dispatch_to_schedule( $job, $params );
    }

    private function dispatch_now( Can_Dispatch $job ) {
        self::dispatcher()->dispatch_to_executor( $job );
    }

    private function dispatch_async( $job ) {
        self::dispatcher()->dispatch_to_shutdown( $job );
    }

    private function save_actions() {
        foreach ( $this->actions as $action ) {
            $action->save_action();
        }
    }
}
