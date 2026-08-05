<?php
/**
 * Image slots — the single registry of every editable photograph on the
 * public front end.
 *
 * One entry here gives you, for free:
 *   - a Customizer section with up to five uploads (Theme_Options::register)
 *   - a country-aware AI generation prompt the owner can copy (self::prompt)
 *   - random rotation between the uploaded images (Media::slot)
 *   - a labelled empty state carrying the recommended pixel size (Media::image)
 *
 * Adding a photo position anywhere on the site is therefore ONE array entry
 * plus one `Media::slot( 'key' )` call in the renderer — nothing else.
 *
 * Back-compat note: the base setting key IS image 1. The extra four are
 * `{key}_2` … `{key}_5`. That keeps every image an owner has already uploaded
 * exactly where it was, instead of orphaning it under a renamed setting.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declares and resolves the front-end image slots.
 */
final class Image_Slots {

	/**
	 * How many images one slot can hold. Raising this is safe: the Customizer
	 * controls and the random pick both derive from it.
	 */
	const MAX = 5;

	/**
	 * Cached registry.
	 *
	 * @var array|null
	 */
	private static $slots = null;

	/**
	 * Cached country context sentence.
	 *
	 * @var array|null
	 */
	private static $country = null;

	/**
	 * Every editable image position on the public site.
	 *
	 * `width`/`height` are the RECOMMENDED source pixels, derived from the real
	 * rendered box at the 1180px container width times a 2x device ratio — they
	 * are what the empty-state placeholder shows and what the AI prompt asks
	 * for, so they need to stay honest if a layout changes.
	 *
	 * @return array
	 */
	public static function slots() {
		if ( null !== self::$slots ) {
			return self::$slots;
		}

		$slots = array(
			'pgt_home_band_main' => array(
				'label'   => __( 'Homepage band — main photo', 'prepgro-theme' ),
				'where'   => __( 'The large left-hand photo in the homepage photo band.', 'prepgro-theme' ),
				'width'   => 1600,
				'height'  => 740,
				'alt'     => __( 'A student working through a practice set on a laptop.', 'prepgro-theme' ),
				'subject' => 'a teenage student at a tidy desk working through a practice test on a laptop, a notebook and pencil beside them',
			),
			'pgt_home_band_side' => array(
				'label'   => __( 'Homepage band — side photo', 'prepgro-theme' ),
				'where'   => __( 'The smaller photo under the "+80" stat plate in the homepage photo band.', 'prepgro-theme' ),
				'width'   => 1200,
				'height'  => 640,
				'alt'     => __( 'A tutor mid-explanation on a video call.', 'prepgro-theme' ),
				'subject' => 'a tutor mid-explanation during a one-to-one video call, gesturing at something off-screen, seen over the shoulder of the screen',
			),
			'pgt_module_photo_evaluate' => array(
				'label'   => __( 'Evaluate — proof band photo', 'prepgro-theme' ),
				'where'   => __( 'The photo beside the results bars on the Evaluate page.', 'prepgro-theme' ),
				'width'   => 1600,
				'height'  => 960,
				'alt'     => __( 'A student taking the diagnostic on a laptop.', 'prepgro-theme' ),
				'subject' => 'a student part-way through a timed diagnostic test on a laptop, concentrating, one hand resting near the trackpad',
			),
			'pgt_module_photo_elevate' => array(
				'label'   => __( 'Elevate — proof band photo', 'prepgro-theme' ),
				'where'   => __( 'The photo beside the results bars on the Elevate page.', 'prepgro-theme' ),
				'width'   => 1600,
				'height'  => 960,
				'alt'     => __( 'A tutor and student on a video call.', 'prepgro-theme' ),
				'subject' => 'a tutor and a student together in a live one-to-one lesson, working through a problem on paper between them',
			),
			'pgt_module_photo_excel' => array(
				'label'   => __( 'Excel — proof band photo', 'prepgro-theme' ),
				'where'   => __( 'The photo beside the results bars on the Excel page.', 'prepgro-theme' ),
				'width'   => 1600,
				'height'  => 960,
				'alt'     => __( 'A student mid practice test, timer visible.', 'prepgro-theme' ),
				'subject' => 'a student mid-way through a full-length practice test, a clock or timer visible in the frame, focused and calm',
			),
		);

		/**
		 * Filter the front-end image-slot registry.
		 *
		 * Add a photo position from a child theme or a plugin without touching
		 * this file. Every consumer (Customizer, prompt builder, renderer)
		 * reads through here.
		 *
		 * @param array $slots Slot key => definition.
		 */
		self::$slots = (array) apply_filters( 'pgt_image_slots', $slots );

		return self::$slots;
	}

