<?php

namespace XWC\Queue;

use XWC\Queue\Interfaces\Batched_Job;
use XWC\Queue\Traits\Schedulable;

class Batch extends Job {
    use Schedulable;

    protected Batched_Job $job;

    /**
     * Undocumented function
     *
     * @param  array $params Job parameters.
     * @param  array{job: class-string<Batched_Job>} $batch Batch parameters.
     */
    public function __construct( $params, $batch ) {
        $this->job = ( new $batch['job']( ...$params ) )
            ->set_batch( $batch['num'], $batch['size'], $batch['total'] )
            ->prev_action( $batch['prev'] );
    }

    public function hook(): string {
        return $this->job->hook();
    }

    public function params(): array {
        return $this->job->params();
    }

    public function handle( ?object $processor = null ): void {
        \error_log(
            \sprintf(
                'Dispatching %d items. Batch (%d of %d).',
                $this->job->batch_size(),
                $this->job->batch_num(),
                $this->job->batch_total(),
            ),
        );
        // No-op.
    }
}
