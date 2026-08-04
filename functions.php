<?php
/**
 * PrepGro Theme — bootstrap.
 *
 * Parent theme for PrepGro exam-prep country sites. Keeps logic thin: the
 * PrepGro Engine plugin owns product behaviour and component CSS; this theme
 * owns chrome + design tokens. See style.css for the theme/plugin split.
 *
 * @package PrepGro\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PGT_VERSION', '3.3.0' );
define( 'PGT_DIR', get_template_directory() );
define( 'PGT_URI', get_template_directory_uri() );

require_once PGT_DIR . '/inc/class-theme-setup.php';
require_once PGT_DIR . '/inc/class-token-loader.php';
require_once PGT_DIR . '/inc/class-icons.php';
require_once PGT_DIR . '/inc/class-media.php';
require_once PGT_DIR . '/inc/class-pricing-levels.php';
require_once PGT_DIR . '/inc/class-module-pages.php';
require_once PGT_DIR . '/inc/class-pricing-page.php';
require_once PGT_DIR . '/inc/class-exams-page.php';
require_once PGT_DIR . '/inc/class-theme-options.php';
require_once PGT_DIR . '/inc/class-chrome.php';
require_once PGT_DIR . '/inc/class-homepage-sections.php';

\PrepGro\Theme\Theme_Setup::instance()->init();
\PrepGro\Theme\Token_Loader::instance()->init();
\PrepGro\Theme\Theme_Options::instance()->init();
\PrepGro\Theme\Chrome::instance()->init();
\PrepGro\Theme\Homepage_Sections::instance()->init();
\PrepGro\Theme\Module_Pages::instance()->init();
\PrepGro\Theme\Pricing_Page::instance()->init();
\PrepGro\Theme\Exams_Page::instance()->init();

/**
 * Site icon (favicon) from the bundled brand-kit mark — no Media Library
 * upload required. Only applies when the admin hasn't set a Site Icon of
 * their own (Settings > General), so this never fights a real choice.
 */
add_action(
	'wp_head',
	function () {
		if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
			return;
		}
		printf(
			'<link rel="icon" type="image/svg+xml" href="%s">' . "\n",
			esc_url( PGT_URI . '/assets/images/favicon.svg' )
		);
	},
	1
);

/**
 * Brand-variable bridge for the PrepGro Engine plugin's product UI (student
 * dashboard, pricing, exam pages, etc).
 *
 * That plugin's CSS (src/styles.css) already reads a whole "Brand Identity
 * Customizer" variable set — `var(--brand-primary, #2563eb)`,
 * `var(--brand-heading-font, 'Sora', ...)`, `var(--brand-canvas, #e7ecf3)`,
 * `var(--brand-ink, #2e3447)`, `var(--brand-accent, ...)` — but nothing in the
 * plugin ever actually SETS those custom properties, so every one of them
 * silently falls back to its literal default (Sora font, purple-blue accent,
 * bluish-gray canvas) instead of this site's actual brand.
 *
 * Defining them here — once, at the theme level — reskins the plugin's whole
 * neumorphic design system (dashboard, pricing, exam UI) to the prepGro brand
 * kit without touching a single plugin file, so it survives plugin updates.
 */
