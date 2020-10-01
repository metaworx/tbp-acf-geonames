<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

use DateTimeZone;
use ErrorException;

class DateTime
    extends \DateTime
{

// protected properties

    /** @var \Tbp\WP\Plugin\AcfFields\Entities\Location|null */
    protected $location;


    public function __construct(
        $time = 'now',
        $timezoneOrLocation = null
    ) {

        switch ( true )
        {

            /** @noinspection PhpMissingBreakStatementInspection */
        case $timezoneOrLocation instanceof Location:
            $this->location     = $timezoneOrLocation;
            $timezoneOrLocation = $timezoneOrLocation->getTimezone();

            // continue
        case $timezoneOrLocation instanceof DateTimeZone:
            parent::__construct( $time, $timezoneOrLocation );
            break;

        default:
            throw new ErrorException(
                sprintf(
                    'Invalid argument type for second parameter: Must be an instance of %s or %s',
                    DateTimeZone::class,
                    Location::class
                )
            );
        }
    }


    /**
     * @return \Tbp\WP\Plugin\AcfFields\Entities\Location|null
     */
    public function getLocation(): ?Location
    {

        return $this->location;
    }


    /**
     * @param  \Tbp\WP\Plugin\AcfFields\Entities\Location|null  $location
     *
     * @return DateTime
     */
    public function setLocation( ?Location $location ): DateTime
    {

        $this->location = $location;

        return $this;
    }


    public function formatBrowser(
        array $format
        = [
            'year'         => 'numeric',
            'month'        => 'short',
            'day'          => 'numeric',
            'hour'         => 'numeric',
            'minute'       => 'numeric',
            'second'       => 'numeric',
            'timeZoneName' => 'short',
        ],
        $locale = null
    ) {

        /**
         * @var string $format
         * @noinspection CallableParameterUseCaseInTypeContextInspection
         */
        $format = ! empty( $format )
            ? \GuzzleHttp\json_encode( array_filter( $format ) )
            : 'undefined';

        return sprintf(
            '<script type="application/javascript">(function() {const date = new Date(%d000); document.write("" + date.toLocaleString("%s", %s));})();</script>',
            $this->getTimestamp(),
            $locale ?? "default",
            $format
        );
    }


    public function formatIntl(
        string $format,
        $locale = null
    ) {

        return $this->location->format( $this, $format, $locale );
    }

}
