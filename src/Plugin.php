<?php

namespace Tbp\WP\Plugin\AcfGeoname;

class Plugin
{

    // vars

    // private properties
    private static $instance;
    private static $fields = [];
    private static $acfVersion;

    /** @var string plugin main file */
    private $plugin_file;

    private $settings;


    /*
    *  __construct
    *
    *  This function will setup the class functionality
    *
    *  @type	function
    *  @date	17/02/2016
    *  @since	1.0.0
    *
    *  @param	void
    *  @return	void
    */
    function __construct($file = __FILE__)
    {

        self::$instance    = self::$instance
            ?: $this;
        $this->plugin_file = $file;

        register_activation_hook($this->get_plugin_file(), [$this, 'activate']);
        register_deactivation_hook($this->get_plugin_file(), [$this, 'deactivate']);
        register_uninstall_hook($this->get_plugin_file(), [__CLASS__, 'uninstall']);

        add_action('admin_init', [$this, 'maybe_upgrade']);

        // settings
        // - these will be passed into the field class.
        $this->settings = [
            'version' => '1.0.2',
            'url'     => plugin_dir_url($file),
            'path'    => plugin_dir_path($file),
        ];

        // include field
        add_action('acf/include_field_types', [$this, 'include_field']); // v5
        add_action('acf/register_fields', [$this, 'include_field']); // v4
    }


    /**
     * @return string full plugin file path
     */
    public function get_plugin_dir()
    {

        return plugin_dir_path($this->get_plugin_file());
    }


    /**
     * @return string full plugin file path
     */
    public function get_plugin_file()
    {

        return $this->plugin_file;
    }


    /**
     * @return string plugin slug
     */
    public function get_slug()
    {

        return basename($this->get_plugin_dir());
    }


    /**
     * @return string Path to the main plugin file from plugins directory
     */
    public function get_wp_plugin()
    {

        return plugin_basename($this->get_plugin_file());
    }


    /**
     *    Fired on plugin activation
     */
    public function activate()
    {

        $this->maybe_upgrade();

        $result = [
            'success'  => true,
            'messages' => [],
        ];

        return $result;
    }


    /**
     *    Fired on plugin deactivation
     */
    public function deactivate()
    {

        $result = [
            'success'  => true,
            'messages' => [],
        ];

        return $result;
    }


    function include_field($version = false)
    {

        // support empty $version
        static::$acfVersion = (int)$version
            ?: 4;

        // load tbp-acf-geoname
        load_plugin_textdomain('tbp-acf-geoname', false, $this->get_plugin_dir() . '/lang');

        $dir = new \DirectoryIterator($this->get_plugin_dir() . '/src/Fields');

        /** @var \SplFileInfo $fileInfo */
        foreach ($dir as $fileInfo)
        {

            if (
                $fileInfo->isDot()
                || $fileInfo->isDir()
                || $fileInfo->getExtension() !== 'php'
            )
            {
                continue;
            }

            $base = $fileInfo->getBasename('.php');

            if (!array_key_exists($base, static::$fields))
            {
                $class = sprintf(
                    '%s\\Fields%s\\%s',
                    __NAMESPACE__,
                    file_exists(sprintf('%ssrc/Fields/v%d/%s.php', $this->get_plugin_dir(), static::$acfVersion, $base))
                        ? sprintf('\\v%d', static::$acfVersion)
                        : '',
                    $base
                );

                //static::$fields[$base] = ([$class, 'Factory'])($this->settings);
                static::$fields[$base] = new $class($this->settings);
            }
        }

    }


    /**
     * @action plugins_loaded
     */
    public function maybe_upgrade()
    {

    }


    /**
     *    Fired on plugin upgrade
     *
     * @param  string  $nev_version
     * @param  string  $old_version
     *
     * @return array(
     *        'success' => bool,
     *        'messages' => array,
     * )
     */
    public function upgrade(
        $new_version,
        $old_version
    ) {

        $result = [
            'success'  => true,
            'messages' => [],
        ];

        return $result;
    }


    /*
    *  include_field
    *
    *  This function will include the field type class
    *
    *  @type	function
    *  @date	17/02/2016
    *  @since	1.0.0
    *
    *  @param	$version (int) major ACF version. Defaults to false
    *  @return	tbp_acf_field_geoname
    */

    static function Factory($file)
    {

        return self::$instance
            ?: self::$instance = new self($file);
    }


    /**
     *    Fired on plugin deinstallation
     */
    public static function uninstall()
    {

        $result = [
            'success'  => true,
            'messages' => [],
        ];

        return $result;
    }

}
