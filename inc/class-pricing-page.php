<?php
/**
 * Pricing page (README §7 + addendum A4).
 *
 * Header → level selector → plan pair → cost-by-level chart → readiness
 * band → small print. Every figure on the page — the two plan cards AND the
 * chart bars — comes from Pricing_Levels, so a SKU price change moves both.
 * Nothing is hard-coded as copy.
 *
 * The level selector is deep-linkable (`?level=ap`) so exam pages can route
 * a buyer straight to their own level and never show all sixteen SKUs. All
 * four levels are rendered server-side and the tabs swap which one is
 * visible, so the chart re-renders on tab change with no request and the
 * page still works with JS off (the `?level=` request decides).
 *
 * ── Conflict note ────────────────────────────────────────────────────
 * /pricing/ is an engine page ([pge_pricing]) with its own tier cards and
 * buy buttons. This template takes the URL over to deliver §7's layout;
 * the plan CTAs still buy the REAL product via the engine's own
 * add-to-cart route (Pricing_Levels::buy_url()). Filter
 * `pgt_pricing_page_slugs` to an empty array to hand the URL back.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the level-scoped pricing page.
 */
final class Pricing_Page {

	/** @var Pricing_Page|null */
	private static $instance = null;

	/** @return Pricing_Page */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/** @return void */
	public function init() {
		add_shortcode( 'pgt_pricing', array( $this, 'render' ) );
		add_filter( 'page_template_hierarchy', array( $this, 'route_template' ), 5 );
	}

	/**
	 * Slugs this template owns.
	 *
	 * @return string[]
	 */
	public function slugs() {
		/**
		 * Filter which page slugs render the redesigned pricing page.
		 *
		 * @param array $slugs Page slugs.
		 */
		return (array) apply_filters( 'pgt_pricing_page_slugs', array( 'pricing' ) );
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
		array_unshift( $templates, 'page-pricing.php' );
		return $templates;
	}

	/** @return bool */
	public function is_pricing_page() {
		$post = get_post();
		return $post && in_array( $post->post_name, $this->slugs(), true );
	}

	/* ─────────────────────────────────────────────────────────────────── */

	/**
	 * Render the whole page.
	 *
	 * @return string
	 */
	public function render() {
		$levels  = Pricing_Levels::levels();
		$current = Pricing_Levels::current();

		return '<div class="pgp" data-pgt-pricing data-level="' . esc_attr( $current ) . '">'
			. $this->header( $levels, $current )
			. '<section class="pgp-body">'
			. $this->plans( $levels, $current )
			. $this->chart( $levels, $current )
			. $this->readiness_band()
			. $this->small_print()
			. '</section>'
			. '</div>';
	}

	/**
	 * Header + level selector.
	 *
	 * @param array  $levels  Levels.
	 * @param string $current Active level.
	 * @return string
	 */
	private function header( $levels, $current ) {
		$tabs = '';
		foreach ( $levels as $key => $l ) {
			$on    = $key === $current;
			$tabs .= '<a class="pgp-tab' . ( $on ? ' is-on' : '' ) . '" role="tab"'
				. ' aria-selected="' . ( $on ? 'true' : 'false' ) . '"'
				. ' href="' . esc_url( Pricing_Levels::url( $key ) ) . '"'
				. ' data-pgt-level="' . esc_attr( $key ) . '">' . esc_html( $l['name'] ) . '</a>';
		}

		$names = '';
		foreach ( $levels as $key => $l ) {
			$names .= '<b class="pgp-levelname" data-pgt-levelname="' . esc_attr( $key ) . '"'
				. ( $key === $current ? '' : ' hidden' ) . '>' . esc_html( $l['name'] ) . '</b>';
		}

		return '<section class="pgp-head">'
			. '<div class="pgp-head__copy">'
			. '<span class="pgp-chip">' . esc_html__( 'Pricing', 'prepgro-theme' ) . '</span>'
			. '<h1 class="pgp-h1">' . esc_html__( 'Practise more.', 'prepgro-theme' )
			. '<br><span class="pgp-h1__quiet">' . esc_html__( 'Feel ready.', 'prepgro-theme' ) . '</span></h1>'
			. '<p class="pgp-lede">' . esc_html__( 'Choose the support that fits your child: independent unlimited test practice, or live 1:1 tutoring with practice included.', 'prepgro-theme' ) . '</p>'
			. '</div>'
			. '<div class="pgp-levels">'
			. '<span class="pgp-levels__label">' . esc_html__( 'Level', 'prepgro-theme' ) . '</span>'
			. '<div class="pgp-tabs" role="tablist" aria-label="' . esc_attr__( 'Pricing level', 'prepgro-theme' ) . '">' . $tabs . '</div>'
			. '</div>'
			. '<p class="pgp-showing">'
			. esc_html__( 'Showing prices for', 'prepgro-theme' ) . ' ' . $names . ' '
			. esc_html__( '· one subject per subscription', 'prepgro-theme' )
			. '</p>'
			. '</section>';
	}