add_action(
	'wp_head',
	function () {
		?>
		<style id="pgt-brand-bridge">
			:root {
				--brand-primary: var(--pge-color-primary);
				--brand-accent: var(--pge-color-primary);
				--brand-ink: var(--pge-color-ink);
				--brand-canvas: var(--pge-color-surface);
				--brand-heading-font: var(--pge-font-heading);
				--brand-font: var(--pge-font-sans);
			}
			/*
			 * v2 "Playful Bold" reskin of the engine's neumorphic depth system.
			 * The plugin declares its --tb-* tokens on :root; declaring the same
			 * tokens on body wins for the whole page subtree regardless of
			 * stylesheet load order, so the dashboard/exam UI restyles without
			 * touching a single plugin file (survives plugin updates).
			 * Alias tokens (--tb-neu-shadow*) are re-declared too because var()
			 * substitution inside custom properties resolves on the declaring
			 * element — the plugin's :root aliases would otherwise keep the old
			 * soft-shadow values baked in.
			 */
			body {
				/*
				 * v4 "Meridian / electric": layered soft elevation, re-tinted onto
				 * the A1 palette. Every value below resolves through a --pge-*
				 * token, so the portal can never fork the palette — change
				 * assets/css/tokens/_core.css and this follows.
				 */
				--tb-neu-shl: 255,255,255;
				--tb-neu-shd: 3,5,15;
				--tb-neu-out:    var(--pge-shadow-lg);
				--tb-neu-out-sm: var(--pge-shadow-md);
				--tb-neu-out-xs: var(--pge-shadow-sm);
				--tb-neu-in:      inset 0 2px 5px rgba(3,5,15,.08);
				--tb-neu-in-deep: inset 0 3px 7px rgba(3,5,15,.12);
				--tb-neu-shadow-sm:         var(--pge-shadow-sm);
				--tb-neu-shadow:            var(--pge-shadow-md);
				--tb-neu-shadow-lg:         var(--pge-shadow-lg);
				--tb-neu-shadow-xl:         var(--pge-shadow-lg);
				--tb-neu-shadow-inset:      inset 0 2px 5px rgba(3,5,15,.08);
				--tb-neu-shadow-inset-sm:   inset 0 2px 5px rgba(3,5,15,.08);
				--tb-neu-shadow-inset-deep: inset 0 3px 7px rgba(3,5,15,.12);
				--tb-neu-accent-out: 0 8px 24px -12px rgba(18,38,200,.35);
				--tb-neu-coral-out:  0 8px 24px -12px rgba(220,38,38,.30);
				--tb-neu-halo-primary: var(--pge-focus-ring);

				/* Kit hairlines */
				--tb-neu-border:        var(--pge-color-line);
				--tb-neu-border-strong: rgba(3,5,15,.30);

				/* Kit surfaces + ink ramp */
				--tb-neu-bg-warm:   var(--pge-color-surface);
				--tb-neu-ink-soft:  var(--pge-color-body);
				--tb-neu-ink-faint: var(--pge-color-muted);

				/* Status accents from the kit ramp (no coral/teal) */
				--tb-neu-coral:      var(--pge-red-600);
				--tb-neu-coral-deep: var(--red-700);
				--tb-neu-coral-soft: var(--pge-red-50);
				--tb-neu-good: var(--pge-color-success);
				--tb-neu-focus-ring: var(--pge-focus-ring);

				/* Flat (non-neumorphic) component tokens used by newer screens.
				   The engine's assets/css/elevate/elevate.css derives its own
				   --pge-primary/--pge-ink from these, so they carry the palette
				   into the Elevate module too. */
				--tb-bg: var(--pge-gray-50);
				--tb-surface: var(--pge-white);
				--tb-border: var(--pge-color-line);
				--tb-border-hover: var(--pge-ink-400);
				--tb-text-main: var(--pge-color-ink);
				--tb-text-secondary: var(--pge-color-body);
				--tb-text-muted: var(--pge-color-muted);
				--tb-primary: var(--pge-color-primary);
				--tb-font-family: var(--pge-font-sans);
				--tb-shadow-sm: var(--pge-shadow-sm);
				--tb-shadow:    var(--pge-shadow-md);
				--tb-shadow-lg: var(--pge-shadow-lg);
				--tb-shadow-xl: var(--pge-shadow-lg);
				--tb-radius: var(--pge-radius-md);
				--tb-radius-lg: 16px;
				--tb-radius-xl: var(--pge-radius-lg);
			}
		</style>
		<?php
	},
	3
);

