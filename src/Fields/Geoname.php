<?php
/** @noinspection PhpUnnecessaryLocalVariableInspection */

namespace Tbp\WP\Plugin\AcfFields\Fields;

use ErrorException;
use Tbp\WP\Plugin\AcfFields\Entities\Location;
use Tbp\WP\Plugin\AcfFields\Field;
use Tbp\WP\Plugin\AcfFields\Integration\FacetWP;
use Tbp\WP\Plugin\AcfFields\Plugin;
use WPGeonames\Core;
use WPGeonames\Query\ApiQuery;

class Geoname
    extends
    Field
{

// constants
    public const CATEGORY = 'relational';
    public const LABEL    = 'Geo Names';
    public const NAME     = 'geoname';

// protected properties
    protected static $filters;
    protected static $fieldSettings;


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
                'feature_class' => [],
                'feature_code'  => [],
                'parent'        => null,
                'decedents'     => 1,
                'min'           => 0,
                'max'           => 0,
                'filters'       => [
                    'search',
                    'feature_class',
                    'feature_code',
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
                'facetwp_facet_sources_geoname',
            ],
            10,
            3
        );

        add_filter(
            "tbp-acf-fields/facet/index/type=" . static::NAME,
            [
                $this,
                'facetwp_indexer_row_data_geoname',
            ],
            10,
            2
        );

    }


    public function initialize()
    {

        self::$filters = [

            // habitation only
            'habitation_only' => [
                'isSetting'         => true,
                'type'              => 'true_false',
                'name'              => 'habitation_only',
                'caption'           => __( "Habitations only", 'tbp-acf-fields' ),
                'label'             => __( 'Filter habitations only', 'tbp-acf-fields' ),
                'instructions'      => 'If selected, only locations denominating a habitation (city, town, village, etc.) will be in the list. (Feature codes: ' . implode(
                        ', ',
                        array_reduce(
                            Core::FEATURE_FILTERS['habitationOnly'],
                            static function (
                                $carry,
                                $item
                            ) {

                                return $carry + $item;
                            },
                            []
                        )
                    ) . ')',
                'allow_null'        => 0,
                'default_value'     => true,
                'ui'                => true,
                'filterCallback'    => [
                    $this,
                    'filterHabitationOnly',
                ],
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'countries_only',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],

            // Countries only
            'countries_only'  => [
                'isSetting'         => true,
                'type'              => 'true_false',
                'name'              => 'countries_only',
                'caption'           => __( "Countries only", 'tbp-acf-fields' ),
                'label'             => __( 'Filter countries only', 'tbp-acf-fields' ),
                'instructions'      => 'If selected, only locations denominating a country will be in the list. (Feature codes: ' . implode(
                        ', ',
                        array_reduce(
                            Core::FEATURE_FILTERS['countriesOnly'],
                            static function (
                                $carry,
                                $item
                            ) {

                                return $carry + $item;
                            },
                            []
                        )
                    ) . ')',
                'allow_null'        => 0,
                'default_value'     => true,
                'ui'                => true,
                'filterCallback'    => [
                    $this,
                    'filterCountriesOnly',
                ],
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'habitation_only',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],

            // feature_class
            'feature_class'   => [
                'isSetting'         => true,
                'type'              => 'select',
                'name'              => 'feature_class',
                'caption'           => __( "Feature Class", 'tbp-acf-fields' ),
                'label'             => __( 'Filter by', 'tbp-acf-fields' ) . ' ' . __(
                        'feature class',
                        'tbp-acf-fields'
                    ),
                'select_label'      => __( 'Select', 'tbp-acf-fields' ) . ' ' . __( 'feature class', 'tbp-acf-fields' ),
                'instructions'      => '',
                'choices'           => [
                    static::class,
                    'getFeatureClasses',
                ],
                'multiple'          => 1,
                'ui'                => 1,
                'allow_null'        => 1,
                'placeholder'       => __( "All feature classes", 'tbp-acf-fields' ),
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'habitation_only',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                        [
                            'field'    => 'countries_only',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],

            // feature_code
            'feature_code'    => [
                'isSetting'         => true,
                'type'              => 'select',
                'name'              => 'feature_code',
                'caption'           => __( "Feature Code", 'tbp-acf-fields' ),
                'label'             => __( 'Filter by', 'tbp-acf-fields' ) . ' ' . __(
                        'Feature Code',
                        'tbp-acf-fields'
                    ),
                'select_label'      => __( 'Select', 'tbp-acf-fields' ) . ' ' . __( 'Feature Code', 'tbp-acf-fields' ),
                'instructions'      => '',
                'choices'           => [
                    static::class,
                    'getFeatureCodes',
                ],
                'multiple'          => 1,
                'ui'                => 1,
                'allow_null'        => 1,
                'placeholder'       => __( "All feature codes", 'tbp-acf-fields' ),
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'habitation_only',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                        [
                            'field'    => 'countries_only',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],

            // country
            'country_code'    => [
                'isSetting'         => true,
                'name'              => 'country_code',
                'type'              => 'select',
                'caption'           => __( "Country", 'tbp-acf-fields' ),
                'label'             => __( 'Filter by', 'tbp-acf-fields' ) . ' ' . __( 'country', 'tbp-acf-fields' ),
                'select_label'      => __( "All countries", 'tbp-acf-fields' ),
                'instructions'      => '',
                'choices'           => [
                    static::class,
                    'getCountryCodes',
                ],
                'multiple'          => 1,
                'ui'                => 1,
                'allow_null'        => 1,
                'placeholder'       => __( "All countries", 'tbp-acf-fields' ),
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'countries_only',
                            'operator' => '!=',
                            'value'    => '1',
                        ],
                    ],
                ],
            ],

            // search
            'search'          => [
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

        self::$fieldSettings = [

            // filter layout
            'one_filter_per_row' => [
                'type'       => 'true_false',
                'name'       => 'one_filter_per_row',
                'label'      => __( 'Display one filter per row', 'tbp-acf-fields' ),
                'ui'         => 1,
                'allow_null' => 0,
                'default'    => false,
            ],

            // choice layout
            'choice_on_new_line' => [
                'type'       => 'true_false',
                'name'       => 'choice_on_new_line',
                'label'      => __( 'Display selected values on new line', 'tbp-acf-fields' ),
                'ui'         => 1,
                'allow_null' => 0,
                'default'    => false,
            ],

            'selection_choices_display_instruction' => [
                'type'       => 'true_false',
                'name'       => 'selection_choices_display_instruction',
                'label'      => __( 'Display instruction for available choices', 'tbp-acf-fields' ),
                'ui'         => 1,
                'allow_null' => 0,
                'default'    => 1,
            ],

            'selection_choices_instruction_text' => [
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

            'selection_values_display_instruction' => [
                'type'       => 'true_false',
                'name'       => 'selection_values_display_instruction',
                'label'      => __( 'Display instruction for selected values', 'tbp-acf-fields' ),
                'ui'         => 1,
                'allow_null' => 0,
                'default'    => 1,
            ],

            'selection_values_instruction_text' => [
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
                    ],
                ],
            ],

            // min
            'min'                               => [
                'type'         => 'number',
                'name'         => 'min',
                'label'        => __( 'Minimum locations', 'tbp-acf-fields' ),
                'instructions' => '',
            ],

            // max
            'max'                               => [
                'type'         => 'number',
                'name'         => 'max',
                'label'        => __( 'Maximum locations', 'tbp-acf-fields' ),
                'instructions' => '',
            ],

            'replace_selected_value' => [
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
                ],
            ],

            // return_format
            'return_format'          => [
                'type'         => 'radio',
                'name'         => 'return_format',
                'label'        => __( 'Return Format', 'acf' ),
                'instructions' => '',
                'choices'      => [
                    'object' => __( "Geoname Location Object", 'tbp-acf-fields' ),
                    'id'     => __( "Geoname ID", 'tbp-acf-fields' ),
                ],
                'layout'       => 'horizontal',
            ],
        ];

        /*
        *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
        *  var message = acf._e('geoname', 'error');
        */
        $this->l10n = [
            'error' => __( 'Error! Please enter a higher value', 'tbp-acf-fields' ),
        ];

        // extra
        add_action(
            'wp_ajax_acf/fields/geoname/query',
            [
                $this,
                'ajax_query',
            ]
        );

        add_action(
            'wp_ajax_nopriv_acf/fields/geoname/query',
            [
                $this,
                'ajax_query',
            ]
        );

        array_walk(
            self::$filters,
            static function (
                $filterSettings,
                $filter
            ) {

                if ( array_key_exists(
                        'filterCallback',
                        $filterSettings
                    )
                    && $filterSettings['filterCallback'] === false )
                {
                    return;
                }

                add_filter(
                    "acf/fields/geoname/filter/name=$filter",
                    array_key_exists( 'filterCallback', $filterSettings )
                    && is_callable(
                        $filterSettings['filterCallback']
                    )
                        ? $filterSettings['filterCallback']
                        : [
                        static::class,
                        'filterDefault',
                    ],
                    10,
                    2
                );
            }
        );
    }


    /**
     *  getData
     *
     *  This function will return an array of data formatted for use in a select2 AJAX response
     *
     * @date      15/10/2014
     * @since     5.0.9
     *
     * @param  array  $options
     *
     * @return    array|false
     * @throws \ErrorException
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
            'geo_id'        => 0,
            'parent'        => 0,
            's'             => '',
            'field_key'     => '',
            'paged'         => 1,
            'feature_class' => null,
            'feature_code'  => null,
            'country_code'  => null,
            'is_search'     => false,
        ];

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $options = wp_parse_args( $options, $defaults );

        // vars
        $args = [];

        // paged
        $args['maxRows'] = 20;
        $args['paged']   = (int) $options['paged'];

        $context = (object) [
            'field'          => &$field,
            'filters'        => static::getFilterDefinitions(),
            'options'        => &$options,
            'filter'         => null,
            'filterSettings' => null,
        ];

        // filters
        while ( ( $context->filter = key( $context->filters ) )
            && ( $context->filterSettings = array_shift(
                $context->filters
            ) ) )
        {

            $args = apply_filters( "acf/fields/geoname/filter/name={$context->filter}", $args, $context );
        }

        $args = apply_filters( 'acf/fields/geoname/query', $args, $field, $options );
        $args = apply_filters( 'acf/fields/geoname/query/name=' . $field['name'], $args, $field );
        $args = apply_filters( 'acf/fields/geoname/query/key=' . $field['key'], $args, $field, $options );

        // get locations grouped by top most ancestor
        $searchResult = Core::getLiveSearch( $args, Location::class );

        // bail early if no posts
        if ( $searchResult->count === 0 )
        {
            return false;
        }

        $result = [];

        array_walk(
            $searchResult->result,
            static function (
                array &$locations,
                string $type
            ) use
            (
                &
                $result
            )
            {

                array_walk(
                    $locations,
                    static function ( Location &$location )
                    use
                    (
                        &
                        $result
                    )
                    {

                        $entry = [
                            'id'   => $location->geonameId,
                            //'text' => sprintf( '%s, %s', $location->name, $location->country->iso2 ),
                            'text' => implode(
                                ', ',
                                array_filter(
                                    [
                                        $location->getName(),
                                        (string) $location->getAdmin2()
                                                          ->getName(),
                                        (string) $location->getAdmin1()
                                                          ->getName(),
                                        (string) $location->getCountry()
                                                          ->getIso2(),
                                    ]
                                )
                            ),
                        ];

                        //** @noinspection CallableParameterUseCaseInTypeContextInspection */
                        //$location = $entry;
                        $result[] = $entry;
                    }
                );

            }
        );

        // vars
        $response = [
            'limit'   => $args['maxRows'],
            'more'    => $searchResult->total > $searchResult->count,
            'results' => $result,
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
     * @throws \ErrorException
     */
    public function ajax_query_helper()
    {

        // get choices
        return $this->getData( $_POST );
    }


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
    public function facetwp_facet_sources_geoname(
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
                foreach (
                    [
                        'name'    => '',
                        'country' => sprintf( ' [%s] ', __( 'by country', Plugin::TEXT_DOMAIN ) ),
                        'admin1'  => sprintf( ' [%s] ', __( 'by 1st admin', Plugin::TEXT_DOMAIN ) ),
                    ]
                    as $property => $label
                )
                {

                    $field_id                               = sprintf(
                        '%s/%s/%s/%s',
                        FacetWP::SOURCE_IDENTIFIER,
                        static::NAME,
                        $property,
                        $field['hierarchy']
                    );
                    $sources['acf']['choices'][ $field_id ] = $field_label . $label;
                }
            }
        );

