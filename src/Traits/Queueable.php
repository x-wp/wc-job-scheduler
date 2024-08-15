<?php

namespace XWC\Queue\Traits;

trait Queueable {
    /**
	 * Flatten multidimensional arrays to store for scheduling.
	 *
	 * @param array $args Argument array.
	 * @return string
	 */
	public function flatten_args( $args ) {
		$flattened = array();

		foreach ( $args as $arg ) {
			$flattened[] = \is_array( $arg ) ? $this->flatten_args( $arg ) : $arg;
		}

		$string = '[' . \implode( ',', $flattened ) . ']';
		return $string;
	}
}
