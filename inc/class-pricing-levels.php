<?php
/**
 * Pricing levels — the single source of truth for level-scoped prices and
 * for the exam → pricing routing rule (README §6 and §7).
 *
 * Everything that shows a price reads this class: the plan cards, the A4
 * cost-by-level chart, the exam index cards, the exam pages and the module
 * landing pages. The spec is explicit that the source must not be
 * duplicated — a SKU price change has to move the chart too.
 *
 * PRICE RESOLUTION, in order:
 *   1. The engine's live packages table, matched by tier name. This is the
 *      real, editable production price.
 *   2. The `pgt_pricing_levels` filter, for sites wiring their own SKUs.
 *   3. The design figures from §7, as the last resort so a fresh install
 *      still renders a coherent page.
 * Nothing here writes; class-woocommerce.php and the packages schema are
 * untouched.
 *
 * ── Conflict note ────────────────────────────────────────────────────
 * §7 specifies four levels — Elementary · Middle school · High school ·
 * AP subjects. The engine's US tier config ships Elementary, Middle
 * School, High School and "All Access". `ap` is mapped onto All Access as
 * the nearest existing tier. There are no Live Tutor packages in the
 * engine at all, so those four prices fall through to the design figures
 * until the SKUs exist.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Level definitions, price resolution and routing.
 */
final class Pricing_Levels {

	/** Default level when none is supplied (README §6). */
	const DEFAULT_LEVEL = 'high';

	/** Widest tutor price in the table; the A4 chart scales bars against it. */
	const CHART_MAX = 249;

