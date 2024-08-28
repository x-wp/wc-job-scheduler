<?php

namespace XWC\Scheduler\Action;

use Closure;
use XWC\Scheduler\Dispatcher;
use XWC\Scheduler\Error\ConstraintError;
use XWC\Scheduler\Error\ConstraintInvalid;
use XWC\Scheduler\Error\DependencyError;
use XWC\Scheduler\Error\JobExecutionError;
use XWC\Scheduler\Middleware\Check_Blockers;
use XWC\Scheduler\Middleware\Verify_Conditions;
use XWC\Scheduler\Pipeline;

/**
 * Default custom action class.
 *
 * @template TSch of \ActionScheduler_Abstract_Schedule|\ActionScheduler_Schedule
 *
 * @method TSch get_schedule() Get the schedule for the action.
 */
class Pending_Action extends \ActionScheduler_Action {
    /**
     * Action Schedule instance
     *
     * @var TSch|null
     * @phpstan-ignore property.phpDocType
     */
    protected $schedule = null;

    protected int $id = 0;

    protected int $attempts = 0;

    protected array $pipes = array(
        Check_Blockers::class,
        Verify_Conditions::class,
    );

    public function with_id( int $id ): static {
        $this->id = $id;

        return $this;
    }

    public function with_attempts( int $attempts ): static {
        $this->attempts = $attempts;

        return $this;
    }

    public function set_id( int $id ): void {
        $this->id = $id;
    }

    public function set_attempts( int $attempts ): void {
        $this->attempts = $attempts;
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_attempts(): int {
        return $this->attempts;
    }

    public function get_data(): array {
        return $this->args['data'] ?? array();
    }

    public function get_meta(): array {
        return $this->args['meta'] ?? array();
    }

    public function get_blocker(): string {
        return $this->get_meta()['needs'] ?? '';
    }

    public function get_retries(): int {
        return $this->get_meta()['retries'] ?? 0;
    }

    /**
     * Get the filters for this action.
     *
     * @return array<Closure>
     */
    public function get_filters(): array {
        return $this->remap( $this->get_meta()['filters'] ?? array() );
    }

    /**
     * Get the rejects for this action.
     *
     * @return array<Closure>
     */
    public function get_rejects(): array {
        return $this->remap( $this->get_meta()['rejects'] ?? array() );
    }

    public function get_job() {
        $job_class = $this->args['job'] ?? null;

        if ( ! $job_class ) {
            return null;
        }

        return new $job_class( ...$this->get_data() );
    }

    public function has_job(): bool {
        return isset( $this->args['job'] ) ||
        \str_starts_with( $this->get_hook(), 'xwc_' ) ||
        'xwc_job' === $this->get_group();
    }

    public function is_strict(): bool {
        return $this->get_meta()['strict'] ?? false;
    }

    public function is_recurring(): bool {
        return $this->get_schedule()->is_recurring();
    }

    public function can_retry(): bool {
        return $this->has_job() &&
            ! $this->is_recurring() &&
            $this->get_attempts() <= $this->get_retries();
    }

    public function execute() {
        if ( ! $this->has_job() ) {
            return parent::execute();
        }

        try {
            return ( new Pipeline() )->send( $this )->through( $this->pipes )->then(
                static fn( $a ) => Dispatcher::instance()->dispatch_to_executor( $a->get_job() ),
            );
        } catch ( DependencyError $e ) {
            $this->log_execution( $e );
            $this->reschedule();
        } catch ( ConstraintInvalid $e ) {
            $this->log_execution( $e );
        } catch ( ConstraintError | \Exception $e ) {
            $this->log_execution( $e );
            throw $e;
        }
    }

    public function log_execution( \Exception|JobExecutionError $e ) {
        $level = \method_exists( $e, 'getLogLevel' ) ? $e->getLogLevel() : 'critical';

        \XWC_Schedule::log( $e->getMessage(), $level );
    }

    public function reschedule(): void {
        if ( $this->is_recurring() ) {
            return;
        }

        \XWC_Schedule::job( $this->get_job() )
            ->delay( 300 )
            ->needs( $this->get_blocker() )
            ->when( ...$this->get_filters() )
            ->skip( ...$this->get_rejects() );
    }

    /**
     * Remap the serialized closures.
     *
     * @param  array $what The array to remap.
     * @return array<int, Closure(): bool>
     */
    private function remap( array $what ): array {
        return \array_map( \xwc_unserialize_closure( ... ), $what );
    }
}
