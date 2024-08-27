<?php

use ActionScheduler_Action as AS_Action;
use XWC\Queue\Dispatcher;
use XWC\Queue\Job;
use XWC\Queue\Scheduler\Canceled_Action;
use XWC\Queue\Scheduler\Finished_Action;
use XWC\Queue\Scheduler\Null_Action;
use XWC\Queue\Scheduler\Pending_Action;

/**
 * WooCommerce Queue Extension.
 */
class XWC_Queue extends WC_Action_Queue implements XWC_Queue_Definition {
    public function unschedule( string $hook, array $args = array(), string $group = '' ): ?int {
        return as_unschedule_action( $hook, $args, $group );
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
            return '0';
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
	 * @param  Pending_Action $action Action.
	 * @return DateTime|null
	 */
	public function get_next_action_time( ?Pending_Action $action ): ?DateTime {
        return $action?->get_schedule()?->get_next( new DateTime() ) ?? null;
    }

    public function has_next_action_time( ?Pending_Action $action ): bool {
        return null !== $this->get_next_action_time( $action );
    }

    public function get_existing( Pending_Action $job ): bool {
        $search_args = array(
            'args'                  => $job->get_data(),
            'claimed'               => false,
            'group'                 => $job->get_group(),
            'hook'                  => $job->get_hook(),
            'partial_args_matching' => 'like',
            'per_page'              => 1,
            'status'                => 'pending',
        );

        $actions = $this->search( $search_args );
        $action  = \current( $actions );

        return ! $action ? false : $action->get_hook() === $job->get_hook();
    }

    public function get_blocker( Pending_Action $job ) {
        $search_args = array(
            'group'    => $job->get_group(),
            'order'    => 'DESC',
            'orderby'  => 'date',
            'per_page' => 1,
            'search'   => $job->get_blocker(), // search is used instead of hook to find queued batch creation.
            'status'   => 'pending',
        );

        $blocking = $job->get_blocker() ? $this->search( $search_args ) : array();

        $blocking = \array_filter( $blocking, $this->has_next_action_time( ... ) );

        $blocking = current( $blocking );

        return $blocking ? $blocking : null;
    }

    public function get_action( int $action_id ): Pending_Action|Canceled_Action|Finished_Action|Null_Action {
        return \ActionScheduler::store()->fetch_action( $action_id );
    }

    public function has_job_meta( AS_Action $action ): bool {
        $args = $action->get_args();

        return isset( $args['meta']['job'] );
    }
}
