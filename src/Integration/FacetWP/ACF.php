<?php

namespace Tbp\WP\Plugin\AcfFields\Integration\FacetWP;

class ACF
    extends
    \FacetWP_Integration_ACF
{

//  public properties

    /** @var array */
    public $types = [];


    /**
     * ACF constructor.
     *
     * @noinspection PhpMissingParentConstructorInspection
     */
    function __construct()
    {

        $this->fields = array_column( $this->get_fields(), null, 'key' );
    }


    function flatten_fields(
        $fields,
        $field_group,
        $hierarchy = '',
        $parents = ''
    ) {

        $self = $this;

        array_walk(
            $fields,
            static function ( array $field ) use
            (
                &
                $self
            )
            {

                $self->types[ $field['type'] ][] = $field['key'];
            }
        );

        parent::flatten_fields( $fields, $field_group, $hierarchy, $parents );
    }

}