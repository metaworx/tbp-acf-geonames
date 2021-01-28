<?php

namespace Tbp\WP\Plugin\AcfFields;

use acf_field;
use Tbp\WP\Plugin\AcfFields\Helpers\FieldTrait;

abstract class Field
    extends
    acf_field
    implements
    FieldInterface
{

    use FieldTrait;

// constants
    public const CATEGORY = 'basic';
    public const LABEL    = 'TBP Field';
    public const NAME     = 'tbp_field';

// protected properties

    /** @var static */
    protected static $instance = [];


    public function initialize(): void
    {

        /*
        *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
        *  var message = acf._e('geoname', 'error');
        */
        $this->l10n = [
            'error' => __( 'Error! Please enter a higher value', 'tbp-acf-fields' ),
        ];

        // extra
        add_action(
            sprintf( 'wp_ajax_acf/fields/%s/query', static::NAME ),
            [
                $this,
                'ajax_query',
            ]
        );

        add_action(
            sprintf( 'wp_ajax_nopriv_acf/fields/%s/query', static::NAME ),
            [
                $this,
                'ajax_query',
            ]
        );

        $this->filterBase = sprintf( 'acf/fields/%s/filter/name=', static::NAME );

    }


    /**
     *  ajax_query_helper
     *
     *  description
     *
     * @date      2020-09-09
     *
     * @return   mixed
     */
    abstract protected function ajax_query_helper();


    /**
     *  render_field()
     *
     *  Create the HTML interface for your field
     *
     * @since          3.6
     * @date           23/01/13
     *
     * @param    $field  (array) the $field being rendered
     *
     * @method-type    action
     */
    public function render_field( $field )
    {
    }

}
