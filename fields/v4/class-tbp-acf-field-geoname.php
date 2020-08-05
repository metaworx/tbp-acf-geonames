<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class tbp_acf_field_geoname extends tbp_acf_field_geoname_base {

	private static $instance = null;

	// vars
	var $settings, // will hold info such as dir / path
		$defaults; // will hold default field options


	/*
	*  __construct
	*
	*  Set name / label needed for actions / filters
	*
	*  @since	3.6
	*  @date	23/01/13
	*/

	function __construct( $settings ) {
		// vars
		$this->defaults = array(
			// add v4-specific default here to merge into your field.
			// This makes life easy when creating the field options as you don't need to use any if( isset('') ) logic. eg:
			//'preview_size' => 'thumbnail'
		);


		// do not delete!
		parent::__construct();

		// overwrite v4-specific category
		$this->category = __( "Basic", 'tbp-acf-geoname' ); // Basic, Content, Choice, etc

	}


	/*
	*  create_options()
	*
	*  Create extra options for your field. This is rendered when editing a field.
	*  The value of $field['name'] can be used (like below) to save extra data to the $field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field	- an array holding all the field's data
	*/

	function create_options( $field ) {
		// defaults?
		/*
		$field = array_merge($this->defaults, $field);
		*/

		// key is needed in the field names to correctly save the data
		$key = $field['name'];


		// Create Field Options HTML
		?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e( "Preview Size", 'tbp-acf-geoname' ); ?></label>
                <p class="description"><?php _e( "Thumbnail is advised", 'tbp-acf-geoname' ); ?></p>
            </td>
            <td>
				<?php

				do_action( 'acf/create_field', array(
					'type'    => 'radio',
					'name'    => 'fields[' . $key . '][preview_size]',
					'value'   => $field['preview_size'],
					'layout'  => 'horizontal',
					'choices' => array(
						'thumbnail'      => __( 'Thumbnail', 'tbp-acf-geoname' ),
						'something_else' => __( 'Something Else', 'tbp-acf-geoname' ),
					)
				) );

				?>
            </td>
        </tr>
		<?php

	}


	/*
	*  create_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field - an array holding all the field's data
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/

	function create_field( $field ) {
		// defaults?
		/*
		$field = array_merge($this->defaults, $field);
		*/

		// perhaps use $field['preview_size'] to alter the markup?


		// create Field HTML
		?>
        <div>

        </div>
		<?php
	}

}

?>