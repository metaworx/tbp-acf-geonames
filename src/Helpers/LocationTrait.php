<?php

namespace Tbp\WP\Plugin\AcfFields\Helpers;

use DateTimeInterface;
use Tbp\WP\Plugin\AcfFields\Entities\DateTime;
use const Tbp\WP\Plugin\Config\LANGUAGE_CODE;

trait LocationTrait
{

    /**
     * @param $dateTimeString
     *
     * @return \Tbp\WP\Plugin\AcfFields\Entities\DateTime
     * @throws \Exception
     */
    public function createDateTime( $dateTimeString ): DateTime
    {

        return new DateTime( $dateTimeString, $this );
    }


    /**
     * @param         $selector
     * @param  false  $post_id
     *
     * @return \Tbp\WP\Plugin\AcfFields\Entities\DateTime|null
     * @throws \Exception
     */
    public function createDateTimeFromField(
        $selector,
        $post_id = false
    ): ?DateTime {

        $dateTime = get_field( $selector, $post_id );

        if ( ! $dateTime )
        {
            return null;
        }

        return $this->createDateTime( $dateTime );
    }


    /**
     * @param  \DateTimeInterface  $dateTime
     * @param  string              $format
     * @param  null                $locale
     *
     * @return false|string
     */
    public function format(
        DateTimeInterface $dateTime,
        string            $format,
                          $locale = null
    ) {

        return parent::format( $dateTime, $format, $locale ?? static::currentLocale() );
    }


    public static function currentLocale()
    {

        /* @var \SitePress $sitepress */
        global $sitepress;

        static $wpml_locale = 0;

        if ( $wpml_locale === 0 )
        {
            $wpml_locale = $sitepress->get_locale( LANGUAGE_CODE );
        }

        return $wpml_locale;
    }

}
