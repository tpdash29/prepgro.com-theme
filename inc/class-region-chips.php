<?php
/**
 * Region-aware filter chips — shared by the three catalogue indexes
 * (practice tests, diagnostics, courses).
 *
 * A country pack's `content.exam_filter_chips` may give a chip a `region`
 * (one region name, or a list: "Manitoba & Nova Scotia"). Such a chip then
 * (a) carries its province/state outline, drawn by the engine's
 * country-aware resolver, and (b) actually matches cards: the indexes'
 * title-pattern family keys only ever coin ap|satact|gradschool|state, so
 * a regional chip ("Ontario · EQAO & OSSLT") used to filter nothing.
 * A chip may also list `terms` (programme names: EQAO, OSSLT) for cards
 * whose titles name the test but not the province.
 * Chips without a region — the US set — behave exactly as before.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static helpers; no state, no hooks.
 */
final class Region_Chips {

	/**
	 * The region names a chip covers ('' / missing → none).
	 *
	 * @param array $chip Pack chip.
	 * @return string[]
	 */
	public static function regions( array $chip ) {
		$raw = isset( $chip['region'] ) ? $chip['region'] : array();
		$out = array();
		foreach ( (array) $raw as $name ) {
			$name = trim( (string) $name );
			if ( '' !== $name ) {
				$out[] = $name;
			}
		}
		return $out;
	}

	/**
	 * The programme names a regional chip also matches in titles.
	 *
	 * @param array $chip Pack chip.
	 * @return string[]
	 */
	public static function terms( array $chip ) {
		$out = array();
		foreach ( (array) ( isset( $chip['terms'] ) ? $chip['terms'] : array() ) as $term ) {
			$term = trim( (string) $term );
			if ( '' !== $term ) {
				$out[] = $term;
			}
		}
		return $out;
	}

	/**
	 * The chip key a card belongs to when the chips are regional: the chip
	 * whose regions include the card's region, else the chip whose region
	 * name or programme term appears in the card's title (diagnostics and courses carry no
	 * region field). '' when the chips are not regional or nothing matches —
	 * the caller then keeps its own title-pattern key.
	 *
	 * @param array  $chips  Normalised chips (each with an optional 'region').
	 * @param string $region The card's region name ('' = unknown).
	 * @param string $title  The card's title.
	 * @return string
	 */
	public static function key_for( array $chips, $region, $title = '' ) {
		$region = trim( (string) $region );
		if ( '' !== $region ) {
			foreach ( $chips as $chip ) {
				foreach ( self::regions( $chip ) as $name ) {
					if ( 0 === strcasecmp( $name, $region ) ) {
						return (string) $chip['key'];
					}
				}
			}
		}
		$title = (string) $title;
		if ( '' !== $title ) {
			foreach ( $chips as $chip ) {
				if ( ! self::regions( $chip ) ) {
					continue;
				}
				$terms = array_merge( self::regions( $chip ), self::terms( $chip ) );
				foreach ( $terms as $name ) {
					if ( preg_match( '/\b' . preg_quote( $name, '/' ) . '\b/iu', $title ) ) {
						return (string) $chip['key'];
					}
				}
			}
		}
		return '';
	}

	/**
	 * The chip's outline(s), inline SVG in currentColor; '' when the chip
	 * has no region or the engine has no art for it. At most two outlines,
	 * so a two-province chip shows both.
	 *
	 * @param array $chip Normalised chip.
	 * @return string
	 */
	public static function outlines( array $chip ) {
		$cls = '\\PrepGro\\Engine\\Public_Side\\Homepage';
		if ( ! class_exists( $cls ) || ! method_exists( $cls, 'region_outline_svg' ) ) {
			return '';
		}
		$out = '';
		foreach ( array_slice( self::regions( $chip ), 0, 2 ) as $name ) {
			$out .= (string) $cls::region_outline_svg( $name, 'pgx-filter__region', 16 );
		}
		return $out;
	}

	/**
	 * Normalise one pack chip to {key,label[,region]}; null when unusable.
	 *
	 * @param mixed $chip Raw chip.
	 * @return array|null
	 */
	public static function normalise( $chip ) {
		if ( ! is_array( $chip ) || empty( $chip['key'] ) || empty( $chip['label'] ) ) {
			return null;
		}
		$out = array(
			'key'   => (string) $chip['key'],
			'label' => (string) $chip['label'],
		);
		$regions = self::regions( $chip );
		if ( $regions ) {
			$out['region'] = $regions;
			$out['terms']  = self::terms( $chip );
		}
		return $out;
	}

	/**
	 * One chip button. Same markup as before for a chip without a region.
	 *
	 * @param array $chip Normalised chip ('all' included).
	 * @return string
	 */
	public static function button( array $chip ) {
		$key = (string) $chip['key'];
		$on  = 'all' === $key;
		return '<button type="button" class="pgx-filter' . ( $on ? ' is-on' : '' ) . '"'
			. ' data-pgt-filter="' . esc_attr( $key ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '">'
			. self::outlines( $chip )
			. esc_html( (string) $chip['label'] ) . '</button>';
	}
}
