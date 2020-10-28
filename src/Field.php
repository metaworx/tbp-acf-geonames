<?php

namespace Tbp\WP\Plugin\AcfFields;

use acf_field;
use Tbp\WP\Plugin\AcfFields\Integration\FacetWP;
use Throwable;
use WP_Error;

abstract class Field
    extends
    acf_field
{

// constants
    public const CATEGORY = 'basic';
    public const LABEL    = 'TBP Field';
    public const NAME     = 'tbp_field';

// protected properties

    /** @var static */
    protected static $instance;
    protected static $filters = [];
    protected static $fieldSettings;

    protected $settings = [];  // will hold info such as dir / path


    public function __construct( $settings = [] )
    {

        /*
         *  name (string) Single word, no spaces. Underscores allowed
         */
        $this->name = $settings['field_name'] ?? static::NAME;

        /*
        *  label (string) Multiple words, can include spaces, visible when selecting a field type
        */
        $this->label = $settings['field_label'] ?? __( static::LABEL, 'tbp-acf-fields' );

        /*
        *  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
        */
        $this->category = $settings['field_category'] ?? static::CATEGORY;

        /**
         *  settings (array) Store plugin settings (url, path, version) as a reference for later use with assets
         */
        $this->settings = $settings;

        parent::__construct();

    }


    function initialize( ?string $filterBase = null )
    {

        if ( $filterBase === null )
        {
            $filterBase = "acf/fields/" . static::NAME . "/filter/name=";
        }

        array_walk(
            self::$filters,
            static function (
                $filterSettings,
                $filter
            ) use
            (
                &
                $filterBase
            )
            {

                if ( array_key_exists(
                        'filterCallback',
                        $filterSettings
                    )
                    && $filterSettings['filterCallback'] === false )
                {
                    return;
                }

                add_filter(
                    "$filterBase$filter",
                    array_key_exists( 'filterCallback', $filterSettings )
                    && is_callable(
                        $filterSettings['filterCallback']
                    )
                        ? $filterSettings['filterCallback']
                        : [
                        static::class,
                        'filterDefault',
                    ],
                    10,
                    2
                );
            }
        );

    }


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
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function ajax_query()
    {

        /** @var callable $oldErrorHandler */
        $oldErrorHandler = null;

        // error handler function
        $myErrorHandler = static function (
            $errno,
            $errString,
            $errFile,
            $errLine
        )
        use
        (
            &
            $oldErrorHandler
        )
        {

            /**
             * @var bool $report
             * @noinspection PhpUnusedLocalVariableInspection
             */
            $report  = error_reporting() & $errno;
            $message = '';

            switch ( true )
            {
            case $errno & E_USER_ERROR:
                $code    = 'ERROR';
                $message .= "$code: [$errno] $errString\n";
                break;

            case $errno & E_USER_WARNING:
                $code    = 'WARNING';
                $message .= "$code: [$errno] $errString\n";
                break;

            case $errno & E_USER_NOTICE:
                $code    = 'NOTICE';
                $message .= "$code: [$errno] $errString\n";
                break;

            default:
                $code    = 'ERROR';
                $message .= "Unknown error type: [$errno] $errString\n";
                break;
            }

            $message .= "  Error in file $errFile:$errLine";
            $message .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";

            if ( true || $report )
            {
                /** @noinspection ForgottenDebugOutputInspection */
                error_log( $message, $errno );
            }

            if ( true && $errno = E_USER_ERROR )
            {
                wp_send_json_error(
                    new WP_Error( strtolower( $code ), $message ),
                    503
                );
            }

            return $oldErrorHandler( $errno, $errString, $errFile, $errLine );
        };

        $myExceptionHandler = static function ( Throwable $e ) use
        (
            $myErrorHandler
        )
        {

            $myErrorHandler( E_USER_ERROR, $e->getMessage(), $e->getFile(), $e->getLine() );
        };

        $oldErrorHandler     = set_error_handler( $myErrorHandler );
        $oldExceptionHandler = set_exception_handler( $myExceptionHandler );

        // validate ajax request
        if ( ! acf_verify_ajax() )
        {
            trigger_error(
                'unable to verify acf ajax request',
                E_USER_ERROR

            );
        }

        $response = $this->ajax_query_helper();

        // return
        acf_send_ajax_results( $response );
    }


    abstract protected function ajax_query_helper();


    /**
     * Add ACF fields to the Data Sources dropdown
     *
     * @param                                  $sources
     * @param  \Tbp\WP\Plugin\AcfFields\Field  $field
     * @param  array                           $acfFields
     *
     * @return array
     * @noinspection PhpUnusedParameterInspection
     */
    public function facetwpFacetSources(
        $sources,
        Field $field,
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

                $field_id = sprintf(
                    '%s/%s/%s/%s',
                    FacetWP::SOURCE_IDENTIFIER,
                    static::NAME,
                    'name',
                    $field['hierarchy']
                );

                $sources['acf']['choices'][ $field_id ] = $field_label;
            }
        );

        return $sources;
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
     */
    public function render_field( $field )
    {
    }


    public static function Factory( $settings ): self
    {

        return self::$instance
            ?: self::$instance = new static( $settings );
    }


    /**
     * @param  array   $args
     * @param  object  $context
     *
     * @return array
     */
    public static function filterDefault(
        array $args,
        object $context
    ): array {

        $filters = acf_get_array( $context->field['filters'] );

        if ( ! empty( $context->options[ $context->filter ] ) && in_array( $context->filter, $filters, true ) )
        {

            $args[ $context->filter ] = acf_get_array( $context->options[ $context->filter ] );
        }
        elseif ( ! empty( $context->field[ $context->filter ] ) )
        {

            $args[ $context->filter ] = acf_get_array( $context->field[ $context->filter ] );
        }
        else
        {

            $args[ $context->filter ] = null; // call_user_func( $getter );

        }

        return $args;
    }


    protected static function getDefinitions(
        $array,
        $key
    ) {

        switch ( true )
        {
        case $key === null:
            // no key given, return all keys
            return $array;

        case is_string( $key ):
            // key is a single key name
            return $array[ $key ];

        case is_array( $key ):

            // key is given, but empty. Return empty array
            if ( empty( $key ) )
            {
                return [];
            }

            // key is a list of field names
            if ( ! is_string( key( $key ) ) )
            {

                return array_intersect_key( $array, array_flip( $key ) );
            }

            // key is a set of properties to match
            return array_filter(
                $array,
                static function ( &$item ) use
                (
                    &
                    $key
                )
                {

                    foreach ( array_keys( $key ) as $key )
                    {

                        if ( ! array_key_exists( $key, $item ) )/**/
                        {
                            return false;
                        }

                        if ( $item[ $key ] !== $key[ $key ] )
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


    public static function getFieldSettingDefinitions( $setting = null )
    {

        return self::getDefinitions( self::$fieldSettings, $setting );

    }


    public static function getFilterDefinitions( $filter = null )
    {

        return self::getDefinitions( self::$filters, $filter );
    }

}
