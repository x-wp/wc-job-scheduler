<?php

namespace XWC\Queue\Interfaces;

interface Batched_Job extends Scheduled_Job {
    public function batch_num(): int;

    public function batch_size(): int;

    public function batch_total(): int;

    public function prev_action( int $prev_action ): static;

    public function set_batch( int $batch_num, int $batch_size, int $batch_total ): static;
}
