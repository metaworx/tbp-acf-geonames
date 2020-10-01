<?php

namespace Tbp\WP\Plugin\AcfFields;

use acf_field;
use Throwable;
use WP_Error;

abstract class Field
    extends acf_field
{

// constants
    public const CATEGORY = 'basic';
    public const LABEL    = 'TBP Field';
    public const NAME     = 'tbp_field';

// protected properties

    /** @var static */
    protected static $instance;

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

}
