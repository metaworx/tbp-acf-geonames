<?php

namespace Tbp\WP\Plugin\AcfFields\FieldTypes;

use Tbp\WP\Plugin\AcfFields\Field;
use Tbp\WP\Plugin\AcfFields\Helpers\RelationalTrait;

abstract class FieldRelational
    extends
    Field
{

    use RelationalTrait;

// constants
    public const CATEGORY = 'relational';

}