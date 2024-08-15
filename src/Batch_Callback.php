<?php

namespace XWC\Queue;

use XWC\Queue\Interfaces\Batched_Job;

/**
 * Batch callback.
 *
 * @extends Job_Callback<Batched_Job>
 */
class Batch_Callback extends Job_Callback {
    protected bool $partial = false;

    protected int $times = 0;

    protected int $hold = 5;


    /**
     * Jobs to be batched.
     *
     * @var array<Batched_Job>
     */
    protected array $jobs = array();

    public function __construct( Batched_Job ...$jobs ) {
        $this->jobs      = $jobs;
        $this->scheduled = true;
        $this->interval  = 0;
    }

    protected function get_timestamp(): int {
        return parent::get_timestamp() + $this->job->batch_num() * $this->hold;
    }

    public function schedule(): static {
        return $this;
    }

    public function repeat( int $interval = 0 ): static {
        return $this;
    }

    public function partial( bool $partial = true ): static {
        $this->partial = $partial;

        return $this;
    }

    public function retry( int $times = 0 ): static {
        $this->times = $times;

        return $this;
    }

    public function hold( int $seconds ): static {
        $this->hold = $seconds;

        return $this;
    }

    public function save(): static {
        $this->action_id = 0;

        foreach ( $this->jobs as $job ) {
            $this->job = $job->prev_action( $this->action_id );
            parent::save();
        }

        return $this;
    }
}
