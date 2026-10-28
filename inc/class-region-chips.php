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
 * Any chip may also list `terms` (programme names: EQAO, NAPLAN, GCSE)
 * matched in card titles — the only way a programme chip without a region
 * matches anything, and how titles that name the test but not the
 * province find their province.
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
	 * The programme names a chip matches in card titles.
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
	 * The pack chip a card belongs to, in order of specificity:
	 *   1. a chip `terms` programme name in the title ("NAPLAN", "HSC") —
	 *      first, so a NAPLAN paper stored under Victoria never lands on
	 *      the VCE chip when only some chips are regional;
	 *   2. a regional chip whose `region` is the card's region;
	 *   3. a regional chip whose region name is in the title (diagnostics
	 *      and courses carry no region field).
	 * '' when nothing matches — the caller keeps its title-pattern key, so
	 * a pack without terms or regions (the US set) is unchanged.
	 *
	 * @param array  $chips  Normalised chips.
	 * @param string $region The card's region name ('' = unknown).
	 * @param string $title  The card's title.
	 * @return string
	 */
	public static function key_for( array $chips, $region, $title = '' ) {
		$title  = (string) $title;
		$region = trim( (string) $region );
		if ( '' !== $title ) {
			foreach ( $chips as $chip ) {
				foreach ( self::terms( $chip ) as $term ) {
					if ( self::in_title( $term, $title ) ) {
						return (string) $chip['key'];
					}
				}
			}
		}
		if ( '' !== $region ) {
			foreach ( $chips as $chip ) {
				foreach ( self::regions( $chip ) as $name ) {
					if ( 0 === strcasecmp( $name, $region ) ) {
						return (string) $chip['key'];
					}
				}
			}
		}
		if ( '' !== $title ) {
			foreach ( $chips as $chip ) {
				foreach ( self::regions( $chip ) as $name ) {
					if ( self::in_title( $name, $title ) ) {
						return (string) $chip['key'];
					}
				}
			}
		}
		return '';
	}

	/**
	 * Whole-word, case-insensitive match that also works for terms ending
	 * in punctuation ("11+", where \b would never match).
	 *
	 * @param string $needle Term.
	 * @param string $title  Title.
	 * @return bool
	 */
	private static function in_title( $needle, $title ) {
		return (bool) preg_match( '/(?<![\p{L}\p{N}])' . preg_quote( $needle, '/' ) . '(?![\p{L}\p{N}])/iu', $title );
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
		}
		$terms = self::terms( $chip );
		if ( $terms ) {
			$out['terms'] = $terms;
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
