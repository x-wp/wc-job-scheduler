<?php


namespace XWC\Queue;

final class Initializer {
    /**
     * Instance of the Initializer
     *
     * @var ?Initializer
     */
    private static ?Initializer $instance = null;

    public static function run(): void {
        self::$instance ??= new self();
    }

    /**
     * Checks if the Integration has been initialized
     *
     * @return bool
     */
    public static function initialized(): bool {
        return null !== self::$instance;
    }

    private function __construct() {
        \add_filter( 'woocommerce_queue_class', $this->change_queue_class( ... ), 99, 0 );
        // \add_action( 'init', Dispatcher::instance( ... ), 0 );
    }

    /**
     * Register the integration
     *
     * @param  class-string<XWC_Queue_Definition> $queue_class The queue classname.
     * @return array
     */
    private function change_queue_class(): string {
        return \XWC_Queue::class;
    }
}
