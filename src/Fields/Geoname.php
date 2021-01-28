<?php
/** @noinspection PhpUnnecessaryLocalVariableInspection */

namespace Tbp\WP\Plugin\AcfFields\Fields;

use Locale;
use Tbp\WP\Plugin\AcfFields\Entities\Country;
use Tbp\WP\Plugin\AcfFields\Entities\Location;
use Tbp\WP\Plugin\AcfFields\FieldInterface;
use Tbp\WP\Plugin\AcfFields\FieldTypes\FieldRelational;
use Tbp\WP\Plugin\AcfFields\Integration\FacetWP;
use Tbp\WP\Plugin\AcfFields\Plugin;
use Throwable;
use WPGeonames\Core;
use WPGeonames\Entities\Location as WpGeonameLocation;
use WPGeonames\Query\ApiQuery;

class Geoname
    extends
    FieldRelational
{

// constants
    public const LABEL = 'Geo Names';
    public const NAME  = 'tbp_geoname';


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


    public function initialize(): void
    {

        /*
        *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
        *  var message = acf._e('tbp_geoname', 'error');
        */
        $this->l10n = [
            'error' => __( 'Error! Please enter a higher value', 'tbp-acf-fields' ),
        ];

        // extra
        add_action(
            'wp_ajax_acf/fields/tbp_geoname/query',
            [
                $this,
                'ajax_query',
            ]
        );

        add_action(
            'wp_ajax_nopriv_acf/fields/tbp_geoname/query',
            [
                $this,
                'ajax_query',
            ]
        );

        $this->filterBase = "acf/fields/tbp_geoname/filter/name=";
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
     * @noinspection PhpUnusedParameterInspection
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
            'filters'        => $this->getFilters(),
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

            $args = apply_filters( "acf/fields/tbp_geoname/filter/name={$context->filter}", $args, $context );
        }

        $args = apply_filters( 'acf/fields/tbp_geoname/query', $args, $field, $options );
        $args = apply_filters( 'acf/fields/tbp_geoname/query/name=' . $field['name'], $args, $field );
        $args = apply_filters( 'acf/fields/tbp_geoname/query/key=' . $field['key'], $args, $field, $options );

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
                    static function ( WpGeonameLocation $location )
                    use
                    (
                        &
                        $result
                    )
                    {

                        /**
                         * @noinspection UnnecessaryCastingInspection
                         * @noinspection NullPointerExceptionInspection
                         */
                        $entry = [
                            'id'   => $location->getGeonameId(),
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
            'more'    => $searchResult->total > $searchResult->processRecords,
            'results' => $result,
        ];

        // return
        return $response;
    }


    protected function getFieldSettingsDefinition(): array
    {

        $searchTypes = [];

        foreach ( ApiQuery::SEARCH_TYPES as $searchTypeBitmask => $searchTypeName )
        {
            $searchTypes[ $searchTypeBitmask ] = __( $searchTypeName, 'tbp-acf-fields' );
        }

        $fieldSettings = parent::getFieldSettingsDefinition(

                [
                    [
                        'isSetting'    => true,
                        'name'         => 'searchTypeDefaults',
                        'type'         => 'checkbox',
                        'label'        => __( 'Default search mode', 'tbp-acf-fields' ),
                        'instructions' => '',
                        'choices'      => $searchTypes,
                    ],
                    [
                        'type'         => 'true_false',
                        'name'         => 'searchTypesAllowUser',
                        'label'        => __( 'User-defined Search Mode', 'tbp-acf-fields' ),
                        'instructions' => __(
                            'Allow user to select from the following search types.',
                            'tbp-acf-fields'
                        ),
                        'ui'           => 1,
                        'allow_null'   => 0,
                        'default'      => 0,
                    ],
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

                    ],
                ],
            );

        return $fieldSettings;
    }


    public function getFilterDefinition(): array
    {

        return $this->addFilterCallbacks(
            [
                // search
                $this->getFilterDefinitionSearch(),

                // habitation only
                [
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
                [
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
                [
                    'isSetting'         => true,
                    'type'              => 'select',
                    'name'              => 'feature_class',
                    'caption'           => __( "Feature Class", 'tbp-acf-fields' ),
                    'label'             => __( 'Filter by', 'tbp-acf-fields' ) . ' ' . __(
                            'feature class',
                            'tbp-acf-fields'
                        ),
                    'select_label'      => __( 'Select', 'tbp-acf-fields' ) . ' ' . __(
                            'feature class',
                            'tbp-acf-fields'
                        ),
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
                [
                    'isSetting'         => true,
                    'type'              => 'select',
                    'name'              => 'feature_code',
                    'caption'           => __( "Feature Code", 'tbp-acf-fields' ),
                    'label'             => __( 'Filter by', 'tbp-acf-fields' ) . ' ' . __(
                            'Feature Code',
                            'tbp-acf-fields'
                        ),
                    'select_label'      => __( 'Select', 'tbp-acf-fields' ) . ' ' . __(
                            'Feature Code',
                            'tbp-acf-fields'
                        ),
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
                [
                    'isSetting'         => true,
                    'name'              => 'country_code',
                    'type'              => 'select',
                    'caption'           => __( "Country", 'tbp-acf-fields' ),
                    'label'             => __( 'Filter by', 'tbp-acf-fields' ) . ' ' . __(
                            'country',
                            'tbp-acf-fields'
                        ),
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
     * @param                                           $sources
     * @param  \Tbp\WP\Plugin\AcfFields\FieldInterface  $field
     * @param  array                                    $acfFields
     *
     * @return array
     * @noinspection PhpUnusedParameterInspection
     */
    public function facetwpFacetSources(
        $sources,
        FieldInterface $field,
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
            if ( $field["key"] !== 'field_tbpEventAdditionalCountries' )
            {
                $rows[] = $default;
            }

            return $rows;
        }

        try
        {
            $locations = Location::load( (array) ( $field['value'] ?? [] ) );

        }
        catch ( Throwable $e )
        {
            /** @noinspection ForgottenDebugOutputInspection */
            error_log(
                sprintf(
                    'Invalid country information "%s" for post_id %d',
                    print_r( $field['value'], true ),
                    $default['post_id']
                )
            );

            $default['facet_value']         = - 1;
            $default['facet_display_value'] = 'Error';
            $rows[]                         = $default;

            return $rows;
        }

        try
        {
            array_walk(
                $locations,
                static function ( WpGeonameLocation $location ) use
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

                    switch ( $source->property )
                    {
                    case 'name':
                        $default['facet_value']         = $location->getGeonameId();
                        $default['facet_display_value'] = $location->getName();
                        break;

                    case 'admin1':
                        if ( ! ( $admin = $location->getAdmin1() ) )
                        {
                            return;
                        }

                        $default['facet_value']         = $admin->getGeonameId();
                        $default['facet_display_value'] = $admin->getName();
                        break;

                    case 'country':
                        if ( ! ( $country = $location->getCountry() ) )
                        {
                            return;
                        }

                        $default['facet_value']         = $country->getGeonameId();
                        $default['facet_display_value'] = $country->getIso2();
                        break;

                    default:
                        break;
                    }

                    $rows[] = $default;

                }
            );

        }
        catch ( Throwable $e )
        {
            /** @noinspection ForgottenDebugOutputInspection */
            error_log(
                sprintf(
                    'Error while processing country information "%s" for post_id %d',
                    print_r( $field['value'], true ),
                    $default['post_id']
                )
            );

            $default['facet_value']         = - 2;
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
    public function &facetwpRender(
        array $args,
        object $source
    ): array {

        if ( $source->property === 'country' && ! empty( $args['values'] ) )
        {

            $languageCode = FWP()->facet->http_params['lang'] ?? ( defined( 'ICL_LANGUAGE_CODE' )
                    ? ICL_LANGUAGE_CODE
                    : null
                );

            array_walk(
                $args['values'],
                static function (
                    &$val,
                    $key
                ) use
                (
                    $languageCode
                )
                {

                    if ( $val['facet_value'] == 0 )
                    {
                        return;
                    }

                    $display_value = null;

                    // WPML integration
                    if ( $languageCode )
                    {
                        $display_value = Locale::getDisplayRegion(
                            '-' . $val['facet_display_value'],
                            $languageCode
                        );
                    }

                    if ( $display_value === null )
                    {
                        $country = Country::load( $val['facet_display_value'] ?? '--' );

                        if ( $country !== null )
                        {
                            $display_value = $country->getName( $languageCode );
                        }

                        if ( $display_value === null )
                        {
                            return;
                        }

                    }

                    $val['facet_display_value'] = $display_value;

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

            unset ( $context->filters['feature_class'] );
            unset ( $context->filters['feature_code'] );
            unset ( $context->filters['habitation_only'] );
            unset ( $context->filters['country_code'] );
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
     * @param  mixed       $value      the value which was loaded from the database
     * @param  int|string  $object_id  the $post_id from which the value was loaded, or user_$userId
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
        return parent::format_value( $value, $object_id, $field );
    }


    protected function render_field_values(
        array &$field,
        callable $render,
        bool $echo = true
    ): string {

        if ( empty( $field['value'] ) )
        {
            return '';
        }

        // get locations
        $locations = Location::load(
            (array) $field['value'],
            (object) [
                'locationClass' => Location::class,
                'countryClass'  => Country::class,
            ]
        );

        $html = '';

        /**
         * loop
         *
         * @var Location $location
         */
        foreach ( $locations as $location )
        {
            $dataId  = $location->geonameId;
            $caption = $location->name . ', ' . $location->countryCode;
            $html    .= $render( $dataId, $caption, $echo );
        }

        return $html;
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

}
