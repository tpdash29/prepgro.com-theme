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

define( 'PGT_VERSION', '3.2.1' );
define( 'PGT_DIR', get_template_directory() );
define( 'PGT_URI', get_template_directory_uri() );

require_once PGT_DIR . '/inc/class-theme-setup.php';
require_once PGT_DIR . '/inc/class-token-loader.php';
require_once PGT_DIR . '/inc/class-theme-options.php';
require_once PGT_DIR . '/inc/class-chrome.php';
require_once PGT_DIR . '/inc/class-homepage-sections.php';

\PrepGro\Theme\Theme_Setup::instance()->init();
\PrepGro\Theme\Token_Loader::instance()->init();
\PrepGro\Theme\Theme_Options::instance()->init();
\PrepGro\Theme\Chrome::instance()->init();
\PrepGro\Theme\Homepage_Sections::instance()->init();

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
				--brand-primary: #2563EB;
				--brand-accent: #2563EB;
				--brand-ink: #0F1419;
				--brand-canvas: #F5F7FB;
				--brand-heading-font: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
				--brand-font: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
				 * v3 "Meridian": layered soft elevation replaces both the misty
				 * neumorphic pair AND v2's hard "pop" offsets. Blue-tinted,
				 * restrained — matches --pge-shadow-* in tokens/_core.css.
				 */
				--tb-neu-shl: 255,255,255;
				--tb-neu-shd: 15,20,25;
				--tb-neu-out:    0 2px 4px rgba(15,20,25,.05), 0 24px 48px -16px rgba(26,79,196,.22);
				--tb-neu-out-sm: 0 1px 2px rgba(15,20,25,.05), 0 8px 24px -12px rgba(26,79,196,.18);
				--tb-neu-out-xs: 0 1px 2px rgba(15,20,25,.05);
				--tb-neu-in:      inset 0 2px 5px rgba(15,20,25,.08);
				--tb-neu-in-deep: inset 0 3px 7px rgba(15,20,25,.12);
				--tb-neu-shadow-sm:         0 1px 2px rgba(15,20,25,.05);
				--tb-neu-shadow:            0 1px 2px rgba(15,20,25,.05), 0 8px 24px -12px rgba(26,79,196,.18);
				--tb-neu-shadow-lg:         0 2px 4px rgba(15,20,25,.05), 0 24px 48px -16px rgba(26,79,196,.22);
				--tb-neu-shadow-xl:         0 2px 4px rgba(15,20,25,.05), 0 24px 48px -16px rgba(26,79,196,.22);
				--tb-neu-shadow-inset:      inset 0 2px 5px rgba(15,20,25,.08);
				--tb-neu-shadow-inset-sm:   inset 0 2px 5px rgba(15,20,25,.08);
				--tb-neu-shadow-inset-deep: inset 0 3px 7px rgba(15,20,25,.12);
				--tb-neu-accent-out: 0 8px 24px -12px rgba(26,79,196,.35);
				--tb-neu-coral-out:  0 8px 24px -12px rgba(220,38,38,.30);
				--tb-neu-halo-primary: 0 0 0 3px rgba(37,99,235,.30);

				/* Kit hairlines */
				--tb-neu-border:        #E3E8F0;
				--tb-neu-border-strong: rgba(15,20,25,.30);

				/* Kit surfaces + ink ramp */
				--tb-neu-bg-warm:   #F5F7FB;
				--tb-neu-ink-soft:  #313B4B;
				--tb-neu-ink-faint: #687078;

				/* Status accents from the kit ramp (no coral/teal) */
				--tb-neu-coral:      #DC2626;
				--tb-neu-coral-deep: #B91C1C;
				--tb-neu-coral-soft: #FDECEC;
				--tb-neu-good: #0E9F6E;
				--tb-neu-focus-ring: 0 0 0 3px rgba(37,99,235,.30);

				/* Flat (non-neumorphic) component tokens used by newer screens */
				--tb-bg: #F8FAFC;
				--tb-surface: #FFFFFF;
				--tb-border: #E3E8F0;
				--tb-border-hover: #8B93A2;
				--tb-text-main: #0F1419;
				--tb-text-secondary: #313B4B;
				--tb-text-muted: #687078;
				--tb-font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
				--tb-shadow-sm: 0 1px 2px rgba(15,20,25,.05);
				--tb-shadow:    0 1px 2px rgba(15,20,25,.05), 0 8px 24px -12px rgba(26,79,196,.18);
				--tb-shadow-lg: 0 2px 4px rgba(15,20,25,.05), 0 24px 48px -16px rgba(26,79,196,.22);
				--tb-shadow-xl: 0 2px 4px rgba(15,20,25,.05), 0 24px 48px -16px rgba(26,79,196,.22);
				--tb-radius: 10px;
				--tb-radius-lg: 14px;
				--tb-radius-xl: 16px;
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


