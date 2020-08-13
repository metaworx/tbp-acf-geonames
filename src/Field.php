<?php

namespace Tbp\WP\Plugin\AcfGeoname;

class Field
    extends \acf_field
{
    // protected properties
    protected static $instance;

    protected $settings = [];  // will hold info such as dir / path


    public function __construct($settings = [])
    {

        /**
        /*
        *  settings (array) Store plugin settings (url, path, version) as a reference for later use with assets
        */
        $this->settings = $settings;

        parent::__construct();


    }

    public static function Factory($settings): self
    {

        return self::$instance
            ?: self::$instance = new self($settings);
    }

}