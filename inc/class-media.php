<?php
/**
 * Media — the production replacement for the prototype's `<image-slot>`.
 *
 * The handoff prototype marks every photo position with an `<image-slot>`
 * custom element whose placeholder text is the art direction for that slot
 * (README addendum A5). That element is prototype-only and does not ship;
 * this class renders the real thing.
 *
 * Two rules from A5 are baked in here so no caller can forget them:
 *   1. Every image box gets an EXPLICIT height plus `object-fit: cover`. A
 *      min-height alone leaves a white gap under the photo.
 *   2. A slot with no image falls back to the brand tint wash rather than
 *      collapsing, so the layout holds either way.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders image slots.
 */
final class Media {

	/**
	 * Render an image slot.
	 *
	 * @param array $args {
	 *     @type int    $attachment  Attachment ID (preferred source).
	 *     @type string $url         Direct URL, used when no attachment is given.
	 *     @type string $alt         Alt text. Empty string marks it decorative.
	 *     @type string $height      CSS height for the box, e.g. '340px' or
	 *                               'clamp(220px,32vw,340px)'. Required by A5.
	 *     @type string $radius      CSS border-radius. Default var(--pge-radius-lg).
	 *     @type string $class       Extra class on the wrapper.
	 *     @type string $size        WP image size for the attachment. Default 'large'.
	 *     @type bool   $border      Draw the hairline border. Default true.
	 *     @type string $sizes       `sizes` attribute. Default '(max-width: 900px) 100vw, 50vw'.
	 * }
	 * @return string
	 */
	public static function image( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'attachment' => 0,
				'url'        => '',
				'alt'        => '',
				'height'     => '280px',
				'radius'     => 'var(--pge-radius-lg)',
				'class'      => '',
				'size'       => 'large',
				'border'     => true,
				'sizes'      => '(max-width: 900px) 100vw, 50vw',
			)
		);

		$classes = 'pgt-imgslot' . ( $args['border'] ? '' : ' pgt-imgslot--flush' );
		if ( $args['class'] ) {
			$classes .= ' ' . $args['class'];
		}

		$style = sprintf(
			'--pgt-img-h:%s;--pgt-img-r:%s;',
			esc_attr( $args['height'] ),
			esc_attr( $args['radius'] )
		);

		$inner = '';

		if ( $args['attachment'] ) {
			$alt = $args['alt'];
			if ( '' === $alt ) {
				$alt = (string) get_post_meta( (int) $args['attachment'], '_wp_attachment_image_alt', true );
			}
			$inner = wp_get_attachment_image(
				(int) $args['attachment'],
				$args['size'],
				false,
				array(
					'class'    => 'pgt-imgslot__img',
					'alt'      => $alt,
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => $args['sizes'],
				)
			);
		} elseif ( $args['url'] ) {
			$inner = sprintf(
				'<img class="pgt-imgslot__img" src="%s" alt="%s" loading="lazy" decoding="async">',
				esc_url( $args['url'] ),
				esc_attr( $args['alt'] )
			);
		}

		if ( '' === $inner ) {
			// No image yet: the brand tint wash holds the box open. Purely
			// decorative, so it is hidden from assistive tech.
			$classes .= ' is-empty';
			$inner    = '<span class="pgt-imgslot__wash" aria-hidden="true"></span>';
		}

		return '<span class="' . esc_attr( $classes ) . '" style="' . $style . '">' . $inner . '</span>';
	}

	/**
	 * Resolve a Customizer image setting to an attachment ID, falling back to
	 * a bundled theme photo when the owner has not chosen one.
	 *
	 * @param string $mod      Theme mod key.
	 * @param string $fallback Bundled file name under assets/images/, or ''.
	 * @return array{attachment:int,url:string}
	 */
	public static function setting_image( $mod, $fallback = '' ) {
		$id = (int) get_theme_mod( $mod, 0 );
		if ( $id > 0 && wp_attachment_is_image( $id ) ) {
			return array(
				'attachment' => $id,
				'url'        => '',
			);
		}
		return array(
			'attachment' => 0,
			'url'        => $fallback ? PGT_URI . '/assets/images/' . ltrim( $fallback, '/' ) : '',
		);
	}

	/**
	 * A "Sample" badge. Every illustrative figure on a public marketing page
	 * carries one — the rule covers the module hero infographics, the module
	 * proof bars, the Excel trend stats, the homepage +80 plate, the Exams
	 * coverage tiles and the Pricing chart facts (README "Sample-data badges").
	 *
	 * @param string $label Badge text. Default "Sample".
	 * @param string $class Extra class.
	 * @return string
	 */
	public static function sample_badge( $label = '', $class = '' ) {
		$label = $label ? $label : __( 'Sample', 'prepgro-theme' );
		return '<span class="pgt-sample' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">' . esc_html( $label ) . '</span>';
	}
}
