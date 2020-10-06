<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

use Tbp\WP\Plugin\AcfFields\Helpers\LocationInterface;
use Tbp\WP\Plugin\AcfFields\Helpers\LocationTrait;

class Country
    extends
    \WPGeonames\Entities\Country
    implements
    LocationInterface
{

    use LocationTrait;
}
