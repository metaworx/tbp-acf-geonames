<?php

namespace Tbp\WP\Plugin\AcfGeoname\Fields;

use Tbp\WP\Plugin\AcfGeoname\Field;
use WP_Error;
use WPGeonames\ApiQuery;
use WPGeonames\Core;
use WPGeonames\Entities\Location;

class Geoname
    extends Field
{

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
    public function __construct($settings = [])
    {

        /*
        *  name (string) Single word, no spaces. Underscores allowed
        */
        $this->name = 'geoname';

        /*
        *  label (string) Multiple words, can include spaces, visible when selecting a field type
        */
        $this->label = __('Geo Names', 'tbp-acf-geoname');

        /*
        *  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
        */
        $this->category = 'relational';

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

        parent::__construct($settings);
    }


    public function initialize()
    {

        self::$filters = [

            // habitation only
            'habitation_only' => [
                'isSetting'         => true,
                'type'              => 'true_false',
                'name'              => 'habitation_only',
                'caption'           => __("Habitations only", 'tbp-acf-geoname'),
                'label'             => __('Filter habitations only', 'tbp-acf-geoname'),
                'instructions'      => 'If selected, only locations denominating a habitation (city, town, village, etc.) will be in the list. (Feature codes: ' . implode(
                        ', ',
                        array_reduce(
                            Core::FEATURE_FILTERS['habitationOnly'],
                            static function (
                                $carry,
                                $item
                            )
                            {

                                return $carry + $item;
                            },
                            []
                        )
                    ) . ')',
                'allow_null'        => 0,
                'default_value'     => true,
                'ui'                => true,
                'filterCallback'    => [$this, 'filterHabitationOnly'],
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
                'caption'           => __("Countries only", 'tbp-acf-geoname'),
                'label'             => __('Filter countries only', 'tbp-acf-geoname'),
                'instructions'      => 'If selected, only locations denominating a country will be in the list. (Feature codes: ' . implode(
                        ', ',
                        array_reduce(
                            Core::FEATURE_FILTERS['countriesOnly'],
                            static function (
                                $carry,
                                $item
                            )
                            {

                                return $carry + $item;
                            },
                            []
                        )
                    ) . ')',
                'allow_null'        => 0,
                'default_value'     => true,
                'ui'                => true,
                'filterCallback'    => [$this, 'filterCountriesOnly'],
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
                'caption'           => __("Feature Class", 'tbp-acf-geoname'),
                'label'             => __('Filter by', 'tbp-acf-geoname') . ' ' . __(
                        'feature class',
                        'tbp-acf-geoname'
                    ),
                'select_label'      => __('Select', 'tbp-acf-geoname') . ' ' . __('feature class', 'tbp-acf-geoname'),
                'instructions'      => '',
                'choices'           => [static::class, 'getFeatureClasses'],
                'multiple'          => 1,
                'ui'                => 1,
                'allow_null'        => 1,
                'placeholder'       => __("All feature classes", 'tbp-acf-geoname'),
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
                'caption'           => __("Feature Code", 'tbp-acf-geoname'),
                'label'             => __('Filter by', 'tbp-acf-geoname') . ' ' . __('Feature Code', 'tbp-acf-geoname'),
                'select_label'      => __('Select', 'tbp-acf-geoname') . ' ' . __('Feature Code', 'tbp-acf-geoname'),
                'instructions'      => '',
                'choices'           => [static::class, 'getFeatureCodes'],
                'multiple'          => 1,
                'ui'                => 1,
                'allow_null'        => 1,
                'placeholder'       => __("All feature codes", 'tbp-acf-geoname'),
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
                'caption'           => __("Country", 'tbp-acf-geoname'),
                'label'             => __('Filter by', 'tbp-acf-geoname') . ' ' . __('country', 'tbp-acf-geoname'),
                'select_label'      => __("All countries", 'tbp-acf-geoname'),
                'instructions'      => '',
                'choices'           => [static::class, 'getCountryCodes'],
                'multiple'          => 1,
                'ui'                => 1,
                'allow_null'        => 1,
                'placeholder'       => __("All countries", 'tbp-acf-geoname'),
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
                'caption'        => __("Search", 'acf'),
                'placeholder'    => __("Search...", 'acf'),
                'data-filter'    => 's',
                'filterCallback' => [$this, 'filterSearch'],
            ],

        ];

        self::$fieldSettings = [

            // filter layout
            'one_filter_per_row' => [
                'type'       => 'true_false',
                'name'       => 'one_filter_per_row',
                'label'      => __('Display one filter per row', 'tbp-acf-geoname'),
                'ui'         => 1,
                'allow_null' => 0,
                'default'    => false,
            ],

            // choice layout
            'choice_on_new_line' => [
                'type'       => 'true_false',
                'name'       => 'choice_on_new_line',
                'label'      => __('Display selected values on new line', 'tbp-acf-geoname'),
                'ui'         => 1,
                'allow_null' => 0,
                'default'    => false,
            ],

            'selection_choices_display_instruction' => [
                'type'       => 'true_false',
                'name'       => 'selection_choices_display_instruction',
                'label'      => __('Display instruction for available choices', 'tbp-acf-geoname'),
                'ui'         => 1,
                'allow_null' => 0,
                'default'    => 1,
            ],

            'selection_choices_instruction_text' => [
                'type'              => 'text',
                'name'              => 'selection_choices_instruction_text',
                'label'             => __('Choices instructions', 'tbp-acf-geoname'),
                'instructions'      => __('This text is shown before the field', 'tbp-acf-geoname'),
                'placeholder'       => __('Click on one of the entries to add it to the selection.', 'tbp-acf-geoname'),
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
                'label'      => __('Display instruction for selected values', 'tbp-acf-geoname'),
                'ui'         => 1,
                'allow_null' => 0,
                'default'    => 1,
            ],

            'selection_values_instruction_text' => [
                'type'              => 'text',
                'name'              => 'selection_values_instruction_text',
                'label'             => __('Selection instructions', 'tbp-acf-geoname'),
                'instructions'      => __('This text is shown before the field', 'tbp-acf-geoname'),
                'placeholder'       => __(
                    'This is/are the current selected values. To remove an entry, click on the minus-symbol at the end of the line.',
                    'tbp-acf-geoname'
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
                'label'        => __('Minimum locations', 'tbp-acf-geoname'),
                'instructions' => '',
            ],

            // max
            'max'                               => [
                'type'         => 'number',
                'name'         => 'max',
                'label'        => __('Maximum locations', 'tbp-acf-geoname'),
                'instructions' => '',
            ],

            'replace_selected_value' => [
                'type'              => 'true_false',
                'name'              => 'replace_selected_value',
                'label'             => __('Replace selected value', 'tbp-acf-geoname'),
                'instructions'      => __(
                    'If there is only one choice allowed and this setting is set to true, the selected value is automatically replaced. If this setting is set to false, the user has to first remove the current selection.',
                    'tbp-acf-geoname'
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
                'label'        => __('Return Format', 'acf'),
                'instructions' => '',
                'choices'      => [
                    'object' => __("Geoname Location Object", 'tbp-acf-geoname'),
                    'id'     => __("Geoname ID", 'tbp-acf-geoname'),
                ],
                'layout'       => 'horizontal',
            ],
        ];

        /*
        *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
        *  var message = acf._e('geoname', 'error');
        */
        $this->l10n = [
            'error' => __('Error! Please enter a higher value', 'tbp-acf-geoname'),
        ];

        // extra
        add_action('wp_ajax_acf/fields/geoname/query', [$this, 'ajax_query']);
        add_action('wp_ajax_nopriv_acf/fields/geoname/query', [$this, 'ajax_query']);

        array_walk(
            self::$filters,
            static function (
                $filterSettings,
                $filter
            )
            {

                if (array_key_exists('filterCallback', $filterSettings) && $filterSettings['filterCallback'] === false)
                {
                    return;
                }

                add_filter(
                    "acf/fields/geoname/filter/name=$filter",
                    array_key_exists('filterCallback', $filterSettings) && is_callable(
                        $filterSettings['filterCallback']
                    )
                        ? $filterSettings['filterCallback']
                        : [static::class, 'filterDefault'],
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
     */
    public function getData($options = [])
    {

        // load field
        $field = acf_get_field($options['field_key']);

        if (!$field)
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

        $options = wp_parse_args($options, $defaults);

        // vars
        $args = [];

        // paged
        $args['maxRows'] = 20;
        $args['paged']   = (int)$options['paged'];

        $context = (object)[
            'field'          => &$field,
            'filters'        => static::getFilterDefinitions(),
            'options'        => &$options,
            'filter'         => null,
            'filterSettings' => null,
        ];

        // filters
        while (($context->filter = key($context->filters)) && ($context->filterSettings = array_shift(
                $context->filters
            )))
        {

            $args = apply_filters("acf/fields/geoname/filter/name={$context->filter}", $args, $context);
        }

        $args = apply_filters('acf/fields/geoname/query', $args, $field, $options);
        $args = apply_filters('acf/fields/geoname/query/name=' . $field['name'], $args, $field);
        $args = apply_filters('acf/fields/geoname/query/key=' . $field['key'], $args, $field, $options);

        // get locations grouped by top most ancestor
        $locations = Core::getLiveSearch($args);

        // bail early if no posts
        if (empty($locations))
        {
            return false;
        }

        $results = [];
        $country = null;

        if ($isSearch = $options['is_search'])
        {
            $data =& $results;
        }

        // loop
        while ($location = array_shift($locations))
        {

            $entry = [
                'id'   => $location->geonameId,
                'text' => sprintf('%s, %s', $location->name, $location->country->iso2),
            ];

            // order posts by search
            if (!$isSearch && $country !== $location->countryCode)
            {

                // vars
                $country = $location->countryCode;

                if (!array_key_exists($country, $results))
                {
                    $results[$country] = [
                        'text'     => $country,
                        'children' => [],
                    ];
                }

                $data =& $results[$country]['children'];

            }

            // append to $results
            $data[] = $entry;
        }

        // vars
        $response = [
            'results' => array_values($results),
            'limit'   => $args['maxRows'],
        ];

        // return
        return $response;
    }


    /**
     *  ajax_query
     *
     *  description
     *
     * @date      24/10/13
     * @since     5.0.0
     *
     * @param
     *
     * @return   void
     */
    public function ajax_query()
    {

        // validate
        if (!acf_verify_ajax())
        {
            die();
        }

        try
        {
            // get choices
            $response = $this->getData($_POST);
        }
        catch (\Exception $e)
        {
            wp_send_json_error(new WP_Error('error', $e->getMessage()), 503);
        }

        // return
        acf_send_ajax_results($response);
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
        $args,
        $context
    ): array {

        if ($context->field['countries_only'])
        {
            $args ['feature_class'] = array_keys(Core::FEATURE_FILTERS['countriesOnly']);
            $args ['feature_code']  = array_reduce(
                Core::FEATURE_FILTERS['countriesOnly'],
                static function (
                    $carry,
                    $item
                )
                {

                    return $carry + $item;
                },
                []
            );

            unset ($context->filters['feature_class'], $context->filters['feature_code'], $context->filters['habitation_only']);
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
        $args,
        $context
    ): array {

        if ($context->field['habitation_only'])
        {
            $args ['feature_class'] = array_keys(Core::FEATURE_FILTERS['habitationOnly']);
            $args ['feature_code']  = array_reduce(
                Core::FEATURE_FILTERS['habitationOnly'],
                static function (
                    $carry,
                    $item
                )
                {

                    return $carry + $item;
                },
                []
            );

            unset ($context->filters['feature_class'], $context->filters['feature_code'], $context->filters['countries_only']);
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
        $args,
        $context
    ): array {

        // search
        if ($context->options['s'] !== '')
        {

            // strip slashes (search may be integer)
            $s = wp_unslash((string)$context->options['s']);

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
        $post_id,
        $field
    ) {

        // bail early if no value
        if (empty($value))
        {

            return $value;
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
        wp_register_script('tbp-acf-geoname', "{$url}assets/js/input.js", ['acf-input'], $version);
        wp_enqueue_script('tbp-acf-geoname');

        // register & include CSS
        wp_register_style('tbp-acf-geoname', "{$url}assets/css/input.css", ['acf-input'], $version);
        wp_enqueue_style('tbp-acf-geoname');
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
    public function load_field($field): array
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
        $post_id,
        $field
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
     **/
    public function render_field($field)
    {

        // field settings
        $fieldSettings = static::getFieldSettingDefinitions();

        // filters
        $filters      = static::getFilterDefinitions(acf_get_array($field['filters']));
        $filter_count = count($filters);

        // div attributes
        $attributes = [
            'id'         => $field['id'],
            'class'      => "acf-geoname {$field['class']}",
            'data-min'   => $field['min'],
            'data-max'   => $field['max'],
            'data-paged' => 1,
        ];

        if ($field['max'] === 1)
        {
            $attributes['data-replace-selected'] = $field['replace_selected_value']
                ? 1
                : 0;
        }

        array_walk(
            $filters,
            static function (&$filter) use
            (
                &
                $attributes
            )
            {

                $filter['data-filter']                        = $filter['data-filter'] ?? $filter['name'];
                $attributes['data-' . $filter['data-filter']] = '';
            }
        );

        ?>
        <div <?php
        echo acf_esc_attrs($attributes); ?>>

            <?php
            acf_hidden_input(['name' => $field['name'], 'value' => '']); ?>

            <?php

            /* filters */
            if ($filter_count)
            {
                ?>
                <div class="filters -f<?php
                echo $field['one_filter_per_row']
                    ? 4
                    : $filter_count; ?>">
                    <?php

                    foreach ($filters as $filter => $filterSettings)
                    {
                        ?>
                        <div class="filter-instruction -<?php
                        echo esc_attr($filter); ?>"><?php

                            if ($field[$filter . '_display_instruction'])
                            {
                                echo $field[$filter . '_instruction_text'] ?? $filterSettings['instructions'];
                            }
                            ?></div>
                        <div class="filter -<?php
                        echo esc_attr($filter); ?>">
                            <?php

                            switch ($filterSettings['type'])
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
                                        [acf_get_array($field[$filter])]
                                    );
                                break;

                            default:
                                throw new \ErrorException("unknown filter input type $filter");
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

            $width  = ($field['choice_on_new_line'] ?? false)
                ? 100
                : 50;
            $height = ($width === 100 && (int)($field['max'] ?? 0) === 1)
                ? 'single-line'
                : '';

            ?>

            <div class="selection">
                <div class="selection-instruction choices-instruction"><?php

                    if ($field['selection_choices_display_instruction'])
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

                    if ($field['selection_values_display_instruction'])
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
                        if (!empty($field['value']))
                        {

                            // get posts
                            $locations = Core::getLocations(
                                [
                                    'location__in' => $field['value'],
                                ]
                            );

                            // loop
                            foreach ($locations as $location)
                            {
                                $key     = $location->geoname_id;
                                $caption = $field['name'] . ', ' . $field['country_code'];

                                ?>
                                <li>
                                    <?php
                                    acf_hidden_input(
                                        [
                                            'name'  => $caption,
                                            'value' => $key,
                                        ]
                                    ); ?>
                                    <span data-id="<?php
                                    echo esc_attr($key); ?>"
                                          class="acf-rel-item">
							<?php
                            echo acf_esc_html($caption); ?>
							<a href="#" class="acf-icon -minus small dark" data-name="remove_item"></a>
						</span>
                                </li>
                                <?php
                            } ?>
                            <?php
                        } ?>
                    </ul>
                </div>
            </div>
            <?php
            echo '<pre>';
            print_r($field);
            echo '</pre>';
            ?>
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
    public function render_field_settings($field)
    {

        // vars
        $field['min']  = empty($field['min'])
            ? ''
            : $field['min'];
        $field['max']  = empty($field['max'])
            ? ''
            : $field['max'];
        $filterChoices = [];

        foreach (self::getFilterDefinitions() as $filter => $setting)
        {

            $filterChoices[$filter] = $setting['caption'];

            if (array_key_exists('choices', $setting) && is_callable($setting['choices']))
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

                        if ($setting['isSetting'] ?? false)
                        {
                            acf_render_field_setting($field, $setting);
                        }

                        $logic = $setting['conditional_logic'] ?? [];

                        acf_render_field_setting(
                            $field,
                            [
                                'type'              => 'true_false',
                                'name'              => $filter . '_display_instruction',
                                'label'             => __('Display instruction', 'tbp-acf-geoname'),
                                'ui'                => 1,
                                'allow_null'        => 0,
                                'default'           => 1,
                                'conditional_logic' => $logic,
                            ]
                        );

                        if (empty($logic))
                        {
                            $logic = [[]];
                        }

                        array_walk(
                            $logic,
                            static function (&$condition) use
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
                                'label'             => __('Filter instructions', 'tbp-acf-geoname'),
                                'instructions'      => __('This text is shown before the field', 'tbp-acf-geoname'),
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
                'label'        => __('Filters', 'acf'),
                'instructions' => '',
                'choices'      => $filterChoices,
            ]
        );

        $searchTypes = [];

        foreach (ApiQuery::SEARCH_TYPES as $searchTypeBitmask => $searchTypeName)
        {
            $searchTypes[$searchTypeBitmask] = __($searchTypeName, 'tbp-acf-geoname');
        }

        acf_render_field_setting(
            $field,
            [
                'isSetting'    => true,
                'name'         => 'searchTypeDefaults',
                'type'         => 'checkbox',
                'label'        => __('Default search mode', 'tbp-acf-geoname'),
                'instructions' => '',
                'choices'      => $searchTypes,
            ]
        );

        acf_render_field_setting(
            $field,
            [
                'type'         => 'true_false',
                'name'         => 'searchTypesAllowUser',
                'label'        => __('User-defined Search Mode', 'tbp-acf-geoname'),
                'instructions' => __('Allow user to select from the following search types.', 'tbp-acf-geoname'),
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
                'label'             => __('Available Search Modes', 'tbp-acf-geoname'),
                'instructions'      => __('Allow user to select from these search modes.', 'tbp-acf-geoname'),
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

        foreach (self::getFieldSettingDefinitions() as $fieldName => $setting)
        {

            acf_render_field_setting($field, $setting);
        };

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
    public function update_field($field): array
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
        $post_id,
        $field
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
        $choices,
        $getter,
        $formatter = null
    ): array {

        if (is_array($choices) && is_string(key($choices)))
        {
            return $choices;
        }

        // get all country codes
        $all = $getter();

        if (!empty($choices))
        {
            $choices = array_intersect_key($all, array_flip($choices));
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
            )
            {

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
        $args,
        $context
    ): array {

        $filters = acf_get_array($context->field['filters']);

        if (!empty($context->options[$context->filter]) && in_array($context->filter, $filters, true))
        {

            $args[$context->filter] = acf_get_array($context->options[$context->filter]);
        }
        elseif (!empty($context->field[$context->filter]))
        {

            $args[$context->filter] = acf_get_array($context->field[$context->filter]);
        }
        else
        {

            $args[$context->filter] = null; // call_user_func( $getter );

        }

        return $args;
    }


    /**
     * @param  array  $country_codes
     *
     * @return array
     */
    public static function getCountryCodes($country_codes = []): array
    {

        return self::checkChoices($country_codes, [Core::class, 'getCountryCodes']);
    }


    protected static function getDefinitions(
        $array,
        $key
    ) {

        switch (true)
        {
        case $key === null:
            // no key given, return all keys
            return $array;

        case is_string($key):
            // key is a single key name
            return $array[$key];

        case is_array($key):

            // key is given, but empty. Return empty array
            if (empty($key))
            {
                return [];
            }

            // key is a list of field names
            if (!is_string(key($key)))
            {

                return array_intersect_key($array, array_flip($key));
            }

            // key is a set of properties to match
            return array_filter(
                $array,
                static function (&$item) use
                (
                    &
                    $key
                )
                {

                    foreach (array_keys($key) as $key)
                    {

                        if (!array_key_exists($key, $item))/**/
                        {
                            return false;
                        }

                        if ($item[$key] !== $key[$key])
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


    public static function getFeatureClasses($feature_classes = []): array
    {

        return self::checkChoices($feature_classes, [Core::class, 'getFeatureClasses']);
    }


    public static function getFeatureCodes($feature_codes = []): array
    {

        return self::checkChoices($feature_codes, [Core::class, 'getFeatureCodes']);
    }


    public static function getFieldSettingDefinitions($setting = null)
    {

        return self::getDefinitions(self::$fieldSettings, $setting);

    }


    public static function getFilterDefinitions($filter = null)
    {

        return self::getDefinitions(self::$filters, $filter);
    }

}
