<?php //phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

use Laravel\SerializableClosure\SerializableClosure;

/**
 * Serialize a closure.
 *
 * @param  Closure|string $what The closure to serialize or the serialized closure.
 * @return string               The serialized closure.
 */
function xwc_serialize_closure( Closure|string $what ): string {
    return serialize( new SerializableClosure( $what ) );
}

/**
 * Unserialize a closure.
 *
 * @param  string|Closure $what The serialized closure or the closure itself.
 * @return Closure              The closure.
 */
function xwc_unserialize_closure( string|Closure $what ): Closure {
    return unserialize( $what )->getClosure();
}

/**
 * Get the hook name for a target.
 *
 * @param  string|object $target The target to get the hook for.
 * @param  string        $prefix The prefix to use. Default is 'xwc'.
 * @return string
 */
function xwc_format_job_hook( string|object $target, string $prefix = 'xwc' ): string {
    $prefix = $prefix ? rtrim( $prefix, '_' ) . '_' : '';
    $target = is_object( $target ) ? $target::class : $target;
    $hook   = \strtolower( \basename( \str_replace( '\\', '/', $target ) ) );

    return class_exists( $target ) ? $prefix . $hook : $hook;
}
