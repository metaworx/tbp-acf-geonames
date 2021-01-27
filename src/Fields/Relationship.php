<?php
/** @noinspection PhpUnnecessaryLocalVariableInspection */

namespace Tbp\WP\Plugin\AcfFields\Fields;

use acf_field_relationship;
use Tbp\WP\Plugin\AcfFields\FieldInterface;
use Tbp\WP\Plugin\AcfFields\FieldTypes\FieldRelational;
use Tbp\WP\Plugin\AcfFields\Helpers\FieldTrait;
use Tbp\WP\Plugin\AcfFields\Helpers\RelationalTrait;

if ( ! class_exists( 'acf_field_relationship' ) )
{
    require_once WP_PLUGIN_DIR . '/advanced-custom-fields/includes/fields/class-acf-field-relationship.php';
}


class Relationship
    extends
    acf_field_relationship
    implements
    FieldInterface
{

    use RelationalTrait;
    use FieldTrait
    {
        __construct as private _FieldTrait__construct;
        load_value as private _FieldTrait_load_value;
        RelationalTrait::field_wrapper_attributes insteadof FieldTrait;
    }

// constants

    public const CATEGORY = FieldRelational::CATEGORY;
    public const LABEL    = 'Relationship (TBP extended)';
    public const NAME     = 'tbp_relationship';

// protected properties

    /** @var static */
    protected static $instance = [];

    /*
    *  __construct
    *
    *  This function will setup the field type data
    *
    *  @method-type	function
    *  @date	2020-07-19
    *  @since	5.0.0
    *
    *  @param	n/a
    *  @return	n/a
    */

    /** @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     *
     * @param  array  $settings
     */
    public function __construct( $settings = [] )
    {

        /*
        *  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
        */
        $this->defaults = wp_parse_args(
            $this->defaults,
            [
                'parent'        => null,
                'decedents'     => 1,
                'min'           => 0,
                'max'           => 0,
                'post_type'     => [],
                'taxonomy'      => [],
                'filters'       => [
                    'search',
                    'post_type',
                    'taxonomy',
                ],
                'elements'      => [],
                'return_format' => 'object',
            ]
        );

        $this->_FieldTrait__construct( $settings );

        add_filter(
            "tbp-acf-fields/facet/source/field/name=" . static::NAME,
            [
                $this,
                'facetwpFacetSources',
            ],
            10,
            3
        );

        add_filter(
            "tbp-acf-fields/facet/index/data/type=" . static::NAME,
            [
                $this,
                'facetwpIndexerRowData',
            ],
            10,
            2
        );

        add_filter(
            "tbp-acf-fields/facet/render/type=" . static::NAME,
            [
                $this,
                'facetwpRender',
            ],
            10,
            2
        );
    }


    /*
    *  validate_value
    *
    *  description
    *
    *  @type	function
    *  @date	11/02/2014
    *  @since	5.0.0
    *
    *  @param	$post_id (int)
    *  @return	$post_id (int)
    */

    public function initialize(): void
    {

        /*
        *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
        *  var message = acf._e('language', 'error');
        */
        $this->l10n = [
            'error' => __( 'Error! Please enter a higher value', 'tbp-acf-fields' ),
        ];

        // extra
        add_action(
            'wp_ajax_acf/fields/tbp_relationship/query',
            [
                $this,
                'ajax_query',
            ]
        );

        add_action(
            'wp_ajax_nopriv_acf/fields/tbp_relationship/query',
            [
                $this,
                'ajax_query',
            ]
        );

        $this->filterBase = "acf/fields/tbp_relationship/filter/name=";
    }


    protected function getPostBySlug(
        string $slug,
        array $field
    ): ?\WP_Post {

        foreach ( $field['post_type'] as $post_type )
        {
            /** @var \WP_Post|null $post */
            $post = get_page_by_path( trim( $slug ), OBJECT, $post_type );

            if ( $post )
            {
                return $post;
            }
        }

        return null;
    }


    public function ajax_query_helper()
    {

        /** @noinspection MissUsingParentKeywordInspection */
        return parent::get_ajax_query( $_POST );
    }


    /**
     *  load_value()
     *
     *  This filter is applied to the $value after it is loaded from the db
     *
     * @method-type    filter
     * @since          3.6
     * @date           23/01/13
     *
     * @param  mixed       $value    the value found in the database
     * @param  int|string  $post_id  the $post_id from which the value was loaded
     * @param  array       $field    the field array holding all the field options
     *
     * @return mixed        $value
     */
    public function load_value(
        $value,
        $post_id,
        array $field
    ) {

        $value = $this->_FieldTrait_load_value( $value, $post_id, $field );

        if ( is_string( $value ) && ! is_numeric( $value ) && trim( $value ) !== '' )
        {
            foreach ( $field['post_type'] as $post_type )
            {
                /** @var \WP_Post|null $post */
                $post = get_page_by_path( trim( $value ), OBJECT, $post_type );

                if ( $post )
                {
                    $value = $this->_FieldTrait_load_value( $post->ID, $post_id, $field );

                    return $value;
                }
            }
        }

        return $value;
    }


    protected function render_field_values(
        array &$field,
        callable $render,
        bool $echo = true
    ): string {

        $data = $this->get_ajax_query(
            [
                'paged'     => 0,
                'post_type' => $field['post_type'],
            ]
        );

        if ( $data === false )
        {
            return '';
        }

        $html = '';

        // loop
        foreach ( $data['results'] as $type )
        {
            $dataId  = $type['id'];
            $caption = $type['text'];
            $html    .= $render( $dataId, $caption, $echo );
        }

        return $html;
    }


    function validate_value(
        $valid,
        $value,
        $field,
        $input
    ) {

        // default
        if ( empty( $value ) )
        {

            $value = [];

        }
        elseif ( ! is_array( $value ) )
        {
            $value = (array) $value;
        }

        return parent::validate_value( $valid, $value, $field, $input );
    }

}
