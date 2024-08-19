<?php

namespace XWC\Queue\Jobs;

use ActionScheduler_Schedule;

class Scheduled_Action extends \ActionScheduler_Action {
    protected int $action_id = 0;

    protected string $job_class;

    protected array $filters;

    protected array $rejects;

    protected int $retry;

    protected $job;

    protected $attempts;

	public function __construct( $hook, ?array $args = null, ActionScheduler_Schedule $schedule = null, $group = '' ) {
        [ 'meta' => $meta ] = $args ?? array( 'data' => array(), 'meta' => array() );

        parent::__construct( $hook, $args, $schedule, $group );

        $this->job_class = $meta['job'];
        $this->filters   = \array_map( \xwc_unserialize_closure( ... ), $meta['filters'] );
        $this->rejects   = \array_map( \xwc_unserialize_closure( ... ), $meta['rejects'] );
        $this->retry     = $meta['retry'] ?? 0;
        $this->attempts  = $meta['attempts'] ?? 0;
    }

    public function with_id( int $action_id ): static {
        $this->action_id = $action_id;

        return $this;
    }

    public function get_id(): int {
        return $this->action_id;
    }

    public function execute() {
        \do_action( $this->get_hook(), $this );
    }

    public function get_data(): array {
        return $this->args['data'] ?? array();
    }

    public function get_job() {
        return $this->job ??= new ( $this->job_class )( ...$this->get_data() );
    }

    public function get_filters(): array {
        return $this->filters;
    }

    public function get_rejects(): array {
        return $this->rejects;
    }

    public function get_retry(): int {
        return $this->retry;
    }

    public function get_attempts(): int {
        return $this->attempts;
    }

    public function can_execute(): bool {
        return $this->test( $this->filters, false ) && $this->test( $this->rejects, true );
    }

    public function test( array $callables, bool $test ): bool {
        foreach ( $callables as $callable ) {
            if ( $callable() === $test ) {
                return false;
            }
        }

        return true;
    }
}
