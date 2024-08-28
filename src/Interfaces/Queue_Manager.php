<?php

namespace XWC\Scheduler\Interfaces;

use DateTime;
use XWC\Scheduler\Action\Canceled_Action;
use XWC\Scheduler\Action\Finished_Action;
use XWC\Scheduler\Action\Pending_Action;

interface Queue_Manager {
    public function unschedule( string $hook, array $args = array(), string $group = '' ): ?int;

    /**
	 * Enqueue an action to run one time, as soon as possible
	 *
	 * @param  string $hook     The hook to trigger.
	 * @param  array  $args     Arguments to pass when the hook triggers.
	 * @param  string $group    The group to assign this job to.
     * @param  bool   $unique   Whether this job should be unique in the queue.
     * @param  int    $priority The priority of the action.
	 * @return int
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
	 * @return int
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
	 * @return int
	 */
    public function schedule_recurring( $timestamp, $interval = 0, $hook = '', $args = array(), $group = '', bool $unique = false, int $priority = 10, $interval_in_seconds = 0 );

    /**
	 * Schedule an action that recurs on a cron-like schedule.
	 *
	 * @param  int    $timestamp  The schedule will start on or after this time.
	 * @param  string $expression A cron-link schedule string.
	 * @param  string $hook       The hook to trigger.
	 * @param  array  $args       Arguments to pass when the hook triggers.
	 * @param  string $group      The group to assign this job to.
     * @param  bool   $unique     Whether this job should be unique in the queue.
     * @param  int    $priority   The priority of the action.
     *
	 * @return int
     *
     * @see http://en.wikipedia.org/wiki/Cron
	 *   *    *    *    *    *    *
	 *   ┬    ┬    ┬    ┬    ┬    ┬
	 *   |    |    |    |    |    |
	 *   |    |    |    |    |    + year [optional]
	 *   |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
	 *   |    |    |    +---------- month (1 - 12)
	 *   |    |    +--------------- day of month (1 - 31)
	 *   |    +-------------------- hour (0 - 23)
	 *   +------------------------- min (0 - 59)
	 */
	public function schedule_cron( $timestamp, $expression = null, $hook = '', $args = array(), $group = '', bool $unique = false, int $priority = 10, ?string $cron_schedule = null );

    /**
	 * Find scheduled actions.
	 *
     * @param  array{
     *   group?: string,
     *   hook?: string,
     *   status?: 'complete'|'pending'|'failed'|'canceled'|'in-progress',
     *   args?: array<mixed>|array<string,mixed>,
     *   partial_args_matching?: 'like'|'json'|'off',
     *   claimed?: bool|int|string,
     *   date?: string|\DateTime|int,
     *   date_compare?: '!='|'>'|'>='|'<'|'<='|'=',
     *   modified?: string|\DateTime|int,
     *   modified_compare?: '!='|'>'|'>='|'<'|'<='|'=',
     *   offset?: int,
     *   per_page?: int,
     *   orderby?: 'date'|'hook'|'group'|'modified',
     *   order?: 'ASC'|'DESC'
     * } $args Possible arguments, with their default values.
     *
	 *     group => '' - the group the action belongs to.
     *
	 *     hook => '' - the name of the action that will be triggered.
     *
	 *     status => '' - Action status. Accepted values are 'complete', 'pending', 'failed', 'canceled', or 'in-progress'.
     *
	 *     args => null - the args array that will be passed with the action.
     *
     *     partial_args_matching => 'off' - Whether to match partial args. Accepted values are 'like', 'json', or 'off'.
     *
	 *     claimed => null - TRUE to find claimed actions, FALSE to find unclaimed actions, a string to find a specific claim ID.
     *
	 *     date => null - the scheduled date of the action. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
     *
	 *     date_compare => '<=' - operator for testing "date". accepted values are '!=', '>', '>=', '<', '<=', '='.
     *
	 *     modified => null - the date the action was last updated. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
     *
	 *     modified_compare' => '<=' - operator for testing "modified". accepted values are '!=', '>', '>=', '<', '<=', '='.
     *
	 *     offset => 0 - The number of results to skip.
     *
	 *     per_page => 5 - Number of results to return.
     *
	 *     orderby => 'date' - accepted values are 'hook', 'group', 'modified', or 'date'.
     *
	 *     order => 'ASC'
	 * @param  string $return_format OBJECT, ARRAY_A, or ids.
	 * @return array<Pending_Action>|array<int>|array<array<string,mixed>>
	 */
	public function search( $args = array(), $return_format = OBJECT );

    /**
	 * Get the DateTime for the next scheduled time an action should run.
	 *
	 * @param  Pending_Action $action Action.
	 * @return DateTime|null
	 */
	public function get_next_action_time( ?Pending_Action $action ): ?DateTime;

    public function get_existing( Pending_Action $job ): bool;

    public function get_blocker( Pending_Action $job );

    public function get_action( int $action_id ): Pending_Action|Canceled_Action|Finished_Action;
}
