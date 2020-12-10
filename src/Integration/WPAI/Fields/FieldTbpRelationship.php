<?php
/** @noinspection AutoloadingIssuesInspection */

namespace wpai_acf_add_on\acf\fields;

use Tbp\WP\Plugin\AcfFields\Fields\Relationship;
use Tbp\WP\Plugin\AcfFields\Integration\WPAI\TbpWpaiField;

class FieldTbpRelationship
    extends
    TbpWpaiField
{

//  public properties

    /**
     *  Field type key
     */
    public $type = Relationship::NAME;

}