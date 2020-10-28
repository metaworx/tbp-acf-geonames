<?php
/** @noinspection PhpUnnecessaryLocalVariableInspection */

namespace Tbp\WP\Plugin\AcfFields\Fields;

use ErrorException;
use Locale;
use Tbp\WP\Plugin\AcfFields\Entities\Language as LanguagePost;
use Tbp\WP\Plugin\AcfFields\Entities\Location;
use Tbp\WP\Plugin\AcfFields\Field;
use WPGeonames\Entities\Country;

class Language
    extends
    Field
{

// constants
    public const CATEGORY = 'relational';
    public const LABEL    = 'Languages';
    public const NAME     = 'language';


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

    }


    public function initialize( ?string $forCompatibilityOnly = null ): void
    {

        self::$filters = [

            // search
            'search' => [
                'type'           => 'text',
                'name'           => 'search',
                'caption'        => __( "Search", 'acf' ),
                'placeholder'    => __( "Search...", 'acf' ),
                'data-filter'    => 's',
                'filterCallback' => [
                    $this,
                    'filterSearch',
                ],
            ],

        ];

        self::$fieldSettings = array_column(
            [

                // choice layout
                [
                    'type'         => 'radio',
                    'name'         => 'choice_layout',
                    'label'        => __( 'Layout', 'tbp-acf-fields' ),
                    'instructions' => '',
                    'choices'      => [
                        'list'    => __( "List", 'tbp-acf-fields' ),
                        'fSelect' => __( "Dynamic Selection", 'tbp-acf-fields' ),
                    ],
                    'layout'       => 'vertical',
                ],

                // selection limit
                [
                    'type'         => 'true_false',
                    'name'         => 'multi_select',
                    'label'        => __( 'Select multiple values?', 'acf' ),
                    'instructions' => '',
                    'ui'           => 1,
                    'allow_null'   => 0,
                    'default'      => true,
                ],

                // min
                [
                    'type'              => 'number',
                    'name'              => 'min',
                    'label'             => __( 'Minimum languages', 'tbp-acf-fields' ),
                    'instructions'      => '',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'multi_select',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                    ],
                ],

                // max
                [
                    'type'              => 'number',
                    'name'              => 'max',
                    'label'             => __( 'Maximum languages', 'tbp-acf-fields' ),
                    'instructions'      => '',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'multi_select',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                    ],
                ],

                [
                    'type'              => 'true_false',
                    'name'              => 'replace_selected_value',
                    'label'             => __( 'Replace selected value', 'tbp-acf-fields' ),
                    'instructions'      => __(
                        'If there is only one choice allowed and this setting is set to true, the selected value is automatically replaced. If this setting is set to false, the user has to first remove the current selection.',
                        'tbp-acf-fields'
                    ),
                    'ui'                => 1,
                    'allow_null'        => 0,
                    'default'           => true,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'max',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                        [
                            [
                                'field'    => 'multi_select',
                                'operator' => '==',
                                'value'    => '0',
                            ],
                        ],
                    ],
                ],

                // filters
                [
                    'name'              => 'filters',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'list',
                            ],
                        ],
                    ],
                ],

                // list options

                // filter layout
                [
                    'type'              => 'true_false',
                    'name'              => 'one_filter_per_row',
                    'label'             => __( 'Display one filter per row', 'tbp-acf-fields' ),
                    'ui'                => 1,
                    'allow_null'        => 0,
                    'default'           => false,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'list',
                            ],
                        ],
                    ],
                ],

                [
                    'type'              => 'true_false',
                    'name'              => 'choice_on_new_line',
                    'label'             => __( 'Display selected values on new line', 'tbp-acf-fields' ),
                    'ui'                => 1,
                    'allow_null'        => 0,
                    'default'           => false,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'list',
                            ],
                        ],
                    ],
                ],

                [
                    'type'              => 'true_false',
                    'name'              => 'selection_choices_display_instruction',
                    'label'             => __( 'Display instruction for available choices', 'tbp-acf-fields' ),
                    'ui'                => 1,
                    'allow_null'        => 0,
                    'default'           => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'list',
                            ],
                        ],
                    ],
                ],

                [
                    'type'              => 'text',
                    'name'              => 'selection_choices_instruction_text',
                    'label'             => __( 'Choices instructions', 'tbp-acf-fields' ),
                    'instructions'      => __( 'This text is shown before the field', 'tbp-acf-fields' ),
                    'placeholder'       => __(
                        'Click on one of the entries to add it to the selection.',
                        'tbp-acf-fields'
                    ),
                    'allow_null'        => 1,
                    'default'           => null,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'selection_choices_display_instruction',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                    ],
                ],

                [
                    'type'              => 'true_false',
                    'name'              => 'selection_values_display_instruction',
                    'label'             => __( 'Display instruction for selected values', 'tbp-acf-fields' ),
                    'ui'                => 1,
                    'allow_null'        => 0,
                    'default'           => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'list',
                            ],
                        ],
                    ],
                ],

                [
                    'type'              => 'text',
                    'name'              => 'selection_values_instruction_text',
                    'label'             => __( 'Selection instructions', 'tbp-acf-fields' ),
                    'instructions'      => __( 'This text is shown before the field', 'tbp-acf-fields' ),
                    'placeholder'       => __(
                        'This is/are the current selected values. To remove an entry, click on the minus-symbol at the end of the line.',
                        'tbp-acf-fields'
                    ),
                    'allow_null'        => 1,
                    'default'           => null,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'selection_values_display_instruction',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'list',
                            ],
                        ],
                    ],
                ],

                // return_format
                [
                    'type'         => 'radio',
                    'name'         => 'return_format',
                    'label'        => __( 'Return Format', 'acf' ),
                    'instructions' => '',
                    'choices'      => [
                        'object' => __( "Language Object", 'tbp-acf-fields' ),
                        'id'     => __( "Language ID", 'tbp-acf-fields' ),
                        'code'   => __( "Language ISO2 Code", 'tbp-acf-fields' ),
                    ],
                    'layout'       => 'horizontal',
                ],
            ],
            null,
            'name'
        );

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

        parent::initialize( "acf/fields/language/filter/name=" );
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
     *
     * @throws \ErrorException
     */
    public function render_field( $field ): void
    {

        // field settings
        $fieldSettings = static::getFieldSettingDefinitions();

        // filters
        $filters      = static::getFilterDefinitions( acf_get_array( $field['filters'] ) );
        $filter_count = count( $filters );

        // div attributes
        $attributes = [
            'id'         => $field['id'],
            'class'      => "tbp-acf-relation-list tbp-acf-language {$field['class']}",
            'data-min'   => $field['min'],
            'data-max'   => $field['max'],
            'data-paged' => 1,
        ];

        if ( $field['max'] === 1 )
        {
            $attributes['data-replace-selected'] = $field['replace_selected_value']
                ? 1
                : 0;
        }

        array_walk(
            $filters,
            static function ( &$filter ) use
            (
                &
                $attributes
            )
            {

                $filter['data-filter']                          = $filter['data-filter'] ?? $filter['name'];
                $attributes[ 'data-' . $filter['data-filter'] ] = '';
            }
        );

        ?>
        <div <?php
        echo acf_esc_attrs( $attributes ); ?>>

            <?php
            acf_hidden_input(
                [
                    'name'  => $field['name'],
                    'value' => '',
                ]
            ); ?>

            <?php

            /* filters */
            if ( $filter_count )
            {
                ?>
                <div class="filters -f<?php
                echo $field['one_filter_per_row']
                    ? 4
                    : $filter_count; ?>">
                    <?php

                    foreach ( $filters as $filter => $filterSettings )
                    {
                        ?>
                        <div class="filter-instruction -<?php
                        echo esc_attr( $filter ); ?>"><?php

                            if ( $field[ $filter . '_display_instruction' ] )
                            {
                                echo $field[ $filter . '_instruction_text' ] ?? $filterSettings['instructions'];
                            }
                            ?></div>
                        <div class="filter -<?php
                        echo esc_attr( $filter ); ?>">
                            <?php

                            switch ( $filterSettings['type'] )
                            {
                            case 'text':
                                $function = 'acf_text_input';
                                break;

                            case 'select':
                                $function                  = 'acf_select_input';
                                $filterSettings['choices'] = [
                                        '' => $filterSettings['select_label'],
                                    ]
                                    + $filterSettings['choices']( acf_get_array( $field[ $filter ] ) );
                                break;

                            default:
                                throw new ErrorException( "unknown filter input type $filter" );
                            }

                            $function(
                                array_intersect_key(
                                    $filterSettings,
                                    array_flip(
                                        [
                                            'placeholder',
                                            'data-filter',
                                            'choices',
                                        ]
                                    )
                                )
                            );

                            ?>
                        </div>
                        <?php
                    }

                    ?>
                </div>
                <?php
            }

            $width  = ( $field['choice_on_new_line'] ?? false )
                ? 100
                : 50;
            $height = ( $width === 100 && (int) ( $field['max'] ?? 0 ) === 1 )
                ? 'single-line'
                : '';

            ?>

            <div class="selection">
                <div class="selection-instruction choices-instruction"><?php

                    if ( $field['selection_choices_display_instruction'] )
                    {
                        echo $field['selection_choices_instruction_text']
                            ?: $fieldSettings['selection_choices_instruction_text']['placeholder'];
                    }
                    ?></div>
                <div class="choices choices-<?php
                echo $width; ?>">
                    <ul class="acf-bl list choices-list"></ul>
                </div>
                <div class="selection-instruction values-instruction"><?php

                    if ( $field['selection_values_display_instruction'] )
                    {
                        echo $field['selection_values_instruction_text']
                            ?: $fieldSettings['selection_values_instruction_text']['placeholder'];
                    }
                    ?></div>
                <div class="values values-<?php
                echo $width; ?>">
                    <ul class="acf-bl list values-list <?php
                    echo $height; ?>">
                        <?php
                        if ( ! empty( $field['value'] ) )
                        {

                            // get posts
                            $languages = LanguagePost::load( $field['value'] );

                            // loop
                            /** @var LanguagePost $language */
                            foreach ( $languages as $language )
                            {
                                $dataId  = $language->getId();
                                $caption = $language->getCaption();

                                printf(
                                    <<<HTML
                               <li>
                                    %s
                                    <span class="acf-rel-item" data-id="%s" >
							            %s
							            <a href="#" class="acf-icon -minus small dark" data-name="remove_item"></a>
                                    </span>
                                </li>
HTML
                                    ,
                                    acf_get_hidden_input(
                                        [
                                            'name'  => $field['name'] . '[]',
                                            'value' => $dataId,
                                        ]
                                    ),
                                    esc_attr( $dataId ),
                                    acf_esc_html( $caption )
                                );
                            }
                        } ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     *  render_field_settings()
     *
     *  Create extra settings for your field. These are visible when editing a field
     *
     * @method-type    action
     * @since          3.6
     * @date           23/01/13
     *
     * @param  array  $field  the $field being edited
     *
     * @return    void
     */
    public function render_field_settings( array $field ): void
    {

        // vars
        $field['min'] = empty( $field['min'] )
            ? ''
            : $field['min'];
        $field['max'] = empty( $field['max'] )
            ? ''
            : $field['max'];

        $settingFields = self::getFieldSettingDefinitions();
        $filters       = self::getFilterDefinitions();

        if ( ! empty( $filters ) && ! array_key_exists( 'filters', $settingFields ) )
        {
            $settingFields['filters'] = null;
        }

        $filterChoices = [];

        foreach ( $filters as $filter => &$setting )
        {

            if ( array_key_exists( 'choices', $setting ) && is_callable( $setting['choices'] ) )
            {
                $setting['choices'] = $setting['choices']();
            }

            ob_start();

            ?>
            <table class="acf-table">
                <?php

                if ( $setting['isSetting'] ?? false )
                {
                    acf_render_field_setting( $field, $setting );
                }

                $logic = $setting['conditional_logic'] ?? [];

                acf_render_field_setting(
                    $field,
                    [
                        'type'              => 'true_false',
                        'name'              => $filter . '_display_instruction',
                        'label'             => __( 'Display instruction', 'tbp-acf-fields' ),
                        'ui'                => 1,
                        'allow_null'        => 0,
                        'default'           => 1,
                        'conditional_logic' => $logic,
                    ]
                );

                if ( empty( $logic ) )
                {
                    $logic = [ [] ];
                }

                array_walk(
                    $logic,
                    static function ( &$condition ) use
                    (
                        $filter
                    )
                    {

                        $condition[] = [
                            'field'    => $filter . '_display_instruction',
                            'operator' => '!=',
                            'value'    => '0',
                        ];
                    }
                );

                //echo "<pre>" . print_r($logic, true) . "</pre>";

                acf_render_field_setting(
                    $field,
                    [
                        'type'              => 'text',
                        'name'              => $filter . '_instruction_text',
                        'label'             => __( 'Filter instructions', 'tbp-acf-fields' ),
                        'instructions'      => __(
                            'This text is shown before the field',
                            'tbp-acf-fields'
                        ),
                        'placeholder'       => $setting['instructions'],
                        'conditional_logic' => $logic,
                    ]
                );
                ?>
            </table>
            <?php

            $filterOptions = ob_get_clean();

            $filterChoices[ $filter ] = $setting['caption'] . $filterOptions;

        }
        unset ( $setting );

        foreach ( $settingFields as $fieldName => $settingField )
        {

            if ( $fieldName !== 'filters' )
            {
                acf_render_field_setting( $field, $settingField );
                continue;
            }

            if ( $settingField === false )
            {
                continue;
            }

            $settingField = wp_parse_args(
                $settingField ?? [],
                [
                    'isSetting'    => true,
                    'name'         => 'filters',
                    'type'         => 'checkbox',
                    'label'        => __( 'Filters', 'acf' ),
                    'instructions' => '',
                    'choices'      => $filterChoices,
                ]
            );

            $printFilterOptions = static function ( $settingField ) use
            (
                $field,
                &
                $filters
            )
            {

                if ( $settingField['_name'] !== 'filters' )
                {
                    return $settingField;
                }

                return $field;
            };

            //add_filter( 'acf/render_field/key=', $printFilterOptions, 999 );

            // filters
            acf_render_field_setting(
                $field,
                $settingField
            );

            //remove_filter( 'acf/render_field/key=', $printFilterOptions, 999 );

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
