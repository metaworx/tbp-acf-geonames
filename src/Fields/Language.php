<?php
/** @noinspection PhpUnnecessaryLocalVariableInspection */

namespace Tbp\WP\Plugin\AcfFields\Fields;

use Locale;
use Tbp\WP\Plugin\AcfFields\Entities\Language as LanguagePost;
use Tbp\WP\Plugin\AcfFields\Entities\Location;
use Tbp\WP\Plugin\AcfFields\FieldTypes\FieldRelational;
use WPGeonames\Entities\Country;

class Language
    extends
    FieldRelational
{

// constants
    public const LABEL = 'Languages';
    public const NAME  = 'language';


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
        *  var message = acf._e('language', 'error');
        */
        $this->l10n = [
            'error' => __( 'Error! Please enter a higher value', 'tbp-acf-fields' ),
        ];

        // extra
        add_action(
            'wp_ajax_acf/fields/language/query',
            [
                $this,
                'ajax_query',
            ]
        );

        add_action(
            'wp_ajax_nopriv_acf/fields/language/query',
            [
                $this,
                'ajax_query',
            ]
        );

        $this->filterBase = "acf/fields/language/filter/name=";
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
            'filters'        => static::getFilterDefinitions(),
            'options'        => &$options,
            'filter'         => null,
            'filterSettings' => null,
        ];

        // post_type
        $args['post_type'] = 'language';

        // filters
        while ( ( $context->filter = key( $context->filters ) )
            && ( $context->filterSettings = array_shift(
                $context->filters
            ) ) )
        {

            $args = apply_filters( "acf/fields/language/filter/name={$context->filter}", $args, $context );
        }

        $args = apply_filters( 'acf/fields/language/query', $args, $field, $options );
        $args = apply_filters( 'acf/fields/language/query/name=' . $field['name'], $args, $field );
        $args = apply_filters( 'acf/fields/language/query/key=' . $field['key'], $args, $field, $options );

        // get posts grouped by post type
        $languages = LanguagePost::load( [], $args );

        // bail early if no posts
        if ( empty( $languages ) )
        {
            return false;
        }

        $results = [];

        // loop
        foreach ( $languages as $language )
        {
            $data = [
                'id'   => $language->getId(),
                'text' => $language->getCaption(),
            ];

            // append to $results
            $results[] = $data;

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


    public function getFilterDefinition(): array
    {

        return $this->addFilterCallbacks(
            [
                // search
                $this->getFilterDefinitionSearch(),

            ]
        );
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
        $field  = $source->field;

        if ( $source->type !== 'language' )
        {
            return $rows;
        }

        $default                        = $params['defaults'];
        $default['facet_value']         = 0;
        $default['facet_display_value'] = 'N/A';

        if ( empty( $field['value'] ) )
        {
            $rows[] = $default;

            return $rows;
        }

        $languages = LanguagePost::load( (array) $field['value'] );

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

        if ( $source->property === 'country' && ! empty( $args['values'] ) )
        {

            array_walk(
                $args['values'],
                static function (
                    &$val,
                    $key
                ) {

                    if ( $val['facet_value'] == 0 )
                    {
                        return;
                    }

                    $display_value = null;

                    // WPML integration
                    if ( defined( 'ICL_LANGUAGE_CODE' ) )
                    {
                        $display_value = Locale::getDisplayRegion(
                            '-' . $val['facet_display_value'],
                            ICL_LANGUAGE_CODE
                        );
                    }

                    $val['facet_display_value'] = $display_value ?? Country::load( $val['facet_display_value'] )
                                                                           ->getName()
                    ;

                }
            );
        }

        return $args;
    }


    /**
     *  field_group_admin_enqueue_scripts()
     *
     *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
     *  Use this action to add CSS + JavaScript to assist your render_field_options() action.
     *
     * @see            http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
     * @method-type    action (admin_enqueue_scripts)
     * @since          3.6
     * @date           23/01/13
     */
    public function field_group_admin_enqueue_scripts(): void
    {

    }


    /**
     *  field_group_admin_head()
     *
     *  This action is called in the admin_head action on the edit screen where your field is edited.
     *  Use this action to add CSS and JavaScript to assist your render_field_options() action.
     *
     * @see            http://codex.wordpress.org/Plugin_API/Action_Reference/admin_head
     * @method-type    action (admin_head)
     * @since          3.6
     * @date           23/01/13
     */
    public function field_group_admin_head(): void
    {

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
     * @param  mixed  $value    the value which was loaded from the database
     * @param  int    $post_id  the $post_id from which the value was loaded
     * @param  array  $field    the field array holding all the field options
     *
     * @return mixed the modified $value
     *
     * @noinspection   PhpUnusedParameterInspection
     */

    public function format_value(
        $value,
        int $post_id,
        array $field
    ) {

        // bail early if no value
        if ( empty( $value ) || ! array_key_exists( 'return_format', $field ) )
        {

            return $value;
        }

        /** @noinspection DegradedSwitchInspection */
        switch ( $field['return_format'] )
        {
        case 'object':
            $value = Location::load( $value );
        }

        // return
        return $value;
    }


    /**
     *  input_admin_enqueue_scripts()
     *
     *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
     *  Use this action to add CSS + JavaScript to assist your render_field() action.
     *
     * @method-type    action (admin_enqueue_scripts)
     * @since          3.6
     * @date           23/01/13
     */
    public function input_admin_enqueue_scripts(): void
    {

        // vars
        $url     = $this->settings['url'];
        $version = $this->settings['version'];

        // register & include JS
        wp_register_script( 'tbp-acf-fields', "{$url}assets/js/input.js", [ 'acf-input' ], $version );
        wp_enqueue_script( 'tbp-acf-fields' );

        // register & include CSS
        wp_register_style( 'tbp-acf-fields', "{$url}assets/css/input.css", [ 'acf-input' ], $version );
        wp_enqueue_style( 'tbp-acf-fields' );
    }


    /**
     *  input_admin_footer()
     *
     *  This action is called in the admin_footer action on the edit screen where your field is created.
     *  Use this action to add CSS and JavaScript to assist your render_field() action.
     *
     * @method-type    action (admin_footer)
     * @since          3.6
     * @date           23/01/13
     */
    public function input_admin_footer(): void
    {

    }


    /**
     *  input_admin_head()
     *
     *  This action is called in the admin_head action on the edit screen where your field is created.
     *  Use this action to add CSS and JavaScript to assist your render_field() action.
     *
     * @see            http://codex.wordpress.org/Plugin_API/Action_Reference/admin_head
     * @method-type    action (admin_head)
     * @since          3.6
     * @date           23/01/13
     */
    public function input_admin_head(): void
    {

    }


    /**
     *  load_field()
     *
     *  This filter is applied to the $field after it is loaded from the database
     *
     * @method-type    filter
     * @date           23/01/2013
     * @since          3.6.0
     *
     * @param  array  $field  (the field array holding all the field options
     *
     * @return array         $field
     */
    public function load_field( array $field ): array
    {

        return $field;
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
     * @noinspection   PhpUnusedParameterInspection
     */
    public function load_value(
        $value,
        $post_id,
        array $field
    ) {

        return $value;
    }


    protected function render_field_values(
        array &$field,
        callable $render
    ): void {

        if ( ! empty( $field['value'] ) )
        {

            // get language posts
            $languages = LanguagePost::load( $field['value'] );

            // loop
            /** @var LanguagePost $language */
            foreach ( $languages as $language )
            {
                $dataId  = $language->getId();
                $caption = $language->getCaption();
                $render( $dataId, $caption );
            }
        }
    }


    /**
     *  update_field()
     *
     *  This filter is applied to the $field before it is saved to the database
     *
     * @method-type    filter
     * @date           23/01/2013
     * @since          3.6.0
     *
     * @param  array  $field  the field array holding all the field options
     *
     * @return  array       $field
     */
    public function update_field( array $field ): array
    {

        return $field;
    }


    /**
     *  update_value()
     *
     *  This filter is applied to the $value before it is saved in the db
     *
     * @method-type    filter
     * @since          3.6
     * @date           23/01/13
     *
     * @param  mixed       $value    the value found in the database
     * @param  int|string  $post_id  the $post_id from which the value was loaded
     * @param  array       $field    the field array holding all the field options
     *
     * @return   mixed      $value
     * @noinspection   PhpUnusedParameterInspection
     */
    public function update_value(
        $value,
        $post_id,
        array $field
    ) {

        $value = LanguagePost::translateLanguageIds( $value, true );

        return $value;
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
