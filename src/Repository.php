<?php

namespace XWC\Queue;

use ActionScheduler_Action as AS_Action;
use Carbon\Carbon;
use DateTime;
use XWC\Queue\Interfaces\Can_Schedule;
use XWC_Queue_Definition;
use XWP\Helper\Traits\Singleton;

/**
 * Job repository.
 *
 * @implements \XWC_Queue_Definition
 */
final class Repository {
    use Singleton;

    /**
	 * Queue instance.
	 *
	 * @var XWC_Queue_Definition
	 */
	protected static $queue = null;

    /**
     * Registered job hooks.
     *
     * @var array<string, array<int, bool>>
     */
    protected array $hooks = array();

    protected array $to_save = array();

    /**
	 * Get queue instance.
	 *
	 * @return XWC_Queue_Definition
	 */
	public static function queue(): XWC_Queue_Definition {
        return self::$queue ??= \WC()->queue();
	}

    protected function __construct() {
        $this->load_hook_actions();
        $this->load_own_hooks();
        // \add_action( 'shutdown', $this->save_hooks( ... ), 100, 0 );
    }

    public function save( Can_Schedule $job, array $params ): int {
        $args = $this->parse_action_args( $job, $params );

        return $this->schedule_action( $args );
        // No-op.
    }

    private function schedule_action( array $args ) {
        $action_id = isset( $args['expression'] )
            ? self::queue()->schedule_cron( ...$args )
            : self::queue()->schedule_single( ...$args );

        return $this->register_hook( $args['hook'], $action_id );
    }

    private function load_hook_actions() {
        $hooks = \get_option( 'xwc_job_hooks', array() );

        foreach ( $hooks as $hook => $actions ) {
            $this->hooks[ $hook ] = \array_map( \boolval( ... ), $actions );
        }
    }

    private function load_own_hooks() {
    }

    public function get_hooks() {
        return $this->hooks;
    }

    private function register_hook( string $hook, int $action_id ): int {
        if ( 0 === $action_id ) {
            return 0;
        }

        $this->hooks[ $hook ][ $action_id ] = true;

        return $action_id;
    }

    private function save_hooks() {
        \update_option( 'xwc_job_hooks', \array_filter( $this->hooks ) );
    }

    private function schedule_actions() {
        foreach ( $this->to_save as $args ) {
            $this->schedule_action( $args );
        }
    }

    private function parse_action_args( Can_Schedule $job, array $params ): array {
        $args = array(
            'args'       => array(
                'data' => $job->get_args(),
                'meta' => $this->get_meta_args( $job, $params ),
            ),
            'expression' => $params['expression'] ?? 'once',
            'group'      => $job->get_group(),
            'hook'       => $job->get_hook(),
            'priority'   => 10,
            'timestamp'  => $params['timestamp'] ?? Carbon::now( 'UTC' )->getTimestamp(),
            'unique'     => $job->is_unique(),
        );

        if ( 'once' === $args['expression'] ) {
            unset( $args['expression'] );
        }

        return $args;
    }

    private function get_meta_args( Can_Schedule $job, array $params ): array {
        return array(
            'job'     => $job::class,
            'filters' => \array_map( \xwc_serialize_closure( ... ), $params['filters'] ),
            'rejects' => \array_map( \xwc_serialize_closure( ... ), $params['rejects'] ),
            'retry'   => $params['retry'],
        );
    }

    public function get_existing( Job $job ): bool {
        $search_args = array(
            'args'                  => $job->params(),
            'claimed'               => false,
            'group'                 => $job->group(),
            'hook'                  => $job->hook(),
            'partial_args_matching' => 'like',
            'per_page'              => 1,
            'status'                => 'pending',
        );

        $actions = self::queue()->search( $search_args );
        $action  = \current( $actions );

        return ! $action ? false : $action->get_hook() === $job->hook();
    }

    public function get_blocker( Job $job ) {
        $search_args = array(
            'group'    => $job->group(),
            'order'    => 'DESC',
            'orderby'  => 'date',
            'per_page' => 1,
            'search'   => $job->needs(), // search is used instead of hook to find queued batch creation.
            'status'   => 'pending',
        );

        $blocking = $job->needs() ? self::queue()->search( $search_args ) : array();
        $blocking = \array_filter( $blocking, $this->get_next_action_time( ... ) );

        return $blocking ? \current( $blocking ) : null;
    }

    /**
	 * Get the DateTime for the next scheduled time an action should run.
	 *
	 * @param  AS_Action $action Action.
	 * @return DateTime|null
	 */
	public function get_next_action_time( ?AS_Action $action ): ?DateTime {
        return $action?->get_schedule()?->get_next( new DateTime() ) ?? null;
    }

    public function get_job_hooks(): array {
        return \get_option( 'xwc_job_hooks', array() );
    }

    /**
     * Proxy method calls to the queue instance.
     *
     * @param  string $method Method name.
     * @param  array  $args  Method arguments.
     * @return mixed
     *
     * @throws \BadMethodCallException If the method does not exist.
     */
    public function __call( string $method, array $args ) {
        if ( ! \method_exists( self::queue(), $method ) ) {
            throw new \BadMethodCallException( \esc_html( "Method $method does not exist" ) );
        }

        return self::queue()->$method( ...$args );
    }
}
