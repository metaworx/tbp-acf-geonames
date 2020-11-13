<?php
/*
Plugin Name: Advanced Custom Fields: TBP Collection
Plugin URI: PLUGIN_URL
Description: List of worldwide locations based on geonames.org and the geonames plugin
Version: 2.12.0
Author: Bhujagendra Ishaya, The Bright Path
Author URI: https://www.thebrightpath.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Depends: Advanced Custom Fields, WP GeoNames
RequiresPHP: 7.3.0
*/

/**
 * @noinspection PhpUnusedParameterInspection
 */

use Tbp\WP\Plugin\AcfFields\Plugin;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
{
    exit;
}

// ignore admin heartbeats
if ( ! defined( 'TBP_IS_ADMIN_HEARTBEAT' ) )
{
    define(
        'TBP_IS_ADMIN_HEARTBEAT',
        (
            'heartbeat' === ( $_REQUEST['action'] ?? false )
            && strpos(
                $_SERVER['REQUEST_URI'],
                '/wp-admin/admin-ajax.php'
            ) >= 0 )
    );
}

if ( TBP_IS_ADMIN_HEARTBEAT )
{
    return;
}

require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

call_user_func(
    static function ()
    {

        $php_version      = '7.3.0';
        $is_plugin_active = is_multisite()
            ? 'is_plugin_active_for_network'
            : 'is_plugin_active';

        $plugins = [
            'advanced-custom-fields/acf.php' => [
                'name'     => 'Advanced Custom Fields',
                'version'  => '5.0.0',
                'url'      => 'https://www.advancedcustomfields.com',
                'required' => false,
                'fields'   => [
                    'Tbp\WP\Plugin\AcfFields\Fields\Geoname' => true,
                ],
            ],
            'wp-geonames/wp-geonames.php'    => [
                'name'     => 'WP Geonames',
                'version'  => '3.5.0',
                'url'      => 'https://wordpress.org/plugins/wp-geonames/',
                'required' => false,
                'fields'   => [
                    'Tbp\WP\Plugin\AcfFields\Fields\Geoname' => true,
                ],
            ],
            'facetwp/index.php'              => [
                'name'     => 'FacetWP',
                'version'  => '3.6.0',
                'url'      => 'https://facetwp.com/',
                'required' => false,
                'fields'   => [
                ],
            ],
        ];

        $callback = static function ( $file ) use
        (
            &
            $plugins
        )
        {

            // only load the plugin, if the minimum php requirement is met
            if ( array_key_exists( 'php', $plugins ) )
            {
                return null;
            }

            require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

            return Plugin::Factory( $file, $plugins );
        };

        if ( version_compare( PHP_VERSION, $php_version, '<' ) )
        {
            $plugins = [ 'php' => [] ];
        }
        else
        {
            foreach ( $plugins as $plugin => &$plugin_data )
            {

                if ( ! file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin ) )
                {
                    $plugin_data['reason'] = 'installed';
                    continue;
                }

                $plugin_data['info'] = get_plugin_data( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin, false, false );

                if ( ! $is_plugin_active( $plugin ) )
                {
                    $plugin_data['reason'] = 'activated';
                    continue;
                }

                if ( version_compare( $plugin_data['info']['Version'], $plugin_data['version'], '<' ) )
                {
                    $plugin_data['reason'] = 'updated';
                    continue;
                }

                unset( $plugins[ $plugin ] );

            }

            unset( $plugin_data );
        }

        if ( empty( $plugins ) )
        {
            return $callback;
        }

        $display_errors = static function ()
        use
        (
            $plugins,
            $php_version
        )
        {

            $deactivate = false;

            if ( array_key_exists( 'php', $plugins ) )
            {
                $deactivate              = true;
                $plugins['php']['error'] = sprintf(
                    __(
                        'Please update to PHP version %s or higher in order to use "Advanced Custom Fields: TBP Collection".',
                        'tbp-acf-fields'
                    ),
                    $php_version
                );

            }
            else
            {
                require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

                foreach ( $plugins as $plugin => &$plugin_data )
                {
                    $message    = '';
                    $deactivate = $deactivate || ( $plugin_data['required'] ?? false );

                    $affected_fields = array_unique(
                        array_map(
                            static function (
                                $class
                            ) {

                                /** @var \Tbp\WP\Plugin\AcfFields\Field $class */
                                return $class::LABEL;
                            },
                            array_keys( $plugin_data['fields'] ?? [] )
                        )
                    );

                    if ( ! empty( $affected_fields ) )
                    {
                        $message = '<br/>' . __(
                                'The following field(s) will be disabled:',
                                'tbp-acf-fields'
                            ) . ' ' . implode(
                                ', ',
                                $affected_fields
                            );
                    }

                    if ( $plugin_data['reason'] === 'updated' )
                    {
                        /** @noinspection HtmlUnknownTarget */
                        $message = sprintf(
                                __(
                                    '"Advanced Custom Fields: TBP Collection" requires <strong>version %s</strong> of <a href="%s" target="_blank">%s</a> to be %s. The current version is %s.',
                                    'tbp-acf-fields'
                                ),
                                $plugin_data['version'],
                                $plugin_data['info']['PluginURI'],
                                $plugin_data['info']['Name'],
                                $plugin_data['reason'],
                                $plugin_data['info']['Version']
                            ) . $message;
                    }
                    else
                    {
                        /** @noinspection HtmlUnknownTarget */
                        $message = sprintf(
                                __(
                                    '"Advanced Custom Fields: TBP Collection" requires <a href="%s" target="_blank">%s</a> (v%s) to be %s.',
                                    'tbp-acf-fields'
                                ),
                                $plugin_data['url'],
                                $plugin_data['name'],
                                $plugin_data['version'],
                                $plugin_data['reason']
                            ) . $message;
                    }

                    $plugin_data['error'] = sprintf( '<div class="notice notice-error"><p>%s</p></div>', $message );
                }
            }
            unset( $plugin_data );

            $errors  = array_column( $plugins, 'error' );
            $message = implode( "\n", $errors );
            // ToDo: don't use absolute name
            // get_admin_url() / get_home_path() / ABSPATH ...?
            // current_user_can( 'activate_plugins' )
            // is_admin()
            if ( $_SERVER['SCRIPT_NAME'] === '/wp-admin/plugins.php'
                && ( $_GET['action']
                    ?: false ) === 'activate'
                && ( isset( $_REQUEST['plugin'] )
                    ? wp_unslash( $_REQUEST['plugin'] )
                    : '' ) === 'tbp-acf-fields/tbp-acf-fields.php'
            )
            {
                trigger_error( strip_tags( $message ), E_USER_ERROR );
            }

            echo $message;

            if ( $deactivate )
            {
                deactivate_plugins( plugin_basename( __FILE__ ) );
            }

            return new WP_Error( 'missing_dependency', __( 'One of the plugins is invalid.' ), $errors );
        };

        $notice = is_multisite()
            ? 'network_admin_notices'
            : 'admin_notices';
        add_action( $notice, $display_errors );
        register_activation_hook( __FILE__, $display_errors );

        return $callback;
    }
)(
    __FILE__
);
