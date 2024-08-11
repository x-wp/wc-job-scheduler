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

    /**
	 * Schedule an action that recurs on a cron-like schedule.
	 *
	 * @param  int    $timestamp     The schedule will start on or after this time.
	 * @param  string $cron_schedule A cron-link schedule string.
	 * @param  string $hook          The hook to trigger.
	 * @param  array  $args          Arguments to pass when the hook triggers.
	 * @param  string $group         The group to assign this job to.
     * @param  bool   $unique        Whether this job should be unique in the queue.
     * @param  int    $priority      The priority of the action.
	 * @return string The action ID
     *
     * @see http://en.wikipedia.org/wiki/Cron
	 */
    public function schedule_cron( $timestamp, $cron_schedule, $hook, $args = array(), $group = '', bool $unique = false, int $priority = 10 );

    /**
	 * Find scheduled actions
	 *
	 * @param array  $args Possible arguments, with their default values:
	 *        'hook' => '' - the name of the action that will be triggered
	 *        'args' => null - the args array that will be passed with the action
	 *        'date' => null - the scheduled date of the action. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
	 *        'date_compare' => '<=' - operator for testing "date". accepted values are '!=', '>', '>=', '<', '<=', '='
	 *        'modified' => null - the date the action was last updated. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
	 *        'modified_compare' => '<=' - operator for testing "modified". accepted values are '!=', '>', '>=', '<', '<=', '='
	 *        'group' => '' - the group the action belongs to
	 *        'status' => '' - ActionScheduler_Store::STATUS_COMPLETE or ActionScheduler_Store::STATUS_PENDING
	 *        'claimed' => null - TRUE to find claimed actions, FALSE to find unclaimed actions, a string to find a specific claim ID
	 *        'per_page' => 5 - Number of results to return
	 *        'offset' => 0
	 *        'orderby' => 'date' - accepted values are 'hook', 'group', 'modified', or 'date'
	 *        'order' => 'ASC'.
	 *
	 * @param  string $return_format OBJECT, ARRAY_A, or ids.
	 * @return array<int|string|ActionScheduler_Action>
	 */
	public function search( $args = array(), $return_format = OBJECT );
}