	/**
	 * The plan pair. All four levels render; the tabs toggle visibility.
	 *
	 * @param array  $levels  Levels.
	 * @param string $current Active level.
	 * @return string
	 */
	private function plans( $levels, $current ) {
		$pack_includes = array(
			__( 'Unlimited mock practice tests for one selected subject', 'prepgro-theme' ),
			__( 'Timed and untimed practice', 'prepgro-theme' ),
			__( 'Answer explanations and review', 'prepgro-theme' ),
			__( 'Skill-level progress tracking', 'prepgro-theme' ),
			__( 'Personalised next-step recommendations', 'prepgro-theme' ),
			__( 'Access while your subscription is active', 'prepgro-theme' ),
		);
		$tutor_includes = array(
			__( '8 live 1:1 classes per month', 'prepgro-theme' ),
			__( 'Unlimited Test Pack for the enrolled subject', 'prepgro-theme' ),
			__( 'A personalised learning plan', 'prepgro-theme' ),
			__( 'Tutor-led support on difficult topics', 'prepgro-theme' ),
			__( 'Parent and student progress tracking', 'prepgro-theme' ),
			__( 'Practice access for as long as the plan is active', 'prepgro-theme' ),
		);

		$tick = Icons::svg( 'circle-check-big', array( 'size' => 14, 'stroke' => 2.4 ) );

		$list = function ( $items ) use ( $tick ) {
			$out = '';
			foreach ( $items as $i ) {
				$out .= '<li>' . $tick . '<span>' . esc_html( $i ) . '</span></li>';
			}
			return $out;
		};

		$out = '<div class="pgp-plans">';

		// ── Left: Unlimited Test Pack (quiet) ──
		$prices = '';
		foreach ( $levels as $key => $l ) {
			$prices .= '<div class="pgp-priceset" data-pgt-price="' . esc_attr( $key ) . '"' . ( $key === $current ? '' : ' hidden' ) . '>'
				. '<div class="pgp-figure"><span class="pgp-figure__n">' . esc_html( Pricing_Levels::money( $l['pack']['monthly'] ) ) . '</span>'
				. '<span class="pgp-figure__u">' . esc_html__( '/month', 'prepgro-theme' ) . '</span></div>'
				. '<div class="pgp-terms">'
				. '<div class="pgp-term"><p class="pgp-term__l">' . esc_html__( 'Quarterly', 'prepgro-theme' ) . '</p>'
				. '<p class="pgp-term__v">' . esc_html( Pricing_Levels::money( $l['pack']['quarterly'] ) ) . '</p></div>'
				. '<div class="pgp-term"><p class="pgp-term__l">' . esc_html__( 'Annual', 'prepgro-theme' ) . '</p>'
				. '<p class="pgp-term__v">' . esc_html( Pricing_Levels::money( $l['pack']['annual'] ) ) . '</p></div>'
				. '</div>'
				. '<a class="pgp-cta pgp-cta--primary" href="' . esc_url( Pricing_Levels::buy_url( $key, 'monthly' ) ) . '">'
				. esc_html__( 'Start unlimited practice', 'prepgro-theme' ) . '</a>'
				. '</div>';
		}

		$out .= '<div class="pgp-card pgp-card--quiet">'
			. '<span class="pgp-tag">' . esc_html__( 'Unlimited test pack', 'prepgro-theme' ) . '</span>'
			. '<h2 class="pgp-card__h">' . esc_html__( 'Unlimited practice for one subject', 'prepgro-theme' ) . '</h2>'
			. '<p class="pgp-card__b">' . esc_html__( 'Take practice tests as often as you need, review every answer, and see which skills to improve next.', 'prepgro-theme' ) . '</p>'
			. '<div class="pgp-priceblock">' . $prices . '</div>'
			. '<ul class="pgp-includes">' . $list( $pack_includes ) . '</ul>'
			. '<p class="pgp-note">' . esc_html__( 'One subject is included in each subscription. Add another subject whenever you need it.', 'prepgro-theme' ) . '</p>'
			. '</div>';

		// ── Right: Live Tutor + Test Pack (featured) ──
		$tprices = '';
		foreach ( $levels as $key => $l ) {
			$tprices .= '<div class="pgp-priceset" data-pgt-price="' . esc_attr( $key ) . '"' . ( $key === $current ? '' : ' hidden' ) . '>'
				. '<div class="pgp-figure pgp-figure--lg"><span class="pgp-figure__n">' . esc_html( Pricing_Levels::money( $l['tutor'] ) ) . '</span>'
				. '<span class="pgp-figure__u">' . esc_html__( '/month', 'prepgro-theme' ) . '</span></div>'
				. '<p class="pgp-subline">' . esc_html(
					sprintf(
						/* translators: %s: level name */
						__( '8 live 1:1 classes each month · %s', 'prepgro-theme' ),
						$l['name']
					)
				) . '</p>'
				. '<a class="pgp-cta pgp-cta--ink" href="' . esc_url( Pricing_Levels::buy_url( $key, 'tutor' ) ) . '">'
				. esc_html__( 'Find a tutor', 'prepgro-theme' ) . '</a>'
				. '</div>';
		}

		$out .= '<div class="pgp-card pgp-card--featured">'
			. '<span class="pgp-badge" aria-hidden="true">' . Icons::svg( 'zap', array( 'size' => 23, 'filled' => true, 'color' => 'var(--blue-600)' ) ) . '</span>'
			. '<span class="pgp-tag pgp-tag--onblue">' . esc_html__( 'Live tutor + test pack', 'prepgro-theme' ) . '</span>'
			. '<h2 class="pgp-card__h pgp-card__h--onblue">' . esc_html__( 'Personal 1:1 support, every month', 'prepgro-theme' ) . '</h2>'
			. '<p class="pgp-card__b pgp-card__b--onblue">' . esc_html__( 'Get live help from a tutor while continuing to practise independently between classes.', 'prepgro-theme' ) . '</p>'
			. '<div class="pgp-priceblock pgp-priceblock--onblue">' . $tprices . '</div>'
			. '<ul class="pgp-includes pgp-includes--onblue">' . $list( $tutor_includes ) . '</ul>'
			. '<p class="pgp-note pgp-note--onblue">' . esc_html__( 'One subject is included in each Live Tutor plan. Need another subject? Add a second subject plan.', 'prepgro-theme' ) . '</p>'
			. '</div>';

		return $out . '</div>';
	}

