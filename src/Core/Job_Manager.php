<?php

use XWC\Queue\Enums\JobType;
use XWC\Queue\Job_Template;
use XWP\Helper\Classes\Reflection;
use XWP\Helper\Traits\Singleton;

class XWC_Job_Manager {
    use Singleton;

    /**
	 * Queue instance.
	 *
	 * @var XWC_Queue_Definition
	 */
	protected static $queue = null;

    /**
     * Job array.
     *
     * @var array<string, Job_Template>
     */
    protected array $jobs = array();

    /**
	 * Get queue instance.
	 *
	 * @return XWC_Queue_Definition
	 */
	public static function queue(): XWC_Queue_Definition {
        return self::$queue ??= \WC()->queue();
	}

    protected function __construct() {
        add_action( 'init', $this->init_jobs( ... ), 10 );
        add_action( 'init', $this->process_jobs( ... ), 11 );
    }

    protected function init_jobs() {
        $jobs = apply_filters( 'xwc_jobs', array() );

        foreach ( $jobs as $classname ) {
            $job = $this->get_job_data( $classname );

            if ( ! $job || isset( $this->jobs[ $job->get_hook() ] ) ) {
                continue;
            }

            $this->jobs[ $job->get_hook() ] = $job;
        }
    }

    protected function get_job_data( string $name ): ?Job_Template {
        $job = Reflection::get_decorator( $name, Job_Template::class );

        return $job?->set_classname( $name );
    }

    protected function process_jobs() {
        foreach ( $this->jobs as $job ) {
            if ( ! $job->initialize() ) {
                continue;
            }

            $this->process( $job );
        }
    }

    protected function schedule_non_batched( Job_Template $job ) {
        add_action( $job->get_hook(), $this->run_or_reschedule( ... ), $job->priority, $job->num_args );
    }

    protected function schedule_page_batched( Job_Template $job ) {
        add_action( $job->get_hook(), $this->run_or_reschedule( ... ), $job->priority, $job->num_args );
    }

    protected function run_or_reschedule( ...$args ) {
        $curr_job = $this->get_job( current_action() );
        $blocking = $this->get_blocking_action( $curr_job );

        if ( $blocking ) {
            self::queue()->schedule_single(
                $this->get_next_action_time( $blocking )->getTimestamp() + 5,
                $curr_job->get_hook(),
                $args,
                $curr_job->group,
            );
            return;
        }

        match ( $curr_job->batched ) {
            JobType::None => $curr_job->run( ...$args ),
        };
    }

    public function is_scheduled( Job_Template $job ): bool {
        $existing = self::queue()->search(
            array(
				'claimed'  => false,
				'group'    => $job->group,
				'hook'     => $job->get_hook(),
				'per_page' => 1,
				'search'   => $this->flatten_args( $job->get_args() ),
				'status'   => 'pending',
            ),
        );

        if ( ! $existing ) {
            return false;
        }

        $scheduled = current( $existing );

        return ( $job->hook === $scheduled->get_hook() ) || in_array(
            $job->hook,
            $scheduled->get_args(),
            true,
        );
    }

    /**
	 * Flatten multidimensional arrays to store for scheduling.
	 *
	 * @param array $args Argument array.
	 * @return string
	 */
	public function flatten_args( $args ) {
		$flattened = array();

		foreach ( $args as $arg ) {
			$flattened[] = \is_array( $arg ) ? $this->flatten_args( $arg ) : $arg;
		}

		$string = '[' . \implode( ',', $flattened ) . ']';
		return $string;
	}

    public function get_job( string $name, string $group = '' ): ?Job_Template {
        $id = $group ? "{$group}.{$name}" : $name;
        return $this->jobs[ $id ] ?? null;
    }

    public function get_blocking_action( Job_Template $job ): \ActionScheduler_Action|false {
        $actions = self::queue()->search(
			array(
                'group'    => $job->group,
                'order'    => 'DESC',
                'orderby'  => 'date',
                'per_page' => 1,
                'search'   => $job->dependency, // search is used instead of hook to find queued batch creation.
                'status'   => 'pending',
			),
		);

        if ( ! \is_array( $actions ) || ! \count( $actions ) ) {
            return false;
        }

        foreach ( $actions as $action ) {
            if ( ! $this->get_next_action_time( $action ) ) {
                continue;
            }

            return $action;
        }

        return false;
    }

    /**
	 * Get the DateTime for the next scheduled time an action should run.
	 * This function allows backwards compatibility with Action Scheduler < v3.0.
	 *
	 * @param  ActionScheduler_Action $action Action.
	 * @return DateTime|null
	 */
	public function get_next_action_time( $action ) {
        /**
         * Override.
         *
         * @var \ActionScheduler_Abstract_Schedule
         */
        $sch = $action->get_schedule();

        return $sch->get_date();
	}
}
