<?php

namespace XWC\Queue;

use XWC_Job_Manager;

#[\Attribute( \Attribute::TARGET_CLASS )]
class Recurring_Job extends Job_Template {
    /**
     * Job options.
     *
     * @var array{
     *   args: array,
     *   enabled: bool,
     *   interval: int,
     *   timestamp: int
     * }
     */
    protected array $opts;

    public function is_recurring(): bool {
        return true;
    }

    public function set_classname( string $classname ): static {
        parent::set_classname( $classname );

        return $this->set_opts();
    }

    public function initialize(): bool {
        $scheduled = \XWC_Job_Manager::instance()->is_scheduled( $this );

        if ( $scheduled && ! $this->opts['enabled'] ) {
            XWC_Job_Manager::queue()->cancel_all( $this->hook, $this->get_args(), $this->group );
        }

        if ( ! $scheduled && $this->opts['enabled'] ) {
            XWC_Job_Manager::queue()->schedule_recurring( \xwp_array_diff_assoc( $this->opts, 'enabled' ) );
        }

        return $this->opts['enabled'];
    }

    protected function set_opts(): static {
        $base = $this->get_needed_recurring_job_opts();
        $defs = $this->get_default_recurring_job_opts();
        $opts = $this->get_process_recurring_job_opts();

        $this->opts = \wp_parse_args( \array_merge( $base, $opts ), $defs );

        return $this;
    }

    public function get_args(): array {
        return $this->opts['args'] ?? array();
    }

    /**
     * Get default arguments for a recurring job.
     *
     * @param  string $action Action name.
     * @return array          Default arguments.
     */
    protected function get_default_recurring_job_opts(): array {
        $args = array(
            'args'      => array(),
            'enabled'   => true,
            'interval'  => 15 * MINUTE_IN_SECONDS,
            'timestamp' => \wc_string_to_timestamp( '+ 15 minutes' ),
        );

        //phpcs:ignore WooCommerce.Commenting
        return \apply_filters( 'xwc_recurring_job_default_args', $args, $this->name, $this->hook );
    }

    protected function get_process_recurring_job_opts(): array {
        $has_args = \method_exists( $this->classname, 'get_opts' );

        return $has_args ? $this->classname::get_opts() : array();
    }

    protected function get_needed_recurring_job_opts(): array {
		return array(
            'group'    => $this->group,
            'hook'     => $this->hook,
            'priority' => $this->priority,
            'unique'   => $this->unique,
		);    }
}