	/**
	 * One slot definition, with defaults filled in.
	 *
	 * @param string $key Slot key.
	 * @return array|null Null when the key is not registered.
	 */
	public static function get( $key ) {
		$slots = self::slots();
		if ( ! isset( $slots[ $key ] ) ) {
			return null;
		}
		return wp_parse_args(
			$slots[ $key ],
			array(
				'label'   => $key,
				'where'   => '',
				'width'   => 1600,
				'height'  => 960,
				'alt'     => '',
				'subject' => '',
			)
		);
	}

	/**
	 * The setting keys backing one slot, in order.
	 *
	 * The FIRST is the bare slot key — see the back-compat note in the file
	 * docblock.
	 *
	 * @param string $key Slot key.
	 * @return string[]
	 */
	public static function setting_keys( $key ) {
		$keys = array( $key );
		for ( $i = 2; $i <= self::MAX; $i++ ) {
			$keys[] = $key . '_' . $i;
		}
		return $keys;
	}

	/**
	 * Attachment IDs actually uploaded to a slot, in slot order.
	 *
	 * Non-images and deleted attachments are dropped rather than rendered as a
	 * broken box, so a stale setting cannot break a page.
	 *
	 * @param string $key Slot key.
	 * @return int[]
	 */
	public static function attachments( $key ) {
		$ids = array();
		foreach ( self::setting_keys( $key ) as $setting ) {
			$id = (int) get_theme_mod( $setting, 0 );
			if ( $id > 0 && wp_attachment_is_image( $id ) ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/**
	 * Pick one image for this page load.
	 *
	 * Random rather than sequential because there is no per-visitor state to
	 * rotate against. Note that a full-page cache freezes the pick for the life
	 * of the cache entry — that is a property of the cache, not of this code.
	 *
	 * @param string $key Slot key.
	 * @return int Attachment ID, or 0 when the slot is empty.
	 */
	public static function pick( $key ) {
		$ids = self::attachments( $key );
		if ( ! $ids ) {
			return 0;
		}
		if ( 1 === count( $ids ) ) {
			return $ids[0];
		}
		return (int) $ids[ wp_rand( 0, count( $ids ) - 1 ) ];
	}

	/**
	 * "1600 × 960 px (5:3)" — shown in the Customizer and in the empty state.
	 *
	 * @param string $key Slot key.
	 * @return string
	 */
	public static function size_label( $key ) {
		$slot = self::get( $key );
		if ( ! $slot ) {
			return '';
		}
		$w = (int) $slot['width'];
		$h = (int) $slot['height'];

		return sprintf(
			/* translators: 1: width in px, 2: height in px, 3: aspect ratio like "5:3" */
			__( '%1$d × %2$d px (%3$s)', 'prepgro-theme' ),
			$w,
			$h,
			self::ratio( $w, $h )
		);
	}

	/**
	 * Reduce a pixel size to a display ratio.
	 *
	 * @param int $w Width.
	 * @param int $h Height.
	 * @return string
	 */
	private static function ratio( $w, $h ) {
		$a = max( 1, $w );
		$b = max( 1, $h );
		while ( $b ) {
			$t = $b;
			$b = $a % $b;
			$a = $t;
		}
		return ( $w / $a ) . ':' . ( $h / $a );
	}

	/**
	 * Country-of-operation facts used to localise every prompt.
	 *
	 * Read from the engine's country profile (`PGE_COUNTRY` →
	 * config/countries/{cc}.php) so a UK or Canadian site asks for UK or
	 * Canadian classrooms without anyone editing this theme. The theme must
	 * still render with the plugin switched off, hence the fallbacks.
	 *
	 * @return array
	 */
	private static function country() {
		if ( null !== self::$country ) {
			return self::$country;
		}

		$ctx = array(
			'name'        => __( 'the United States', 'prepgro-theme' ),
			'system'      => '',
			'conventions' => '',
			'student'     => __( 'Student', 'prepgro-theme' ),
			'exam'        => __( 'Practice Test', 'prepgro-theme' ),
		);

		if ( function_exists( 'pge_country' ) ) {
			$country = pge_country();

			$name = (string) $country->name();
			if ( '' !== $name ) {
				$ctx['name'] = $name;
			}

			$education = (array) $country->value( 'education', array() );
			if ( ! empty( $education['system'] ) ) {
				$ctx['system'] = (string) $education['system'];
			}
			if ( ! empty( $education['conventions'] ) ) {
				$ctx['conventions'] = (string) $education['conventions'];
			}

			$ctx['student'] = (string) $country->label( 'aspirant', $ctx['student'] );
			$ctx['exam']    = (string) $country->label( 'exam', $ctx['exam'] );
		}

		self::$country = $ctx;

		return self::$country;
	}

	/**
	 * The image-generation prompt for one slot.
	 *
	 * Written to be pasted straight into an image model. Everything that varies
	 * by deployment (country name, schooling system, local conventions, what a
	 * learner is even called) comes from the country profile, so the same
	 * registry produces a US prompt on the US site and a Canadian one on the
	 * Canadian site.
	 *
	 * The people/safeguarding line is not boilerplate: these are photographs of
	 * minors on a children's education site.
	 *
	 * @param string $key Slot key.
	 * @return string Empty string when the key is unknown.
	 */
	public static function prompt( $key ) {
		$slot = self::get( $key );
		if ( ! $slot ) {
			return '';
		}

		$c       = self::country();
		$subject = $slot['subject'] ? $slot['subject'] : strtolower( (string) $slot['label'] );

		$setting = sprintf(
			/* translators: 1: country name, 2: learner noun, 3: assessment noun */
			__( 'Set in %1$s. A learner is called a "%2$s" and an assessment a "%3$s".', 'prepgro-theme' ),
			$c['name'],
			$c['student'],
			$c['exam']
		);
		if ( $c['system'] ) {
			$setting .= ' ' . sprintf(
				/* translators: %s: schooling system name, e.g. "United States K-12" */
				__( 'Schooling system: %s.', 'prepgro-theme' ),
				$c['system']
			);
		}
		if ( $c['conventions'] ) {
			$setting .= ' ' . $c['conventions'];
		}

		$lines = array(
			sprintf(
				/* translators: %s: country name */
				__( 'Photorealistic editorial photograph for an exam-preparation website serving %s.', 'prepgro-theme' ),
				$c['name']
			),
			'',
			__( 'SUBJECT: ', 'prepgro-theme' ) . $subject . '.',
			__( 'SETTING: ', 'prepgro-theme' ) . $setting,
			__( 'PEOPLE: age-appropriate and wholesome; a realistic mix of the local student population. No identifiable faces of real people.', 'prepgro-theme' ),
			__( 'STYLE: natural daylight, candid rather than posed, shallow depth of field, uncluttered modern interior. No visible brand logos and no readable text on any screen.', 'prepgro-theme' ),
			__( 'COMPOSITION: landscape crop, subject slightly off-centre with clean negative space for a text overlay.', 'prepgro-theme' ),
			__( 'OUTPUT: ', 'prepgro-theme' ) . self::size_label( $key ) . __( ', JPEG, sRGB.', 'prepgro-theme' ),
		);

		$prompt = implode( "\n", $lines );

		/**
		 * Filter a generated image prompt.
		 *
		 * @param string $prompt The prompt text.
		 * @param string $key    Slot key.
		 * @param array  $slot   Resolved slot definition.
		 */
		return (string) apply_filters( 'pgt_image_slot_prompt', $prompt, $key, $slot );
	}

	/**
	 * Whether the current request should see the authoring affordances — the
	 * labelled empty state naming the slot and its pixel size.
	 *
	 * Deliberately NOT shown to visitors: "1600 × 960 px — add images in the
	 * Customizer" reads as a broken page to a prospective customer. They get
	 * the brand wash instead, which reads as a designed panel.
	 *
	 * @return bool
	 */
	public static function author_can_see() {
		if ( is_customize_preview() ) {
			return true;
		}
		return current_user_can( 'edit_theme_options' );
	}
}
