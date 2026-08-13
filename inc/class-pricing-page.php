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

	/**
	 * Is a pillar switched on? Defaults to TRUE when pge_feature() is
	 * unavailable (engine deactivated) — same convention as class-chrome.php.
	 *
	 * @param string $key Pillar key: evaluate | elevate | excel.
	 * @return bool
	 */
	private function pillar_on( $key ) {
		if ( ! function_exists( 'pge_feature' ) ) {
			return true;
		}
		return (bool) \pge_feature( $key );
	}

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
		$levels   = Pricing_Levels::levels();
		$current  = Pricing_Levels::current();
		// Live Tutor only exists when Elevate boots (Tutoring_Wiring, the
		// booking page, the portal shortcode all gate on it) — showing the
		// card, or the chart's tutor row, when the pillar is off advertises a
		// page that 404s. See class-activator.php's live-tutoring seeding note.
		$tutor_on = $this->pillar_on( 'elevate' );

		return '<div class="pgp" data-pgt-pricing data-level="' . esc_attr( $current ) . '">'
			. $this->header( $levels, $current, $tutor_on )
			. '<section class="pgp-body">'
			. $this->plans( $levels, $current, $tutor_on )
			. $this->chart( $levels, $current, $tutor_on )
			. $this->readiness_band()
			. $this->small_print( $tutor_on )
			. '</section>'
			. '</div>';
	}

	/**
	 * Header + level selector.
	 *
	 * @param array  $levels   Levels.
	 * @param string $current  Active level.
	 * @param bool   $tutor_on Whether the lede should mention live tutoring (Elevate on).
	 * @return string
	 */
	private function header( $levels, $current, $tutor_on = true ) {
		$tabs = '';
		foreach ( $levels as $key => $l ) {
			$on    = $key === $current;
			$tabs .= '<a class="pgp-tab' . ( $on ? ' is-on' : '' ) . '" role="tab"'
				. ' aria-selected="' . ( $on ? 'true' : 'false' ) . '"'
				. ' href="' . esc_url( Pricing_Levels::url( $key ) ) . '"'
				. ' data-pgt-level="' . esc_attr( $key ) . '" data-no-overlay="1">' . esc_html( $l['name'] ) . '</a>';
		}

		$names = '';
		foreach ( $levels as $key => $l ) {
			$names .= '<b class="pgp-levelname" data-pgt-levelname="' . esc_attr( $key ) . '"'
				. ( $key === $current ? '' : ' hidden' ) . '>' . esc_html( $l['name'] ) . '</b>';
		}

		return '<section class="pgp-head">'
			. '<div class="pgp-head__copy">'
			. '<span class="pgp-chip">' . esc_html__( 'Pricing', 'prepgro-theme' ) . '</span>'
			. '<h1 class="pgp-h1">' . esc_html__( 'Practice more.', 'prepgro-theme' )
			. '<br><span class="pgp-h1__quiet">' . esc_html__( 'Feel ready.', 'prepgro-theme' ) . '</span></h1>'
			. '<p class="pgp-lede">' . ( $tutor_on
				? esc_html__( 'Choose the support that fits your child: independent unlimited test practice, or live 1:1 tutoring with practice included.', 'prepgro-theme' )
				: esc_html__( 'Independent, unlimited test practice for one subject — practice as often as you need.', 'prepgro-theme' )
			) . '</p>'
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
	 * The billing-term selector — the control that was missing.
	 *
	 * Real <button>s in a radiogroup, not the styled <div>s this replaces: a
	 * price the visitor cannot choose is a price they cannot buy, and it read
	 * as clickable anyway because the Level chips directly above it are.
	 *
	 * The saving badge rides on the tab rather than only inside the panel, so
	 * the reason to pick annual is visible BEFORE the click, which is the
	 * whole job of a segmented control.
	 *
	 * @param array $pack Level's pack prices.
	 * @return string
	 */
	private function term_switch( array $pack ) {
		$out = '';
		foreach ( Pricing_Levels::terms() as $tkey => $t ) {
			$on   = Pricing_Levels::DEFAULT_TERM === $tkey;
			$v    = Pricing_Levels::term_value( $pack, $tkey );
			$badge = $v['percent'] > 0
				? '<span class="pgp-termtab__save">' . esc_html(
					sprintf(
						/* translators: %d: percent saved. */
						__( '-%d%%', 'prepgro-theme' ),
						$v['percent']
					)
				) . '</span>'
				: '';

			$out .= '<button type="button" class="pgp-termtab' . ( $on ? ' is-on' : '' ) . '"'
				. ' role="radio" aria-checked="' . ( $on ? 'true' : 'false' ) . '"'
				. ' data-pgt-termtab="' . esc_attr( $tkey ) . '">'
				. '<span class="pgp-termtab__l">' . esc_html( $t['label'] ) . '</span>'
				. $badge
				. '</button>';
		}

		return '<div class="pgp-termswitch" role="radiogroup" aria-label="'
			. esc_attr__( 'Billing term', 'prepgro-theme' ) . '">' . $out . '</div>';
	}

	/**
	 * The plan pair. All four levels render; the tabs toggle visibility.
	 *
	 * @param array  $levels   Levels.
	 * @param string $current  Active level.
	 * @param bool   $tutor_on Whether the Live Tutor card should render (Elevate on).
	 * @return string
	 */
	private function plans( $levels, $current, $tutor_on = true ) {
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
		//
		// Rebuilt as a real sales unit. It used to set the MONTHLY price huge
		// and list quarterly and annual beneath it as two inert <div>s, with a
		// single button hardwired to buy_url($key,'monthly') — so the page's
		// best offer (annual is ~58% off) was its smallest, greyest text, the
		// saving was never stated, and the two other terms could not be bought
		// at all. Now every term is selectable, the headline is the effective
		// per-month cost of the SELECTED term with the real charge spelled out
		// under it, and the button says what it will do.
		$prices = '';
		$terms  = Pricing_Levels::terms();
		foreach ( $levels as $key => $l ) {
			$sets = '';
			foreach ( $terms as $tkey => $t ) {
				$v       = Pricing_Levels::term_value( $l['pack'], $tkey );
				$has_sku = Pricing_Levels::has_sku( $key, $tkey );

				$save = $v['percent'] > 0
					? '<p class="pgp-save">' . esc_html(
						sprintf(
							/* translators: 1: percent saved, 2: money saved e.g. $139.89. */
							__( 'Save %1$d%% — %2$s less than paying monthly', 'prepgro-theme' ),
							$v['percent'],
							Pricing_Levels::money( $v['saved'] )
						)
					) . '</p>'
					: '';

				// The button carries the decision: what happens, at what price,
				// on what term. "Start unlimited practice" named none of those.
				$cta = $has_sku
					? '<a class="pgp-cta pgp-cta--primary" href="' . esc_url( Pricing_Levels::buy_url( $key, $tkey ) ) . '">'
						. esc_html(
							sprintf(
								/* translators: 1: total price, 2: term label, e.g. "$99.99 · Annual". */
								__( 'Subscribe — %1$s %2$s', 'prepgro-theme' ),
								Pricing_Levels::money( $v['total'] ),
								strtolower( $t['label'] )
							)
						) . '</a>'
						. '<p class="pgp-reassure">' . esc_html__( 'Cancel anytime. One subject per subscription.', 'prepgro-theme' ) . '</p>'
					// No purchasable SKU: say so, and keep the lead. Silently
					// bouncing this click to the readiness funnel is what sent
					// buyers to an exam page (see Pricing_Levels::enquiry_url).
					: '<a class="pgp-cta pgp-cta--enquire" href="' . esc_url( Pricing_Levels::enquiry_url() ) . '">'
						. esc_html__( 'Enquire about this plan', 'prepgro-theme' ) . '</a>'
						. '<p class="pgp-reassure">' . esc_html__( 'Online checkout for this plan is not open yet — ask us and we will set it up for you.', 'prepgro-theme' ) . '</p>';

				$sets .= '<div class="pgp-termset" data-pgt-term="' . esc_attr( $tkey ) . '"'
					. ( Pricing_Levels::DEFAULT_TERM === $tkey ? '' : ' hidden' ) . '>'
					. '<div class="pgp-figure"><span class="pgp-figure__n">' . esc_html( Pricing_Levels::money( $v['per_month'] ) ) . '</span>'
					. '<span class="pgp-figure__u">' . esc_html__( '/month', 'prepgro-theme' ) . '</span></div>'
					. '<p class="pgp-billed">' . esc_html( sprintf( $t['billed'], Pricing_Levels::money( $v['total'] ) ) ) . '</p>'
					. $save
					. $cta
					. '</div>';
			}

			$prices .= '<div class="pgp-priceset" data-pgt-price="' . esc_attr( $key ) . '"' . ( $key === $current ? '' : ' hidden' ) . '>'
				. $this->term_switch( $l['pack'] )
				. $sets
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

		// ── Right: Live Tutor + Test Pack (featured) — Elevate only ──
		if ( ! $tutor_on ) {
			return $out . '</div>';
		}

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
			. '<p class="pgp-card__b pgp-card__b--onblue">' . esc_html__( 'Get live help from a tutor while continuing to practice independently between classes.', 'prepgro-theme' ) . '</p>'
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
	 * @param array  $levels   Levels.
	 * @param string $current  Active level.
	 * @param bool   $tutor_on Whether the tutor row/legend/fact should render (Elevate on).
	 * @return string
	 */
	private function chart( $levels, $current, $tutor_on = true ) {
		$rows = '';
		foreach ( $levels as $key => $l ) {
			$pack = (float) $l['pack']['monthly'];
			$pw   = max( 1, min( 100, round( $pack / Pricing_Levels::CHART_MAX * 100 ) ) );

			$tutor_bar = '';
			if ( $tutor_on ) {
				$tutor = (float) $l['tutor'];
				$tw    = max( 1, min( 100, round( $tutor / Pricing_Levels::CHART_MAX * 100 ) ) );
				$tutor_bar = '<div class="pgp-barline"><span class="pgp-bar pgp-bar--tutor" style="width:' . esc_attr( $tw ) . '%"></span>'
					. '<span class="pgp-barval pgp-barval--strong">' . esc_html( Pricing_Levels::money( $tutor ) ) . esc_html__( '/mo', 'prepgro-theme' ) . '</span></div>';
			}

			$rows .= '<div class="pgp-chartrow' . ( $key === $current ? ' is-on' : '' ) . '" data-pgt-chartrow="' . esc_attr( $key ) . '">'
				. '<span class="pgp-chartrow__l">' . esc_html( $l['name'] ) . '</span>'
				. '<div class="pgp-chartrow__bars">'
				. '<div class="pgp-barline"><span class="pgp-bar pgp-bar--pack" style="width:' . esc_attr( $pw ) . '%"></span>'
				. '<span class="pgp-barval">' . esc_html( Pricing_Levels::money( $pack ) ) . esc_html__( '/mo', 'prepgro-theme' ) . '</span></div>'
				. $tutor_bar
				. '</div></div>';
		}

		$facts = array(
			array( 'v' => Pricing_Levels::money( 0 ), 'l' => __( 'to take the readiness check and read the report', 'prepgro-theme' ) ),
		);
		if ( $tutor_on ) {
			// The per-class figure is derived, never typed: the high-school
			// tutor price divided by the eight included classes.
			$high      = isset( $levels['high'] ) ? (float) $levels['high']['tutor'] : 0;
			$per_class = $high > 0 ? Pricing_Levels::money( round( $high / 8 ) ) : '—';

			array_unshift(
				$facts,
				array( 'v' => '8', 'l' => __( 'live 1:1 classes included every month on a tutor plan', 'prepgro-theme' ) ),
				array(
					'v' => $per_class,
					'l' => __( 'average cost per live class at the high-school level', 'prepgro-theme' ),
				)
			);
		}

		$factrows = '';
		foreach ( $facts as $f ) {
			$factrows .= '<div class="pgp-fact"><p class="pgp-fact__v">' . esc_html( $f['v'] ) . '</p>'
				. '<p class="pgp-fact__l">' . esc_html( $f['l'] ) . '</p></div>';
		}

		$legend_tutor = $tutor_on
			? '<span class="pgp-legend__i"><i class="pgp-sw pgp-sw--tutor"></i>' . esc_html__( 'Live tutor plan', 'prepgro-theme' ) . '</span>'
			: '';

		return '<section class="pgp-chart">'
			. '<div class="pgp-chart__head">'
			. '<div>'
			. '<span class="pgp-kicker">' . esc_html__( 'Compare', 'prepgro-theme' ) . '</span>'
			. '<h2 class="pgp-chart__h">' . esc_html__( 'Monthly cost by level', 'prepgro-theme' ) . '</h2>'
			. '</div>'
			. '<div class="pgp-legend">'
			. '<span class="pgp-legend__i"><i class="pgp-sw pgp-sw--pack"></i>' . esc_html__( 'Unlimited test pack', 'prepgro-theme' ) . '</span>'
			. $legend_tutor
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
		// The readiness check is Evaluate's. With that pillar switched off it
		// does not exist, and /get-started/ resolves into its diagnostic
		// catalogue — so the band was closing the pricing page with a CTA into
		// a dead pillar. No band is better than a broken one.
		if ( ! $this->pillar_on( 'evaluate' ) ) {
			return '';
		}

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
	 * @param bool $tutor_on Whether the Live Tutor bullets apply (Elevate on).
	 * @return string
	 */
	private function small_print( $tutor_on = true ) {
		$items = array(
			__( 'Subscriptions renew automatically unless cancelled before the next billing date.', 'prepgro-theme' ),
			__( 'Quarterly and annual plans provide the same unlimited practice access for the selected subject.', 'prepgro-theme' ),
		);
		if ( $tutor_on ) {
			$items[] = __( 'Live Tutor plans include up to eight 1:1 live classes each month.', 'prepgro-theme' );
			$items[] = __( 'Unlimited Test Pack access remains active until the end of the current paid Live Tutor billing period. After that, families can continue with a Test Pack subscription.', 'prepgro-theme' );
		}
		$items[] = __( 'Practice tests are designed to help students prepare; prepGro does not guarantee examination scores.', 'prepgro-theme' );

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
