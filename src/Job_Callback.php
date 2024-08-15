<?php

namespace XWC\Queue;

use ActionScheduler_Action as Action;
use XWC\Queue\Interfaces\Scheduled_Job;
use XWC_Queue_Definition;

/**
 * Job callback.
 *
 * @template T of Scheduled_Job
 */
class Job_Callback {
    /**
	 * Queue instance.
	 *
	 * @var XWC_Queue_Definition
	 */
	protected static $queue = null;

    protected bool $scheduled = false;

    private ?int $timestamp = null;

    protected int $interval = 0;

    protected int $action_id = 0;

    /**
	 * Get queue instance.
	 *
	 * @return XWC_Queue_Definition
	 */
	public static function queue(): XWC_Queue_Definition {
        return self::$queue ??= \WC()->queue();
	}

    /**
     * Undocumented function
     *
     * @param T $job The job to be scheduled.
     */
    public function __construct( protected Scheduled_Job $job ) {
    }

    private function set_timestamp( string|int|\DateTime|Action $timestamp ): static {
        $this->timestamp = $this->parse_timestamp( $timestamp );

        return $this;
    }

    private function parse_timestamp( string|int|\DateTime|Action $when ): int {
        $now         = new \DateTime();
        $time_string = match ( true ) {
            $when instanceof Action      => $when->get_schedule()?->get_next( $now )->getTimestamp(),
            $when instanceof \DateTime   => $when->getTimestamp(),
            ! \is_int( $when )               => \wc_string_to_timestamp( $when ),
            $when < $now->getTimestamp() => $now->getTimestamp() + $when,
            default                      => $when,
        };

        return $time_string;
    }

    protected function get_timestamp(): int {
        return $this->timestamp ?? \wc_string_to_timestamp( 'now' );
    }

    public function schedule(): static {
        $this->scheduled = true;

        return $this;
    }

    public function in( string $time_string ): static {
        return $this->set_timestamp( $time_string );
    }

    public function at( string|int|\DateTime|Action $when ): static {
        return $this->set_timestamp( $when );
    }

    public function now(): static {
        return $this->set_timestamp( \wc_string_to_timestamp( 'now' ) );
    }

    public function delay( int $delay ): static {
        return $this->set_timestamp( $delay );
    }

    public function repeat( int $interval ): static {
        $this->interval = $interval;

        return $this;
    }

    public function dispatch(): void {
        $this->job->handle( $this->job->handler() );
    }

    public function save(): static {
        $this->action_id = match ( true ) {
            $this->exists()     => 0,
            ! $this->scheduled  => 0,
            $this->interval > 0 => $this->recurring(),
            default             => $this->single(),
        };

        return $this;
    }

    public function data(): array {
        return $this->job->data();
    }

    public function dump(): array {
        return \array_merge(
            array(
                'action_id' => $this->action_id,
                'interval'  => $this->interval,
                'timestamp' => $this->get_timestamp(),
            ),
            $this->job->data(),
        );
    }

    public function exists(): bool {
        $jobs = self::queue()->search(
            array(
                'args'                  => $this->data()['args'],
                'claimed'               => false,
                'group'                 => $this->job->group(),
                'hook'                  => $this->job->hook(),
                'partial_args_matching' => 'like',
                'per_page'              => 1,
                'status'                => 'pending',
            ),
        );

        if ( $jobs ) {
            $job = \current( $jobs );

            if ( $job->get_hook() === $this->job->hook() ) {
                return true;
            }
        }

        return false;
    }

    protected function recurring(): int {
        return self::queue()->schedule_recurring(
            ...$this->data(),
            timestamp: $this->get_timestamp(),
            interval: $this->interval,
        );
    }

    protected function single(): int {
        return self::queue()->schedule_single( ...$this->data(), timestamp: $this->get_timestamp() );
    }
}
