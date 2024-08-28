<?php

namespace XWC\Scheduler\Job;

use XWC\Scheduler\Interfaces\Is_Unique;

trait Schedulable {
    protected $schedule_args = array();

    protected array $job_meta;

    private function get_default_schedule_arg( $name ) {
        return match ( $name ) {
            'unique' => $this instanceof Is_Unique,
            'priority' => 10,
            default => $this->schedule_args[ $name ] ?? null,
        };
    }

    protected function get_default_schedule_group(): string {
        if ( ! isset( $this->hook ) && ! ( $this instanceof Is_Unique ) ) {
            return 'xwc_job';
        }

        return '';
    }

    public function __isset( $name ) {
        return isset( $this->$name ) || isset( $this->schedule_args[ $name ] );
    }

    public function __set( $name, $value ) {
        if ( null === $value ) {
            return;
        }

        $this->schedule_args[ $name ] = $value;
    }

    public function __get( $name ) {
        return $this->$name ?? $this->get_default_schedule_arg( $name );
    }

    public function with_hook( ?string $hook ): static {
        if ( $hook ) {
            $this->hook = $hook;
        }

        return $this;
    }

    public function with_group( ?string $group ): static {
        if ( $group ) {
            $this->group = $group;
        }

        return $this;
    }

    public function with_meta( array $meta ): static {
        $this->job_meta = $meta;

        return $this;
    }

    public function get_hook(): string {
        $hook = \xwc_format_job_hook( $this->hook ?? static::class, );
        $ext  = '';

        if ( $this instanceof Is_Unique ) {
            $ext = \is_bool( $this->unique_id() ) ? '' : '_' . $this->unique_id();
        }

        return $hook . $ext;
    }

    public function get_group(): string {
        return match ( true ) {
            isset( $this->group ) => $this->group,
            isset( $this->hook )  => '',
            default               => 'xwc_job',
        };
    }

    public function get_meta(): array {
        return $this->job_meta ?? array();
    }

    public function is_unique(): bool {
        return $this instanceof Is_Unique || isset( $this->unique ) && $this->unique;
    }

    protected function has_hook(): bool {
        return isset( $this->hook ) && $this->hook;
    }
}
