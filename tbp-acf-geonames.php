<?php

/*
Plugin Name: Advanced Custom Fields: Geo Names
Plugin URI: PLUGIN_URL
Description: List of worldwide locations based on geonames.org and the geonames plugin
Version: 1.0.1
Author: Bhujagendra Ishaya
Author URI: https://www.thebrightpath.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class tbp_acf_plugin_geoname {

	// vars

	private static $instance = null;

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
	function __construct( $file = __FILE__ ) {

		self::$instance    = self::$instance ?: $this;
		$this->plugin_file = $file;

		register_activation_hook( $this->get_plugin_file(), [ $this, 'activate' ] );
		register_deactivation_hook( $this->get_plugin_file(), [ $this, 'deactivate' ] );
		register_uninstall_hook( $this->get_plugin_file(), [ __CLASS__, 'uninstall' ] );

		add_action( 'admin_init', [ $this, 'maybe_upgrade' ] );

		// settings
		// - these will be passed into the field class.
		$this->settings = array(
			'version' => '1.0.0',
			'url'     => plugin_dir_url( __FILE__ ),
			'path'    => plugin_dir_path( __FILE__ )
		);

		// include field
		add_action( 'acf/include_field_types', array( $this, 'include_field' ) ); // v5
		add_action( 'acf/register_fields', array( $this, 'include_field' ) ); // v4
	}


	/**
	 * @return string full plugin file path
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * @return string full plugin file path
	 */
	public function get_plugin_dir() {
		return plugin_dir_path( $this->get_plugin_file() );
	}

	/**
	 * @return string plugin slug
	 */
	public function get_slug() {
		return basename( $this->get_plugin_dir() );
	}

	/**
	 * @return string Path to the main plugin file from plugins directory
	 */
	public function get_wp_plugin() {
		return plugin_basename( $this->get_plugin_file() );
	}

	/**
	 *    Fired on plugin activation
	 */
	public function activate() {

		$this->maybe_upgrade();

		$result = [
			'success'  => true,
			'messages' => [],
		];

		return $result;
	}

	/**
	 * @action plugins_loaded
	 */
	public function maybe_upgrade() {

	}


	/**
	 *    Fired on plugin updgrade
	 *
	 * @param string $nev_version
	 * @param string $old_version
	 *
	 * @return array(
	 *        'success' => bool,
	 *        'messages' => array,
	 * )
	 */
	public function upgrade( $new_version, $old_version ) {

		$result = [
			'success'  => true,
			'messages' => [],
		];

		return $result;
	}

	/**
	 *    Fired on plugin deactivation
	 */
	public function deactivate() {

		$result = [
			'success'  => true,
			'messages' => [],
		];

		return $result;
	}

	/**
	 *    Fired on plugin deinstallation
	 */
	public static function uninstall() {

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
	function include_field( $version = false ) {

		// support empty $version
		if ( ! $version ) {
			$version = 4;
		}

		// load tbp-acf-geoname
		load_plugin_textdomain( 'tbp-acf-geoname', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );

		require_once( 'fields/class-tbp-acf-field-geoname-base.php' );
		/**
		 * include
		 *
		 * @noinspection PhpIncludeInspection
		 */
		require_once( 'fields/v' . $version . '/class-tbp-acf-field-geoname.php' );

		// initialize
		return tbp_acf_field_geoname::Factory( $this->settings );
	}

	static function Factory( $file ) {
		return self::$instance ?: self::$instance = new self( $file );
	}
}

return tbp_acf_plugin_geoname::Factory(__FILE__);
