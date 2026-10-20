<?php
/**
 * REST API routes.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public suggestion endpoint.
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
	 * Registers a frontend rewrite that TranslatePress can prefix.
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

		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$lang  = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$min   = (int) Oli_AS_Settings::get( 'min_chars', 2 );

		if ( mb_strlen( $query, 'UTF-8' ) < $min ) {
			wp_send_json(
				array(
					'query'       => $query,
					'language'    => Oli_AS_Language::instance()->from_request( $lang ),
					'groups'      => array(),
					'total'       => 0,
					'results_url' => '',
					'took_ms'     => 0,
				)
			);
		}

		$search = new Oli_AS_Search();
		$data   = $search->suggest( $query, $lang );
		Oli_AS_Analytics::instance()->record( $query, $data['language'], (int) $data['total'] );
		wp_send_json( $data );
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
						'required'          => true,
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
	 * @return WP_REST_Response
	 */
	public function suggest( $request ) {
		$query = (string) $request->get_param( 'q' );
		$lang  = (string) $request->get_param( 'lang' );
		$min   = (int) Oli_AS_Settings::get( 'min_chars', 2 );

		if ( mb_strlen( $query, 'UTF-8' ) < $min ) {
			return rest_ensure_response(
				array(
					'query'       => $query,
					'language'    => Oli_AS_Language::instance()->from_request( $lang ),
					'groups'      => array(),
					'total'       => 0,
					'results_url' => '',
					'took_ms'     => 0,
				)
			);
		}

		$search = new Oli_AS_Search();
		$data   = $search->suggest( $query, $lang );

		Oli_AS_Analytics::instance()->record( $query, $data['language'], (int) $data['total'] );

		return rest_ensure_response( $data );
	}
}
