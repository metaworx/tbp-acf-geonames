<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

use DateTimeInterface;

class Location
    extends \WPGeonames\Entities\Location
{
    // protected properties
    protected static $timezoneClass = Timezone::class;


    /**
     * @param $dateTimeString
     *
     * @return \Tbp\WP\Plugin\AcfFields\Entities\DateTime
     * @throws \Exception
     */
    public function createDateTime($dateTimeString)
    {

        return new DateTime($dateTimeString, $this);
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
    ) {

        return $this->createDateTime(get_field($selector, $post_id));
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

        return parent::format($dateTime, $format, $locale ?? static::currentLocale());
    }


    public static function currentLocale()
    {

        static $wpml_locale = false;

        if ($wpml_locale === false)
        {
            /**
             * test WPML info
             *
             * @see https://wpml.org/documentation/support/wpml-coding-api/wpml-hooks-reference/#hook-605645
             * @var array
             */
            $wpml = apply_filters('wpml_post_language_details', null);

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
                ? $wpml['locale']
                : null;
        }

    }

}
