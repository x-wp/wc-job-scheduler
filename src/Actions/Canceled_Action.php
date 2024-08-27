<?php

namespace XWC\Queue\Scheduler;

use ActionScheduler_NullSchedule as Null_Schedule;
use ActionScheduler_Schedule as Schedule;

/**
 *
* Stored action which was canceled and therefore acts like a finished action but should always return a null schedule,
* regardless of schedule passed to its constructor.
*/
class Canceled_Action extends Finished_Action {
    /**
     * Constructor.
     *
	 * @param string   $hook     Hook name.
	 * @param array    $args     Arguments to pass when the hook is run.
	 * @param Schedule $schedule Schedule for when the action should run.
	 * @param string   $group    Optional. Group for the action. Default empty string.
	 */
	public function __construct( $hook, array $args = array(), Schedule $schedule = null, $group = '' ) {
        $schedule ??= new Null_Schedule();
		parent::__construct( $hook, $args, $schedule, $group );
	}

    public function is_recurring(): bool {
        return false;
    }
}