/**
 * Enqueue the theme stylesheet (consumes the --pge-* tokens) + fonts + chrome JS.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		// Google Fonts — Outfit only (the brand kit's single family: wordmark,
		// display AND UI text) + JetBrains Mono for tabular data/numerals.
		// One family = faster loads and a stronger identity.
		wp_enqueue_style(
			'pgt-fonts',
			'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap',
			array(),
			null
		);

		// Main theme stylesheet — loaded after the token bundle (pge-tokens).
		wp_enqueue_style(
			'pgt-theme',
			PGT_URI . '/assets/css/theme.css',
			array( 'pge-tokens' ),
			PGT_VERSION
		);

		// Readiness-first homepage sections (2026-07 redesign).
		if ( is_front_page() ) {
			wp_enqueue_style(
				'pgt-home',
				PGT_URI . '/assets/css/pg-home.css',
				array( 'pgt-theme' ),
				(string) filemtime( get_template_directory() . '/assets/css/pg-home.css' )
			);
		}

		// Portal reskin — restyles the engine plugin's student dashboard
		// (which injects hardcoded runtime CSS) to the Playful Bold system.
		// Loaded after pgt-theme; wins via higher selector specificity.
		wp_enqueue_style(
			'pgt-portal-reskin',
			PGT_URI . '/assets/css/portal-reskin.css',
			array( 'pgt-theme' ),
			PGT_VERSION
		);

		wp_enqueue_script(
			'pgt-chrome',
			PGT_URI . '/assets/js/theme.js',
			array(),
			PGT_VERSION,
			true
		);
	},
	20
);

/**
 * Front-page meta description (SEO/AEO). Skipped automatically when a
 * dedicated SEO plugin (Yoast, Rank Math, AIOSEO) is active.
 */
add_action(
	'wp_head',
	function () {
		if ( ! is_front_page() ) {
			return;
		}
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
			return;
		}
		echo '<meta name="description" content="' . esc_attr__( 'Free online practice tests for grades 3–12, SAT, ACT and AP — full-length mocks aligned to state standards, instant AI scoring, and a parent dashboard that tracks real progress. Start free, no credit card required.', 'prepgro-theme' ) . '">' . "\n";
	},
	2
);

/**
 * Content pages (privacy, terms, about, …) get the "Standard Page" template
 * (title band + long-form prose) automatically. App pages powered by
 * PrepGro Engine shortcodes keep the bare edge-to-edge page template.
 * A template chosen manually in the editor always wins.
 */
add_filter(
	'page_template_hierarchy',
	function ( $templates ) {
		$post = get_post();
		if ( ! $post || get_page_template_slug( $post ) ) {
			return $templates;
		}
		$content = (string) $post->post_content;
		$has_app_shortcode = (bool) preg_match( '/\[(pge_|testbook|tb_|pgt_)/', $content );
		if ( '' !== trim( $content ) && ! $has_app_shortcode ) {
			array_unshift( $templates, 'page-standard.php' );
		}
		return $templates;
	}
);

/**
 * Legacy shortcode aliases. Some page content still references pre-rename
 * engine shortcode tags (e.g. [testbook_exams] on /all-exams/). Map them to
 * the current engine shortcodes so those pages render instead of printing
 * the raw tag. Runs late so the engine registers its shortcodes first.
 */
add_action(
	'init',
	function () {
		global $shortcode_tags;
		$aliases = array(
			'testbook_exams'     => 'pge_exam_search',
			'testbook_dashboard' => 'pge_dashboard',
			'testbook_login'     => 'pge_login',
			'testbook_signup'    => 'pge_signup',
			'testbook_pricing'   => 'pge_pricing',
			'testbook_homepage'  => 'pge_homepage',
		);
		foreach ( $aliases as $legacy => $current ) {
			if ( ! shortcode_exists( $legacy ) && shortcode_exists( $current ) ) {
				add_shortcode( $legacy, $shortcode_tags[ $current ] );
			}
		}
	},
	99
);

/**
 * Register block patterns + a "PrepGro" pattern category.
 */
add_action(
	'init',
	function () {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category(
				'prepgro',
				array( 'label' => __( 'PrepGro', 'prepgro-theme' ) )
			);
		}

		$patterns = array( 'hero-exam-landing', 'feature-grid', 'how-it-works', 'cta-band', 'faq' );
		foreach ( $patterns as $slug ) {
			$file = PGT_DIR . '/patterns/' . $slug . '.php';
			if ( is_readable( $file ) ) {
				require $file; // each file calls register_block_pattern()
			}
		}
	}
);


