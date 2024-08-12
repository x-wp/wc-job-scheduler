<?php

class XWC_Queue extends WC_Action_Queue implements XWC_Queue_Definition {
	public function add( $hook, $args = array(), $group = '', bool $unique = false, int $priority = 10 ) {
        return $this->schedule_single( time(), $hook, $args, $group, $unique, $priority );
    }

    public function schedule_single( $timestamp, $hook, $args = array(), $group = '', bool $unique = false, int $priority = 10 ) {
		return as_schedule_single_action( $timestamp, $hook, $args, $group, $unique, $priority );
	}

    public function schedule_recurring( $timestamp, $interval = null, $hook = '', $args = array(), $group = '', bool $unique = false, int $priority = 10, $interval_in_seconds = null ) {
        $interval = $interval ?? $interval_in_seconds ?? -1;

        if ( '' === $hook || -1 === $interval ) {
            return 0;
        }

        return as_schedule_recurring_action( $timestamp, $interval, $hook, $args, $group, $unique, $priority );
    }
}
