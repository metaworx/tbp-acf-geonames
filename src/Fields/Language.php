<?php
/** @noinspection PhpUnnecessaryLocalVariableInspection */

namespace Tbp\WP\Plugin\AcfFields\Fields;

use Tbp\WP\Plugin\AcfFields\Entities\Language as LanguagePost;
use Tbp\WP\Plugin\AcfFields\Entities\LanguageBase;
use Tbp\WP\Plugin\AcfFields\FieldTypes\FieldRelational;
use Throwable;

class Language
    extends
    FieldRelational
{

// constants
    public const LABEL = 'Languages';
    public const NAME  = 'tbp_language';


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
                'filters'       => [
                    'search',
                    'parent',
                ],
                'elements'      => [],
                'return_format' => 'object',
            ]
        );

        parent::__construct( $settings );

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

        add_action(
            'init',
            [
                LanguagePost::class,
                'registerCustomPostType',
            ]
        );

        add_action(
            'init',
            [
                LanguagePost::class,
                'registerLanguageCustomFields',
            ]
        );

        add_filter(
            "manage_language_posts_columns",
            [
                LanguagePost::class,
                'adminLanguagePostColumns',
            ],
            PHP_INT_MAX
        );

        add_action(
            "manage_language_posts_custom_column",
            [
                LanguagePost::class,
                'adminLanguagePostColumnValues',
            ],
            10,
            2
        );

    }


    public function initialize(): void
    {

        /*
        *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
        *  var message = acf._e('tbp_language', 'error');
        */
        $this->l10n = [
            'error' => __( 'Error! Please enter a higher value', 'tbp-acf-fields' ),
        ];

        // extra
        add_action(
            'wp_ajax_acf/fields/tbp_language/query',
            [
                $this,
                'ajax_query',
            ]
        );

        add_action(
            'wp_ajax_nopriv_acf/fields/tbp_language/query',
            [
                $this,
                'ajax_query',
            ]
        );

        $this->filterBase = "acf/fields/tbp_language/filter/name=";
    }


    /**
     *  getData
     *
     *  This function will return an array of data formatted for use in a select2 AJAX response
     *
     * @date         15/10/2014
     * @since        5.0.9
     *
     * @param  array  $options
     *
     * @return    array|false
     */
    public function getData( $options = [] )
    {

        // load field
        $field = acf_get_field( $options['field_key'] );

        if ( ! $field )
        {
            return false;
        }

        // defaults
        $defaults = [
            'post_id'   => 0,
            'parent'    => 0,
            's'         => '',
            'field_key' => '',
            'paged'     => 1,
            'is_search' => false,
        ];

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $options = wp_parse_args( $options, $defaults );

        // vars
        $args = [];

        // paged
        $args['posts_per_page'] = 20;
        $args['paged']          = (int) $options['paged'];

        $context = (object) [
            'field'          => &$field,
            'filters'        => $this->getFilters(),
            'options'        => &$options,
            'filter'         => null,
            'filterSettings' => null,
        ];

        // post_type
        $args['post_type'] = LanguagePost::POST_TYPE;

        // filters
        while ( ( $context->filter = key( $context->filters ) )
            && ( $context->filterSettings = array_shift(
                $context->filters
            ) ) )
        {

            $args = apply_filters( "acf/fields/tbp_language/filter/name={$context->filter}", $args, $context );
        }

        $args = apply_filters( 'acf/fields/tbp_language/query', $args, $field, $options );
        $args = apply_filters( 'acf/fields/tbp_language/query/name=' . $field['name'], $args, $field );
        $args = apply_filters( 'acf/fields/tbp_language/query/key=' . $field['key'], $args, $field, $options );

        // get posts grouped by post type
        $languages = LanguagePost::load( [], $args );

        // bail early if no posts
        if ( empty( $languages ) )
        {
            return false;
        }

        $results = [];
        $s       = $options['s']
            ?: null;
        $x       = strtolower( $s );

        // loop
        foreach ( $languages as $language )
        {
            $code    = $language->getCode();
            $caption = $language->getCaption();

            if ( $s !== null
                && false === \strpos( $code, $x )
                && false === \stripos( $caption, $x )
            )
            {
                continue;
            }

            $data = [
                'id'   => $code,
                'text' => $caption,
            ];

            // append to $results
            $results[] = $data;

        }

        if ( $s !== null )
        {
            // ToDo: sort by weight
        }

        // vars
        $response = [
            'results' => $results,
            'limit'   => $args['posts_per_page'],
        ];

        // return
        return $response;
    }


    protected function getFieldSettingsDefinition(): array
    {

        $fieldSettings = parent::getFieldSettingsDefinition();

        $fieldSettings['return_format']['choices']['code'] = __( "Language ISO2 Code", 'tbp-acf-fields' );

        return $fieldSettings;
    }


    /**
     * @inheritDoc
     */
    public function ajax_query_helper()
    {

        // get choices
        return $this->getData( $_POST );
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
     * @noinspection OnlyWritesOnParameterInspection
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
            $rows[] = $default;

            return $rows;
        }

        try
        {
            $languages = LanguagePost::load( (array) ( $field['value'] ?? [] ) );

            array_walk(
                $languages,
                static function ( LanguagePost $language ) use
                (
                    $default,
                    &
                    $source,
                    &
                    $rows,
                    &
                    $params
                )
                {

                    $default['facet_value']         = $language->getId();
                    $default['facet_display_value'] = $language->getCode();

                    $rows[] = $default;

                }
            );

        }
        catch ( Throwable $e )
        {
            /** @noinspection ForgottenDebugOutputInspection */
            error_log(
                sprintf(
                    'Invalid language code "%s" for post_id %d',
                    print_r( $field['value'], true ),
                    $default['post_id']
                )
            );

            $default['facet_value']         = - 1;
            $default['facet_display_value'] = 'Error';
            $rows[]                         = $default;
        }

        return $rows;
    }


    /**
     * Add ACF fields to the Data Sources dropdown
     *
     * This function is not called directly by FacetWP, but from
     * \Tbp\WP\Plugin\AcfFields\Integration\FacetWP::facetwp_facet_render_args
     *
     * @see https://facetwp.com/documentation/developers/querying/facetwp_facet_render_args/
     *
     *
     * @param  array   $args    $arguments as in original filter
     * @param  object  $source  Source of facet data
     *
     * @return array
     *
     */
    public function facetwpRender(
        array $args,
        object $source
    ): array {

        if ( $source->type === static::NAME && ! empty( $args['values'] ) )
        {

            array_walk(
                $args['values'],
                static function (
                    &$val,
                    $key
                ) {

                    if ( $val['facet_value'] <= 0 )
                    {
                        $val['facet_display_value'] = __( $val['facet_display_value'], 'tbp-acf-fields' );

                        return;
                    }

                    $display_value = LanguageBase::get( $val['facet_display_value'] ?? '--', true );

                    if ( $display_value !== null )
                    {
                        $display_value = $display_value->getCaption();
                    }

                    $val['facet_display_value'] = $display_value ?? $val['facet_display_value'];

                }
            );
        }

        return $args;
    }


    /**
     * @param  array   $args
     * @param  object  $context
     *
     * @return array
     */
    public function filterSearch(
        array $args,
        object $context
    ): array {

        // search
        if ( $context->options['s'] !== '' )
        {

            // strip slashes (search may be integer)
            $s = wp_unslash( (string) $context->options['s'] );

            // update vars
            $args['s']                     = $s;
            $context->options['is_search'] = true;
        }
        else
        {
            $args['posts_per_page'] = 20;
        }

        return $args;
    }


    /**
     *  format_value()
     *
     *  This filter is applied to the $value after it is loaded from the db and before it is returned to the template
     *
     * @method-type    filter
     * @since          3.6
     * @date           23/01/13
     *
     * @param  mixed       $value      the value which was loaded from the database
     * @param  int|string  $object_id  the $post_id from which the value was loaded or user_$userId for users
     * @param  array       $field      the field array holding all the field options
     *
     * @return mixed the modified $value
     */
    public function format_value(
        $value,
        $object_id,
        $field
    ) {

        // bail early if no value
        if ( empty( $value ) )
        {

            return $value;
        }

        /** @noinspection DegradedSwitchInspection */
        switch ( $field['return_format'] )
        {
        case 'object':
            $value = LanguagePost::load( $value );

        }

        // return
        return parent::format_value( $value, $object_id, $field );
    }


    /**
     * @inheritDoc
     */
    protected function render_field_values(
        array &$field,
        callable $render
    ): void {

        //if ( ! empty( $field['value'] ) )
        //{

        // get language posts
        //$languages = LanguagePost::load( $field['value'] );
        $languages = LanguagePost::load( null );

        // loop
        /** @var LanguagePost $language */
        foreach ( $languages as $language )
        {
            $dataId  = $language->getCode();
            $caption = $language->getCaption();
            $render( $dataId, $caption );
        }
        //}
    }


    /**
     * @param  array          $choices
     * @param  callable       $getter
     * @param  callable|null  $formatter
     *
     * @return array
     */
    public static function &checkChoices(
        array $choices,
        callable $getter,
        $formatter = null
    ): array {

        if ( is_array( $choices ) && is_string( key( $choices ) ) )
        {
            return $choices;
        }

        // get all country codes
        $all = $getter();

        if ( ! empty( $choices ) )
        {
            $choices = array_intersect_key( $all, array_flip( $choices ) );
        }
        else
        {
            $choices = $all;
        }

        array_walk(
            $choices,
            $formatter
                ?: static function (
                &$caption,
                $key
            ) {

                $caption = "$key: $caption";
            }
        );

        return $choices;
    }

}
