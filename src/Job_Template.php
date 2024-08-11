<?php

namespace XWC\Queue;

use XWC\Queue\Enums\JobType;
use XWC_Job_Manager;

abstract class Job_Template {
    protected string $classname;

    public int $num_args;

    public function __construct(
        public readonly string $name,
        public readonly string $group = '',
        public readonly JobType $type = JobType::None,
        public readonly int $batch_size = 0,
        public readonly string $dependency = '',
        public readonly bool $unique = false,
        public readonly int $priority = 10,
    ) {
    }

    abstract public function initialize(): bool;

    abstract public function is_recurring(): bool;

    public function get_hook(): string {
        return $this->group ? "{$this->group}_{$this->name}" : $this->name;
    }

    public function get_args(): array {
        return array();
    }

    protected function get_num_args(): int {
        $ref = new \ReflectionMethod( $this->classname, 'run' );

        return $ref->getNumberOfParameters();
    }

    public function set_classname( string $classname ): static {
        $this->classname ??= $classname;
        $this->num_args  ??= $this->get_num_args();

        return $this;
    }

    public function run( ...$args ) {
        return match ( $this->batched ) {
            JobType::None => $this->run_single( ...$args ),
        };
    }

    protected function run_single( ...$args ) {
        ( new $this->classname() )->run( ...$args );
    }

    public function is_blocked(): \ActionScheduler_Action|false {
        if ( '' === $this->dependency ) {
            return false;
        }

        $actions = XWC_Job_Manager::queue()->search(
            array(
				'group'    => $this->group,
				'order'    => 'DESC',
				'orderby'  => 'date',
				'per_page' => 1,
				'search'   => $this->dependency, // search is used instead of hook to find queued batch creation.
				'status'   => 'pending',
            ),
        );

        if ( ! \is_array( $actions ) || ! \count( $actions ) ) {
            return false;
        }

        foreach ( $actions as $action ) {
            /**
             * Override.
             *
             * @var \ActionScheduler_Abstract_Schedule
             */
            $sch = $action->get_schedule();

            if ( $sch->get_date() ) {
                return $action;
            }
        }

        return false;
    }
}
