<?php
/**
 * Diagnostics index — /diagnostic-tests/ (public-listing-pages redesign).
 *
 * The Evaluate pillar's public catalogue of free readiness checks. The
 * engine's [pge_all_diagnostics] page still exists as the data owner's
 * fallback; when this theme is active the page routes through
 * templates/page-diagnostics.html and renders [pgt_diagnostics] instead,
 * the same hijack Exams_Page performs for /practice-tests/.
 *
 * Card data comes from the engine's Package_Bundles::list_public_diagnostics()
 * — the servable-question-count gate lives THERE (published is not a proxy
 * for sittable), so this class never re-derives readiness.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Diagnostics index.
 */
final class Diagnostics_Page {

	/** @var Diagnostics_Page|null */
	private static $instance = null;

	/** @var array<int,array<string,mixed>>|null Memoized catalogue rows. */
	private $rows = null;

	/** @return Diagnostics_Page */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/** @return void */
	public function init() {
		add_shortcode( 'pgt_diagnostics', array( $this, 'render' ) );
		add_filter( 'page_template_hierarchy', array( $this, 'route_template' ), 5 );
	}

	/**
	 * Slugs this template owns. `all-diagnostics` stays claimed so a site
	 * that hasn't run the engine's slug migration still gets the redesign.
	 *
	 * @return string[]
	 */
	public function slugs() {
		/**
		 * Filter which page slugs render the redesigned diagnostics index.
		 *
		 * @param array $slugs Page slugs.
		 */
		return (array) apply_filters( 'pgt_diagnostics_page_slugs', array( 'diagnostic-tests', 'all-diagnostics' ) );
	}

	/**
	 * @param array $templates Template hierarchy.
	 * @return array
	 */
	public function route_template( $templates ) {
		$post = get_post();
		if ( ! $post || 'page' !== $post->post_type || get_page_template_slug( $post ) ) {
			return $templates;
		}
		if ( ! in_array( $post->post_name, $this->slugs(), true ) ) {
			return $templates;
		}
		array_unshift( $templates, 'page-diagnostics.php' );
		return $templates;
	}

