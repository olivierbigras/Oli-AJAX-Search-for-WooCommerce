<?php
/**
 * Activation, schema, and cron cleanup.
 *
 * @package Oli_Ajax_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates database tables and default options.
 *
 * @since 0.1.0
 */
class Oli_AS_Install {

	/**
	 * Schema version option.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	const DB_VERSION = '0.1.0';

	/**
	 * Activation callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_tables();
		if ( false === get_option( Oli_AS_Settings::OPTION, false ) ) {
			add_option( Oli_AS_Settings::OPTION, Oli_AS_Settings::defaults(), '', false );
		}
		add_option( 'oli_as_db_version', self::DB_VERSION, '', false );
		self::migrate_options();
		add_option(
			'oli_as_index_status',
			array(
				'state'     => 'idle',
				'processed' => 0,
				'total'     => 0,
				'started'   => 0,
				'updated'   => 0,
				'message'   => '',
			),
			'',
			false
		);

		if ( ! wp_next_scheduled( 'oli_as_cron_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'oli_as_cron_maintenance' );
		}

		add_rewrite_rule( '^oli-as/suggest/?$', 'index.php?oli_as_suggest=1', 'top' );
		flush_rewrite_rules();
	}

	/**
	 * Deactivation callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'oli_as_cron_maintenance' );
		wp_clear_scheduled_hook( 'oli_as_index_batch' );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'oli_as_index_batch', null, 'oli-ajax-search' );
		}
		flush_rewrite_rules();
	}

	/**
	 * Creates custom tables.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$docs    = self::docs_table();
		$inv     = self::inv_table();
		$terms   = self::terms_table();
		$stats   = self::stats_table();

		$sql = "CREATE TABLE {$docs} (
			object_key varchar(80) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			object_type varchar(32) NOT NULL,
			language varchar(20) NOT NULL,
			title text NOT NULL,
			sku varchar(191) NOT NULL DEFAULT '',
			excerpt text NULL,
			url text NOT NULL,
			image_id bigint(20) unsigned NOT NULL DEFAULT 0,
			parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
			extra longtext NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (object_key),
			KEY object_lookup (object_id, object_type, language),
			KEY type_lang (object_type, language)
		) {$charset};
		CREATE TABLE {$inv} (
			term varchar(191) NOT NULL,
			language varchar(20) NOT NULL,
			object_key varchar(80) NOT NULL,
			field varchar(20) NOT NULL,
			weight smallint(5) unsigned NOT NULL DEFAULT 1,
			PRIMARY KEY  (term, language, object_key, field),
			KEY lang_term (language, term),
			KEY object_key (object_key)
		) {$charset};
		CREATE TABLE {$terms} (
			term varchar(191) NOT NULL,
			language varchar(20) NOT NULL,
			PRIMARY KEY  (term, language),
			KEY language (language)
		) {$charset};
		CREATE TABLE {$stats} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			query_hash char(32) NOT NULL,
			query_text varchar(191) NOT NULL,
			language varchar(20) NOT NULL,
			result_count int(11) NOT NULL DEFAULT 0,
			hits int(11) NOT NULL DEFAULT 1,
			last_hit datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY query_lang (query_hash, language),
			KEY result_count (result_count, hits)
		) {$charset};";

		dbDelta( $sql );
		update_option( 'oli_as_db_version', self::DB_VERSION );
	}

	/**
	 * Documents table name.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function docs_table() {
		global $wpdb;
		return $wpdb->prefix . 'oli_as_docs';
	}

	/**
	 * Inverted index table name.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function inv_table() {
		global $wpdb;
		return $wpdb->prefix . 'oli_as_inv';
	}

	/**
	 * Terms dictionary table name.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function terms_table() {
		global $wpdb;
		return $wpdb->prefix . 'oli_as_terms';
	}

	/**
	 * Analytics table name.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function stats_table() {
		global $wpdb;
		return $wpdb->prefix . 'oli_as_stats';
	}

	/**
	 * Maybe upgrades schema on admin init.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$current = get_option( 'oli_as_db_version', '' );
		if ( self::DB_VERSION !== $current ) {
			self::create_tables();
		}

		self::migrate_options();
	}

	/**
	 * Legacy option names that must be copied to the current oli_as_ keys.
	 *
	 * 0.1.x already used the unique oli_as_ prefix. The map is the single
	 * place for any future rename so stored settings are not lost.
	 *
	 * @since 0.2.1
	 *
	 * @return array<string, string>
	 */
	public static function legacy_option_map() {
		return array();
	}

	/**
	 * Plans a lossless copy of old option keys onto new keys.
	 *
	 * @since 0.2.1
	 *
	 * @param array<string, mixed>  $stored Existing option_name => value. Missing keys use false.
	 * @param array<string, string> $map    Old option name => new option name.
	 * @return array{keep: array<string, mixed>, delete: array<int, string>}
	 */
	public static function plan_option_migration( $stored, $map ) {
		$keep   = is_array( $stored ) ? $stored : array();
		$delete = array();

		foreach ( $map as $old => $new ) {
			$has_new = array_key_exists( $new, $keep ) && false !== $keep[ $new ];
			$has_old = array_key_exists( $old, $keep ) && false !== $keep[ $old ];

			if ( ! $has_new && $has_old ) {
				$keep[ $new ] = $keep[ $old ];
			}

			if ( $has_old && $old !== $new ) {
				unset( $keep[ $old ] );
				$delete[] = $old;
			}
		}

		return array(
			'keep'   => $keep,
			'delete' => array_values( array_unique( $delete ) ),
		);
	}

	/**
	 * Copies legacy options onto the current prefixed keys, then deletes the old keys.
	 *
	 * @since 0.2.1
	 *
	 * @return void
	 */
	public static function migrate_options() {
		$map = self::legacy_option_map();
		if ( empty( $map ) ) {
			if ( defined( 'OLI_AS_VERSION' ) ) {
				update_option( 'oli_as_data_version', OLI_AS_VERSION, false );
			}
			return;
		}

		$stored = array();
		foreach ( $map as $old => $new ) {
			$stored[ $old ] = get_option( $old, false );
			$stored[ $new ] = get_option( $new, false );
		}

		$plan = self::plan_option_migration( $stored, $map );

		foreach ( $map as $old => $new ) {
			if ( ! array_key_exists( $new, $plan['keep'] ) ) {
				continue;
			}
			if ( false === get_option( $new, false ) && false !== $plan['keep'][ $new ] ) {
				add_option( $new, $plan['keep'][ $new ], '', false );
			}
			unset( $old );
		}

		foreach ( $plan['delete'] as $old ) {
			delete_option( $old );
		}

		if ( defined( 'OLI_AS_VERSION' ) ) {
			update_option( 'oli_as_data_version', OLI_AS_VERSION, false );
		}
	}
}
