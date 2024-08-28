<?php //phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log

use XWC\Scheduler\Action\Canceled_Action;
use XWC\Scheduler\Action\Finished_Action;
use XWC\Scheduler\Action\Pending_Action;
use XWC\Scheduler\Dispatcher;

class XWC_Action_Store extends ActionScheduler_DBStore {
    /**
     * Array of action IDs to retry.
     *
     * @var array<int>
     */
    protected array $to_retry = array();

    public function __construct() {
        add_filter( 'action_scheduler_stored_action_class', $this->change_action_class( ... ), 100, 2 );
        add_action( 'action_scheduler_failed_execution', $this->schedule_retry( ... ), 100, 1 );
        add_action( 'shutdown', $this->retry_actions_by_id( ... ), 100, 0 );
    }

    protected function change_action_class( string $classname, string $status ): string {
        return match ( $status ) {
            static::STATUS_PENDING  => Pending_Action::class,
            static::STATUS_CANCELED => Canceled_Action::class,
            default                 => Finished_Action::class,
        };
    }

    /**
	 * Create an action from a database record.
     *
     * @param  object $data Action database record.
     * @return Pending_Action|Canceled_Action|Finished_Action
     */
	protected function make_action_from_db_record( $data ) {
        /**
         * Action instance.
         *
         * @var Pending_Action|Canceled_Action|Finished_Action $action
         */
        $action = parent::make_action_from_db_record( $data );

        return $action
            ->with_id( $data->action_id )
            ->with_attempts( $data->attempts );
    }

    private function schedule_retry( int $action_id ): void {
        /* translators: %1$d: Attempts, %2$d: Retries */
        $message = \__( 'action failed - retry limit reached: %1$d/%2$d', 'xwc-job-manager' );
        $action  = Dispatcher::queue()->get_action( $action_id );

        if ( $action->can_retry() ) {
            /* translators: %1$d: Attempts, %2$d: Retries */
            $message          = \__( 'action moved to pending - attempt: %1$d/%2$d', 'xwc-job-manager' );
            $this->to_retry[] = $action_id;

        }

        \ActionScheduler::logger()->log(
            $action_id,
            \sprintf(
                $message,
                $action->get_attempts(),
                $action->get_retries(),
            ),
        );
    }

    public function retry_action( int|Pending_Action $action ): int|false {
        $id = is_int( $action ) ? $action : $action->get_id();

        global $wpdb;

        return $wpdb->update(
            $wpdb->actionscheduler_actions,
            array( 'status' => ActionScheduler_Store::STATUS_PENDING ),
            array( 'action_id' => $id ),
            array( '%s' ),
            array( '%d' ),
        );
    }

    public function retry_actions_by_id( ?array $ids = null ): void {
        $ids ??= $this->to_retry;

        foreach ( $ids as $action_id ) {
            $upd = $this->retry_action( $action_id );

        }
    }
}
