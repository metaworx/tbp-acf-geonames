<?php

namespace Tbp\WP\Plugin\AcfFields;

interface FieldInterface
{

    public function initialize(): void;


    public function getFieldSettings( $setting = null ): ?array;


    /**
     *  ajax_query
     *
     *  description
     *
     * @date         24/10/13
     * @since        5.0.0
     *
     *
     * @return   void
     */
    public function ajax_query();


    /**
     * Add ACF fields to the Data Sources dropdown
     *
     * @param                                           $sources
     * @param  \Tbp\WP\Plugin\AcfFields\FieldInterface  $field
     * @param  array                                    $acfFields
     *
     * @return array
     */
    public function facetwpFacetSources(
        $sources,
        FieldInterface $field,
        array $acfFields
    ): array;


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
     */
    public function render_field( $field );


    function validate_field( $field );


    public static function Factory( $settings ): FieldInterface;


    /**
     * @param  array   $args
     * @param  object  $context
     *
     * @return array
     */
    public static function filterDefault(
        array $args,
        object $context
    ): array;

}