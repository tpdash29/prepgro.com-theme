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
 * `slot()` is the front door for anything declared in Image_Slots: it resolves
 * the owner's uploads, rotates between them, and labels the empty state. Use
 * `image()` directly only for images that are NOT Customizer-managed — the blog
 * reading a post's own featured image, for instance.
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
	 * Render a registered image slot: rotate between the owner's uploads, or
	 * show the labelled empty state.
	 *
	 * Every argument `image()` accepts passes straight through, so a caller can
	 * still set the box height, radius and `sizes` for its own layout. Passing a
	 * non-zero `attachment` overrides the rotation — that is how the module
	 * pages let a page's own featured image win over the site-wide slot.
	 *
	 * @param string $key  Slot key registered in Image_Slots.
	 * @param array  $args Overrides for image(). See that method.
	 * @return string
	 */
	public static function slot( $key, $args = array() ) {
		$slot = Image_Slots::get( $key );
		if ( ! $slot ) {
			return '';
		}

		$override = isset( $args['attachment'] ) ? (int) $args['attachment'] : 0;

		$args['attachment']  = $override > 0 ? $override : Image_Slots::pick( $key );
		$args['placeholder'] = array(
			'label' => $slot['label'],
			'size'  => Image_Slots::size_label( $key ),
		);
		if ( empty( $args['alt'] ) ) {
			$args['alt'] = $slot['alt'];
		}

		return self::image( $args );
	}

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
	 *     @type array  $placeholder Empty-state authoring label: {label, size}.
	 *                               Rendered only for users who can edit theme
	 *                               options — see Image_Slots::author_can_see().
	 * }
	 * @return string
	 */
	public static function image( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'attachment'  => 0,
				'url'         => '',
				'alt'         => '',
				'height'      => '280px',
				'radius'      => 'var(--pge-radius-lg)',
				'class'       => '',
				'size'        => 'large',
				'border'      => true,
				'sizes'       => '(max-width: 900px) 100vw, 50vw',
				'placeholder' => array(),
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
			$inner   .= self::placeholder_label( $args['placeholder'] );
		}

		return '<span class="' . esc_attr( $classes ) . '" style="' . $style . '">' . $inner . '</span>';
	}

	/**
	 * The authoring label drawn over an empty slot: what this position is, and
	 * the pixel size it wants.
	 *
	 * Only ever shown to someone who can act on it. A visitor sees the wash
	 * alone — "1600 × 960 px, add images in the Customizer" reads as a broken
	 * page to a prospective customer, which is worse than a plain tinted panel.
	 *
	 * @param array $placeholder {label, size}.
	 * @return string
	 */
	private static function placeholder_label( $placeholder ) {
		if ( empty( $placeholder['label'] ) || ! Image_Slots::author_can_see() ) {
			return '';
		}

		$out = '<span class="pgt-imgslot__ph" aria-hidden="true">'
			. '<span class="pgt-imgslot__ph-icon">'
			. Icons::svg( 'image', array( 'size' => 20, 'stroke' => 1.6 ) )
			. '</span>'
			. '<span class="pgt-imgslot__ph-label">' . esc_html( $placeholder['label'] ) . '</span>';

		if ( ! empty( $placeholder['size'] ) ) {
			$out .= '<span class="pgt-imgslot__ph-size">' . esc_html( $placeholder['size'] ) . '</span>';
		}

		$out .= '<span class="pgt-imgslot__ph-hint">'
			. esc_html__( 'Customize → PrepGro Images', 'prepgro-theme' )
			. '</span></span>';

		return $out;
	}

	/**
	 * Resolve a Customizer image setting to an attachment ID, falling back to
	 * a bundled theme photo when the owner has not chosen one.
	 *
	 * Retained for callers outside the Image_Slots registry. Registered slots
	 * should use `slot()`, which rotates and labels the empty state.
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
