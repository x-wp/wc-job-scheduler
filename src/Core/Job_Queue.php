<?php

use ActionScheduler_Action as AS_Action;
use XWC\Queue\Dispatcher;
use XWC\Queue\Job;
use XWC\Queue\Jobs\Scheduled_Action as XWC_Action;

class XWC_Queue extends WC_Action_Queue implements XWC_Queue_Definition {
    protected static bool $init;

    protected static bool $fired = false;

    protected ?int $action_id = null;

    /**
     * Registered hooks.
     *
     * @var array<string, array<int, bool>>
     */
    protected static array $hooks;

    protected static bool $changed = false;

    public function __construct() {
        static::$hooks ??= $this->load_custom_hooks();
        static::$init  ??= $this->init();
    }

    protected function init(): bool {
        add_action( 'action_scheduler_begin_execute', $this->get_action_id( ... ), 10, 1 );
        add_action( 'action_scheduler_after_execute', $this->clear_action_id( ... ), 10, 1 );
        add_action( 'action_scheduler_failed_execution', $this->clear_action_id( ... ), 10, 1 );

        add_filter( 'action_scheduler_stored_action_class', $this->change_action_class( ... ), 100, 3 );
        add_filter( 'action_scheduler_stored_action_instance', $this->initialize_action( ... ), 100, 1 );
        add_action( 'action_scheduler_before_execute', $this->fire_custom_hooks( ... ), 0, 0 );
        add_action( 'action_scheduler_before_process_queue', $this->fire_custom_hooks( ... ), 0, 0 );
        add_action( 'action_scheduler_after_process_queue', $this->save_custom_hooks( ... ), 100, 0 );

        add_action( 'shutdown', $this->save_custom_hooks( ... ), 100, 0 );

        add_action( 'action_scheduler_stored_action', $this->add_custom_hook( ... ), 10, 1 );
        add_action( 'action_scheduler_completed_action', $this->remove_custom_hook( ... ), 10, 1 );
        add_action( 'action_scheduler_canceled_action', $this->remove_custom_hook( ... ), 10, 1 );
        add_action( 'action_scheduler_deleted_action', $this->remove_custom_hook( ... ), 10, 1 );

        return true;
    }

    protected function get_action_id( int $action_id ) {
        $this->action_id = $action_id;
    }

    protected function clear_action_id() {
        $this->action_id = 0;
    }

    protected function change_action_class( string $classname, string $status, string $hook ): string {
        if ( $this->has_custom_hook( $hook, $this->action_id ?? 0 ) ) {
            $classname = XWC_Action::class;
        }

        return $classname;
    }

    protected function initialize_action( AS_Action|XWC_Action $action ) {
        if ( ! ( $action instanceof XWC_Action ) ) {
            return $action;
        }

        return $action->with_id( $this->action_id ?? 0 );
    }

    protected function load_custom_hooks(): array {
        $hooks = get_option( 'xwc_queue_hooks', false );

        if ( false === $hooks ) {
            static::$changed = true;

            $hooks = array();
        }

        return $hooks;
    }

	public function add( $hook, $args = array(), $group = '', bool $unique = false, int $priority = 10 ) {
        return $this->schedule_single( time(), $hook, $args, $group, $unique, $priority );
    }

    public function schedule_single( $timestamp, $hook, $args = array(), $group = '', bool $unique = false, int $priority = 10 ) {
		return as_schedule_single_action( $timestamp, $hook, $args, $group, $unique, $priority );
	}

    public function schedule_recurring( $timestamp, $interval = null, $hook = '', $args = array(), $group = '', bool $unique = false, int $priority = 10, $interval_in_seconds = null ) {
        $interval = $interval ?? $interval_in_seconds ?? -1;

        if ( '' === $hook || -1 === $interval ) {
            return 0;
        }

        return as_schedule_recurring_action( $timestamp, $interval, $hook, $args, $group, $unique, $priority );
    }

