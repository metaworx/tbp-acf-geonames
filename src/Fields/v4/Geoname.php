<?php

namespace Tbp\WP\Plugin\AcfFields\Fields\v4;


class Geoname
    extends \Tbp\WP\Plugin\AcfFields\Fields\Geoname
{

//  public properties

    /*
    *  __construct
    *
    *  Set name / label needed for actions / filters
    *
    *  @since	3.6
    *  @date	23/01/13
    */

    function __construct( $settings )
    {

        // do not delete!
        parent::__construct( $settings );

        // overwrite v4-specific category
        $this->category = __( "Choice", 'tbp-acf-fields' ); // Basic, Content, Choice, etc

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

    function create_field( $field )
    {

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

    function create_options( $field )
    {

        // defaults?
        /*
        $field = array_merge($this->defaults, $field);
        */

        // key is needed in the field names to correctly save the data
        $key = $field['name'];

        // Create Field Options HTML
        ?>
        <tr class="field_option field_option_<?php
        echo $this->name; ?>">
            <td class="label">
                <label><?php
                    _e( "Preview Size", 'tbp-acf-fields' ); ?></label>
                <p class="description"><?php
                    _e( "Thumbnail is advised", 'tbp-acf-fields' ); ?></p>
            </td>
            <td>
                <?php

                do_action(
                    'acf/create_field',
                    [
                        'type'    => 'radio',
                        'name'    => 'fields[' . $key . '][preview_size]',
                        'value'   => $field['preview_size'],
                        'layout'  => 'horizontal',
                        'choices' => [
                            'thumbnail'      => __( 'Thumbnail', 'tbp-acf-fields' ),
                            'something_else' => __( 'Something Else', 'tbp-acf-fields' ),
                        ],
                    ]
                );

                ?>
            </td>
        </tr>
        <?php

    }

}


?>
