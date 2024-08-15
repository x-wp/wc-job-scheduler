<?php

namespace XWC\Queue;

use XWC_Queue_Definition;
use XWP\Helper\Traits\Singleton;

class Scheduler {
    use Singleton;

    /**
     * Registered jobs.
     *
     * @var array<string, bool>
     */
    private static array $jobs = array();

    /**
	 * Queue instance.
	 *
	 * @var XWC_Queue_Definition
	 */
	protected static $queue = null;

    /**
     * Delayed jobs.
     *
     * @var array<string, Job_Callback>
     */
    private array $delayed = array();

    /**
	 * Get queue instance.
	 *
	 * @return XWC_Queue_Definition
	 */
	public static function queue(): XWC_Queue_Definition {
        return self::$queue ??= \WC()->queue();
	}

    public static function register_job( string $hook ): void {
        if ( \did_action( 'xwc_scheduler_init' ) ) {
            return;
        }

        self::$jobs[ $hook ] ??= false;
    }

    public static function job( $job ): Job_Callback {
        return new Job_Callback( $job );
    }

    public static function batch( ...$jobs ): Batch_Callback {
        return ( new Batch_Callback( ...$jobs ) )->schedule();
    }

    public static function call( callable $cb ) {
        // No-op.
    }

    protected function __construct() {
        $this->init_runners();
        $this->init_delayed();

        \do_action( 'xwc_scheduler_init' );
    }

    protected function init_runners(): void {
        foreach ( self::$jobs as $job => &$reg ) {
            $reg = \add_action( $job, $this->run_or_delay( ... ), 10, 2 ) ? 'registered' : 'failed';
        }
    }

    protected function init_delayed(): void {
        \add_action( 'action_scheduler_after_process_queue', $this->save_delayed( ... ), 99, 0 );
    }

    /**
     * Dispatch job.
     *
     * @param class-string<Job> $job Job class name.
     * @param array $args Job arguments.
     */
    protected function run_or_delay( string $job, array $args ): void {
        $current_job = new $job( ...$args );
        $blocker_job = $this->get_blocking_job( $current_job );

        $blocker_job
            ? $this->delay( $current_job, $blocker_job )
            : $this->run( $current_job );
    }

    protected function run( Job $job ): void {
        self::job( $job )->dispatch();
    }

    protected function delay( Job $current, \ActionScheduler_Action $blocker ): void {
        $this->delayed[] = self::job( $current )
            ->schedule()
            ->at( $blocker )
            ->delay( 5 );
    }

    protected function save_delayed(): void {
        foreach ( $this->delayed as $job ) {
            $job->save();
        }
    }

    public function get_blocking_job( Job $job ) {
        $search_args = array(
            'group'    => $job->group(),
            'order'    => 'DESC',
            'orderby'  => 'date',
            'per_page' => 1,
            'search'   => $job->needs(), // search is used instead of hook to find queued batch creation.
            'status'   => 'pending',
        );

        /**
         * Blocking job.
         *
         * @var array<\ActionScheduler_Action>|null
         */
        $blocking = $job->needs() ? self::queue()->search( $search_args ) : array();
        $blocking = \array_filter( $blocking, $this->get_next_action_time( ... ) );

        return $blocking ? \current( $blocking ) : null;
    }

    public function get_next_action_time( ?\ActionScheduler_Action $action ): ?\DateTime {
        /**
         * Schedule of the action.
         *
         * @var \ActionScheduler_SimpleSchedule|null
         */
        $schedule = $action?->get_schedule() ?? null;

        return $schedule?->get_next( new \DateTime() ) ?? null;
    }
}
