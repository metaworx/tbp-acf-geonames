<?php
/**
 * @noinspection PhpUnusedParameterInspection
 */

namespace Tbp\WP\Plugin\AcfFields;

use DirectoryIterator;
use ErrorException;
use Tbp\WP\Plugin\AcfFields\Integration\FacetWP;

class Plugin
{

// constants

    public const TEXT_DOMAIN = 'tbp-acf-fields';

//  public properties

    public static $fields = [];

// protected properties

    /** @var array */
    protected $settings;

    /** @var \Tbp\WP\Plugin\AcfFields\Integration\FacetWP|null */
    protected $facet;

// private properties

    /** @var \Tbp\WP\Plugin\AcfFields\Plugin|null */
    private static $instance;

    /** @var string */
    private static $acfVersion;

    /** @var string plugin main file */
    private static $pluginFile;

    /** @var array */
    private static $missingPlugins;


    /**
     *  __construct
     *
     *  This function will setup the class functionality
     *
     * @date     17/02/2016
     * @since    1.0.0
     *
     * @param  string  $file
     * @param  array   $missingPlugins
     *
     * @throws \ErrorException
     */
    function __construct(
        string $file,
        array $missingPlugins
    ) {

        if ( self::$instance !== null )
        {
            throw new ErrorException(
                sprintf(
                    '%s is a singleton class. Please instantiate using the static Factory() constructor.',
                    static::class
                )
            );
        }

        self::$instance       = $this;
        self::$pluginFile     = $file;
        self::$missingPlugins = $missingPlugins;

        register_activation_hook(
            $this->get_plugin_file(),
            [
                $this,
                'activate',
            ]
        );

        register_deactivation_hook(
            $this->get_plugin_file(),
            [
                $this,
                'deactivate',
            ]
        );

        register_uninstall_hook(
            $this->get_plugin_file(),
            [
                __CLASS__,
                'uninstall',
            ]
        );

        add_action(
            'admin_init',
            [
                $this,
                'maybe_upgrade',
            ]
        );

        // settings
        // - these will be passed into the field class.
        $this->settings = [
            'version' => '2.2.0',
            'url'     => plugin_dir_url( $file ),
            'path'    => plugin_dir_path( $file ),
        ];

        // include field
        add_action(
            'acf/include_field_types',
            [
                $this,
                'include_field',
            ]
        ); // v5

        add_action(
            'acf/register_fields',
            [
                $this,
                'include_field',
            ]
        ); // v4

        if ( ! array_key_exists( 'facetwp/index.php', self::$missingPlugins ) )
            // use low priority (>10) to make sure ACF has already loaded
        {
            $this->facet = new FacetWP();
        }

    }


    /**
     * @return string full plugin file path
     */
    public function get_plugin_dir()
    {

        return plugin_dir_path( $this->get_plugin_file() );
    }


    /**
     * @return string full plugin file path
     */
    public function get_plugin_file()
    {

        return self::$pluginFile;
    }


    /**
     * @return string plugin slug
     */
    public function get_slug()
    {

        return basename( $this->get_plugin_dir() );
    }


    /**
     * @return string Path to the main plugin file from plugins directory
     */
    public function get_wp_plugin()
    {

        return plugin_basename( $this->get_plugin_file() );
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


    /**
     *  include_field
     *
     *  This function will include the field type class
     *
     * @date     17/02/2016
     * @since    1.0.0
     *
     * @param  bool  $version  (int) major ACF version. Defaults to false
     *
     * @return void
     * @throw \ErrorException
     */
    function include_field( $version = false )
    {

        // support empty $version
        static::$acfVersion = (int) $version
            ?: 4;

        // load tbp-acf-fields
        load_plugin_textdomain( 'tbp-acf-fields', false, $this->get_plugin_dir() . '/lang' );

        $dir = new DirectoryIterator( $this->get_plugin_dir() . '/src/Fields' );

        foreach ( $dir as $fileInfo )
        {

            if (
                $fileInfo->isDot()
                || $fileInfo->isDir()
                || $fileInfo->getExtension() !== 'php'
            )
            {
                continue;
            }

            $base = $fileInfo->getBasename( '.php' );

            if ( ! array_key_exists( $base, static::$fields ) )
            {
                /** @var \Tbp\WP\Plugin\AcfFields\Field $baseClass */
                $baseClass = sprintf(
                    '%s\\Fields\\%s',
                    __NAMESPACE__,
                    $base
                );

                /** @var \Tbp\WP\Plugin\AcfFields\Field $class */
                $class = file_exists(
                    sprintf( '%ssrc/Fields/v%d/%s.php', $this->get_plugin_dir(), static::$acfVersion, $base )
                )
                    ? sprintf(
                        '%s\\Fields\\v%d\\%s',
                        __NAMESPACE__,
                        static::$acfVersion,
                        $base
                    )
                    : $baseClass;

                foreach ( self::$missingPlugins as $plugin )
                {
                    if ( ! array_key_exists( 'fields', $plugin ) )
                    {
                        continue;
                    }

                    if ( ! array_key_exists( $class, $plugin['fields'] )
                        && ! array_key_exists( $baseClass, $plugin['fields'] ) )
                    {
                        continue;
                    }

                    switch ( $plugin['reason'] )
                    {
                    case 'installed':
                        $reason = __( 'inactive due to missing dependency', 'tbp-acf-fields' );
                        break;

                    case 'activated':
                        $reason = __( 'inactive due to deactivated dependency', 'tbp-acf-fields' );
                        break;

                    case 'updated':
                        $reason = __( 'inactive due to outdated dependency', 'tbp-acf-fields' );
                        break;

                    default:
                        $reason = __( 'inactive due to error with a dependency', 'tbp-acf-fields' );
                        break;

                    }

                    static::$fields[ $base ] = new InactiveField(
                        $this->settings + [
                            'field_name'      => $class::NAME,
                            'field_label'     => sprintf( '%s (%s)', $class::LABEL, $reason ),
                            'field_category'  => $class::CATEGORY,
                            'inactive_reason' => $reason,
                        ]
                    );

                    continue 2;
                }

                static::$fields[ $base ] = new $class( $this->settings );
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
        string $nev_version,
        string $old_version
    ): array {

        $result = [
            'success'  => true,
            'messages' => [],
        ];

        return $result;
    }


    /**
     * @param         $file
     * @param  array  $missingPlugins
     *
     * @return \Tbp\WP\Plugin\AcfFields\Plugin|null
     * @throws \ErrorException
     */
    static function Factory(
        $file,
        array $missingPlugins = []
    ) {

        return self::$instance
            ?: self::$instance = new self( $file, $missingPlugins );
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
