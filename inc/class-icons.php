<?php
/**
 * Lucide icon set — inline SVG, no HTTP request, no icon font.
 *
 * The redesign specifies Lucide throughout (handoff README §2, "Icons"):
 * stroke 1.5–2, round caps, never filled. The one deliberate exception is
 * `zap`, the featured-plan badge, which is filled — pass $filled = true.
 *
 * Only the glyphs the theme actually renders live here. Add a path set when
 * a screen needs one rather than pulling the whole library in.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inline Lucide glyph provider.
 */
final class Icons {

	/**
	 * name => path markup, on Lucide's 24×24 grid.
	 *
	 * @return array<string,string>
	 */
	private static function paths() {
		return array(
			'circle-check'      => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
			'circle-check-big'  => '<path d="M9 11l3 3L22 4"/><path d="M21 12a9 9 0 1 1-6.2-8.5"/>',
			'play-circle'       => '<circle cx="12" cy="12" r="10"/><path d="m10 8 6 4-6 4Z"/>',
			'file-text'         => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/>',
			'graduation-cap'    => '<path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/><path d="m2 10 10-5 10 5-10 5z"/>',
			'list'              => '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>',
			'map-pin'           => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
			'line-chart'        => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m19 9-5 5-4-4-3 3"/>',
			'trend-up'          => '<path d="M3 17l6-6 4 4 8-8"/><path d="M21 7v6h-6"/>',
			'info'              => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
			'mail'              => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
			'book-open'         => '<path d="M4 19.5V6a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 1.5Z"/><path d="M9 8h6"/>',
			'calendar'          => '<rect width="18" height="17" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/>',
			'clipboard'         => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',
			'clipboard-list'    => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>',
			'users'             => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
			'user-check'        => '<path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M19 8v6M16 11h6"/>',
			'pencil'            => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>',
			'dollar-sign'       => '<path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
			'circle-plus'       => '<circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/>',
			'layers'            => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m6.08 9.5-3.5 1.6a1 1 0 0 0 0 1.81l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9a1 1 0 0 0 0-1.83l-3.5-1.59"/><path d="m6.08 14.5-3.5 1.6a1 1 0 0 0 0 1.81l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9a1 1 0 0 0 0-1.83l-3.5-1.59"/>',
			'refresh-cw'        => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>',
			'clock'             => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
			'shield'            => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
			'alert-triangle'    => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
			'activity'          => '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>',
			'bar-chart'         => '<path d="M12 20V10M18 20V4M6 20v-4"/>',
			'log-in'            => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
			'user-plus'         => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
			'help-circle'       => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3V13M12 16.5h.01"/>',
			'credit-card'       => '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>',
			'arrow-right'       => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
			'arrow-left'        => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
			'search'            => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4.2-4.2"/>',
			'chevron-down'      => '<path d="m6 9 6 6 6-6"/>',
			'plus'              => '<path d="M12 5v14M5 12h14"/>',
			'minus'             => '<path d="M5 12h14"/>',
			'x'                 => '<path d="M6 6l12 12M18 6L6 18"/>',
			'layout-dashboard'  => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
			'user'              => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'log-out'           => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
			'trophy'            => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
			'package'           => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
			'receipt'           => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
			'flag'              => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/>',
			'zap'               => '<path d="M13 2 4.5 13.2H11l-1 8.8 8.5-11.2H12l1-8.8Z"/>',
			'target'            => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
			'sparkle'           => '<path d="M9.94 14.06 2 22"/><path d="M14 2 15.5 6.5 20 8l-4.5 1.5L14 14l-1.5-4.5L8 8l4.5-1.5z"/>',
			'moon'              => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
			'sun'               => '<circle cx="12" cy="12" r="4.5"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.6 4.6l1.8 1.8M17.6 17.6l1.8 1.8M19.4 4.6l-1.8 1.8M6.4 17.6l-1.8 1.8"/>',
		);
	}

	/**
	 * Render an inline SVG glyph.
	 *
	 * @param string $name   Glyph key.
	 * @param array  $args   size (px), stroke (width), class, filled (bool), color.
	 * @return string SVG markup, or '' for an unknown name.
	 */
	public static function svg( $name, $args = array() ) {
		$paths = self::paths();
		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'size'   => 16,
				'stroke' => 1.8,
				'class'  => '',
				'filled' => false,
				'color'  => 'currentColor',
			)
		);

		$size  = (float) $args['size'];
		$class = $args['class'] ? ' class="' . esc_attr( $args['class'] ) . '"' : '';

		if ( $args['filled'] ) {
			return '<svg' . $class . ' viewBox="0 0 24 24" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '"'
				. ' fill="' . esc_attr( $args['color'] ) . '" aria-hidden="true" focusable="false">'
				. $paths[ $name ] . '</svg>';
		}

		return '<svg' . $class . ' viewBox="0 0 24 24" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '"'
			. ' fill="none" stroke="' . esc_attr( $args['color'] ) . '" stroke-width="' . esc_attr( (string) $args['stroke'] ) . '"'
			. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
			. $paths[ $name ] . '</svg>';
	}
}
