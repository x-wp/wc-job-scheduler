<?php

namespace XWC\Queue\Traits;

use XWC\Queue\Batch;
use XWC\Queue\Scheduler;

trait Batchable {
    public int $prev_action = 0;

    public int $batch_num = 0;

    public int $batch_size = 0;

    abstract public static function get_total( int $size, array $args = array() ): int;

    public static function batch( int $batch_size, ...$args ) {
        $items_count = static::get_total( $batch_size, $args );
        $batch_total = (int) \ceil( $items_count / $batch_size );
        $batch_jobs  = array();

        foreach ( \range( 1, $batch_total ) as $batch_num ) {
            $batch_jobs[] = static::for_batch( $batch_num, $batch_size, $batch_total, $args );
        }

        return Scheduler::batch( ...$batch_jobs );
    }

    protected static function for_batch( int $batch_num, int $batch_size, int $batch_total, array $args = array() ) {
        return ( new static( ...$args ) )->set_batch( $batch_num, $batch_size, $batch_total );
    }

    public function set_batch( int $batch_num, int $batch_size, int $batch_total ): static {
        $this->batch_num   = $batch_num;
        $this->batch_size  = $batch_size;
        $this->batch_total = $batch_total;

        return $this;
    }

    final public function args(): array {
        //phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys
        return array(
            'job'  => Batch::class,
            'args' => array(
                'params' => $this->params(),
                'batch'  => array(
                    'job'   => static::class,
                    'num'   => $this->batch_num,
                    'total' => $this->batch_total,
                    'size'  => $this->batch_size,
                    'prev'  => $this->prev_action,
                ),
            ),
        );
        //phpcs:enable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys
    }

    final public function needs(): string {
        return '';
    }

    public function batch_num(): int {
        return $this->batch_num;
    }

    public function batch_size(): int {
        return $this->batch_size;
    }

    public function batch_total(): int {
        return $this->batch_total;
    }

    public function prev_action( int $prev_action ): static {
        $this->prev_action = $prev_action;

        return $this;
    }
}
