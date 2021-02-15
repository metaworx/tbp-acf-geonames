<?php
/** @noinspection PhpUnnecessaryLocalVariableInspection */

namespace Tbp\WP\Plugin\AcfFields\Fields;

use acf_field_relationship;
use Tbp\WP\Plugin\AcfFields\FieldInterface;
use Tbp\WP\Plugin\AcfFields\FieldTypes\FieldRelational;
use Tbp\WP\Plugin\AcfFields\Helpers\FieldTrait;
use Tbp\WP\Plugin\AcfFields\Helpers\RelationalTrait;
use Tbp\WP\Plugin\AcfFields\Helpers\Utils;

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

    use RelationalTrait
    {
        format_value as private _RelationalTrait_format_value;
    }
    use FieldTrait
    {
        __construct as private _FieldTrait__construct;
        load_value as private _FieldTrait_load_value;
        update_value as private _FieldTrait_update_value;
        normalize_value as private _FieldTrait_normalize_value;
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
            "tbp-acf-fields/facet/index/data/field/type=" . static::NAME,
            [
                $this,
                'facetwpIndexerRowData',
            ],
            10,
            2
        );

        add_filter(
            "tbp-acf-fields/facet/render/field/type=" . static::NAME,
            [
                $this,
                'facetwpRender',
            ],
            10,
            2
        );
    }


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


    public function get_ajax_query(
        $options = [],
        array &$field = null
    ) {

        if ( $field === null )
        {
            // load field
            $field = acf_get_field( $options['field_key'] ?? '' );
        }

        if ( $field['meta_query'] ?? false )
        {
            // allocate pointer
            $addMetaQuery = null;

            $addMetaQuery = function (
                $args,
                $origField,
                $post_id
            ) use
            (
                &
                $field,
                &
                $addMetaQuery
            )
            {

                if ( ! empty( $args['post__in'] ) )
                {
                    return $args;
                }

                $args['meta_query'] = $field['meta_query'];

                remove_filter( 'acf/fields/relationship/query/key=' . $field['key'], $addMetaQuery, 11 );

                return $args;
            };

            add_filter( 'acf/fields/relationship/query/key=' . $field['key'], $addMetaQuery, 11, 3 );
        }

        ///
        //	$data = array(
        //		'text'		=> $group_title,
        //		'children'	=> array()
        //	);
        //
        // $response = array(
        //			'results'	=> $data,
        //			'limit'		=> $args['posts_per_page']
        //		);
        $response = parent::get_ajax_query( $options );

        switch ( $field['storage_format'] ?? $this->getFieldSettingsDefinition()['storage_format']['default'] )
        {
        case 'ID':

            return $response;

        case 'csv':
        case ',csv,':
        case 'post_name':
            array_walk(
                $response['results'],
                function (
                    &$row
                ) {

                    if ( is_numeric( $row['id'] )
                        && ( $post = \get_post( $row['id'] ) )
                        && $post->post_name )
                    {
                        $row['id'] = $post->post_name;
                    }
                }
            );

            // make sure empty value (e.g resulting from ",csv," format)
            $response['results'] = array_filter( $response['results'] );

            return $response;

        default:
            throw $this->new_exception_for_invalid_storage_type( $field );
        }
    }


    public function ajax_query_helper()
    {

        return $this->get_ajax_query( $_POST );
    }


    /**
     * Add ACF fields to the Data Sources dropdown
     *
     * This function is not called directly by FacetWP, but from
     * \Tbp\WP\Plugin\AcfFields\Integration\FacetWP::facetwp_indexer_row_data
     *
     * @param  array  $rows
     * @param  array  $params
     *
     * @return array
     */
    public function &facetwpIndexerRowData(
        array $rows,
        array $params
    ): array {

        $source = $params['source'];

        if ( $source->type !== static::NAME )
        {
            return $rows;
        }

        $field                          = $source->field;
        $default                        = $params['defaults'];
        $default['facet_value']         = 0;
        $default['facet_display_value'] = 'N/A';

        if ( empty( $field['value'] ) )
        {
            $params['defaults'] = $default;

            $add = apply_filters(
                "tbp-acf-fields/facet/index/row/empty/type=" . static::NAME,
                $default,
                $params
            );

            if ( ! empty( $add ) )
            {
                $rows[] = $add;
            }

            return $rows;
        }

        try
        {
            $post = null;

            if ( is_numeric( $field['value'] ) )
            {
                $post = get_post( $field['value'] );
            }
            else
            {
                foreach ( $field['post_type'] as $post_type )
                {
                    if ( $post = get_page_by_path( $field['value'], OBJECT, $post_type ) )
                    {
                        break;
                    }
                }
            }

            if ( $post )
            {
                $default['facet_value']         = $post->ID;
                $default['facet_display_value'] = $post->{$source->property};
            }
        }
        catch ( \Throwable $e )
        {
            /** @noinspection ForgottenDebugOutputInspection */
            error_log(
                sprintf(
                    'Unable to retrieve post information "%s" for post_id %d',
                    print_r( $field['value'], true ),
                    $default['post_id']
                )
            );

            $default['facet_value']         = - 1;
            $default['facet_display_value'] = 'Error';
        }

        $rows[] = $default;

        return $rows;
    }


    public function format_value(
        $value,
        $object_id,
        $field
    ) {

        $value = $this->_RelationalTrait_format_value( $value, $object_id, $field );

        // bail early if no value
        if ( empty( $value ) )
        {

            return $value;

        }

        // load posts if needed
        switch ( $field['return_format'] )
        {

        case 'object':
            // get posts
            $posts = [];

            foreach ( $field['post_type'] as $post_type )
            {
                $posts = Utils::getPosts( $value, $post_type, null, null, null, $posts );
            }

            $value = is_array( $value )
                ? $posts
                : reset( $posts );
        }

        // return
        return $value;
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

        $field['storage_format'] = $field['storage_format']
            ?? acf_get_local_field( $field['key'] )['storage_format']
            ?? $this->getFieldSettingsDefinition()['storage_format']['default'];

        if ( in_array(
            $field['storage_format'],
            [
                'csv',
                ',csv,',
            ],
            true
        ) )
        {
            if ( ! empty( $value ) )
            {
                $value = array_filter( explode( ',', $value ) );
            }

            $field['storage_format'] = 'post_name';
        }

        $value = $this->_FieldTrait_load_value( $value, $post_id, $field );

        return $value;
    }


    public function new_exception_for_invalid_storage_type( array &$field ): \ErrorException
    {

        return new \ErrorException(
            sprintf(
                'Undefined storage_format "%s" for field "%s/%s" (field type: "%s")',
                $field['storage_format'],
                $field['key'],
                $field['name'],
                $field['type']
            )
        );
    }


    protected function normalize_value(
        &$value,
        array &$field,
        bool $forStorage
    ): bool {

        if ( $this->_FieldTrait_normalize_value( $value, $field, $forStorage ) === null )
        {
            return true;
        }

        switch ( $field['storage_format'] ?? $this->getFieldSettingsDefinition()['storage_format']['default'] )
        {
        case 'ID':
            if ( ! is_numeric( $value ) )
            {
                foreach ( $field['post_type'] as $post_type )
                {
                    /** @var \WP_Post|null $post */
                    $post = get_page_by_path( trim( $value ), OBJECT, $post_type );

                    if ( $post )
                    {
                        $value = $post->ID;

                        return true;
                    }
                }
            }

            return false;

        case 'post_name':
            if ( is_numeric( $value )
                && ( $post = \get_post( $value ) )
                && $post->post_name )
            {
                $value = $post->post_name;

                return true;
            }

            return false;

        default:
            throw $this->new_exception_for_invalid_storage_type( $field );
        }

    }


    protected function render_field_values(
        array &$field,
        callable $render,
        bool $echo = true
    ): string {

        if ( empty( $field['value'] ) && $field['type'] === 'list' )
        {
            return '';
        }

        $addValue = function (
            $args,
            $origField,
            $post_id
        ) use
        (
            &
            $field
        )
        {

            if ( ( $field['value'] ?? '' ) === '' )
            {
                return $args;
            }

            $value = $field['value'];

            if ( is_array( $value ) )
            {
                $value = reset( $value );
            }

            if ( is_numeric( $value ) )
            {
                $args['post__in'] = (array) $field['value'];
            }

            elseif ( is_string( $value ) )
            {
                $args['post_name__in'] = (array) $field['value'];
            }

            return $args;
        };

        $params = [
            'paged'     => 0,
            'post_type' => $field['post_type'],
            'field_key' => $field['key'],
        ];

        if ( $field['ajax'] ?? false )
        {
            if ( empty( $field['value'] ) )
            {
                return '';
            }

            add_filter( 'acf/fields/relationship/query/key=' . $field['key'], $addValue, 10, 3 );
        }

        $data = $this->get_ajax_query( $params, $field );

        remove_filter( 'acf/fields/relationship/query/key=' . $field['key'], $addValue, 10 );

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


    public function update_value(
        $value,
        $post_id,
        $field
    ) {

        $field['storage_format'] = $field['storage_format']
            ?? acf_get_local_field( $field['key'] )['storage_format']
            ?? $this->getFieldSettingsDefinition()['storage_format']['default'];

        if ( $csv = array_search(
            $field['storage_format'],
            [
                1 => 'csv',
                2 => ',csv,',
            ],
            true
        ) )
        {
            $field['storage_format'] = 'post_name';
        }

        $value = $this->_FieldTrait_update_value( $value, $post_id, $field );

        if ( $csv && is_array( $value ) )
        {
            $value = implode( ',', $value );

            if ( $value !== '' && $csv === 2 )
            {
                $value = ",$value,";
            }
        }

        return $value;
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
