<?php
/**
 * REST API routes.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public suggestion endpoint, rate-limited by IP.
 *
 * @since 0.1.0
 */
class Oli_AS_Rest {

	/**
	 * Singleton.
	 *
	 * @since 0.1.0
	 * @var Oli_AS_Rest|null
	 */
	protected static $instance = null;

	/**
	 * Returns the singleton.
	 *
	 * @since 0.1.0
	 *
	 * @return Oli_AS_Rest
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers routes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_rewrite' ) );
		add_action( 'template_redirect', array( $this, 'handle_pretty_endpoint' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
	}

	/**
	 * Registers a frontend rewrite that multilingual plugins can prefix.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_rewrite() {
		add_rewrite_rule( '^oli-as/suggest/?$', 'index.php?oli_as_suggest=1', 'top' );
	}

	/**
	 * Adds the suggest query var.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, string> $vars Query vars.
	 * @return array<int, string>
	 */
	public function query_vars( $vars ) {
		$vars[] = 'oli_as_suggest';
		return $vars;
	}

	/**
	 * Serves JSON from /oli-as/suggest so language prefixes stay on the URL.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function handle_pretty_endpoint() {
		if ( '1' !== (string) get_query_var( 'oli_as_suggest' ) ) {
			return;
		}

		if ( isset( $_GET['oli_as_ping'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_send_json( array( 'pong' => 1 ) );
		}

		wp_send_json( $this->suggest_from_request() );
	}

	/**
	 * Registers REST routes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'oli-as/v1',
			'/suggest',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'suggest' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'    => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'lang' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Suggestion callback.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function suggest( $request ) {
		$query = (string) $request->get_param( 'q' );
		$lang  = (string) $request->get_param( 'lang' );

		$result = $this->run_suggest( $query, $lang );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Shared GET handler for the pretty endpoint.
	 *
	 * @since 0.2.0
	 *
	 * @return array<string, mixed>
	 */
	public function suggest_from_request() {
		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lang  = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = $this->run_suggest( $query, $lang );
		if ( is_wp_error( $result ) ) {
			status_header( (int) $result->get_error_data() );
			return array(
				'query'       => $query,
				'language'    => Oli_AS_Language::instance()->from_request( $lang ),
				'groups'      => array(),
				'total'       => 0,
				'results_url' => '',
				'took_ms'     => 0,
				'error'       => $result->get_error_code(),
			);
		}

		return $result;
	}

	/**
	 * Executes a public suggestion search with rate limit and cache.
	 *
	 * @since 0.2.0
	 *
	 * @param string $query Query.
	 * @param string $lang  Language.
	 * @return array<string, mixed>|WP_Error
	 */
	public function run_suggest( $query, $lang ) {
		if ( ! Oli_AS_Rate_Limit::allow( 'search' ) ) {
			return new WP_Error( 'oli_as_rate_limited', __( 'Too many search requests. Please wait a moment.', 'oli-ajax-search' ), 429 );
		}

		$min = (int) Oli_AS_Settings::get( 'min_chars', 3 );
		if ( mb_strlen( $query, 'UTF-8' ) < $min ) {
			return array(
				'query'       => $query,
				'language'    => Oli_AS_Language::instance()->from_request( $lang ),
				'groups'      => array(),
				'total'       => 0,
				'results_url' => '',
				'took_ms'     => 0,
			);
		}

		$cached = Oli_AS_Cache::get( $query, $lang );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$search = new Oli_AS_Search();
		$data   = $search->suggest( $query, $lang );

		if ( Oli_AS_Settings::enabled( 'analytics_enabled' ) ) {
			Oli_AS_Analytics::instance()->record( $query, $data['language'], (int) $data['total'] );
		}

		Oli_AS_Cache::set( $query, $lang, $data );

		return $data;
	}
}
