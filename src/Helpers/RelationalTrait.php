<?php

namespace Tbp\WP\Plugin\AcfFields\Helpers;

trait RelationalTrait
{

    protected function getFieldSettingsDefinition(): array
    {

        return array_column(
            [

                // choice layout
                [
                    'type'         => 'radio',
                    'name'         => 'choice_layout',
                    'label'        => __( 'Layout', 'tbp-acf-fields' ),
                    'instructions' => '',
                    'choices'      => [
                        'list'   => __( "List", 'tbp-acf-fields' ),
                        'select' => __( "Dynamic Selection", 'tbp-acf-fields' ),
                    ],
                    'layout'       => 'vertical',
                    'default'      => 'list',
                ],

                // selection limit
                [
                    'type'         => 'true_false',
                    'name'         => 'multiple',
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
                                'field'    => 'multiple',
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
                                'field'    => 'multiple',
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
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'list',
                            ],
                            [
                                'field'    => 'max',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                        [
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'list',
                            ],
                            [
                                'field'    => 'multiple',
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
                    'name'              => 'values_before_choices',
                    'label'             => __( 'Display selected values first', 'tbp-acf-fields' ),
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
                    'label'             => __( 'Display selected values on separate line', 'tbp-acf-fields' ),
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

                // select options

                [
                    'type'              => 'true_false',
                    'name'              => 'allow_null',
                    'label'             => __( 'Allow Null?', 'acf' ),
                    'allow_null'        => 0,
                    'ui'                => 1,
                    'default'           => 0,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'select',
                            ],
                        ],
                    ],
                ],

                [
                    'type'              => 'true_false',
                    'name'              => 'ui',
                    'label'             => __( 'Stylised UI', 'acf' ),
                    'allow_null'        => 0,
                    'ui'                => 1,
                    'default'           => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'select',
                            ],
                        ],
                    ],
                ],

                [
                    'type'              => 'true_false',
                    'name'              => 'ajax',
                    'label'             => __( 'Use AJAX to lazy load choices?', 'acf' ),
                    'allow_null'        => 0,
                    'ui'                => 1,
                    'default'           => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'select',
                            ],
                            [
                                'field'    => 'ui',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                    ],
                ],

                [
                    'type'              => 'true_false',
                    'name'              => 'allow_custom',
                    'label'             => __( 'Allow Custom', 'acf' ),
                    'message'           => __( "Allow 'custom' values to be added", 'acf' ),
                    'allow_null'        => 0,
                    'ui'                => 1,
                    'default'           => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'choice_layout',
                                'operator' => '==',
                                'value'    => 'select',
                            ],
                            [
                                'field'    => 'ui',
                                'operator' => '==',
                                'value'    => '1',
                            ],
                        ],
                    ],
                ],

                [
                    'type'              => 'text',
                    'name'              => 'placeholder',
                    'label'             => __( 'Placeholder', 'acf' ),
                    'instructions'      => __( 'Appears within the input', 'acf' ),
                    'allow_null'        => 1,
                    'ui'                => 0,
                    'conditional_logic' => [
                        [
                            'field'    => 'choice_layout',
                            'operator' => '==',
                            'value'    => 'list',
                        ],
                    ],
                    [
                        [
                            'field'    => 'choice_layout',
                            'operator' => '==',
                            'value'    => 'select',
                        ],
                        [
                            'field'    => 'ui',
                            'operator' => '==',
                            'value'    => '1',
                        ],
                    ],
                ],

                [
                    'type'              => 'text',
                    'name'              => 'search_placeholder',
                    'label'             => __( 'Search Input Placeholder', 'acf' ),
                    'instructions'      => __( 'Appears within the search input', 'acf' ),
                    'allow_null'        => 1,
                    'ui'                => 0,
                    'conditional_logic' => [
                        [
                            'field'    => 'choice_layout',
                            'operator' => '==',
                            'value'    => 'list',
                        ],
                    ],
                    [
                        [
                            'field'    => 'choice_layout',
                            'operator' => '==',
                            'value'    => 'select',
                        ],
                        [
                            'field'    => 'ui',
                            'operator' => '==',
                            'value'    => '1',
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
                        'object' => __( static::LABEL . " Object", 'tbp-acf-fields' ),
                        'id'     => __( static::LABEL . " ID", 'tbp-acf-fields' ),
                        'value'  => __( "Value", 'acf' ),
                        'label'  => __( "Label", 'acf' ),
                        'array'  => __( "Both (Array)", 'acf' ),
                    ],
                ],

                // storage_format
                [
                    'type'         => 'radio',
                    'name'         => 'storage_format',
                    'label'        => __( 'Storage Format', 'tbp-acf-fields' ),
                    'instructions' => 'In what format should the value(s) be stored in the database?',
                    'choices'      => [
                        'ID'        => __( static::LABEL . " ID", 'tbp-acf-fields' ),
                        'post_name' => __( static::LABEL . " post_name (slug)", 'tbp-acf-fields' ),
                    ],
                ],
            ],
            null,
            'name'
        );

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


    protected function getFilterDefinitionSearch(): array
    {

        return [
            'type'           => 'text',
            'name'           => 'search',
            'caption'        => __( "Search", 'acf' ),
            'placeholder'    => __( "Search...", 'acf' ),
            'data-filter'    => 's',
            'filterCallback' => [
                $this,
                'filterSearch',
            ],
        ];
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


    public function field_wrapper_attributes(
        $wrapper,
        $field
    ) {

        if ( $field['type'] !== static::NAME )
        {
            return $wrapper;
        }

        if ( $field['choice_layout'] ?? false )
        {
            $wrapper['data-layout'] = $field['choice_layout'];
        }

        return $wrapper;
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

        $value = \apply_filters( 'acf/format_value/name=' . $field['name'], $value, $object_id, $field );

        // bail early if no value
        if ( empty( $value ) || ! array_key_exists( 'return_format', $field ) )
        {

            return $value;
        }

        if ( is_array( $value )
            && ( ( $field['multiple'] ?? 0 ) === 0 || ( $field['max'] ?? null ) === 1 ) )
        {
            $value = reset( $value );
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

        // bail early if no enqueue
        if ( ! acf_get_setting( 'enqueue_select2' ) )
        {
            return;
        }

        // globals
        global $wp_scripts;

        // vars
        $min   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG
            ? ''
            : '.min';
        $major = acf_get_setting( 'select2_version' );

        // attempt to find 3rd party Select2 version
        // - avoid including v3 CSS when v4 JS is already enqueued
        if ( isset( $wp_scripts->registered['select2'] ) )
        {

            $major = (int) $wp_scripts->registered['select2']->ver;

        }

        // v4
        if ( $major == 4 )
        {

            $version = '4.0';
            $script  = acf_get_url( "assets/inc/select2/4/select2.full{$min}.js" );
            $style   = acf_get_url( "assets/inc/select2/4/select2{$min}.css" );

            // v3
        }
        else
        {

            $version = '3.5.2';
            $script  = acf_get_url( "assets/inc/select2/3/select2{$min}.js" );
            $style   = acf_get_url( "assets/inc/select2/3/select2.css" );

        }

        // enqueue
        wp_enqueue_script( 'select2', $script, [ 'jquery' ], $version );
        wp_enqueue_style( 'select2', $style, '', $version );
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

        $field = \apply_filters( 'acf/load_field/name=' . $field['name'], $field );

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
     */
    public function load_value(
        $value,
        $post_id,
        array $field
    ) {

        $value = \apply_filters( 'acf/load_value/name=' . $field['name'], $value, $post_id, $field );
        $value = \apply_filters( 'acf/load_value/key=' . $field['key'], $value, $post_id, $field );

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

        $field = \apply_filters( 'acf/render_field/name=' . $field['name'], $field );

        if ( empty( $field ) )
        {
            return;
        }

        // field settings
        $fieldSettings = $this->getFieldSettings();

        if ( $field['multiple'] ?? $fieldSettings['multiple']['default'] )
        {
            $field['max'] = 1;
        }

        if ( $field['required'] ?? false )
        {
            $field['min'] = 1;
        }

        switch ( $field['choice_layout'] ?? $fieldSettings['choice_layout']['default'] )
        {
        case 'list':
            $this->render_fieldAsList( $field, $fieldSettings );
            break;

        case 'fSelect':
        case 'select':
            $this->render_fieldAsSelect( $field, $fieldSettings );
            break;

        default:
            break;
        }
    }


    /**
     *  render_field()
     *
     *  Create the HTML interface for your field
     *
     * @since          3.6
     * @date           23/01/13
     *
     * @param  array  $field  (array) the $field being rendered
     * @param  array  $fieldSettings
     *
     * @throws \ErrorException
     * @method-type    action
     */
    public function render_fieldAsList(
        array &$field,
        array &$fieldSettings
    ): void {

        // filters
        $filters = $this->getFilters( acf_get_array( $field['filters'] ) );

        // div attributes
        $attributes = [
            'id'          => $field['id'],
            'class'       => sprintf(
                "tbp-acf tbp-acf-relation tbp-acf-relation-list tbp-acf-%s %s",
                static::NAME,
                $field['class']
            ),
            'data-layout' => 'list',
            'data-min'    => $field['min'],
            'data-max'    => $field['max'],
            'data-paged'  => 1,
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

        $replacements = [];

        $replacements['%width%']  = ( $field['choice_on_new_line'] ?? false )
            ? 100
            : 50;
        $replacements['%height%'] = ( $replacements['%width%'] === 100 && (int) ( $field['max'] ?? 0 ) === 1 )
            ? 'single-line'
            : '';

        $replacements['%attrs%']        = acf_esc_attrs( $attributes );
        $replacements['%hidden_field%'] = acf_get_hidden_input(
            [
                'name'  => $field['name'],
                'value' => '',
            ]
        );
        $replacements['%filter%']       = $this->render_field_filters( $field, $filters );

        if ( $field['selection_choices_display_instruction'] )
        {
            $replacements['%choices_instruction%'] = $field['selection_choices_instruction_text']
                ?: $fieldSettings['selection_choices_instruction_text']['placeholder'];
        }
        else
        {
            $replacements['%choices_instruction%'] = '';
        }

        if ( $field['selection_values_display_instruction'] )
        {
            $replacements['%values_instruction%'] = $field['selection_values_instruction_text']
                ?: $fieldSettings['selection_values_instruction_text']['placeholder'];
        }
        else
        {
            $replacements['%values_instruction%'] = '';
        }

        $replacements['%values%'] = $this->render_field_values(
            $field,
            static function (
                string $dataId,
                string $caption,
                bool $echo = true,
                ?string $template = null
            ) use
            (
                &
                $field
            )
            {

                $html = sprintf(
                    $template ??
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

                if ( $echo )
                {
                    echo $html;
                }

                return $html;
            },
            false
        );

        $html = $field['values_before_choices']
            ? <<<HTML
<div %attrs%>
    %hidden_field%
    <div class="selection tbp-acf-selection tbp-acf">
        <div class="values values-%width% tbp-acf">
            <ul class="acf-bl list values-list %height% tbp-acf">
                %values%
            </ul>
        </div>
        <div class="selection-instruction values-instruction tbp-acf">%values_instruction%</div>
        %filter%
        <div class="selection-instruction choices-instruction tbp-acf">%choices_instruction%</div>
        <div class="choices choices-%width% tbp-acf">
            <ul class="acf-bl list choices-list tbp-acf"></ul>
        </div>
    </div>
</div>

HTML

            : <<<HTML
<div %attrs%>
    %hidden_field%
    %filter%
    <div class="selection tbp-acf">
        <div class="selection-instruction choices-instruction tbp-acf">%choices_instruction%</div>
        <div class="choices choices-%width% tbp-acf">
            <ul class="acf-bl list choices-list tbp-acf"></ul>
        </div>
        <div class="selection-instruction values-instruction tbp-acf">%values_instruction%</div>
        <div class="values values-%width% tbp-acf">
            <ul class="acf-bl list values-list %height% tbp-acf">
                %values%
            </ul>
        </div>
    </div>
</div>

HTML;

        $html = strtr( $html, $replacements );

        echo $html;
    }


    public function render_fieldAsSelect(
        array &$field,
        array &$fieldSettings
    ): void {

        // Change Field into a select.
        $field['type']       = 'select';
        $field['ui']         = $field['ui'] ?? $fieldSettings['ui']['default'] ?? 1;
        $field['ajax']       = $field['ui']
            && $field['ajax'] ?? $fieldSettings['ajax']['default'] ?? 0; // ajax can only work if UI is enabled
        $field['allow_null'] = $field['allow_null'] ?? $fieldSettings['allow_null']['default'] ?? 1;
        // $field['readonly']
        // $field['disabled']
        // $field['ajax_action']
        // $field['id'],
        $field['wrapper']['class'] = ( $field['wrapper']['class'] ?? '' ) . ' tbp-acf-relation-select';
        $field['class']            = $field['wrapper']['class'];

        if ( empty( $field['ajax'] ) )
        {
            $field['choices'] = [];

            // Populate choices.

            $this->render_field_values(
                $field,
                static function (
                    string $dataId,
                    string $caption,
                    ?string $template = null
                ) use
                (
                    &
                    $field
                )
                {

                    $field['choices'][ $dataId ] = $caption;
                }
            );
        }

        // render.
        ob_start();
        acf_render_field( $field );
        $html = ob_get_clean();

        $attrs = [
            'data-layout' => 'select',
        ];

        $attr = acf_esc_attrs( $attrs );

        $html = preg_replace( '/ data-/', " $attr data-", $html, 1 );

        echo $html;
    }


    protected function render_field_filters(
        array &$field,
        array &$filters,
        $echo = false
    ): string {

        /* filters */
        if ( 0 === ( $filter_count = count( $filters ) ) )
        {
            return '';
        }

        $html = sprintf(
            '<div class="filters -f%d">',
            $field['one_filter_per_row']
                ? 4
                : $filter_count
        );

        foreach ( $filters as $filter => $filterSettings )
        {
            $replacements = [];

            $replacements['%id%']     = sprintf( '%s-filter-%s', esc_attr( $field['key'] ), esc_attr( $filter ) );
            $replacements['%filter%'] = esc_attr( $filter );

            if ( $field[ $filter . '_display_instruction' ] )
            {
                $replacements['%instructions%']
                    = $field[ $filter . '_instruction_text' ] ?? $filterSettings['instructions'];
            }
            else
            {
                $replacements['%instructions%'] = '';
            }

            switch ( $filterSettings['type'] )
            {
            case 'text':
                $function = 'acf_text_input';
                $script   = '';
                break;

            case 'select':
                $function                  = 'acf_select_input';
                $filterSettings['choices'] = [
                        '' => $filterSettings['select_label'],
                    ]
                    + $filterSettings['choices']( acf_get_array( $field[ $filter ] ) );

                $script
                    = '<script>document.addEventListener("DOMContentLoaded",function() { jQuery("#%id% > select").select2({width: \'100%\'});} );</script>';
                break;

            default:
                throw new \ErrorException( "unknown filter input type $filter" );
            }

            ob_start();
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
            $replacements['%html%'] = ob_get_clean();

            $html .= strtr(
                <<<HTML

 <div id="%id%-instruction" class="filter-instruction -%filter%">%instructions%</div>
 <div id="%id%" class="filter -%filter%">
    %html%
</div>$script

HTML,
                $replacements
            );

        }

        $html .= '</div>';

        if ( $echo )
        {
            echo $html;
        }

        return $html;
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
    public function render_field_settings( $field ): void
    {

        // vars
        $field['min'] = empty( $field['min'] )
            ? ''
            : $field['min'];
        $field['max'] = empty( $field['max'] )
            ? ''
            : $field['max'];

        $settingFields = $this->getFieldSettings();
        $filters       = $this->getFilters();

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


    abstract protected function render_field_values(
        array &$field,
        callable $render,
        bool $echo = true
    ): string;


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

        ksort( $field );

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
     * @param  int|string  $post_id  the $post_id from which the value was loaded or user_$userID for users
     * @param  array       $field    the field array holding all the field options
     *
     * @return   mixed      $value
     */
    public function update_value(
        $value,
        $post_id,
        $field
    ) {

        $value = \apply_filters( 'acf/update_value/name=' . $field['name'], $value, $post_id, $field );
        $value = \apply_filters( 'acf/update_value/key=' . $field['key'], $value, $post_id, $field );

        if ( empty( $value ) )
        {
            return null;
        }

        if ( is_array( $value ) && ( ( $field['multiple'] ?? 1 ) === 0 || ( $field['max'] ?? 0 ) === 1 ) )
        {
            $value = reset( $value );
        }

        return $value;
    }

}