	/**
	 * A4 — cost-by-level chart. Bar width is price / CHART_MAX, read from the
	 * same Pricing_Levels source as the cards above, so the two cannot drift.
	 *
	 * @param array  $levels  Levels.
	 * @param string $current Active level.
	 * @return string
	 */
	private function chart( $levels, $current ) {
		$rows = '';
		foreach ( $levels as $key => $l ) {
			$pack  = (float) $l['pack']['monthly'];
			$tutor = (float) $l['tutor'];
			$pw    = max( 1, min( 100, round( $pack / Pricing_Levels::CHART_MAX * 100 ) ) );
			$tw    = max( 1, min( 100, round( $tutor / Pricing_Levels::CHART_MAX * 100 ) ) );

			$rows .= '<div class="pgp-chartrow' . ( $key === $current ? ' is-on' : '' ) . '" data-pgt-chartrow="' . esc_attr( $key ) . '">'
				. '<span class="pgp-chartrow__l">' . esc_html( $l['name'] ) . '</span>'
				. '<div class="pgp-chartrow__bars">'
				. '<div class="pgp-barline"><span class="pgp-bar pgp-bar--pack" style="width:' . esc_attr( $pw ) . '%"></span>'
				. '<span class="pgp-barval">' . esc_html( Pricing_Levels::money( $pack ) ) . esc_html__( '/mo', 'prepgro-theme' ) . '</span></div>'
				. '<div class="pgp-barline"><span class="pgp-bar pgp-bar--tutor" style="width:' . esc_attr( $tw ) . '%"></span>'
				. '<span class="pgp-barval pgp-barval--strong">' . esc_html( Pricing_Levels::money( $tutor ) ) . esc_html__( '/mo', 'prepgro-theme' ) . '</span></div>'
				. '</div></div>';
		}

		// The per-class figure is derived, never typed: the high-school tutor
		// price divided by the eight included classes.
		$high      = isset( $levels['high'] ) ? (float) $levels['high']['tutor'] : 0;
		$per_class = $high > 0 ? Pricing_Levels::money( round( $high / 8 ) ) : '—';

		$facts = array(
			array( 'v' => '8', 'l' => __( 'live 1:1 classes included every month on a tutor plan', 'prepgro-theme' ) ),
			array(
				'v' => $per_class,
				'l' => __( 'average cost per live class at the high-school level', 'prepgro-theme' ),
			),
			array( 'v' => Pricing_Levels::money( 0 ), 'l' => __( 'to take the readiness check and read the report', 'prepgro-theme' ) ),
		);

		$factrows = '';
		foreach ( $facts as $f ) {
			$factrows .= '<div class="pgp-fact"><p class="pgp-fact__v">' . esc_html( $f['v'] ) . '</p>'
				. '<p class="pgp-fact__l">' . esc_html( $f['l'] ) . '</p></div>';
		}

		return '<section class="pgp-chart">'
			. '<div class="pgp-chart__head">'
			. '<div>'
			. '<span class="pgp-kicker">' . esc_html__( 'Compare', 'prepgro-theme' ) . '</span>'
			. '<h2 class="pgp-chart__h">' . esc_html__( 'Monthly cost by level', 'prepgro-theme' ) . '</h2>'
			. '</div>'
			. '<div class="pgp-legend">'
			. '<span class="pgp-legend__i"><i class="pgp-sw pgp-sw--pack"></i>' . esc_html__( 'Unlimited test pack', 'prepgro-theme' ) . '</span>'
			. '<span class="pgp-legend__i"><i class="pgp-sw pgp-sw--tutor"></i>' . esc_html__( 'Live tutor plan', 'prepgro-theme' ) . '</span>'
			. '</div>'
			. '</div>'
			. '<div class="pgp-chartrows">' . $rows . '</div>'
			. '<div class="pgp-facts">' . $factrows . Media::sample_badge() . '</div>'
			. '</section>';
	}

