<?php
/**
 * Courses index — /courses/ (public-listing-pages redesign).
 *
 * The Elevate pillar's public course catalogue, rendered on the Course
 * CPT's own archive (the CPT registers `has_archive => true` with rewrite
 * slug `courses`, so the URL already resolves; this class supplies the
 * template that was missing).
 *
 * Business rule (owner-confirmed): courses are NOT included with a plain
 * test pack. The first lesson previews free; the full course unlocks on
 * that exam's Live Tutor package (internally the "Live Teacher Package" —
 * Package_Bundles). The lock badge is honest per course: it only shows on
 * courses that actually carry `_course_packages` gating; an ungated course
 * says "Included free" instead.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Courses index.
 */
final class Courses_Page {

	/** @var Courses_Page|null */
	private static $instance = null;

	/** @var \WP_Post[]|null Memoized course list. */
	private $courses = null;

	/** @var array<int,int>|null Memoized lesson counts by course id. */
	private $lesson_counts = null;

	/** @return Courses_Page */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/** @return void */
	public function init() {
		add_shortcode( 'pgt_courses', array( $this, 'render' ) );
	}

	/** @return bool */
	public function is_courses_page() {
		return is_post_type_archive( 'course' );
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Rendering.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * Render the courses index.
	 *
	 * @return string
	 */
	public function render() {
		return '<div class="pgx pgx--elevate pgc">'
			. $this->breadcrumb()
			. $this->header()
			. '<section class="pgx-body">'
			. $this->filters()
			. $this->cards()
			. $this->whats_inside()
			. $this->faq()
			. $this->cta_band()
			. '</section></div>'
			. $this->schema_jsonld();
	}

	/** @return string */
	private function breadcrumb() {
		return '<nav class="pgx-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'prepgro-theme' ) . '">'
			. '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'prepgro-theme' ) . '</a>'
			. '<span aria-hidden="true">/</span>'
			. '<span class="pgx-crumbs__here" aria-current="page">' . esc_html__( 'Courses', 'prepgro-theme' ) . '</span>'
			. '</nav>';
	}

	/**
	 * Hero: copy column + the weekly-plan checklist sample card (Elevate's
	 * signature infographic — illustrative, Sample badge stays).
	 *
	 * @return string
	 */
	private function header() {
		$courses = $this->courses();
		$preview = $courses ? get_permalink( $courses[0] ) : '#pgx-grid';

		$copy = '<div class="pgx-head__copy">'
			. '<span class="pgx-chip"><span class="pgx-chip__dot" aria-hidden="true"></span>' . esc_html__( 'Elevate · Courses', 'prepgro-theme' ) . '</span>'
			. '<h1 class="pgx-h1">' . esc_html__( 'Learn it, then prove it on a real practice test.', 'prepgro-theme' ) . '</h1>'
			. '<p class="pgx-lede">' . esc_html__( 'Short video lessons paired with practice questions, organized by exam and skill. Free to preview — unlocked in full on a Live Tutor package.', 'prepgro-theme' ) . '</p>'
			. '<div class="pgx-ctas">'
			. '<a class="pgx-btn pgx-btn--accent" href="' . esc_url( $preview ) . '">' . esc_html__( 'Preview a lesson', 'prepgro-theme' ) . '</a>'
			. '<a class="pgx-btn pgx-btn--ghost" href="' . esc_url( home_url( '/pricing/' ) ) . '">' . esc_html__( 'Compare packages', 'prepgro-theme' ) . '</a>'
			. '</div></div>';

		return '<section class="pgx-head">'
			. '<div class="pgx-head__grid">' . $copy . $this->plan_card() . '</div>'
			. '</section>';
	}

	/**
	 * The dark "this week's plan" checklist card.
	 *
	 * @return string
	 */
	private function plan_card() {
		$check = Icons::svg( 'check', array( 'size' => 11, 'stroke' => 3 ) );
		$rows  = array(
			array( 'done' => true, 'l' => __( '3 lessons · data analysis', 'prepgro-theme' ) ),
			array( 'done' => true, 'l' => __( '12-question timed set', 'prepgro-theme' ) ),
			array( 'done' => false, 'l' => __( 'Full practice test', 'prepgro-theme' ) ),
		);

		$list = '';
		foreach ( $rows as $r ) {
			$list .= '<div class="pgc-plan__row' . ( $r['done'] ? ' is-done' : '' ) . '">'
				. '<span class="pgc-plan__box" aria-hidden="true">' . ( $r['done'] ? $check : '' ) . '</span>'
				. '<span>' . esc_html( $r['l'] ) . '</span>'
				. '</div>';
		}

		return '<div class="pgx-trend pgc-plan" role="img" aria-label="' . esc_attr__( 'Sample checklist: a weekly study plan with two of three items done', 'prepgro-theme' ) . '">'
			. '<div class="pgx-trend__head"><span>' . esc_html__( 'This week’s plan', 'prepgro-theme' ) . '</span>'
			. '<span class="pgx-trend__sample">' . esc_html__( 'Sample', 'prepgro-theme' ) . '</span></div>'
			. $list
			. '</div>';
	}

	/**
	 * Filter chips — same client-side mechanism as the other two indexes.
	 *
	 * @return string
	 */
	private function filters() {
		$chips = array(
			'all'        => __( 'All courses', 'prepgro-theme' ),
			'satact'     => __( 'SAT · ACT', 'prepgro-theme' ),
			'ap'         => __( 'AP', 'prepgro-theme' ),
			'state'      => __( 'State & grade', 'prepgro-theme' ),
			'gradschool' => __( 'GRE · GMAT', 'prepgro-theme' ),
		);

		$out = '';
		foreach ( $chips as $key => $label ) {
			$out .= '<button type="button" class="pgx-filter' . ( 'all' === $key ? ' is-on' : '' ) . '"'
				. ' data-pgt-filter="' . esc_attr( $key ) . '" aria-pressed="' . ( 'all' === $key ? 'true' : 'false' ) . '">'
				. esc_html( $label ) . '</button>';
		}
		return '<div class="pgx-filters">' . $out . '</div>';
	}

	/**
	 * Published courses.
	 *
	 * @return \WP_Post[]
	 */
	private function courses() {
		if ( null === $this->courses ) {
			$this->courses = get_posts(
				array(
					'post_type'      => 'course',
					'post_status'    => 'publish',
					'posts_per_page' => (int) apply_filters( 'pgt_courses_index_limit', 60 ),
					'orderby'        => 'title',
					'order'          => 'ASC',
					'no_found_rows'  => true,
				)
			);
		}
		return $this->courses;
	}

	/**
	 * Real lesson counts per course, one query over the engine's
	 * course_lesson_map table.
	 *
	 * @return array<int,int>
	 */
	private function lesson_counts() {
		if ( null !== $this->lesson_counts ) {
			return $this->lesson_counts;
		}
		$this->lesson_counts = array();

		if ( class_exists( '\\PrepGro\\Engine\\Storage\\Storage_Map' ) ) {
			global $wpdb;
			$map = \PrepGro\Engine\Storage\Storage_Map::table( 'course_lesson_map' );
			if ( $map && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $map ) ) === $map ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from Storage_Map.
				$rows = $wpdb->get_results( "SELECT course_id, COUNT(DISTINCT lesson_id) AS n FROM {$map} WHERE lesson_id > 0 GROUP BY course_id" );
				foreach ( (array) $rows as $row ) {
					$this->lesson_counts[ (int) $row->course_id ] = (int) $row->n;
				}
			}
		}
		return $this->lesson_counts;
	}

	/**
	 * Whether a course is package-gated (premium). Mirrors
	 * Course_Entitlement::packages_for_course() — a course with no linked
	 * packages is open, so its card must not claim a lock.
	 *
	 * @param int $course_id Course id.
	 * @return bool
	 */
	private function is_gated( $course_id ) {
		$key = class_exists( '\\PrepGro\\Engine\\Storage\\Storage_Map' )
			? \PrepGro\Engine\Storage\Storage_Map::meta( 'course_packages' )
			: '_course_packages';
		$raw = get_post_meta( (int) $course_id, $key, true );
		return is_array( $raw ) && array_filter( array_map( 'intval', $raw ) );
	}

	/**
	 * Course cards: cover (real thumbnail or an art-directed placeholder),
	 * family chip + real lesson count, title, excerpt, and the honest
	 * access line.
	 *
	 * @return string
	 */
	private function cards() {
		$courses = $this->courses();

		if ( ! $courses ) {
			return '<div class="pgx-empty" id="pgx-grid"><p class="pgx-empty__t">' . esc_html__( 'Courses are being prepared', 'prepgro-theme' ) . '</p>'
				. '<p>' . esc_html__( 'New exam-aligned courses are added regularly. Check back soon.', 'prepgro-theme' ) . '</p></div>';
		}

		$counts = $this->lesson_counts();
		$lock   = Icons::svg( 'lock', array( 'size' => 13, 'stroke' => 2 ) );
		$check  = Icons::svg( 'check', array( 'size' => 14, 'stroke' => 2.4 ) );
		$out    = '<div class="pgx-grid pgc-grid" id="pgx-grid">';

		foreach ( $courses as $course ) {
			$title   = get_the_title( $course );
			$excerpt = get_the_excerpt( $course );
			$n       = isset( $counts[ $course->ID ] ) ? (int) $counts[ $course->ID ] : 0;
			$family  = $this->family_key( $title );
			$gated   = $this->is_gated( $course->ID );

			// Prefer the course's own featured image; fall back to an
			// uploaded default cover (Customizer → Site Images → "Courses —
			// default cover photo" — a fresh pick per card, so several
			// uncovered courses don't show the identical photo); only the
			// family-label tile if neither exists.
			$cover = get_the_post_thumbnail( $course, 'medium_large', array( 'class' => 'pgc-card__img', 'loading' => 'lazy' ) );
			if ( ! $cover ) {
				$default_id = Image_Slots::pick( 'pgt_course_cover_default' );
				if ( $default_id ) {
					$cover = wp_get_attachment_image(
						$default_id,
						'medium_large',
						false,
						array(
							'class'    => 'pgc-card__img',
							'alt'      => '',
							'loading'  => 'lazy',
							'decoding' => 'async',
						)
					);
				}
			}
			if ( ! $cover ) {
				$cover = '<div class="pgc-card__imgslot" aria-hidden="true"><span>' . esc_html( $this->family_label( $family ) ) . '</span></div>';
			}

			$access = $gated
				? '<span class="pgc-card__lock">' . $lock . esc_html__( 'Unlocks with Live Tutor', 'prepgro-theme' ) . '</span>'
				: '<span class="pgx-card__perk">' . $check . esc_html__( 'Included free', 'prepgro-theme' ) . '</span>';

			$out .= '<a class="pgx-card pgc-card" href="' . esc_url( get_permalink( $course ) ) . '"'
				. ' data-pgt-family="' . esc_attr( $family ) . '">'
				. '<div class="pgc-card__cover">' . $cover . '</div>'
				. '<div class="pgc-card__tags">'
				. '<span class="pgx-card__badge">' . esc_html( $this->family_label( $family ) ) . '</span>'
				. ( $n > 0 ? '<span class="pgc-card__lessons">' . esc_html(
					sprintf(
						/* translators: %d: lesson count */
						_n( '%d lesson', '%d lessons', $n, 'prepgro-theme' ),
						$n
					)
				) . '</span>' : '' )
				. '</div>'
				. '<p class="pgx-card__name">' . esc_html( $title ) . '</p>'
				. ( $excerpt ? '<p class="pgx-card__sub">' . esc_html( wp_trim_words( $excerpt, 18 ) ) . '</p>' : '' )
				. '<div class="pgx-card__rule" aria-hidden="true"></div>'
				. '<div class="pgx-card__meta">'
				. '<span class="pgx-card__level">' . esc_html__( 'First lesson free', 'prepgro-theme' ) . '</span>'
				. $access
				. '</div></a>';
		}

		return $out . '</div>';
	}

	/**
	 * Which filter family a course belongs to — same keys as the other two
	 * indexes so theme.js needs no changes.
	 *
	 * @param string $name Course title.
	 * @return string
	 */
	private function family_key( $name ) {
		$n = strtolower( $name );
		if ( preg_match( '/\bap\b|advanced placement/', $n ) ) {
			return 'ap';
		}
		if ( preg_match( '/\b(sat|act|psat)\b/', $n ) ) {
			return 'satact';
		}
		if ( preg_match( '/\b(gre|gmat)\b/', $n ) ) {
			return 'gradschool';
		}
		return 'state';
	}

	/**
	 * Display label for a family chip.
	 *
	 * @param string $key Family key.
	 * @return string
	 */
	private function family_label( $key ) {
		$labels = array(
			'ap'         => __( 'AP', 'prepgro-theme' ),
			'satact'     => __( 'SAT · ACT', 'prepgro-theme' ),
			'gradschool' => __( 'GRE · GMAT', 'prepgro-theme' ),
			'state'      => __( 'State & grade', 'prepgro-theme' ),
		);
		return isset( $labels[ $key ] ) ? $labels[ $key ] : $labels['state'];
	}

	/**
	 * "What's inside": the per-course stat tiles and the two-tier
	 * comparison strip that carries the business rule.
	 *
	 * @return string
	 */
	private function whats_inside() {
		$tiles = array(
			array( 'v' => '20+', 'l' => __( 'video lessons per course', 'prepgro-theme' ) ),
			array( 'v' => '300+', 'l' => __( 'practice questions per course', 'prepgro-theme' ) ),
			array( 'v' => '12', 'l' => __( 'skill quizzes per course', 'prepgro-theme' ) ),
			array( 'v' => '✓', 'l' => __( 'progress tracked per skill', 'prepgro-theme' ) ),
		);

		$tilerow = '';
		foreach ( $tiles as $t ) {
			$tilerow .= '<div class="pgc-stat"><b>' . esc_html( $t['v'] ) . '</b><span>' . esc_html( $t['l'] ) . '</span></div>';
		}

		$tiers = '<div class="pgc-tier">'
			. '<h3>' . esc_html__( 'Test pack', 'prepgro-theme' ) . '</h3>'
			. '<p>' . esc_html__( 'Unlimited practice tests and question banks. Course previews only.', 'prepgro-theme' ) . '</p>'
			. '</div>'
			. '<div class="pgc-tier pgc-tier--featured">'
			. '<h3>' . esc_html__( 'Live Tutor package', 'prepgro-theme' ) . '</h3>'
			. '<p>' . esc_html__( 'Everything in a test pack, plus every course for that exam, unlocked in full.', 'prepgro-theme' ) . '</p>'
			. '</div>'
			. '<div class="pgc-tier pgc-tier--facts">'
			. '<div><span class="pgc-tier__k">' . esc_html__( 'Full access requires', 'prepgro-theme' ) . '</span>'
			. '<span class="pgc-tier__v">' . esc_html__( 'Live Tutor package', 'prepgro-theme' ) . '</span></div>'
			. '<div><span class="pgc-tier__k">' . esc_html__( 'Pace', 'prepgro-theme' ) . '</span>'
			. '<span class="pgc-tier__v">' . esc_html__( 'Fully self-paced', 'prepgro-theme' ) . '</span></div>'
			. '</div>';

		return '<section class="pgx-coverage pgc-inside">'
			. '<div class="pgx-coverage__head">'
			. '<div><span class="pgx-kicker pgx-kicker--accent">' . esc_html__( 'What’s inside', 'prepgro-theme' ) . '</span>'
			. '<h2 class="pgx-coverage__h">' . esc_html__( 'Every course, the same way', 'prepgro-theme' ) . '</h2></div>'
			. Media::sample_badge()
			. '</div>'
			. '<div class="pgc-stats">' . $tilerow . '</div>'
			. '<div class="pgc-tiers">' . $tiers . '</div>'
			. '</section>';
	}

	/**
	 * FAQ copy. The tier gate leads (AEO: answers open with the conclusion).
	 *
	 * @return array<int,array{q:string,a:string}>
	 */
	private function faq_items() {
		return array(
			array(
				'q' => __( 'Are courses included with a test pack?', 'prepgro-theme' ),
				'a' => __( 'The first lesson of each course is free to preview. The full course unlocks only for students on that exam’s Live Tutor package — a plain test pack covers unlimited practice, not courses.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'Can I take a course without a Live Tutor package?', 'prepgro-theme' ),
				'a' => __( 'You can preview the first lesson of any course free. To go further, you’ll need the Live Tutor package for that exam, which includes the full course alongside your live sessions.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'Do courses expire?', 'prepgro-theme' ),
				'a' => __( 'No. As long as your Live Tutor package is active, every course for that exam stays available, including your progress.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'Are courses self-paced?', 'prepgro-theme' ),
				'a' => __( 'Yes, fully. Lessons are short and ordered, but you choose when to watch and when to practice — your tutor can see progress and adjust sessions around it.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'Is there a certificate?', 'prepgro-theme' ),
				'a' => __( 'The goal is a score, not a certificate: each course ends in a full practice test that shows exactly where you stand.', 'prepgro-theme' ),
			),
		);
	}

	/**
	 * FAQ accordion (data-pgt-faq mechanics from theme.js, ids prefixed
	 * pgc-).
	 *
	 * @return string
	 */
	private function faq() {
		$chev = Icons::svg( 'chevron-down', array( 'size' => 16, 'stroke' => 2.2 ) );
		$rows = '';
		foreach ( $this->faq_items() as $i => $item ) {
			$open = 0 === $i;
			$id   = 'pgc-faq-' . $i;
			$rows .= '<div class="pgx-faq__item">'
				. '<h3 class="pgx-faq__q"><button type="button" class="pgx-faq__btn" data-pgt-faq aria-expanded="' . ( $open ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $id ) . '">'
				. '<span>' . esc_html( $item['q'] ) . '</span>'
				. '<span class="pgx-faq__chev" aria-hidden="true">' . $chev . '</span>'
				. '</button></h3>'
				. '<div class="pgx-faq__a" id="' . esc_attr( $id ) . '"' . ( $open ? '' : ' hidden' ) . '><p>' . esc_html( $item['a'] ) . '</p></div>'
				. '</div>';
		}

		return '<section class="pgx-faq">'
			. '<span class="pgx-kicker pgx-kicker--accent">' . esc_html__( 'Common questions', 'prepgro-theme' ) . '</span>'
			. '<h2 class="pgx-faq__h">' . esc_html__( 'About courses', 'prepgro-theme' ) . '</h2>'
			. '<div class="pgx-faq__list">' . $rows . '</div>'
			. '</section>';
	}

	/**
	 * Bottom CTA band in the Elevate accent.
	 *
	 * @return string
	 */
	private function cta_band() {
		return '<section class="pgx-ctaband">'
			. '<div><h2>' . esc_html__( 'Courses unlock with Live Tutor.', 'prepgro-theme' ) . '</h2>'
			. '<p>' . esc_html__( 'Pick your exam to see what’s included at each tier.', 'prepgro-theme' ) . '</p></div>'
			. '<a class="pgx-btn pgx-btn--onaccent" href="' . esc_url( home_url( '/pricing/' ) ) . '">' . esc_html__( 'Compare packages', 'prepgro-theme' ) . '</a>'
			. '</section>';
	}

	/**
	 * BreadcrumbList + ItemList + FAQPage JSON-LD from the rendered data.
	 *
	 * @return string
	 */
	private function schema_jsonld() {
		$graph = array(
			array(
				'@type'           => 'BreadcrumbList',
				'itemListElement' => array(
					array(
						'@type'    => 'ListItem',
						'position' => 1,
						'name'     => __( 'Home', 'prepgro-theme' ),
						'item'     => home_url( '/' ),
					),
					array(
						'@type'    => 'ListItem',
						'position' => 2,
						'name'     => __( 'Courses', 'prepgro-theme' ),
						'item'     => home_url( '/courses/' ),
					),
				),
			),
		);

		$items = array();
		foreach ( $this->courses() as $i => $course ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => get_the_title( $course ),
				'url'      => get_permalink( $course ),
			);
		}
		if ( $items ) {
			$graph[] = array(
				'@type'           => 'ItemList',
				'name'            => __( 'Courses', 'prepgro-theme' ),
				'itemListElement' => $items,
			);
		}

		$faq = array();
		foreach ( $this->faq_items() as $item ) {
			$faq[] = array(
				'@type'          => 'Question',
				'name'           => $item['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $item['a'],
				),
			);
		}
		$graph[] = array(
			'@type'      => 'FAQPage',
			'mainEntity' => $faq,
		);

		return '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			)
		) . '</script>';
	}
}