	/** @return bool */
	public function is_diagnostics_page() {
		$post = get_post();
		return $post && in_array( $post->post_name, $this->slugs(), true );
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Rendering.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * Render the diagnostics index.
	 *
	 * @return string
	 */
	public function render() {
		return '<div class="pgx pgx--evaluate pgd">'
			. $this->breadcrumb()
			. $this->header()
			. '<section class="pgx-body">'
			. $this->filters()
			. $this->cards()
			. $this->steps()
			. $this->sample_report()
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
			. '<span class="pgx-crumbs__here" aria-current="page">' . esc_html__( 'Diagnostic tests', 'prepgro-theme' ) . '</span>'
			. '</nav>';
	}

	/**
	 * Hero: copy column + the readiness-donut sample card (Evaluate's
	 * signature infographic — illustrative, so the Sample badge stays).
	 *
	 * @return string
	 */
	private function header() {
		$copy = '<div class="pgx-head__copy">'
			. '<span class="pgx-chip"><span class="pgx-chip__dot" aria-hidden="true"></span>' . esc_html__( 'Evaluate · Free diagnostic', 'prepgro-theme' ) . '</span>'
			. '<h1 class="pgx-h1">' . esc_html__( 'Find out exactly what to fix — in about 20 minutes.', 'prepgro-theme' ) . '</h1>'
			. '<p class="pgx-lede">' . esc_html__( 'Pick your exam. Answer an adaptive set of questions. Get a same-day report that names your weakest sub-skills in priority order — no card required.', 'prepgro-theme' ) . '</p>'
			. '<div class="pgx-ctas">'
			. '<a class="pgx-btn pgx-btn--accent" href="#pgx-grid">' . esc_html__( 'Start your free diagnostic', 'prepgro-theme' ) . '</a>'
			. '<a class="pgx-btn pgx-btn--ghost" href="' . esc_url( home_url( '/sample-report/' ) ) . '">' . esc_html__( 'See a sample report', 'prepgro-theme' ) . '</a>'
			. '</div></div>';

		return '<section class="pgx-head">'
			. '<div class="pgx-head__grid">' . $copy . $this->donut_card() . '</div>'
			. '</section>';
	}

	/**
	 * The dark readiness-donut card. 68% of the 100.5-circumference ring.
	 *
	 * @return string
	 */
	private function donut_card() {
		return '<div class="pgx-trend pgd-donutcard" role="img" aria-label="' . esc_attr__( 'Sample chart: a readiness ring at 68 percent', 'prepgro-theme' ) . '">'
			. '<div class="pgd-donutcard__row">'
			. '<div class="pgd-donut">'
			. '<svg viewBox="0 0 42 42" width="110" height="110" aria-hidden="true" focusable="false">'
			. '<circle cx="21" cy="21" r="16" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="5"/>'
			. '<circle cx="21" cy="21" r="16" fill="none" stroke="var(--blue-400)" stroke-width="5" stroke-linecap="round" stroke-dasharray="68.4 32.1" transform="rotate(-90 21 21)"/>'
			. '</svg>'
			. '<div class="pgd-donut__label"><b>68%</b><span>' . esc_html__( 'readiness', 'prepgro-theme' ) . '</span></div>'
			. '</div>'
			. '<div class="pgd-donutcard__copy">'
			. '<span class="pgx-trend__sample">' . esc_html__( 'Sample', 'prepgro-theme' ) . '</span>'
			. '<p>' . esc_html__( '4 sub-skills named, ranked by priority, before your first practice set.', 'prepgro-theme' ) . '</p>'
			. '</div></div></div>';
	}

	/**
	 * Filter chips over the same client-side mechanism the exams index uses
	 * (data-pgt-filter / data-pgt-family, wired in theme.js).
	 *
	 * @return string
	 */
	private function filters() {
		$chips = array(
			'all'        => __( 'All diagnostics', 'prepgro-theme' ),
			'satact'     => __( 'SAT · ACT diagnostic', 'prepgro-theme' ),
			'ap'         => __( 'AP diagnostic', 'prepgro-theme' ),
			'state'      => __( 'State-test diagnostic', 'prepgro-theme' ),
			'gradschool' => __( 'GRE · GMAT diagnostic', 'prepgro-theme' ),
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
	 * Catalogue rows from the engine. Empty array when the engine is absent.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function rows() {
		if ( null !== $this->rows ) {
			return $this->rows;
		}
		$this->rows = class_exists( '\\PrepGro\\Engine\\Core\\Package_Bundles' )
			? (array) \PrepGro\Engine\Core\Package_Bundles::list_public_diagnostics()
			: array();
		return $this->rows;
	}

	/**
	 * Diagnostic cards. Every row here is already servable — the engine's
	 * catalogue query excludes zero-question diagnostics.
	 *
	 * @return string
	 */
	private function cards() {
		$rows = $this->rows();

		if ( ! $rows ) {
			return '<div class="pgx-empty" id="pgx-grid"><p class="pgx-empty__t">' . esc_html__( 'No diagnostics available yet', 'prepgro-theme' ) . '</p>'
				. '<p>' . esc_html__( 'Check back soon — new readiness checks are added regularly.', 'prepgro-theme' ) . '</p></div>';
		}

		$icon  = Icons::svg( 'clipboard-list', array( 'size' => 18, 'stroke' => 1.8 ) );
		$check = Icons::svg( 'check', array( 'size' => 14, 'stroke' => 2.4 ) );
		$out   = '<div class="pgx-grid" id="pgx-grid">';

		foreach ( $rows as $d ) {
			$sub = implode( ' · ', array_filter( array( (string) $d['grade'], (string) $d['subject'] ) ) );
			$min = (int) $d['estimated_minutes'];

			$out .= '<a class="pgx-card" href="' . esc_url( $d['permalink'] ) . '"'
				. ' data-pgt-family="' . esc_attr( $this->family_key( (string) $d['title'] ) ) . '">'
				. '<div class="pgx-card__top">'
				. '<div class="pgx-card__id">'
				. '<span class="pgx-card__icon" aria-hidden="true">' . $icon . '</span>'
				. '<div><p class="pgx-card__name">' . esc_html( $d['title'] ) . '</p>'
				. ( $sub ? '<p class="pgx-card__sub">' . esc_html( $sub ) . '</p>' : '' )
				. '</div></div>'
				. '<span class="pgx-card__badge">' . esc_html__( 'Free', 'prepgro-theme' ) . '</span>'
				. '</div>'
				. '<div class="pgx-card__rule" aria-hidden="true"></div>'
				. '<div class="pgx-card__meta">'
				. '<span class="pgx-card__level">' . esc_html(
					sprintf(
						/* translators: %d: estimated minutes */
						__( '~%d min · Adaptive', 'prepgro-theme' ),
						$min
					)
				) . '</span>'
				. '<span class="pgx-card__perk">' . $check . esc_html__( 'No card required', 'prepgro-theme' ) . '</span>'
				. '</div></a>';
		}

		return $out . '</div>';
	}

	/**
	 * Which filter family a diagnostic belongs to. Same keys as the exams
	 * index so theme.js needs no changes.
	 *
	 * @param string $name Diagnostic title.
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
	 * The 4-step "how it works" strip.
	 *
	 * @return string
	 */
	private function steps() {
		$steps = array(
			array( 'n' => '01', 't' => __( 'Answer adaptive questions', 'prepgro-theme' ), 'b' => __( 'Difficulty adjusts as you go, so the test finds your level fast.', 'prepgro-theme' ) ),
			array( 'n' => '02', 't' => __( 'We map every sub-skill', 'prepgro-theme' ), 'b' => __( 'Not just a score — which specific skills are solid and which aren’t.', 'prepgro-theme' ) ),
			array( 'n' => '03', 't' => __( 'Get your report, same day', 'prepgro-theme' ), 'b' => __( 'A plain-English readiness report, delivered the day you finish.', 'prepgro-theme' ) ),
			array( 'n' => '04', 't' => __( 'Start on your 4 weakest skills', 'prepgro-theme' ), 'b' => __( 'Your report ranks gaps in priority order, so you know what to fix first.', 'prepgro-theme' ) ),
		);

		$cards = '';
		foreach ( $steps as $s ) {
			$cards .= '<div class="pgx-step">'
				. '<span class="pgx-step__n">' . esc_html( $s['n'] ) . '</span>'
				. '<h3 class="pgx-step__t">' . esc_html( $s['t'] ) . '</h3>'
				. '<p class="pgx-step__b">' . esc_html( $s['b'] ) . '</p>'
				. '</div>';
		}

		return '<section class="pgx-steps">'
			. '<span class="pgx-kicker pgx-kicker--accent">' . esc_html__( 'How it works', 'prepgro-theme' ) . '</span>'
			. '<div class="pgx-steps__grid pgx-steps__grid--four">' . $cards . '</div>'
			. '</section>';
	}

	/**
	 * "What you get": the sample readiness report (donut + skill bars) and
	 * the $0 / same-day tiles. Entirely illustrative — Sample badge stays.
	 *
	 * @return string
	 */
	private function sample_report() {
		$bars = array(
			array( 'l' => __( 'Algebra', 'prepgro-theme' ), 'v' => 82, 'c' => 'var(--blue-600)' ),
			array( 'l' => __( 'Geometry', 'prepgro-theme' ), 'v' => 61, 'c' => 'var(--blue-500)' ),
			array( 'l' => __( 'Reading craft', 'prepgro-theme' ), 'v' => 74, 'c' => 'var(--blue-500)' ),
			array( 'l' => __( 'Grammar mechanics', 'prepgro-theme' ), 'v' => 58, 'c' => 'var(--blue-400)' ),
		);

		$rows = '';
		foreach ( $bars as $b ) {
			$rows .= '<div class="pgx-bar">'
				. '<div class="pgx-bar__head"><span>' . esc_html( $b['l'] ) . '</span>'
				. '<span class="pgx-bar__v">' . esc_html( $b['v'] ) . '%</span></div>'
				. '<div class="pgx-bar__track"><span style="width:' . esc_attr( $b['v'] ) . '%;background:' . esc_attr( $b['c'] ) . '"></span></div>'
				. '</div>';
		}

		$tiles = '<div class="pgx-tile"><span class="pgx-tile__copy">'
			. '<span class="pgx-tile__l">' . esc_html__( 'To take any diagnostic', 'prepgro-theme' ) . '</span>'
			. '<span class="pgx-tile__s">' . esc_html__( 'No card required to start or to see your report', 'prepgro-theme' ) . '</span></span>'
			. '<span class="pgx-tile__v">' . esc_html( Pricing_Levels::money( 0 ) ) . '</span></div>'
			. '<div class="pgx-tile"><span class="pgx-tile__copy">'
			. '<span class="pgx-tile__l">' . esc_html__( 'Report delivery', 'prepgro-theme' ) . '</span>'
			. '<span class="pgx-tile__s">' . esc_html__( 'Delivered the day you finish', 'prepgro-theme' ) . '</span></span>'
			. '<span class="pgx-tile__v">' . esc_html__( 'Same day', 'prepgro-theme' ) . '</span></div>';

		return '<section class="pgx-coverage pgd-report">'
			. '<div class="pgx-coverage__head">'
			. '<div><span class="pgx-kicker pgx-kicker--accent">' . esc_html__( 'What you get', 'prepgro-theme' ) . '</span>'
			. '<h2 class="pgx-coverage__h">' . esc_html__( 'A sample readiness report', 'prepgro-theme' ) . '</h2></div>'
			. '<p class="pgx-coverage__asof">' . esc_html__( 'Sample report, not a live result', 'prepgro-theme' ) . '</p>'
			. '</div>'
			. '<div class="pgx-coverage__cols">'
			. '<div class="pgx-bars">' . $rows . '</div>'
			. '<div class="pgx-tiles">' . $tiles . Media::sample_badge() . '</div>'
			. '</div></section>';
	}

	/**
	 * FAQ copy. Answers lead with their conclusion (AEO).
	 *
	 * @return array<int,array{q:string,a:string}>
	 */
	private function faq_items() {
		return array(
			array(
				'q' => __( 'Is the diagnostic really free?', 'prepgro-theme' ),
				'a' => __( 'Yes, for every exam. No card is required to start or to see your report.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'How long does it take?', 'prepgro-theme' ),
				'a' => __( 'About 20 minutes. It’s adaptive, so it stops once your level is clear rather than running a fixed, longer test.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'How is a diagnostic different from a practice test?', 'prepgro-theme' ),
				'a' => __( 'A diagnostic places you — it’s short and adaptive. A practice test is a full-length or skill-specific set you use afterward, unlimited times, to build on that placement.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'Do I need an account?', 'prepgro-theme' ),
				'a' => __( 'You can start without one. You’ll create a free account to save and view your report.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'What do I get afterward?', 'prepgro-theme' ),
				'a' => __( 'A readiness report ranking your weakest sub-skills in priority order, plus a suggested starting point for practice or tutoring.', 'prepgro-theme' ),
			),
		);
	}

	/**
	 * FAQ accordion, same disclosure mechanics as the exams index
	 * (data-pgt-faq in theme.js). IDs are prefixed pgd- so the two pages
	 * could never collide even if both rendered.
	 *
	 * @return string
	 */
	private function faq() {
		$chev = Icons::svg( 'chevron-down', array( 'size' => 16, 'stroke' => 2.2 ) );
		$rows = '';
		foreach ( $this->faq_items() as $i => $item ) {
			$open = 0 === $i;
			$id   = 'pgd-faq-' . $i;
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
			. '<h2 class="pgx-faq__h">' . esc_html__( 'About the diagnostic', 'prepgro-theme' ) . '</h2>'
			. '<div class="pgx-faq__list">' . $rows . '</div>'
			. '</section>';
	}

	/**
	 * Bottom CTA band in the Evaluate accent.
	 *
	 * @return string
	 */
	private function cta_band() {
		return '<section class="pgx-ctaband" id="pgx-ctaband">'
			. '<div><h2>' . esc_html__( 'Free. No card needed.', 'prepgro-theme' ) . '</h2>'
			. '<p>' . esc_html__( '20 minutes now saves weeks of practicing the wrong thing.', 'prepgro-theme' ) . '</p></div>'
			. '<a class="pgx-btn pgx-btn--onaccent" href="#pgx-grid">' . esc_html__( 'Start your free diagnostic', 'prepgro-theme' ) . '</a>'
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
						'name'     => __( 'Diagnostic tests', 'prepgro-theme' ),
						'item'     => home_url( '/diagnostic-tests/' ),
					),
				),
			),
		);

		$items = array();
		foreach ( $this->rows() as $i => $d ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => (string) $d['title'],
				'url'      => (string) $d['permalink'],
			);
		}
		if ( $items ) {
			$graph[] = array(
				'@type'           => 'ItemList',
				'name'            => __( 'Diagnostic tests', 'prepgro-theme' ),
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
