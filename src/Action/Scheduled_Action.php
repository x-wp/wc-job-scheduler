<?php

namespace XWC\Scheduler\Action;

use Closure;
use XWC\Scheduler\Job\Schedule_Frequency_Methods;

class Scheduled_Action {
    use Schedule_Frequency_Methods;

    /**
     * The time the event is scheduled to run.
     *
     * @var int|null
     */
    public ?int $timestamp = null;

    /**
     * The cron expression representing the event's frequency.
     *
     * @var string
     */
    public string $expression = 'once';

    /**
     * Max number of retries.
     *
     * @var int
     */
    public int $retries = 0;

    /**
     * Determines if the action should be scheduled.
     *
     * @var Closure
     */
    public Closure $if_cb;

    /**
     * The array of filter callbacks.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * The array of reject callbacks.
     *
     * @var array
     */
    protected $rejects = array();

    /**
     * The array of jobs this action depends on.
     *
     * @var string
     */
    protected string $needs = '';

    /**
     * The hook to trigger.
     *
     * @var string
     */
    public ?string $hook = null;

    /**
     * The group to assign this job to.
     *
     * @var string
     */
    public ?string $group = null;

    public bool $strictly = true;

    /**
     * The timezone the date should be evaluated on.
     *
     * @var \DateTimeZone
     */
    public ?\DateTimeZone $timezone = null;

    public function __construct(
        /**
         * Callback to run when the action is scheduled.
         *
         * @var Closure(array): void
         */
        private readonly Closure $callback,
	) {
        $this->if_cb = \__return_true( ... );
    }

    protected function get_params(): array {
        $meta = array(
            'filters' => \array_map( \xwc_serialize_closure( ... ), $this->filters ),
            'needs'   => $this->needs,
            'rejects' => \array_map( \xwc_serialize_closure( ... ), $this->rejects ),
            'retries' => $this->retries,
            'strict'  => $this->strictly,
        );

        $params = array(
            'args'       => array(
                'meta' => \array_filter( $meta ),
            ),
            'expression' => $this->expression,
            'group'      => $this->group,
            'hook'       => $this->hook,
            'timestamp'  => $this->timestamp,
        );

        $params['method'] = match ( true ) {
            false === ( $this->if_cb )()   => 'unschedule',
            $this->is_recurring()          => 'schedule_cron',
            default                        => 'schedule_single',
        };

        return \array_filter( $params );
    }

    public function if( bool|Closure $condition ): static {
        $this->if_cb = \is_callable( $condition ) ? $condition : static fn() => $condition;

        return $this;
    }

    public function strict( bool $strict = true ): static {
        $this->strictly = $strict;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  Closure|bool  ...$callbacks The callbacks to register.
     * @return static
     */
    public function when( ...$callbacks ): static {
        foreach ( $callbacks as $callback ) {
            $this->add_filter( 'filters', $callback );
        }

        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  Closure|bool  ...$callbacks The callbacks to register.
     * @return $this
     */
    public function skip( ...$callbacks ) {
        foreach ( $callbacks as $callback ) {
            $this->add_filter( 'rejects', $callback );
        }

        return $this;
    }

    public function needs( string|object $job ): static {
        $this->needs = \xwc_format_job_hook( $job );

        return $this;
    }

    private function add_filter( string $which, Closure|bool $callback ): static {
        $this->{$which}[] = \is_callable( $callback ) ? $callback : static fn() => $callback;

        return $this;
    }

    public function retries( int $times ): static {
        $this->retries = $times;

        return $this;
    }

    public function hook( ?string $hook ): static {
        if ( $hook ) {
            $this->hook = $hook;
        }

        return $this;
    }

    public function group( ?string $group ): static {
        if ( $group ) {
            $this->group = $group;
        }

        return $this;
    }

    public function is_recurring(): bool {
        return 'once' !== $this->expression;
    }

    public function __destruct() {
        ( $this->callback )( $this->get_params() );
    }
}
