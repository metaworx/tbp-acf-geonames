<?php

namespace Tbp\WP\Plugin\AcfFields\Helpers;

use DateTimeInterface;
use Tbp\WP\Plugin\AcfFields\Entities\DateTime;

interface LocationInterface
{

    /**
     * @param $dateTimeString
     *
     * @return \Tbp\WP\Plugin\AcfFields\Entities\DateTime
     * @throws \Exception
     */
    public function createDateTime( $dateTimeString ): DateTime;


    /**
     * @param         $selector
     * @param  false  $post_id
     *
     * @return \Tbp\WP\Plugin\AcfFields\Entities\DateTime
     * @throws \Exception
     */
    public function createDateTimeFromField(
        $selector,
        $post_id = false
    ): DateTime;


    /**
     * @param  \DateTimeInterface  $dateTime
     * @param  string              $format
     * @param  null                $locale
     *
     * @return false|string
     */
    public function format(
        DateTimeInterface $dateTime,
        string $format,
        $locale = null
    );


    public static function currentLocale();

}
