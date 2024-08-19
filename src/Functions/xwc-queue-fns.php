<?php //phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

use Laravel\SerializableClosure\SerializableClosure;
use XWC\Queue\Dispatcher;
use XWC\Queue\Interfaces\Can_Dispatch;

function xwc_event( Can_Dispatch $job ) {
    return Dispatcher::instance()->dispatch_to_executor( $job );
}

function xwc_serialize_closure( Closure $closure ) {
    return serialize( new SerializableClosure( $closure ) );
}

function xwc_unserialize_closure( string $serialized_closure ) {
    return unserialize( $serialized_closure )->getClosure();
}
