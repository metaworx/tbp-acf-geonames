<?php
/** @noinspection AutoloadingIssuesInspection */

namespace wpai_acf_add_on\acf\fields;

use Tbp\WP\Plugin\AcfFields\Fields\Language;
use Tbp\WP\Plugin\AcfFields\Integration\WPAI\TbpWpaiField;

class FieldTbpLanguage
    extends
    TbpWpaiField
{

//  public properties

    /**
     *  Field type key
     */
    public $type = Language::NAME;

}