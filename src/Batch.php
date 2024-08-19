<?php

namespace XWC\Queue;

class Batch {
    /**
     * The batch name.
     *
     * @var string
     */
    public $name = '';

    /**
     * The jobs that belong to the batch.
     *
     * @var array
     */
    public array $jobs;

	/**
		* The batch options.
		*
		* @var array
		*/
    public array $options = array();

    public function __construct( ...$jobs ) {
        $this->jobs = $jobs;
    }

    public function add( iterable|object $jobs ): static {
        if ( ! \is_array( $jobs ) ) {
            $jobs = array( $jobs );
        }

        foreach ( $jobs as $job ) {
            $this->jobs[] = $job;
        }

        return $this;
    }
}
