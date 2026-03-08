<?php
/**
 * Plugin Name: WooCommerce Google Address Autocomplete (Places API)
 * Description: Adds Google Places address autocomplete to WooCommerce checkout billing and shipping fields using PlaceAutocompleteElement.
 * Version: 1.0.0
 * Author: Ida De Smet
 * Author URI: https://www.katinkaa.com
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: google-autocomplete-plugin
 * Domain Path: /languages
 * Developer: Ida De Smet
 * Developer URI: https://github.com/idaknow
 */

defined( 'ABSPATH' ) || exit;

class Google_Autocomplete {

	const VERSION = '1.0.0';

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_admin_notice' ) );

		add_filter( 'woocommerce_form_field_text_html', array( $this, 'inject_autocomplete_container' ), 10, 3 );
	}

	/**
	 * Injects the container for the PlaceAutocompleteElement before the standard address input.
	 *
	 * @param string $field_html The HTML for the WooCommerce field.
	 * @param string $key        The field key (e.g., 'billing_address_1').
	 * @param array  $args       The field arguments.
	 * @return string Modified HTML.
	 */
	public function inject_autocomplete_container( $field_html, $key, $args ) {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return $field_html;
		}

		// Only target the main address line 1 field for billing and shipping.
		if (
			isset( $args['id'] ) &&
			( 'billing_address_1' === $args['id'] || 'shipping_address_1' === $args['id'] )
		) {
			$prefix       = explode( '_', $args['id'] )[0];
			$container_id = "{$prefix}_address_autocomplete_container";

			// Inject the new container div before the existing field HTML.
			$injected_html = '<div id="' . esc_attr( $container_id ) . '" class="gmp-address-autocomplete-container"></div>' . $field_html;

			return $injected_html;
		}

		return $field_html;
	}

	public function add_settings_page() {
		add_options_page(
			'Google Autocomplete Settings',
			'Google Autocomplete',
			'manage_options',
			'google-autocomplete-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'google_autocomplete_settings',
			'google_maps_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'google_autocomplete_settings',
			'google_maps_region_code',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_region_code' ),
				'default'           => 'nz',
			)
		);

		add_settings_section(
			'google_autocomplete_main',
			'API Configuration',
			null,
			'google-autocomplete-settings'
		);

		add_settings_field(
			'google_maps_api_key',
			'Google Maps API Key',
			array( $this, 'render_api_key_field' ),
			'google-autocomplete-settings',
			'google_autocomplete_main'
		);
		add_settings_field(
			'google_maps_region_code',
			'Region Code',
			array( $this, 'render_region_code_field' ),
			'google-autocomplete-settings',
			'google_autocomplete_main'
		);
	}

	public function sanitize_region_code( $value ) {
		$region_code = strtolower( sanitize_text_field( $value ) );
		if ( ! preg_match( '/^[a-z]{2}$/', $region_code ) ) {
			return 'nz';
		}

		return $region_code;
	}

	public function render_api_key_field() {
		$api_key = get_option( 'google_maps_api_key', '' );
		?>
		<input
			type="text"
			name="google_maps_api_key"
			value="<?php echo esc_attr( $api_key ); ?>"
			class="regular-text"
			placeholder="ABC..."
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your Google Maps API key. Get one from', 'google-autocomplete-plugin' ); ?>
			<a href="https://console.cloud.google.com/google/maps-apis" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Cloud Console', 'google-autocomplete-plugin' ); ?></a>.
		</p>
		<?php
	}

	public function render_region_code_field() {
		$region_code = get_option( 'google_maps_region_code', 'nz' );
		?>
		<input
			type="text"
			name="google_maps_region_code"
			value="<?php echo esc_attr( $region_code ); ?>"
			class="regular-text"
			placeholder="nz"
			maxlength="2"
		/>
		<p class="description">
			<?php esc_html_e( 'Two-letter country code (ISO 3166-1 alpha-2) for autocomplete results, e.g.', 'google-autocomplete-plugin' ); ?>
			<code>nz</code>, <code>au</code>, <code>us</code>.
		</p>
		<?php
	}

	public function maybe_show_admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$api_key = get_option( 'google_maps_api_key', '' );
		if ( ! empty( $api_key ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && isset( $screen->id ) && false !== strpos( $screen->id, 'google-autocomplete-settings' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php echo esc_html__( 'Google Autocomplete: API key is not configured.', 'google-autocomplete-plugin' ); ?>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=google-autocomplete-settings' ) ); ?>">
					<?php echo esc_html__( 'Configure settings', 'google-autocomplete-plugin' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'google_autocomplete_settings' );
				do_settings_sections( 'google-autocomplete-settings' );
				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}

	public function enqueue_scripts() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		$api_key = get_option( 'google_maps_api_key', '' );

		if ( empty( $api_key ) ) {
			return;
		}

		wp_enqueue_script(
			'google-maps-api',
			sprintf(
				'https://maps.googleapis.com/maps/api/js?key=%s&v=beta&libraries=places',
				rawurlencode( $api_key )
			),
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'google-address-autocomplete-js',
			plugin_dir_url( __FILE__ ) . 'assets/autocomplete.js',
			array( 'google-maps-api', 'jquery' ),
			self::VERSION,
			true
		);
		wp_localize_script(
			'google-address-autocomplete-js',
			'GoogleAutocompleteConfig',
			array(
				'regionCode' => get_option( 'google_maps_region_code', 'nz' ),
			)
		);

		wp_enqueue_style(
			'google-address-autocomplete-css',
			plugin_dir_url( __FILE__ ) . 'assets/autocomplete.css',
			array(),
			self::VERSION
		);
	}
}

new Google_Autocomplete();