//        echo "<pre>\n";
//        print_r($sources['acf']);
//        echo "</pre>\n";

        return $sources;
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
    public function facetwp_indexer_row_data_geoname(
        array &$rows,
        array &$params
    ) {

        $field = $params['source']->field;
        /*
        $post  = get_post( $params['defaults']['post_id'] );

        if ( $post->post_type === 'events' )
        {
            echo '';
        }
        */

        if ( empty( $field['value'] ) )
        {
            $row                        = $params['defaults'];
            $row['facet_value']         = 0;
            $row['facet_display_value'] = 'N/A';

            $rows[] = $row;

            return $rows;
        }

        $locations = Location::load( (array) $field['value'] );

        array_walk(
            $locations,
            static function ( Location $location ) use
            (
                &
                $rows,
                &
                $params
            )
            {

                $row                        = $params['defaults'];
                $row['facet_value']         = $location->getGeonameId();
                $row['facet_display_value'] = $location->getCountry( true, true )
                                                       ->getNameIntl()
                ;

                $rows[] = $row;

//                $row                        = $params['defaults'];
//                $row['facet_value']         = $location->getGeonameId();
//                $row['facet_display_value'] = $location->getAsciiName();
//
//                $rows[] = $row;
            }
        );

        return $rows;
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
    public function field_group_admin_enqueue_scripts()
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
    public function field_group_admin_head()
    {

    }


    /**
     * @param  array   $args
     * @param  object  $context
     *
     * @return array
     */
    public function filterCountriesOnly(
        array $args,
        object $context
    ): array {

        if ( $context->field['countries_only'] ?? false )
        {
            $args ['feature_class'] = array_keys( Core::FEATURE_FILTERS['countriesOnly'] );
            $args ['feature_code']  = array_reduce(
                Core::FEATURE_FILTERS['countriesOnly'],
                static function (
                    $carry,
                    $item
                ) {

                    return $carry + $item;
                },
                []
            );

            unset ( $context->filters['feature_class'], $context->filters['feature_code'], $context->filters['habitation_only'] );
        }

        return $args;
    }


    /**
     * @param  array   $args
     * @param  object  $context
     *
     * @return array
     */
    public function filterHabitationOnly(
        array $args,
        object $context
    ): array {

        if ( $context->field['habitation_only'] ?? false )
        {
            $args ['feature_class'] = array_keys( Core::FEATURE_FILTERS['habitationOnly'] );
            $args ['feature_code']  = array_reduce(
                Core::FEATURE_FILTERS['habitationOnly'],
                static function (
                    $carry,
                    $item
                ) {

                    return $carry + $item;
                },
                []
            );

            unset ( $context->filters['feature_class'], $context->filters['feature_code'], $context->filters['countries_only'] );
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
            $args['s']       = 'a';
            $args['maxRows'] = 20;
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
    public function input_admin_enqueue_scripts()
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
    public function input_admin_footer()
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
    public function input_admin_head()
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
     * @param  mixed  $value    the value found in the database
     * @param  int    $post_id  the $post_id from which the value was loaded
     * @param  array  $field    the field array holding all the field options
     *
     * @return mixed        $value
     * @noinspection   PhpUnusedParameterInspection
     */
    public function load_value(
        $value,
        int $post_id,
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
    public function render_field( $field )
    {

        // field settings
        $fieldSettings = static::getFieldSettingDefinitions();

        // filters
        $filters      = static::getFilterDefinitions( acf_get_array( $field['filters'] ) );
        $filter_count = count( $filters );

        // div attributes
        $attributes = [
            'id'         => $field['id'],
            'class'      => "acf-geoname {$field['class']}",
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
                                    + call_user_func_array(
                                        $filterSettings['choices'],
                                        [ acf_get_array( $field[ $filter ] ) ]
                                    );
                                break;

                            default:
                                throw new ErrorException( "unknown filter input type $filter" );
                            }

                            call_user_func_array(
                                $function,
                                [
                                    array_intersect_key(
                                        $filterSettings,
                                        array_flip(
                                            [
                                                'placeholder',
                                                'data-filter',
                                                'choices',
                                            ]
                                        )
                                    ),
                                ]
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
                            $locations = Location::load( $field['value'] );

                            // loop
                            /** @var Location $location */
                            foreach ( $locations as $location )
                            {
                                $dataId  = $location->geonameId;
                                $caption = $location->name . ', ' . $location->countryCode;

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
    public function render_field_settings( array $field )
    {

        // vars
        $field['min']  = empty( $field['min'] )
            ? ''
            : $field['min'];
        $field['max']  = empty( $field['max'] )
            ? ''
            : $field['max'];
        $filterChoices = [];

        foreach ( self::getFilterDefinitions() as $filter => $setting )
        {

            $filterChoices[ $filter ] = $setting['caption'];

            if ( array_key_exists( 'choices', $setting ) && is_callable( $setting['choices'] ) )
            {
                $setting['choices'] = $setting['choices']();
            }

            ?>
            <tr>
                <td>
                    <?php
                    echo $setting['caption'];
                    ?>
                </td>
                <td>
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
                                'instructions'      => __( 'This text is shown before the field', 'tbp-acf-fields' ),
                                'placeholder'       => $setting['instructions'],
                                'conditional_logic' => $logic,
                            ]
                        );
                        ?>
                    </table>
                </td>
            </tr>
            <?php
        }

        // filters
        acf_render_field_setting(
            $field,
            [
                'isSetting'    => true,
                'name'         => 'filters',
                'type'         => 'checkbox',
                'label'        => __( 'Filters', 'acf' ),
                'instructions' => '',
                'choices'      => $filterChoices,
            ]
        );

        $searchTypes = [];

        foreach ( ApiQuery::SEARCH_TYPES as $searchTypeBitmask => $searchTypeName )
        {
            $searchTypes[ $searchTypeBitmask ] = __( $searchTypeName, 'tbp-acf-fields' );
        }

        acf_render_field_setting(
            $field,
            [
                'isSetting'    => true,
                'name'         => 'searchTypeDefaults',
                'type'         => 'checkbox',
                'label'        => __( 'Default search mode', 'tbp-acf-fields' ),
                'instructions' => '',
                'choices'      => $searchTypes,
            ]
        );

        acf_render_field_setting(
            $field,
            [
                'type'         => 'true_false',
                'name'         => 'searchTypesAllowUser',
                'label'        => __( 'User-defined Search Mode', 'tbp-acf-fields' ),
                'instructions' => __( 'Allow user to select from the following search types.', 'tbp-acf-fields' ),
                'ui'           => 1,
                'allow_null'   => 0,
                'default'      => 0,
            ]
        );

        acf_render_field_setting(
            $field,
            [
                'isSetting'         => true,
                'name'              => 'searchTypeUserEditable',
                'type'              => 'checkbox',
                'label'             => __( 'Available Search Modes', 'tbp-acf-fields' ),
                'instructions'      => __( 'Allow user to select from these search modes.', 'tbp-acf-fields' ),
                'choices'           => $searchTypes,
                'conditional_logic' => [
                    [
                        'field'    => 'searchTypesAllowUser',
                        'operator' => '!=',
                        'value'    => '0',
                    ],
                ],

            ]
        );

        foreach ( self::getFieldSettingDefinitions() as $fieldName => $setting )
        {

            acf_render_field_setting( $field, $setting );
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
     * @param  mixed  $value    the value found in the database
     * @param  int    $post_id  the $post_id from which the value was loaded
     * @param  array  $field    the field array holding all the field options
     *
     * @return   mixed      $value
     * @noinspection   PhpUnusedParameterInspection
     */
    public function update_value(
        $value,
        int $post_id,
        array $field
    ) {

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


    /**
     * @param  array   $args
     * @param  object  $context
     *
     * @return array
     */
    public static function filterDefault(
        array $args,
        object $context
    ): array {

        $filters = acf_get_array( $context->field['filters'] );

        if ( ! empty( $context->options[ $context->filter ] ) && in_array( $context->filter, $filters, true ) )
        {

            $args[ $context->filter ] = acf_get_array( $context->options[ $context->filter ] );
        }
        elseif ( ! empty( $context->field[ $context->filter ] ) )
        {

            $args[ $context->filter ] = acf_get_array( $context->field[ $context->filter ] );
        }
        else
        {

            $args[ $context->filter ] = null; // call_user_func( $getter );

        }

        return $args;
    }


    /**
     * @param  array  $country_codes
     *
     * @return array
     */
    public static function getCountryCodes( $country_codes = [] ): array
    {

        return self::checkChoices(
            $country_codes,
            [
                Core::class,
                'getCountryCodes',
            ]
        );
    }


    /** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */
    protected static function getDefinitions(
        $array,
        $key
    ) {

        switch ( true )
        {
        case $key === null:
            // no key given, return all keys
            return $array;

        case is_string( $key ):
            // key is a single key name
            return $array[ $key ];

        case is_array( $key ):

            // key is given, but empty. Return empty array
            if ( empty( $key ) )
            {
                return [];
            }

            // key is a list of field names
            if ( ! is_string( key( $key ) ) )
            {

                return array_intersect_key( $array, array_flip( $key ) );
            }

            // key is a set of properties to match
            return array_filter(
                $array,
                static function ( &$item ) use
                (
                    &
                    $key
                )
                {

                    foreach ( array_keys( $key ) as $key )
                    {

                        if ( ! array_key_exists( $key, $item ) )/**/
                        {
                            return false;
                        }

                        if ( $item[ $key ] !== $key[ $key ] )
                        {
                            return false;
                        }
                    }

                    return true;
                }
            );
        }

        return null;
    }


    public static function getFeatureClasses( $feature_classes = [] ): array
    {

        return self::checkChoices(
            $feature_classes,
            [
                Core::class,
                'getFeatureClasses',
            ]
        );
    }


    public static function getFeatureCodes( $feature_codes = [] ): array
    {

        return self::checkChoices(
            $feature_codes,
            [
                Core::class,
                'getFeatureCodes',
            ]
        );
    }


    public static function getFieldSettingDefinitions( $setting = null )
    {

        return self::getDefinitions( self::$fieldSettings, $setting );

    }


    public static function getFilterDefinitions( $filter = null )
    {

        return self::getDefinitions( self::$filters, $filter );
    }

}
