<?php

namespace XWC\Queue\Scheduler;

/**
 * Finished action class.
 */
class Finished_Action extends Pending_Action {
    public function execute() {
		// No-op.
	}

	public function is_finished() {
		return true;
	}
}
