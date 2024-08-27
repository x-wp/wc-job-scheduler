<?php

namespace XWC\Queue\Scheduler;

/**
 * Null action class.
 *
 * @extends Pending_Action<\ActionScheduler_NullSchedule>
 */
class Null_Action extends Pending_Action {
    protected function set_schedule( \ActionScheduler_Schedule $schedule ) {
		$this->schedule = new \ActionScheduler_NullSchedule();
	}

    public function is_recurring(): bool {
        return false;
    }
}
