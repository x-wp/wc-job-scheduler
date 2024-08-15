<?php

interface XWC_Queue_Definition extends \WC_Queue_Interface {
    /**
	 * Enqueue an action to run one time, as soon as possible
	 *
	 * @param  string $hook     The hook to trigger.
	 * @param  array  $args     Arguments to pass when the hook triggers.
	 * @param  string $group    The group to assign this job to.
     * @param  bool   $unique   Whether this job should be unique in the queue.
     * @param  int    $priority The priority of the action.
	 * @return int The action ID.
	 */
	public function add( $hook, $args = array(), $group = '', bool $unique = false, int $priority = 10 );

    /**
	 * Schedule an action to run once at some time in the future
	 *
	 * @param  int    $timestamp When the job will run.
	 * @param  string $hook      The hook to trigger.
	 * @param  array  $args      Arguments to pass when the hook triggers.
	 * @param  string $group     The group to assign this job to.
     * @param  bool   $unique    Whether this job should be unique in the queue.
     * @param  int    $priority  The priority of the action.
	 * @return int The action ID.
	 */
    public function schedule_single( $timestamp, $hook, $args = array(), $group = '', bool $unique = false, int $priority = 10 );

    /**
	 * Schedule a recurring action
	 *
	 * @param  int    $timestamp When the job will run.
     * @param  int    $interval  How long to wait between runs.
	 * @param  string $hook      The hook to trigger.
	 * @param  array  $args      Arguments to spass when the hook triggers.
	 * @param  string $group     The group to assign this job to.
     * @param  bool   $unique    Whether this job should be unique in the queue.
     * @param  int    $priority  The priority of the action.
     *
     * @param  int    $interval_in_seconds  The interval in seconds. Legacy parameter, use $interval instead.
     *
	 * @return int The action ID.
	 */
    public function schedule_recurring( $timestamp, $interval = 0, $hook = '', $args = array(), $group = '', bool $unique = false, int $priority = 10, $interval_in_seconds = 0 );
}
