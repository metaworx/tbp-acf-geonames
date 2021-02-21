<?php
/** @noinspection AutoloadingIssuesInspection */

namespace Tbp\WP\Plugin\AcfFields\Integration\WPAI;

require_once WP_PLUGIN_DIR . '/wpai-acf-add-on/libraries/acf-fields/fieldInterface.php';
require_once WP_PLUGIN_DIR . '/wpai-acf-add-on/libraries/acf-fields/field.php';

use wpai_acf_add_on\acf\ACFService;
use wpai_acf_add_on\acf\fields\Field;

class TbpWpaiField
    extends
    Field
{

    /**
     * Field constructor.
     *
     * @param $field
     * @param $post
     * @param $field_name
     * @param $parent_field
     *
     */
    public function __construct(
        $field,
        $post,
        $field_name = "",
        $parent_field = false
    ) {

        parent::__construct( $field, $post, $field_name, $parent_field );
    }


    /**
     * @return false|int|mixed|string
     */
    public function getFieldValue()
    {

        $xpath = $this->getOption( 'xpath' );

        $values = parent::getFieldValue();

        if ( ! is_array( $values ) )
        {
            $values = explode( $xpath['delim'], $values );
        }

        $values = array_filter( $values );

        if ( empty( $values ) )
        {
            return null;
        }

        if ( 1 === count( $values ) )
        {
            return reset( $values );
        }

        return $values;
    }


    /**
     * @param         $importData
     * @param  array  $args
     *
     * @return mixed
     */
    public function import(
        $importData,
        $args = []
    ) {

        $isUpdated = parent::import( $importData, $args );
        if ( ! $isUpdated )
        {
            return false;
        }

        ACFService::update_post_meta( $this, $this->getPostID(), $this->getFieldName(), $this->getFieldValue() );

        return true;
    }


    /**
     *
     * Parse field data
     *
     * @param         $xpath
     * @param         $parsingData
     * @param  array  $args
     */
    public function parse(
        $xpath,
        $parsingData,
        $args = []
    ) {

        parent::parse( $xpath, $parsingData, $args );
        $xpath  = is_array( $xpath )
            ? $xpath['value']
            : $xpath;
        $values = $this->getByXPath( $xpath );
        $this->setOption( 'values', $values );
    }


    /**
     *  Render field
     */
    public function view()
    {

        $this->renderHeader();
        extract( $this->data );
        $fields = $this->getSubFields();
        switch ( $this->supportedVersion )
        {
        case 'v4':
        case 'v5':
            $fieldDir = apply_filters( 'wp_all_import_acf_field_view_dir', __DIR__ . '/views/' . $this->type, $this );
            $fieldDir = apply_filters( 'wp_all_import_acf_field_view_dir_' . $this->type, $fieldDir, $this );
            $filePath = apply_filters(
                'wp_all_import_acf_field_view_path',
                $fieldDir . DIRECTORY_SEPARATOR .
                $this->type . '-' . $this->supportedVersion . '.php',
                $this
            );
            $filePath = apply_filters( 'wp_all_import_acf_field_view_path_' . $this->type, $filePath, $this );
            if ( is_file( $filePath ) )
            {
                // Render field header.
                $header = $fieldDir . DIRECTORY_SEPARATOR . 'header.php';
                if ( file_exists( $header ) && is_readable( $header ) )
                {
                    include $header;
                }
                // Render field.
                include $filePath;
                // Render field footer.
                $footer = $fieldDir . DIRECTORY_SEPARATOR . 'footer.php';
                if ( file_exists( $footer ) && is_readable( $footer ) )
                {
                    include $footer;
                }
            }
            break;
        default:
            $filePath = apply_filters(
                'wp_all_import_acf_field_view_path',
                __DIR__ . '/views/' . $this->type . '.php',
                $this
            );
            $filePath = apply_filters( 'wp_all_import_acf_field_view_path_' . $this->type, $filePath, $this );
            if ( is_file( $filePath ) )
            {
                include $filePath;
            }
            break;
        }
        $this->renderFooter();
    }

}