	/**
	 * Readiness-check band.
	 *
	 * @return string
	 */
	private function readiness_band() {
		return '<section class="pgp-band">'
			. '<div class="pgp-band__copy">'
			. '<p class="pgp-band__eyebrow">' . esc_html__( 'Not sure where to start?', 'prepgro-theme' ) . '</p>'
			. '<h3 class="pgp-band__h">' . esc_html__( 'Take the free readiness check', 'prepgro-theme' ) . '</h3>'
			. '<p class="pgp-band__b">' . esc_html__( 'In a few minutes, prepGro will show your child’s strengths, the skills that need more work, and the best next step.', 'prepgro-theme' ) . '</p>'
			. '</div>'
			. '<a class="pgp-cta pgp-cta--primary pgp-cta--inline" href="' . esc_url( home_url( '/get-started/' ) ) . '">'
			. esc_html__( 'Take the free readiness check', 'prepgro-theme' ) . '</a>'
			. '</section>';
	}

	/**
	 * The small print.
	 *
	 * @return string
	 */
	private function small_print() {
		$items = array(
			__( 'Subscriptions renew automatically unless cancelled before the next billing date.', 'prepgro-theme' ),
			__( 'Quarterly and annual plans provide the same unlimited practice access for the selected subject.', 'prepgro-theme' ),
			__( 'Live Tutor plans include up to eight 1:1 live classes each month.', 'prepgro-theme' ),
			__( 'Unlimited Test Pack access remains active until the end of the current paid Live Tutor billing period. After that, families can continue with a Test Pack subscription.', 'prepgro-theme' ),
			__( 'Practice tests are designed to help students prepare; prepGro does not guarantee examination scores.', 'prepgro-theme' ),
		);

		$rows = '';
		foreach ( $items as $i ) {
			$rows .= '<li><span class="pgp-dot" aria-hidden="true"></span>' . esc_html( $i ) . '</li>';
		}

		return '<section class="pgp-print">'
			. '<p class="pgp-print__l">' . esc_html__( 'The small print', 'prepgro-theme' ) . '</p>'
			. '<ul>' . $rows . '</ul>'
			. '</section>';
	}
}