	public function schedule_cron( $timestamp, $expression = null, $hook = '', $args = array(), $group = '', bool $unique = false, int $priority = 10, ?string $cron_schedule = null ) {
        $expression = $expression ?? $cron_schedule ?? '-1';

		if ( '' === $hook || '-1' === $expression ) {
			return 0;
		}

        return as_schedule_cron_action( $timestamp, $expression, $hook, $args, $group, $unique, $priority );
    }

    /**
	 * Get the DateTime for the next scheduled time an action should run.
	 *
	 * @param  AS_Action $action Action.
	 * @return DateTime|null
	 */
	public function get_next_action_time( ?AS_Action $action ): ?DateTime {
        return $action?->get_schedule()?->get_next( new DateTime() ) ?? null;
    }

    public function get_existing( Job $job ): bool {
        $search_args = array(
            'args'                  => $job->params(),
            'claimed'               => false,
            'group'                 => $job->group(),
            'hook'                  => $job->hook(),
            'partial_args_matching' => 'like',
            'per_page'              => 1,
            'status'                => 'pending',
        );

        $actions = $this->search( $search_args );
        $action  = \current( $actions );

        return ! $action ? false : $action->get_hook() === $job->hook();
    }

    public function get_blocker( Job $job ) {
        $search_args = array(
            'group'    => $job->group(),
            'order'    => 'DESC',
            'orderby'  => 'date',
            'per_page' => 1,
            'search'   => $job->needs(), // search is used instead of hook to find queued batch creation.
            'status'   => 'pending',
        );

        $blocking = $job->needs() ? $this->search( $search_args ) : array();
        $blocking = \array_filter( $blocking, $this->get_next_action_time( ... ) );

        return $blocking ? \current( $blocking ) : null;
    }

    public function add_custom_hook( int $action_id ) {
        $action = $this->get_action( $action_id );

        if ( ! $this->has_job_meta( $action ) || $this->has_custom_hook( $action->get_hook(), $action_id ) ) {
            return;
        }

        static::$changed = true;

        static::$hooks[ $action->get_hook() ][ $action_id ] = true;
    }

    public function remove_custom_hook( int $action_id, ?string $hook = null ) {
        $hook ??= $this->find_hook_by_id( $action_id );

        if ( ! $hook ) {
            return;
        }

        static::$changed = true;

        unset( static::$hooks[ $hook ][ $action_id ] );

        if ( 0 !== count( static::$hooks[ $hook ] ) ) {
            return;
        }

        unset( static::$hooks[ $hook ] );
    }

    public function find_hook_by_id( int $action_id ): ?string {
        foreach ( static::$hooks as $hook => $ids ) {
            if ( isset( $ids[ $action_id ] ) ) {
                return $hook;
            }
        }

        return null;
    }

    public function has_custom_hook( string $hook, int $action_id ): bool {
        if ( 0 === $action_id && is_null( $this->action_id ) ) {
            return isset( static::$hooks[ $hook ] );
        }

        return isset( static::$hooks[ $hook ][ $action_id ] );
    }

    public function save_custom_hooks( bool $force = false ) {
        if ( ! static::$changed && ! $force ) {
            return;
        }

        update_option( 'xwc_queue_hooks', static::$hooks );

        static::$changed = false;
    }

    public function fire_custom_hooks() {
        if ( static::$fired ) {
            return;
        }

        foreach ( array_keys( static::$hooks ) as $hook ) {
            add_action( $hook, $this->dispatch_action( ... ), 10, 1 );
        }

        static::$fired = true;
    }

    public function get_action( int $action_id ): AS_Action {
        return \ActionScheduler::store()->fetch_action( $action_id );
    }

    public function has_job_meta( AS_Action $action ): bool {
        $args = $action->get_args();

        return isset( $args['meta']['job'] );
    }

    public function dispatch_action( XWC_Action $action ) {
        error_log( 'dispatch ' . $action->get_id() );
        Dispatcher::instance()->dispatch_to_executor( $action->get_job() );
    }
}
