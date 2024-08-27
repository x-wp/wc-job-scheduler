<?php

use Automattic\Jetpack\Constants;
use XWC\Queue\Dispatcher;
use XWC\Queue\Interfaces\Can_Dispatch;
use XWC\Queue\Interfaces\Can_Schedule;
use XWC\Queue\Scheduler\Callback_Action;
use XWP\Helper\Traits\Singleton;

/**
 * Job scheduling class.
 *
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

    private static ?WC_Logger_Interface $logger = null;

    /**
     * Registered
     *
     * @var array<Callback_Action>
     */
    private array $actions = array();

    public function __call( string $name, array $args = array() ) {
        return self::__callStatic( $name, $args );
    }

    public static function __callStatic( string $name, array $args = array() ) {
        if ( ! method_exists( self::class, $name ) ) {
            throw new \BadMethodCallException( esc_html( "Method {$name} does not exist." ) );
        }

        return self::instance()->$name( ...$args );
    }

    private static function dispatcher(): Dispatcher {
        return self::$dispatcher ??= Dispatcher::instance();
    }

    /**
     * Checks if the Integration has been initialized
     *
     * @return bool
     */
    public static function initialized(): bool {
        return null !== self::$instance;
    }

    /**
     * Get the logger instance.
     *
     * @return WC_Logger_Interface
     */
    public static function logger(): WC_Logger_Interface {
        return self::$logger ??= wc_get_logger();
    }

    /**
     * Log a message.
     *
     * @param  string $message Message to log.
     * @param  string $level   Log level.
     */
    public static function log( string $message, string $level = 'info' ) {
        if ( ! Constants::is_true( 'XWC_JOB_DEBUG' ) ) {
            return;
        }

        self::logger()->log( $level, $message, array( 'source' => 'xwc-job-scheduler' ) );
    }

    private function __construct() {
        add_filter( 'action_scheduler_store_class', $this->change_as_store_class( ... ), 10000, 0 );
        add_filter( 'woocommerce_queue_class', $this->change_queue_class( ... ), 99, 0 );
        add_action( 'shutdown', $this->save_actions( ... ), 50, 0 );
    }

    /**
     * Schedule a job.
     *
     * @param  T|class-string<T> $job Job to schedule.
     * @param  string|null         $hook
     * @param  string|null         $group
     * @return Callback_Action
     *
     * @template T of Can_Dispatch|Can_Schedule
     */
    private function job( Can_Dispatch|string $job, ?string $hook = null, ?string $group = null ): Callback_Action {
        $cb = function ( array $params = array() ) use ( $job ) {
            if ( is_string( $job ) ) {
                $job = new $job();
            }

            $job instanceof Can_Schedule
                ? $this->schedule_job( $job, $params )
                : $this->dispatch_job( $job );
        };

        return $this->call( $cb, $hook, $group );
    }

    private function call( callable|Closure $cb, ?string $hook, ?string $group ): Callback_Action {
        $action = ( new Callback_Action( $cb ) )
            ->hook( $hook )
            ->group( $group );

        $this->actions[] = $action;

        return $action;
    }

    private function schedule_job( Can_Schedule $job, array $params = array() ) {
        $params['args']['job']    = $job::class;
        $params['args']['data']   = $job->get_args();
        $params['args']['meta'] ??= array();

        self::dispatcher()->dispatch_to_schedule( $job, $params );
    }

    private function dispatch_job( Can_Dispatch $job ) {
        self::dispatcher()->dispatch_to_shutdown( $job );
    }

    private function dispatch_async( $job ) {
        // NOOP.
    }

    /**
     * Register the integration
     *
     * @return class-string<XWC_Queue_Definition>
     */
    private function change_queue_class(): string {
        return XWC_Queue::class;
    }

    /**
     * Change the Action Scheduler store class.
     *
     * @return class-string<XWC_Action_Store>
     */
    private function change_as_store_class(): string {
        return XWC_Action_Store::class;
    }

    private function save_actions() {
        foreach ( $this->actions as &$action ) {
            unset( $action );
        }
    }
}
