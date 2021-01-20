<?php
/** @noinspection AutoloadingIssuesInspection */

namespace wpai_acf_add_on\acf\fields;

use Tbp\WP\Plugin\AcfFields\Entities\Country;
use Tbp\WP\Plugin\AcfFields\Entities\Location;
use Tbp\WP\Plugin\AcfFields\Fields\Geoname;
use Tbp\WP\Plugin\AcfFields\Integration\WPAI\TbpWpaiField;
use WPGeonames\Core;
use WPGeonames\Query\Status;

class FieldTbpGeoname
    extends
    TbpWpaiField
{

//  public properties

    /**
     *  Field type key
     */
    public $type = Geoname::NAME;


    public static function getGeonameByLocation(
        $country,
        $location
    ) {

        if ( empty( $location ) )
        {
            return null;
        }

        // vars
        $args = [];

        // paged
        $args['maxRows']       = 20;
        $args['paged']         = 1;
        $args['q']             = $location;
        $args['feature_class'] = array_keys( Core::FEATURE_FILTERS['habitationOnly'] );
        $args['feature_code']  = array_reduce(
            Core::FEATURE_FILTERS['habitationOnly'],
            static function (
                $carry,
                $item
            ) {

                return $carry + $item;
            },
            []
        );

        if (
            ! empty( $country )
            && ( $countryId = static::getGeonameIdByCountryName( $country ) )
            && ( $countryObject = Country::load( $countryId ) )
        )
        {
            $countryCode      = $countryObject->getIso2();
            $args ['country'] = $countryCode;
        }

        // get locations grouped by top most ancestor
        $searchResult = Core::getLiveSearch( $args, Location::class );

        if ( $searchResult instanceof Status
            && array_key_exists( 'q', $searchResult->result )
            && ! empty( $searchResult->result['q'] ) )
        {
            $location = reset( $searchResult->result['q'] );

            return $location->getGeonameId();
        }

        return null;
    }


    public static function getGeonameIdByCountryName( $country )
    {

        /** @var \WPGeonames\WpDb */
        global $wpdb;

        if ( empty( $country ) )
        {
            return null;
        }

        $country = html_entity_decode( $country );
        $country = $wpdb->prepare( '%s', $country );
        $country = trim( $country, "'" );

        $sql = <<<SQL
SELECT
       geoname_id
        -- , name
        --  , ascii_name
        -- , alternate_names
        -- ,IF( `name` = '$country' , 0 , 1 ) a
        -- ,IF( `alternate_names` LIKE '%"$country"%' , 0 , 1 ) b
        -- ,IF( `ascii_name` = '%$country%' , 0 , 1 ) c
        -- ,IF( `alternate_names` LIKE '%$country%' , 0 , 1 ) d

FROM
`wp_geonames_locations_cache`

WHERE
      `feature_class` = 'A'
  AND feature_code IN (
        'PCL',
        'PCLD',
        'PCLF',
        'PCLI',
        'PCLIX',
        'PCLS'
        )
AND (
    `name` = '$country'
OR  `ascii_name` LIKE '%$country%'
OR  `alternate_names` LIKE '%$country%'
)

ORDER BY 
     IF( `name` = '$country' , 0 , 1 )
    ,IF( `alternate_names` LIKE '%"$country"%' , 0 , 1 )
    ,IF( `ascii_name` = '%$country%' , 0 , 1 )
    ,IF( `alternate_names` LIKE '%$country%' , 0 , 1 )

LIMIT 1
SQL;

        $sql = $wpdb::replaceTablePrefix( $sql );

        $geonameId = $wpdb->get_var( $sql );

        if ( $geonameId === null )
        {
            ;
        }

        return $geonameId;
    }

}