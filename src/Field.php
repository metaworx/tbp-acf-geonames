<?php

namespace Tbp\WP\Plugin\AcfFields;

use acf_field;
use Tbp\WP\Plugin\AcfFields\Helpers\FieldTrait;
use Tbp\WP\Plugin\AcfFields\Integration\FacetWP;

abstract class Field
    extends
    acf_field
{

    use FieldTrait;

// constants
    public const CATEGORY = 'basic';
    public const LABEL    = 'TBP Field';
    public const NAME     = 'tbp_field';

// protected properties

    /** @var static */
    protected static $instance = [];

    protected $filters;
    protected $fieldSettings;


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
     * Add ACF fields to the Data Sources dropdown
     *
     * @param                                  $sources
     * @param  \Tbp\WP\Plugin\AcfFields\Field  $field
     * @param  array                           $acfFields
     *
     * @return array
     * @noinspection PhpUnusedParameterInspection
     */
    public function facetwpFacetSources(
        $sources,
        Field $field,
        array $acfFields
    ): array {

        array_walk(
        /**
         * @param  array   $field
         * @param  string  $key
         */
            $acfFields,
            static function (
                $field,
                $key
            ) use
            (
                &
                $sources
            )
            {

                // get information from the current source
                $field_id    = $field['hierarchy'];
                $field_label = $sources['acf']['choices']["acf/$field_id"] ?? null;

                if ( $field_label === null )
                {
                    return;
                }

                // remove the native acf entry
                unset ( $sources['acf']['choices']["acf/$field_id"] );

                // re-insert as our own entry

                $field_id = sprintf(
                    '%s/%s/%s/%s',
                    FacetWP::SOURCE_IDENTIFIER,
                    static::NAME,
                    'name',
                    $field['hierarchy']
                );

                $sources['acf']['choices'][ $field_id ] = $field_label;
            }
        );

        return $sources;
    }


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
