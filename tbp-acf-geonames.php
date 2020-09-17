<?php
/*
Plugin Name: Advanced Custom Fields: Geo Names
Plugin URI: PLUGIN_URL
Description: List of worldwide locations based on geonames.org and the geonames plugin
Version: 1.1.0
Author: Bhujagendra Ishaya
Author URI: https://www.thebrightpath.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Depends: Advanced Custom Fields, WP GeoNames
RequiresPHP: 7.3.0
*/

use Tbp\WP\Plugin\AcfGeoname\Plugin;

// exit if accessed directly
if (!defined('ABSPATH'))
{
    exit;
}

require_once(ABSPATH . '/wp-admin/includes/plugin.php');

if (call_user_func(
    static function ()
    {

        $php_version      = '7.3.0';
        $is_plugin_active = is_multisite()
            ? 'is_plugin_active_for_network'
            : 'is_plugin_active';

        $errors  = [];
        $plugins = [
            'advanced-custom-fields/acf.php' => [
                'name'    => 'Advanced Custom Fields',
                'version' => '5.0.0',
                'url'     => 'https://www.advancedcustomfields.com',
            ],
            'wp-geonames/wp-geonames.php'    => [
                'name'    => 'WP Geonames',
                'version' => '2.0.7',
                'url'     => 'https://wordpress.org/plugins/wp-geonames/',
            ],
        ];

        if (version_compare(PHP_VERSION, $php_version, '<'))
        {
            $errors[] = 'php';
        }

        foreach ($plugins as $plugin => &$plugin_data)
        {

            if (!$is_plugin_active($plugin))
            {
                $errors[] = $plugin;
                continue;
            }

            $plugin_data['info'] = get_plugin_data(WP_PLUGIN_DIR .DIRECTORY_SEPARATOR. $plugin, false, false);

            if (version_compare($plugin_data['info']['Version'], $plugin_data['version'], '<'))
            {
                $errors[] = $plugin;
                continue;
            }

        }
        unset($plugin_data);

        if (empty($errors))
        {
            return true;
        }

        $display_errors = static function ()
        use
        (
            $errors,
            $plugins,
            $php_version
        )
        {

            foreach ($errors as &$error)
            {
                if ($error === 'php')
                {
                    $message = sprintf(
                        __(
                            'Please update to PHP version %s or higher in order to use Geonames Advanced Custom Field.',
                            'tbp-acf-geonames'
                        ),
                        $php_version
                    );

                }
                else
                {
                    $plugin =& $plugins[$error];

                    if (array_key_exists('info', $plugin))
                    {
                        /** @noinspection HtmlUnknownTarget */
                        $message = sprintf(
                            __(
                                'Geonames Advanced Custom Field requires <strong>version %s</strong> of <a href="%s" target="_blank">%s</a> to be installed. The current version is %s',
                                'tbp-acf-geonames'
                            ),
                            $plugin['version'],
                            $plugin['info']['PluginURI'],
                            $plugin['info']['Name'],
                            $plugin['info']['Version']
                        );
                    }
                    else
                    {
                        /** @noinspection HtmlUnknownTarget */
                        $message = sprintf(
                            __(
                                'Geonames Advanced Custom Field requires <a href="%s" target="_blank">%s</a> (v%s) to be installed and activated.',
                                'tbp-acf-geonames'
                            ),
                            $plugin['url'],
                            $plugin['name'],
                            $plugin['version']
                        );
                    }
                }

                $error = sprintf('<div class="notice notice-error"><p>%s</p></div>', $message);
            }
            unset($error);

            $message= implode("\n", $errors);
            // ToDo: don't use absolute name
            // get_admin_url() / get_home_path() / ABSPATH ...?
            // current_user_can( 'activate_plugins' )
            // is_admin()
            if ($_SERVER['SCRIPT_NAME'] === '/wp-admin/plugins.php'
                && ($_GET['action'] ?: false) === 'activate'
                && (isset( $_REQUEST['plugin'] ) ? wp_unslash( $_REQUEST['plugin'] ) : '') === 'tbp-acf-geonames/tbp-acf-geonames.php'
            ) {
                trigger_error(strip_tags( $message), E_USER_ERROR);
            }

            echo $message;

            deactivate_plugins(plugin_basename(__FILE__));

            return new WP_Error( 'missing_dependency', __( 'One of the plugins is invalid.' ), $errors );
        };

        $notice = is_multisite()
            ? 'network_admin_notices'
            : 'admin_notices';
        add_action($notice, $display_errors);
        register_activation_hook(__FILE__, $display_errors);

        return false;
    }
))
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

    return Plugin::Factory(__FILE__);
}
