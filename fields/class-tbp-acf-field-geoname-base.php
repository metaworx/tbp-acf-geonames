<?php

// exit if accessed directly
use WPGeonames\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class tbp_acf_field_geoname_base extends acf_field {

	protected static $instance = null;
	protected static $filters = null;

	protected $settings = [];  // will hold info such as dir / path

	/*
	*  __construct
	*
	*  This function will setup the field type data
	*
	*  @type	function
	*  @date	2020-07-19
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/

	function __construct( $settings = [] ) {

		/*
		*  name (string) Single word, no spaces. Underscores allowed
		*/
		$this->name = 'geoname';

		/*
		*  label (string) Multiple words, can include spaces, visible when selecting a field type
		*/
		$this->label = __( 'Geo Names', 'tbp-acf-geoname' );

		/*
		*  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
		*/
		$this->category = 'relational';

		/*
		*  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
		*/
		$this->defaults = wp_parse_args( $this->defaults, array(
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
		) );

		/*
		*  settings (array) Store plugin settings (url, path, version) as a reference for later use with assets
		*/
		$this->settings = $settings;

		self::$filters = [

			// habitation only
			'habitation_only' => [
				'isSetting'      => true,
				'type'           => 'true_false',
				'name'           => 'habitation_only',
				'caption'        => __( "Habitations only", 'tbp-acf-geoname' ),
				'label'          => __( 'Filter habitations only', 'tbp-acf-geoname' ),
				'instructions'   => '',
				'allow_null'     => 0,
				'default_value'  => true,
				'ui'             => true,
				'filterCallback' => [ $this, 'filterHabitationOnly' ],
			],

			// feature_class
			'feature_class'   => [
				'isSetting'         => true,
				'type'              => 'select',
				'name'              => 'feature_class',
				'caption'           => __( "Feature Class", 'tbp-acf-geoname' ),
				'label'             => __( 'Filter by', 'tbp-acf-geoname' ) . ' ' . __( 'feature class', 'tbp-acf-geoname' ),
				'select_label'      => __( 'Select', 'tbp-acf-geoname' ) . ' ' . __( 'feature class', 'tbp-acf-geoname' ),
				'instructions'      => '',
				'choices'           => [ static::class, 'getFeatureClasses' ],
				'multiple'          => 1,
				'ui'                => 1,
				'allow_null'        => 1,
				'placeholder'       => __( "All feature classes", 'tbp-acf-geoname' ),
				'conditional_logic' => [
					[
						[
							'field'    => 'habitation_only',
							'operator' => '!=',
							'value'    => '1',
						],
					]
				]
			],

			// feature_code
			'feature_code'    => [
				'isSetting'         => true,
				'type'              => 'select',
				'name'              => 'feature_code',
				'caption'           => __( "Feature Code", 'tbp-acf-geoname' ),
				'label'             => __( 'Filter by', 'tbp-acf-geoname' ) . ' ' . __( 'Feature Code', 'tbp-acf-geoname' ),
				'select_label'      => __( 'Select', 'tbp-acf-geoname' ) . ' ' . __( 'Feature Code', 'tbp-acf-geoname' ),
				'instructions'      => '',
				'choices'           => [ static::class, 'getFeatureCodes' ],
				'multiple'          => 1,
				'ui'                => 1,
				'allow_null'        => 1,
				'placeholder'       => __( "All feature codes", 'tbp-acf-geoname' ),
				'conditional_logic' => [
					[
						[
							'field'    => 'habitation_only',
							'operator' => '!=',
							'value'    => '1',
						],
					]
				]
			],

			// country
			'country_code'    => [
				'isSetting'    => true,
				'name'         => 'country_code',
				'type'         => 'select',
				'caption'      => __( "Country", 'tbp-acf-geoname' ),
				'label'        => __( 'Filter by', 'tbp-acf-geoname' ) . ' ' . __( 'country', 'tbp-acf-geoname' ),
				'select_label' => __( "All countries", 'tbp-acf-geoname' ),
				'instructions' => '',
				'choices'      => [ static::class, 'getCountryCodes' ],
				'multiple'     => 1,
				'ui'           => 1,
				'allow_null'   => 1,
				'placeholder'  => __( "All countries", 'tbp-acf-geoname' ),
			],

			// search
			'search'          => [
				'type'           => 'text',
				'name'           => 'search',
				'caption'        => __( "Search", 'acf' ),
				'placeholder'    => __( "Search...", 'acf' ),
				'data-filter'    => 's',
				'filterCallback' => [ $this, 'filterSearch' ],
			],

		];

		// do not delete!
		parent::__construct();

	}

	function initialize() {

		/*
		*  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
		*  var message = acf._e('geoname', 'error');
		*/
		$this->l10n = array(
			'error' => __( 'Error! Please enter a higher value', 'tbp-acf-geoname' ),
		);

		// extra
		add_action( 'wp_ajax_acf/fields/geoname/query', array( $this, 'ajax_query' ) );
		add_action( 'wp_ajax_nopriv_acf/fields/geoname/query', array( $this, 'ajax_query' ) );

		array_walk( self::$filters, static function ( $filterSettings, $filter ) {

			if ( array_key_exists( 'filterCallback', $filterSettings ) && $filterSettings['filterCallback'] === false ) {
				return;
			}

			add_filter(
				"acf/fields/geoname/filter/name=$filter",
				array_key_exists( 'filterCallback', $filterSettings ) && is_callable( $filterSettings['filterCallback'] )
					? $filterSettings['filterCallback']
					: [ static::class, 'filterDefault' ],
				10,
				2
			);

		} );

	}

	/**
	 * @param array $args
	 * @param object $context
	 *
	 * @return array
	 */
	static function filterDefault( $args, $context ) {

		$filters = acf_get_array( $context->field['filters'] );

		if ( in_array( $context->filter, $filters ) && ! empty( $context->options[ $context->filter ] ) ) {

			$args[ $context->filter ] = acf_get_array( $context->options[ $context->filter ] );

		} elseif ( ! empty( $context->field[ $context->filter ] ) ) {

			$args[ $context->filter ] = acf_get_array( $context->field[ $context->filter ] );

		} else {

			$args[ $context->filter ] = null; // call_user_func( $getter );

		}

		return $args;

	}


	/**
	 * @param array $args
	 * @param object $context
	 *
	 * @return array
	 */
	function filterSearch( $args, $context ) {

		// search
		if ( $context->options['s'] !== '' ) {

			// strip slashes (search may be integer)
			$s = wp_unslash( strval( $context->options['s'] ) );


			// update vars
			$args['s']                     = $s;
			$context->options['is_search'] = true;

		} else {
			$args['s']       = 'a';
			$args['maxRows'] = 20;
		}

		return $args;

	}

	/**
	 * @param array $args
	 * @param object $context
	 *
	 * @return array
	 */
	function filterHabitationOnly( $args, $context ) {

		if ( $context->field['habitation_only'] ) {
			$args ['feature_class'] = [ 'P' ];
			$args ['feature_code']  = [
				'PPL',
				'PPLA',
				'PPLA2',
				'PPLA3',
				'PPLA4',
				'PPLC'
			];

			unset ( $context->filters['feature_class'], $context->filters['feature_code'] );
		}

		return $args;
	}

	/*
	*  ajax_query
	*
	*  description
	*
	*  @type	function
	*  @date	24/10/13
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	function ajax_query() {

		// validate
		if ( ! acf_verify_ajax() ) {
			die();
		}

		// get choices
		$response = $this->get_ajax_query( $_POST );

		// return
		acf_send_ajax_results( $response );

	}


	/*
	*  get_ajax_query
	*
	*  This function will return an array of data formatted for use in a select2 AJAX response
	*
	*  @type	function
	*  @date	15/10/2014
	*  @since	5.0.9
	*
	*  @param	$options (array)
	*  @return	(array)
	*/

	function get_ajax_query( $options = array() ) {

		// load field
		$field = acf_get_field( $options['field_key'] );

		if ( ! $field ) {
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

		$options = wp_parse_args( $options, $defaults );


		// vars
		$results = [];
		$args    = [];


		// paged
		$args['maxRows'] = 20;
		$args['paged']   = intval( $options['paged'] );

		$context = (object) [
			'field'          => &$field,
			'filters'        => static::getFilterDefinitions(),
			'options'        => &$options,
			'filter'         => null,
			'filterSettings' => null,
		];

		// filters
		while ( ( $context->filter = key( $context->filters ) ) && ( $context->filterSettings = array_shift( $context->filters ) ) ) {

			$args = apply_filters( "acf/fields/geoname/filter/name={$context->filter}", $args, $context );

		}

		$args = apply_filters( 'acf/fields/geoname/query', $args, $field, $options );
		$args = apply_filters( 'acf/fields/geoname/query/name=' . $field['name'], $args, $field );
		$args = apply_filters( 'acf/fields/geoname/query/key=' . $field['key'], $args, $field, $options );


		// get locations grouped by top most ancestor
		$locations = Core::getLiveSearch( $args );


		// bail early if no posts
		if ( empty( $locations ) ) {
			return false;
		}


		$results = [];


		// loop
		while ( $location = array_shift( $locations ) ) {

			$entry = [
				'id'   => $location->geonameId,
				'text' => sprintf( '%s, %s', $location->name, $location->countryCode ),
			];

			// order posts by search
			if ( ! $options['is_search'] ) {

				// vars
				$country = $location->countryCode;

				if ( array_key_exists( $country, $results ) ) {
					$data = [
						'text'     => $country,
						'children' => []
					];

					$results[ $country ] = &$data;
				} else {
					$data = &$results[ $country ];
				}

			} else {
				$data =& $results;
			}


			// append to $results
			$results[] = $entry;

		}

		// vars
		$response = array(
			'results' => $results,
			'limit'   => $args['maxRows']
		);


		// return
		return $response;

	}


	/*
	*  render_field_settings()
	*
	*  Create extra settings for your field. These are visible when editing a field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	function render_field_settings( $field ) {

		// vars
		$field['min']  = empty( $field['min'] ) ? '' : $field['min'];
		$field['max']  = empty( $field['max'] ) ? '' : $field['max'];
		$filterChoices = [];

		foreach ( self::getFilterDefinitions() as $filter => $setting ) {

			$filterChoices[ $filter ] = $setting['caption'];

			if ( ! $setting['isSetting'] ?: false ) {
				continue;
			}

			if ( array_key_exists( 'choices', $setting ) && is_callable( $setting['choices'] ) ) {
				$setting['choices'] = $setting['choices']();
			}

			acf_render_field_setting( $field, $setting );

		}

		// filters
		acf_render_field_setting( $field, [
			'isSetting'    => true,
			'name'         => 'filters',
			'type'         => 'checkbox',
			'label'        => __( 'Filters', 'acf' ),
			'instructions' => '',
			'choices'      => $filterChoices,
		] );

		// filter layout
		acf_render_field_setting( $field, [
			'type'       => 'true_false',
			'name'       => 'one_filter_per_row',
			'label'      => __( 'Display one filter per row', 'tbp-acf-geoname' ),
			'ui'         => 1,
			'allow_null' => 0,
			'default'    => false,
		] );

		// choice layout
		acf_render_field_setting( $field, [
			'type'       => 'true_false',
			'name'       => 'choice_on_new_line',
			'label'      => __( 'Display selected values on new line', 'tbp-acf-geoname' ),
			'ui'         => 1,
			'allow_null' => 0,
			'default'    => false,
		] );

		// min
		acf_render_field_setting( $field, array(
			'label'        => __( 'Minimum locations', 'tbp-acf-geoname' ),
			'instructions' => '',
			'type'         => 'number',
			'name'         => 'min',
		) );

		// max
		acf_render_field_setting( $field, array(
			'label'        => __( 'Maximum locations', 'tbp-acf-geoname' ),
			'instructions' => '',
			'type'         => 'number',
			'name'         => 'max',
		) );

		// return_format
		acf_render_field_setting( $field, array(
			'label'        => __( 'Return Format', 'acf' ),
			'instructions' => '',
			'type'         => 'radio',
			'name'         => 'return_format',
			'choices'      => array(
				'object' => __( "Geoname Location Object", 'tbp-acf-geoname' ),
				'id'     => __( "Geoname ID", 'tbp-acf-geoname' ),
			),
			'layout'       => 'horizontal',
		) );

	}


	static function getFilterDefinitions( $filter = null ) {


		switch ( true ) {
			case $filter === null:
				// no filter given, return all filters
				return self::$filters;

			case is_string( $filter ):
				// filter is a single filter name
				return self::$filters[ $filter ];

			case is_array( $filter ):

				// filter is given, but empty. Return empty array
				if ( empty( $filter ) ) {
					return [];
				}

				// filter is a list of field names
				if ( ! is_string( key( $filter ) ) ) {

					return array_intersect_key( self::$filters, array_flip( $filter ) );
				}

				// filter is a set of properties to match
				return array_filter( self::$filters, static function ( &$item ) use ( &$filter ) {

					foreach ( array_keys( $filter ) as $key ) {

						if ( ! array_key_exists( $key, $item ) )/**/ {
							return false;
						}

						if ( $item[ $key ] != $filter[ $key ] ) {
							return false;
						}

					}

					return true;
				} );
		}

		return null;
	}

	static function getFeatureClasses( $feature_classes = [] ) {

		return self::checkChoices( $feature_classes, [ 'WPGeonames\Core', 'getFeatureClasses' ] );

	}


	/**
	 * @param array $feature_codes
	 *
	 * @return array
	 */
	static function getFeatureCodes( $feature_codes = [] ) {

		return self::checkChoices( $feature_codes, [ 'WPGeonames\Core', 'getFeatureCodes' ] );

	}


	/**
	 * @param array $country_codes
	 *
	 * @return array
	 */
	static function getCountryCodes( $country_codes = [] ) {

		return self::checkChoices( $country_codes, [ 'WPGeonames\Core', 'getCountryCodes' ] );

	}


	/**
	 * @param array $choices
	 * @param callable $getter
	 * @param callable|null $formatter
	 *
	 * @return array
	 */
	static function &checkChoices( $choices, $getter, $formatter = null ) {

		if ( is_array( $choices ) && is_string( key( $choices ) ) ) {
			return $choices;
		}

		// get all country codes
		$all = $getter();

		if ( ! empty( $choices ) ) {
			$choices = array_intersect_key( $all, array_flip( $choices ) );
		} else {
			$choices = $all;
		}

		array_walk( $choices, $formatter ?: static function ( &$caption, $key ) {
			$caption = "$key: $caption";
		} );

		return $choices;

	}

	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field (array) the $field being rendered
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	function render_field( $field ) {

		// filters
		$filters      = static::getFilterDefinitions( acf_get_array( $field['filters'] ) );
		$filter_count = count( $filters );

		// div attributes
		$attributes = array(
			'id'         => $field['id'],
			'class'      => "acf-geoname {$field['class']}",
			'data-min'   => $field['min'],
			'data-max'   => $field['max'],
			'data-paged' => 1,
		);


		array_walk( $filters, static function ( &$filter ) use ( &$attributes ) {
			$filter['data-filter']                          = $filter['data-filter'] ?? $filter['name'];
			$attributes[ 'data-' . $filter['data-filter'] ] = '';
		} );


		?>
        <div <?php echo acf_esc_attrs( $attributes ); ?>>

			<?php acf_hidden_input( array( 'name' => $field['name'], 'value' => '' ) ); ?>

			<?php

			/* filters */
			if ( $filter_count ) {
				?>
                <div class="filters -f<?php echo $field['one_filter_per_row'] ? 4 : $filter_count; ?>">
					<?php

					foreach ( $filters as $filter => $filterSettings ) {
						?>
                        <div class="filter -<?php echo esc_attr( $filter ); ?>">
							<?php

							switch ( $filterSettings['type'] ) {
								case 'text':
									$function = 'acf_text_input';
									break;

								case 'select':
									$function                  = 'acf_select_input';
									$filterSettings['choices'] = [
										                             '' => $filterSettings['select_label']
									                             ]
									                             + call_user_func_array(
										                             $filterSettings['choices'],
										                             [ acf_get_array( $field[ $filter ] ) ]
									                             );
									break;

								default:
									die ( "unknown filter input type $filter" );
							}

							call_user_func_array( $function, [
								array_intersect_key( $filterSettings, array_flip( [
									'placeholder',
									'data-filter',
									'choices',
								] ) )
							] );

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
			$height = ( $width === 100 && intval( $field['max'] ?? 0 ) === 1 )
				? 'single-line'
				: '';

			?>

            <div class="selection">
                <div class="choices choices-<?php echo $width; ?>">
                    <ul class="acf-bl list choices-list"></ul>
                </div>
                <div class="values values-<?php echo $width; ?>">
                    <ul class="acf-bl list values-list <?php echo $height; ?>">
						<?php if ( ! empty( $field['value'] ) ) {

							// get posts
							$locations = Core::getLocations( [
								'location__in' => $field['value']
							] );

							// loop
							foreach ( $locations as $location ) {
								$key     = $location->geoname_id;
								$caption = $field['name'] . ', ' . $field['country_code'];

								?>
                                <li>
									<?php acf_hidden_input( array(
										'name'  => $caption,
										'value' => $key
									) ); ?>
                                    <span data-id="<?php echo esc_attr( $key ); ?>"
                                          class="acf-rel-item">
							<?php echo acf_esc_html( $caption ); ?>
							<a href="#" class="acf-icon -minus small dark" data-name="remove_item"></a>
						</span>
                                </li>
							<?php } ?>
						<?php } ?>
                    </ul>
                </div>
            </div>
			<?php
			echo '<pre>';
			print_r( $field );
			echo '</pre>';
			?>
        </div>
		<?php
	}


	/*
	*  input_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	*  Use this action to add CSS + JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/


	function input_admin_enqueue_scripts() {

		// vars
		$url     = $this->settings['url'];
		$version = $this->settings['version'];


		// register & include JS
		wp_register_script( 'tbp-acf-geoname', "{$url}assets/js/input.js", array( 'acf-input' ), $version );
		wp_enqueue_script( 'tbp-acf-geoname' );


		// register & include CSS
		wp_register_style( 'tbp-acf-geoname', "{$url}assets/css/input.css", array( 'acf-input' ), $version );
		wp_enqueue_style( 'tbp-acf-geoname' );

	}




	/*
	*  input_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your render_field() action.
	*
    *  @info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_head
    *  @type	action (admin_head)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*

	function input_admin_head() {



	}

	*/


	/*
	*  input_admin_footer()
	*
	*  This action is called in the admin_footer action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_footer)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*

	function input_admin_footer() {



	}

	*/


	/*
	*  field_group_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
	*  Use this action to add CSS + JavaScript to assist your render_field_options() action.
	*
	*  $info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*

	function field_group_admin_enqueue_scripts() {

	}

	*/


	/*
	*  field_group_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is edited.
	*  Use this action to add CSS and JavaScript to assist your render_field_options() action.
	*
	*  @info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_head
	*  @type	action (admin_head)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*

	function field_group_admin_head() {

	}

	*/


	/*
	*  load_value()
	*
	*  This filter is applied to the $value after it is loaded from the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/

	/*

	function load_value( $value, $post_id, $field ) {

		return $value;

	}

	*/


	/*
	*  update_value()
	*
	*  This filter is applied to the $value before it is saved in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/

	/*

	function update_value( $value, $post_id, $field ) {

		return $value;

	}

	*/


	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value which was loaded from the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*
	*  @return	$value (mixed) the modified value
	*/

	/*

	function format_value( $value, $post_id, $field ) {

		// bail early if no value
		if( empty($value) ) {

			return $value;

		}


		// apply setting
		if( $field['font_size'] > 12 ) {

			// format the value
			// $value = 'something';

		}


		// return
		return $value;
	}

	*/


	/*
	*  load_field()
	*
	*  This filter is applied to the $field after it is loaded from the database
	*
	*  @type	filter
	*  @date	23/01/2013
	*  @since	3.6.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	$field
	*/

	/*

	function load_field( $field ) {

		return $field;

	}

	*/


	/*
	*  update_field()
	*
	*  This filter is applied to the $field before it is saved to the database
	*
	*  @type	filter
	*  @date	23/01/2013
	*  @since	3.6.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	$field
	*/

	/*

	function update_field( $field ) {

		return $field;

	}

	*/


	/**
	 * @param $file
	 *
	 * @return tbp_acf_field_geoname
	 */
	static function Factory( $file ) {
		return self::$instance ?: self::$instance = new self( $file );
	}

}

