<?php

namespace Tbp\WP\Plugin\AcfGeoname;

use acf_field;
use WP_Error;

abstract class Field
    extends acf_field
{
    // protected properties
    protected static $instance;

    protected $settings = [];  // will hold info such as dir / path


    public function __construct($settings = [])
    {

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
     * @date      24/10/13
     * @since     5.0.0
     *
     *
     * @return   void
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

            $message = '';
            $report  = error_reporting() & $errno;

            switch (true)
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

            $message .= "  Error on line $errLine in file $errFile";
            $message .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";

            if (true || $report)
            {
                error_log($message, $errno);
            }

            if (true && $errno = E_USER_ERROR)
            {
                wp_send_json_error(
                    new WP_Error(strtolower($code), $message),
                    503
                );
            }

            return $oldErrorHandler($errno, $errString, $errFile, $errLine);
        };

        $myExceptionHandler = static function (\Throwable $e) use
        (
            $myErrorHandler
        )
        {

            $myErrorHandler(E_USER_ERROR, $e->getMessage(), $e->getFile(), $e->getLine());
        };

        $oldErrorHandler     = set_error_handler($myErrorHandler);
        $oldExceptionHandler = set_exception_handler($myExceptionHandler);

        // validate ajax request
        if (!acf_verify_ajax())
        {
            trigger_error(
                'unable to verify acf ajax request',
                E_USER_ERROR

            );
        }

        $response = $this->ajax_query_helper();

        // return
        acf_send_ajax_results($response);
    }


    abstract protected function ajax_query_helper();


    public static function Factory($settings): self
    {

        return self::$instance
            ?: self::$instance = new static($settings);
    }

}