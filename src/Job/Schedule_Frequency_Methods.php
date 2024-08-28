<?php

namespace XWC\Scheduler\Job;

use Carbon\Carbon;
use XWC_Schedule;

trait Schedule_Frequency_Methods {
    /**
     * The Cron expression representing the event's frequency.
     *
     * @param  string  $expression
     * @return static
     */
    public function cron( $expression ) {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Schedule the command at a given time.
     *
     * @param  mixed $when
     * @return static
     */
    public function at( mixed $when = null ): static {
        $this->timestamp = $when
            ? Carbon::parse( $when, $this->get_timezone() )->getTimestamp()
            : $this->get_timestamp();

        return $this;
    }

    /**
     * Delay the event by the given amount of time.
     *
     * @param  int    $delay
     * @return static
     */
    public function delay( int $delay ): static {
        $this->timestamp = Carbon::parse( $this->get_timestamp(), $this->get_timezone() )
            ->addSeconds( $delay )
            ->getTimestamp();

        return $this;
    }

    /**
     * Schedule the event to run between start and end time.
     *
     * @param  string  $start_time
     * @param  string  $end_time
     * @return static
     */
    public function between( $start_time, $end_time ) {
        return $this->when( $this->in_time_interval( $start_time, $end_time ) );
    }

    /**
     * Schedule the event to not run between start and end time.
     *
     * @param  string  $start_time
     * @param  string  $end_time
     * @return static
     */
    public function unless_between( $start_time, $end_time ) {
        return $this->skip( $this->in_time_interval( $start_time, $end_time ) );
    }

    /**
     * Schedule the event to run between start and end time.
     *
     * @param  string  $start_time
     * @param  string  $end_time
     * @return \Closure
     */
    private function in_time_interval( $start_time, $end_time ) {
        [ $now, $start_time, $end_time ] = array(
            Carbon::now( $this->get_timezone() ),
            Carbon::parse( $start_time, $this->get_timezone() ),
            Carbon::parse( $end_time, $this->get_timezone() ),
        );

        if ( $end_time->lessThan( $start_time ) ) {
            if ( $start_time->greaterThan( $now ) ) {
                $start_time = $start_time->subDay();
            } else {
                $end_time = $end_time->addDay();
            }
        }

        return static fn() => $now->between( $start_time, $end_time );
    }

    /**
     * Shortcut to schedule the event to run in N number of minutes.
     *
     * @param  int    $num_minutes The number of minutes to wait.
     * @return static
     */
    public function every( int $num_minutes ): static {
        return match ( true ) {
            1 === $num_minutes  => $this->every_minute(),
            2 === $num_minutes  => $this->every_two_minutes(),
            3 === $num_minutes  => $this->every_three_minutes(),
            4 === $num_minutes  => $this->every_four_minutes(),
            5 === $num_minutes  => $this->every_five_minutes(),
            10 === $num_minutes => $this->every_ten_minutes(),
            15 === $num_minutes => $this->every_fifteen_minutes(),
            30 === $num_minutes => $this->every_thirty_minutes(),
            60 === $num_minutes => $this->hourly(),
            60 > $num_minutes   => $this->every( $num_minutes % 60 ),
            default             => $this->cron( "*/{$num_minutes} * * * *" ),
        };
    }

    /**
     * Schedule the event to run every minute.
     *
     * @return static
     */
    public function every_minute() {
        return $this->splice_into( 1, '*' );
    }

    /**
     * Schedule the event to run every two minutes.
     *
     * @return static
     */
    public function every_two_minutes() {
        return $this->splice_into( 1, '*/2' );
    }

    /**
     * Schedule the event to run every three minutes.
     *
     * @return static
     */
    public function every_three_minutes() {
        return $this->splice_into( 1, '*/3' );
    }

    /**
     * Schedule the event to run every four minutes.
     *
     * @return static
     */
    public function every_four_minutes() {
        return $this->splice_into( 1, '*/4' );
    }

    /**
     * Schedule the event to run every five minutes.
     *
     * @return static
     */
    public function every_five_minutes() {
        return $this->splice_into( 1, '*/5' );
    }

    /**
     * Schedule the event to run every ten minutes.
     *
     * @return static
     */
    public function every_ten_minutes() {
        return $this->splice_into( 1, '*/10' );
    }

    /**
     * Schedule the event to run every fifteen minutes.
     *
     * @return static
     */
    public function every_fifteen_minutes() {
        return $this->splice_into( 1, '*/15' );
    }

    /**
     * Schedule the event to run every thirty minutes.
     *
     * @return static
     */
    public function every_thirty_minutes() {
        return $this->splice_into( 1, '0,30' );
    }

    /**
     * Schedule the event to run hourly.
     *
     * @return static
     */
    public function hourly() {
        return $this->splice_into( 1, 0 );
    }

    /**
     * Schedule the event to run hourly at a given offset in the hour.
     *
     * @param  array|string|int  $offset
     * @return static
     */
    public function hourly_at( $offset ) {
        return $this->hourly_schedule( $offset, '*' );
    }

    /**
     * Schedule the event to run every odd hour.
     *
     * @param  array|string|int  $offset
     * @return static
     */
    public function every_odd_hour( $offset = 0 ) {
        return $this->hourly_schedule( $offset, '1-23/2' );
    }

    /**
     * Schedule the event to run every two hours.
     *
     * @param  array|string|int  $offset
     * @return static
     */
    public function every_two_hours( $offset = 0 ) {
        return $this->hourly_schedule( $offset, '*/2' );
    }

    /**
     * Schedule the event to run every three hours.
     *
     * @param  array|string|int  $offset
     * @return static
     */
    public function every_three_hours( $offset = 0 ) {
        return $this->hourly_schedule( $offset, '*/3' );
    }

    /**
     * Schedule the event to run every four hours.
     *
     * @param  array|string|int  $offset
     * @return static
     */
    public function every_four_hours( $offset = 0 ) {
        return $this->hourly_schedule( $offset, '*/4' );
    }

    /**
     * Schedule the event to run every six hours.
     *
     * @param  array|string|int  $offset
     * @return static
     */
    public function every_six_hours( $offset = 0 ) {
        return $this->hourly_schedule( $offset, '*/6' );
    }

    /**
     * Schedule the event to run daily.
     *
     * @return static
     */
    public function daily() {
        return $this->hourly_schedule( 0, 0 );
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * @param  string  $time
     * @return static
     */
    public function daily_at( $time ) {
        $segments = \explode( ':', $time );

        return $this->hourly_schedule(
            2 === \count( $segments ) ? (int) $segments[1] : '0',
            (int) $segments[0],
        );
    }

    /**
     * Schedule the event to run twice daily.
     *
     * @param  int  $first
     * @param  int  $second
     * @return static
     */
    public function twice_daily( $first = 1, $second = 13 ) {
        return $this->twice_daily_at( $first, $second, 0 );
    }

    /**
     * Schedule the event to run twice daily at a given offset.
     *
     * @param  int  $first
     * @param  int  $second
     * @param  int  $offset
     * @return static
     */
    public function twice_daily_at( $first = 1, $second = 13, $offset = 0 ) {
        $hours = $first . ',' . $second;

        return $this->hourly_schedule( $offset, $hours );
    }

    /**
     * Schedule the event to run at the given minutes and hours.
     *
     * @param  array|string|int  $minutes
     * @param  array|string|int  $hours
     * @return static
     */
    protected function hourly_schedule( $minutes, $hours ) {
        $minutes = \is_array( $minutes ) ? \implode( ',', $minutes ) : $minutes;

        $hours = \is_array( $hours ) ? \implode( ',', $hours ) : $hours;

        return $this->splice_into( 1, $minutes )
                    ->splice_into( 2, $hours );
    }

    /**
     * Schedule the event to run only on weekdays.
     *
     * @return static
     */
    public function weekdays() {
        return $this->days( XWC_Schedule::MONDAY . '-' . XWC_Schedule::FRIDAY );
    }

    /**
     * Schedule the event to run only on weekends.
     *
     * @return static
     */
    public function weekends() {
        return $this->days( XWC_Schedule::SATURDAY . ',' . XWC_Schedule::SUNDAY );
    }

    /**
     * Schedule the event to run only on Mondays.
     *
     * @return static
     */
    public function mondays() {
        return $this->days( XWC_Schedule::MONDAY );
    }

    /**
     * Schedule the event to run only on Tuesdays.
     *
     * @return static
     */
    public function tuesdays() {
        return $this->days( XWC_Schedule::TUESDAY );
    }

    /**
     * Schedule the event to run only on Wednesdays.
     *
     * @return static
     */
    public function wednesdays() {
        return $this->days( XWC_Schedule::WEDNESDAY );
    }

    /**
     * Schedule the event to run only on Thursdays.
     *
     * @return static
     */
    public function thursdays() {
        return $this->days( XWC_Schedule::THURSDAY );
    }

    /**
     * Schedule the event to run only on Fridays.
     *
     * @return static
     */
    public function fridays() {
        return $this->days( XWC_Schedule::FRIDAY );
    }

    /**
     * Schedule the event to run only on Saturdays.
     *
     * @return static
     */
    public function saturdays() {
        return $this->days( XWC_Schedule::SATURDAY );
    }

    /**
     * Schedule the event to run only on Sundays.
     *
     * @return static
     */
    public function sundays() {
        return $this->days( XWC_Schedule::SUNDAY );
    }

    /**
     * Schedule the event to run weekly.
     *
     * @return static
     */
    public function weekly() {
        return $this->splice_into( 1, 0 )
                    ->splice_into( 2, 0 )
                    ->splice_into( 5, 0 );
    }

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * @param  array|mixed  $day_of_week
     * @param  string  $time
     * @return static
     */
    public function weekly_on( $day_of_week, $time = '0:0' ) {
        $this->daily_at( $time );

        return $this->days( $day_of_week );
    }

    /**
     * Schedule the event to run monthly.
     *
     * @return static
     */
    public function monthly() {
        return $this->splice_into( 1, 0 )
                    ->splice_into( 2, 0 )
                    ->splice_into( 3, 1 );
    }

    /**
     * Schedule the event to run monthly on a given day and time.
     *
     * @param  int  $day_of_month
     * @param  string  $time
     * @return static
     */
    public function monthly_on( $day_of_month = 1, $time = '0:0' ) {
        $this->daily_at( $time );

        return $this->splice_into( 3, $day_of_month );
    }

    /**
     * Schedule the event to run twice monthly at a given time.
     *
     * @param  int  $first
     * @param  int  $second
     * @param  string  $time
     * @return static
     */
    public function twice_monthly( $first = 1, $second = 16, $time = '0:0' ) {
        $days_of_month = $first . ',' . $second;

        $this->daily_at( $time );

        return $this->splice_into( 3, $days_of_month );
    }

    /**
     * Schedule the event to run on the last day of the month.
     *
     * @param  string  $time
     * @return static
     */
    public function last_day_of_month( $time = '0:0' ) {
        $this->daily_at( $time );

        return $this->splice_into( 3, Carbon::now()->endOfMonth()->day );
    }

    /**
     * Schedule the event to run quarterly.
     *
     * @return static
     */
    public function quarterly() {
        return $this->splice_into( 1, 0 )
                    ->splice_into( 2, 0 )
                    ->splice_into( 3, 1 )
                    ->splice_into( 4, '1-12/3' );
    }

    /**
     * Schedule the event to run quarterly on a given day and time.
     *
     * @param  int  $day_of_qt
     * @param  string  $time
     * @return static
     */
    public function quarterly_on( $day_of_qt = 1, $time = '0:0' ) {
        $this->daily_at( $time );

        return $this->splice_into( 3, $day_of_qt )
                    ->splice_into( 4, '1-12/3' );
    }

    /**
     * Schedule the event to run yearly.
     *
     * @return static
     */
    public function yearly() {
        return $this->splice_into( 1, 0 )
                    ->splice_into( 2, 0 )
                    ->splice_into( 3, 1 )
                    ->splice_into( 4, 1 );
    }

    /**
     * Schedule the event to run yearly on a given month, day, and time.
     *
     * @param  int  $month
     * @param  int|string  $day_of_month
     * @param  string  $time
     * @return static
     */
    public function yearly_on( $month = 1, $day_of_month = 1, $time = '0:0' ) {
        $this->daily_at( $time );

        return $this->splice_into( 3, $day_of_month )
                    ->splice_into( 4, $month );
    }

    /**
     * Set the days of the week the command should run on.
     *
     * @param  array|mixed  $days
     * @return static
     */
    public function days( $days ) {
        $days = \is_array( $days ) ? $days : \func_get_args();

        return $this->splice_into( 5, \implode( ',', $days ) );
    }

    /**
     * Set the timezone the date should be evaluated on.
     *
     * @param  \DateTimeZone|string  $timezone
     * @return static
     */
    public function timezone( $timezone ) {
        $this->timezone = $timezone;

        return $this;
    }

    public function get_timestamp() {
        return $this->timestamp ?? Carbon::now( $this->get_timezone() )->getTimestamp();
    }

    public function get_timezone() {
        return $this->timezone ?? \wp_timezone();
    }

    /**
     * Splice the given value into the given position of the expression.
     *
     * @param  int        $position
     * @param  string|int $value
     * @return static
     */
    protected function splice_into( $position, $value ): static {
        if ( 'once' === $this->expression ) {
            $this->cron( '* * * * *' );
        }

        $segments = \preg_split( '/\s+/', $this->expression );

        $segments[ $position - 1 ] = $value;

        return $this->cron( \implode( ' ', $segments ) );
    }
}