	/**
	 * The four levels, in display order, with the design figures and the
	 * engine tier each maps onto.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function definitions() {
		return array(
			'elementary' => array(
				'name'  => __( 'Elementary', 'prepgro-theme' ),
				'tier'  => 'Elementary',
				'pack'  => array( 'monthly' => 9.99, 'quarterly' => 24.99, 'annual' => 49.99 ),
				'tutor' => 129,
			),
			'middle'     => array(
				'name'  => __( 'Middle school', 'prepgro-theme' ),
				'tier'  => 'Middle School',
				'pack'  => array( 'monthly' => 14.99, 'quarterly' => 34.99, 'annual' => 79.99 ),
				'tutor' => 149,
			),
			'high'       => array(
				'name'  => __( 'High school', 'prepgro-theme' ),
				'tier'  => 'High School',
				'pack'  => array( 'monthly' => 24.99, 'quarterly' => 59.99, 'annual' => 119.99 ),
				'tutor' => 179,
			),
			'ap'         => array(
				'name'  => __( 'AP subjects', 'prepgro-theme' ),
				'tier'  => 'All Access',
				'pack'  => array( 'monthly' => 29.99, 'quarterly' => 74.99, 'annual' => 149.99 ),
				'tutor' => 249,
			),
		);
	}

	/**
	 * Resolved levels: design figures overlaid with real package prices.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function levels() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$levels = self::definitions();
		$live   = self::package_prices();

		foreach ( $levels as $key => &$level ) {
			$tier = $level['tier'];
			if ( isset( $live[ $tier ] ) ) {
				foreach ( array( 'monthly', 'quarterly', 'annual' ) as $term ) {
					if ( isset( $live[ $tier ][ $term ] ) && $live[ $tier ][ $term ] > 0 ) {
						$level['pack'][ $term ] = (float) $live[ $tier ][ $term ];
					}
				}
			}
			$level['key'] = $key;
		}
		unset( $level );

		/**
		 * Filter the resolved pricing levels. Use this to wire real Live
		 * Tutor SKUs, which the engine does not model yet.
		 *
		 * @param array $levels Level key => {name, tier, pack, tutor}.
		 */
		$cache = (array) apply_filters( 'pgt_pricing_levels', $levels );
		return $cache;
	}

	/**
	 * Read the engine's active packages and reduce them to
	 * tier => {monthly, quarterly, annual}, cheapest wins per term.
	 *
	 * Read-only, and every step is guarded: a missing table, a missing
	 * config or a disabled engine all degrade to the design figures rather
	 * than erroring.
	 *
	 * @return array<string,array<string,float>>
	 */
	private static function package_prices() {
		$cached = get_transient( 'pgt_pricing_levels_v1' );
		if ( false !== $cached ) {
			return (array) $cached;
		}

		$out = array();

		if ( ! class_exists( '\\PrepGro\\Engine\\Storage\\Storage_Map' ) ) {
			set_transient( 'pgt_pricing_levels_v1', $out, HOUR_IN_SECONDS );
			return $out;
		}

		global $wpdb;
		$table = \PrepGro\Engine\Storage\Storage_Map::table( 'packages' );
		if ( ! $table || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			set_transient( 'pgt_pricing_levels_v1', $out, HOUR_IN_SECONDS );
			return $out;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from Storage_Map.
		$rows = $wpdb->get_results( "SELECT name, price, duration_months FROM {$table} WHERE is_active = 1" );
		if ( ! $rows ) {
			set_transient( 'pgt_pricing_levels_v1', $out, HOUR_IN_SECONDS );
			return $out;
		}

		$tiers = array();
		foreach ( self::definitions() as $def ) {
			$tiers[] = $def['tier'];
		}

		foreach ( $rows as $row ) {
			$price = (float) $row->price;
			if ( $price <= 0 ) {
				continue;
			}
			$months = (int) $row->duration_months;
			$term   = 1 === $months ? 'monthly' : ( 3 === $months ? 'quarterly' : ( $months >= 12 ? 'annual' : '' ) );
			if ( '' === $term ) {
				continue;
			}
			foreach ( $tiers as $tier ) {
				if ( 0 === stripos( (string) $row->name, $tier ) ) {
					if ( ! isset( $out[ $tier ][ $term ] ) || $price < $out[ $tier ][ $term ] ) {
						$out[ $tier ][ $term ] = $price;
					}
					break;
				}
			}
		}

		set_transient( 'pgt_pricing_levels_v1', $out, HOUR_IN_SECONDS );
		return $out;
	}

	/** @return void */
	public static function flush() {
		delete_transient( 'pgt_pricing_levels_v1' );
	}

	/**
	 * Whether a level key is one of the four.
	 *
	 * @param string $key Level key.
	 * @return bool
	 */
	public static function is_level( $key ) {
		return isset( self::levels()[ $key ] );
	}

	/**
	 * The level for the current request, from `?level=`, defaulting per §6.
	 *
	 * @return string
	 */
	public static function current() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display preference.
		$raw = isset( $_GET['level'] ) ? sanitize_key( wp_unslash( $_GET['level'] ) ) : '';
		return self::is_level( $raw ) ? $raw : self::DEFAULT_LEVEL;
	}

	/**
	 * The pricing URL for a level. Never link to a page showing every SKU.
	 *
	 * @param string $level Level key. Empty uses the request's level.
	 * @return string
	 */
	public static function url( $level = '' ) {
		$level = $level && self::is_level( $level ) ? $level : self::current();
		return add_query_arg( 'level', $level, home_url( '/pricing/' ) );
	}

	/**
	 * The pricing level an exam routes to.
	 *
	 * Reads, in order: the `pricing_level` term on the exam, then the
	 * `pricing_level` / `_pricing_level` post meta, then a name-based guess
	 * for the national exam families (SAT/ACT/PSAT and AP map to `ap`, per
	 * the client rule that an ACT page must reach AP Subject pricing), then
	 * the `high` default.
	 *
	 * @param int|\WP_Post|null $post Exam post.
	 * @return string
	 */
	public static function for_exam( $post = null ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return self::DEFAULT_LEVEL;
		}

		$terms = get_the_terms( $post, 'pricing_level' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( self::is_level( $term->slug ) ) {
					return $term->slug;
				}
			}
		}

		foreach ( array( 'pricing_level', '_pricing_level' ) as $key ) {
			$meta = sanitize_key( (string) get_post_meta( $post->ID, $key, true ) );
			if ( self::is_level( $meta ) ) {
				return $meta;
			}
		}

		return self::guess_level( get_the_title( $post ) . ' ' . $post->post_name );
	}

	/**
	 * Best-effort level from an exam name. National college-admission exams
	 * and AP subjects map to `ap`; grade bands map by number.
	 *
	 * @param string $name Exam name/slug.
	 * @return string
	 */
	public static function guess_level( $name ) {
		$n = strtolower( (string) $name );

		if ( preg_match( '/\b(sat|act|ap)\b|advanced placement/', $n ) ) {
			return 'ap';
		}
		if ( preg_match( '/\bpsat\b/', $n ) ) {
			return 'high';
		}
		if ( preg_match( '/grade\s*(\d{1,2})|(\d{1,2})(st|nd|rd|th)\s*grade/', $n, $m ) ) {
			$g = (int) ( $m[1] ? $m[1] : $m[2] );
			if ( $g >= 9 ) {
				return 'high';
			}
			if ( $g >= 6 ) {
				return 'middle';
			}
			if ( $g >= 1 ) {
				return 'elementary';
			}
		}
		if ( preg_match( '/elementary/', $n ) ) {
			return 'elementary';
		}
		if ( preg_match( '/middle/', $n ) ) {
			return 'middle';
		}
		if ( preg_match( '/high school|algebra|geometry|calculus|biology|chemistry|physics/', $n ) ) {
			return 'high';
		}

		return self::DEFAULT_LEVEL;
	}

	/**
	 * Format a price for display. Whole numbers lose the decimals, matching
	 * the design ("$129", but "$9.99").
	 *
	 * @param float $amount Price.
	 * @return string
	 */
	public static function money( $amount ) {
		$amount = (float) $amount;
		$sym    = self::currency_symbol();
		if ( abs( $amount - round( $amount ) ) < 0.005 ) {
			return $sym . number_format( $amount, 0 );
		}
		return $sym . number_format( $amount, 2 );
	}

	/**
	 * Currency symbol for the country of operation, defaulting to USD.
	 *
	 * @return string
	 */
	public static function currency_symbol() {
		$map  = array( 'US' => '$', 'CA' => '$', 'IN' => '₹', 'GB' => '£', 'EU' => '€' );
		$code = defined( 'PGE_COUNTRY' ) ? strtoupper( (string) PGE_COUNTRY ) : 'US';
		return isset( $map[ $code ] ) ? $map[ $code ] : '$';
	}
}
