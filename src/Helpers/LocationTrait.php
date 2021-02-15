<?php

namespace Tbp\WP\Plugin\AcfFields\Helpers;

use DateTimeInterface;
use Tbp\WP\Plugin\AcfFields\Entities\DateTime;
use const Tbp\WP\Plugin\AcfFields\ORIG_POST_ID;

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
     * @return \Tbp\WP\Plugin\AcfFields\Entities\DateTime
     * @throws \Exception
     */
    public function createDateTimeFromField(
        $selector,
        $post_id = false
    ): DateTime {

        return $this->createDateTime( get_field( $selector, $post_id ) );
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
        string $format,
        $locale = null
    ) {

        return parent::format( $dateTime, $format, $locale ?? static::currentLocale() );
    }


    public static function currentLocale()
    {

        static $wpml_locale = 0;

        if ( $wpml_locale === 0 )
        {
            /**
             * test WPML info
             *
             * @see https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/#hook-605645
             * @var array
             */
            $wpml = apply_filters( 'wpml_post_language_details', null, ORIG_POST_ID );

            /**
             * array (
             * 'language_code' => 'en',
             * 'locale' => 'en_US',
             * 'text_direction' => false,
             * 'display_name' => 'English',
             * 'native_name' => 'English',
             * 'different_language' => false,
             * )
             */

            $wpml_locale = $wpml
                ? ( $wpml['locale']
                    ?: null )
                : null;
        }

        return $wpml_locale;
    }

}
