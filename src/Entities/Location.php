<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

use Tbp\WP\Plugin\AcfFields\Helpers\LocationInterface;
use Tbp\WP\Plugin\AcfFields\Helpers\LocationTrait;

class Location
    extends
    \WPGeonames\Entities\Location
    implements
    LocationInterface
{

    use LocationTrait;

// protected properties

    /**
     * @var \Tbp\WP\Plugin\AcfFields\Entities\Country
     */
    public static $_countryClass = Country::class;

    /**
     * @var \WPGeonames\Entities\Timezone
     */
    public static $_timezoneClass = Timezone::class;

}
