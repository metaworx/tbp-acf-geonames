<?php

namespace Tbp\WP\Plugin\AcfFields\Fields\v5;

class Geoname
    extends
    \Tbp\WP\Plugin\AcfFields\Fields\Geoname
{

    /*
       *  input_form_data()
       *
       *  This function is called once on the 'input' page between the head and footer
       *  There are 2 situations where ACF did not load during the 'acf/input_admin_enqueue_scripts' and
       *  'acf/input_admin_head' actions because ACF did not know it was going to be used. These situations are
       *  seen on comments / user edit forms on the front end. This function will always be called, and includes
       *  $args that related to the current screen such as $args['post_id']
       *
       *  @type	function
       *  @date	6/03/2014
       *  @since	5.0.0
       *
       *  @param	$args (array)
       *  @return	n/a
       */

    /*

    function input_form_data( $args ) {



    }

    */

    /*
    *  validate_value()
    *
    *  This filter is used to perform validation on the value prior to saving.
    *  All values are validated regardless of the field's required setting. This allows you to validate and return
    *  messages to the user if the value is not correct
    *
    *  @type	filter
    *  @date	11/02/2014
    *  @since	5.0.0
    *
    *  @param	$valid (boolean) validation status based on the value and the field's required setting
    *  @param	$value (mixed) the $_POST value
    *  @param	$field (array) the field array holding all the field options
    *  @param	$input (string) the corresponding input name for $_POST value
    *  @return	$valid
    */

    /*

    function validate_value( $valid, $value, $field, $input ){

        // Basic usage
        if( $value < $field['custom_minimum_setting'] )
        {
            $valid = false;
        }


        // Advanced usage
        if( $value < $field['custom_minimum_setting'] )
        {
            $valid = __('The value is too little!','tbp-acf-fields'),
        }


        // return
        return $valid;

    }

    */

    /*
    *  delete_value()
    *
    *  This action is fired after a value has been deleted from the db.
    *  Please note that saving a blank value is treated as an update, not a delete
    *
    *  @type	action
    *  @date	6/03/2014
    *  @since	5.0.0
    *
    *  @param	$post_id (mixed) the $post_id from which the value was deleted
    *  @param	$key (string) the $meta_key which the value was deleted
    *  @return	n/a
    */

    /*

    function delete_value( $post_id, $key ) {



    }

    */

    /*
    *  delete_field()
    *
    *  This action is fired after a field is deleted from the database
    *
    *  @type	action
    *  @date	11/02/2014
    *  @since	5.0.0
    *
    *  @param	$field (array) the field array holding all the field options
    *  @return	n/a
    */

    /*

    function delete_field( $field ) {



    }

    */
}

