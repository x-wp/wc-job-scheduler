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
        \add_filter( 'action_scheduler_store_class', $this->change_as_store_class( ... ), 10000, 0 );
        \add_filter( 'woocommerce_queue_class', $this->change_queue_class( ... ), 99, 0 );
    }

    /**
     * Register the integration
     *
     * @return class-string<\XWC_Queue_Definition>
     */
    private function change_queue_class(): string {
        return \XWC_Queue::class;
    }

    /**
     * Change the Action Scheduler store class.
     *
     * @return class-string<\XWC_Action_Store>
     */
    private function change_as_store_class(): string {
        return \XWC_Action_Store::class;
    }
}
