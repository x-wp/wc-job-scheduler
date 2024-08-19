<?php

namespace XWC\Queue;

use Closure;
use XWC\Queue\Interfaces\Can_Dispatch;
use XWC\Queue\Interfaces\Can_Schedule;

class Callback_Action {
    use Traits\Schedule_Frequency_Methods;

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

    public int $retry = 0;

    /**
     * How often to repeat the event during a minute.
     *
     * @var int|null
     */
    public ?int $repeat_sec = null;

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
     * The timezone the date should be evaluated on.
     *
     * @var \DateTimeZone|string
     */
    public ?\DateTimeZone $timezone = null;

    public function __construct(
        private readonly Closure $callback,
	) {
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  \Closure|bool  $callback
     * @return static
     */
    public function when( $callback ): static {
        $this->filters[] = \is_callable( $callback ) ? $callback : static fn() => $callback;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  \Closure|bool  $callback
     * @return $this
     */
    public function skip( $callback ) {
        $this->rejects[] = \is_callable( $callback ) ? $callback : static fn() => $callback;

        return $this;
    }

    public function retry( int $times ): static {
        $this->retry = $times;

        return $this;
    }

    public function save_action(): void {
        ( $this->callback )( $this->get_schedule_params() );
    }

    protected function get_schedule_params(): array {
        $params = array(
            'expression' => $this->expression,
            'filters'    => $this->filters,
            'rejects'    => $this->rejects,
            'retry'      => $this->retry,
            'timestamp'  => $this->timestamp,
        );

        if ( ! $this->is_recurring() ) {
            unset( $params['expression'] );
        }

        return $params;
    }

    public function is_recurring(): bool {
        return 'once' !== $this->expression;
    }
}
