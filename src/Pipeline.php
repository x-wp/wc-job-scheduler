<?php

namespace XWC\Queue;

use Closure;
use RuntimeException;
use Throwable;

class Pipeline {
    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = array();

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send( $passable ) {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through( $pipes ) {
        $this->pipes = \is_array( $pipes ) ? $pipes : \func_get_args();

        return $this;
    }

    /**
     * Push additional pipes onto the pipeline.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function pipe( $pipes ) {
        \array_push( $this->pipes, ...( \is_array( $pipes ) ? $pipes : \func_get_args() ) );

        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     * @return $this
     */
    public function via( $method ) {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then( Closure $destination ) {
        $pipeline = \array_reduce(
            \array_reverse( $this->pipes() ),
            $this->carry(),
            $this->prepare_destination( $destination ),
        );

        return $pipeline( $this->passable );
    }

    /**
     * Run the pipeline and return the result.
     *
     * @return mixed
     */
    public function then_return() {
        return $this->then(
            static fn( $passable ) => $passable,
        );
    }

    /**
     * Get the final piece of the Closure onion.
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function prepare_destination( Closure $destination ) {
        return function ( $passable ) use ( $destination ) {
            try {
                return $destination( $passable );
            } catch ( \Throwable $e ) {
                return $this->handle_exception( $passable, $e );
            }
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function carry() {
        return fn( $stack, $pipe ) => $this->handle_carry( 'a' );
        // return fn( $stack, $pipe ) => function ( $passable ) use ( $stack, $pipe ) {
        //         try {
        //             if ( \is_callable( $pipe ) ) {
        //                 // If the pipe is a callable, then we will call it directly, but otherwise we
        //                 // will resolve the pipes out of the dependency container and call it with
        //                 // the appropriate method and arguments, returning the results back out.
        //                 return $pipe( $passable, $stack );
        //             }

        //             // If the pipe is already an object we'll just make a callable and pass it to
        //             // the pipe as-is. There is no need to do any extra parsing and formatting
        //             // since the object we're given was already a fully instantiated object.
        //             $parameters = array( $passable, $stack );

        //             $carry = \method_exists( $pipe, $this->method )
        //                             ? $pipe->{$this->method}( ...$parameters )
        //                             : $pipe( ...$parameters );

        //             return $this->handle_carry( $carry );
        //         } catch ( \Throwable $e ) {
        //             return $this->handle_exception( $passable, $e );
        //         }
        //     }
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string  $pipe
     * @return array
     */
    protected function parse_pipe( $pipe ) {
        [ $name, $parameters ] = \array_pad( \explode( ':', $pipe, 2 ), 2, array() );

        if ( \is_string( $parameters ) ) {
            $parameters = \explode( ',', $parameters );
        }

        return array( $name, $parameters );
    }

    /**
     * Get the array of configured pipes.
     *
     * @return array
     */
    protected function pipes() {
        return $this->pipes;
    }

    /**
     * Handle the value returned from each pipe before passing it to the next.
     *
     * @param  mixed  $carry
     * @return mixed
     */
    protected function handle_carry( $carry ) {
        return $carry;
    }

    /**
     * Handle the given exception.
     *
     * @param  mixed  $passable
     * @param  \Throwable  $e
     * @return mixed
     *
     * @throws \Throwable
     */
    protected function handle_exception( $passable, \Throwable $e ) {
        throw $e;
    }
}
