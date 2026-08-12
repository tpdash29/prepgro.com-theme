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

define( 'PGT_VERSION', '3.4.0' );
define( 'PGT_DIR', get_template_directory() );
define( 'PGT_URI', get_template_directory_uri() );

require_once PGT_DIR . '/inc/class-theme-setup.php';
require_once PGT_DIR . '/inc/class-token-loader.php';
require_once PGT_DIR . '/inc/class-icons.php';
require_once PGT_DIR . '/inc/class-image-slots.php';
require_once PGT_DIR . '/inc/class-media.php';
require_once PGT_DIR . '/inc/class-pricing-levels.php';
require_once PGT_DIR . '/inc/class-module-pages.php';
require_once PGT_DIR . '/inc/class-pricing-page.php';
require_once PGT_DIR . '/inc/class-exams-page.php';
require_once PGT_DIR . '/inc/class-diagnostics-page.php';
require_once PGT_DIR . '/inc/class-courses-page.php';
require_once PGT_DIR . '/inc/class-blog.php';
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
\PrepGro\Theme\Diagnostics_Page::instance()->init();
\PrepGro\Theme\Courses_Page::instance()->init();
\PrepGro\Theme\Blog::instance()->init();

/**
 * Carry the brand palette into the PWA.
 *
 * The engine builds the web-app manifest and the <meta name="theme-color">
 * from get_option( 'primary_color' ), whose hardcoded fallback is the old
 * #2563eb. That option is unset here, so the installed app's splash screen
 * and Android's browser chrome still render in the pre-A1 blue while every
 * pixel of the site renders in #1b3beb.
 *
 * Fixed theme-side rather than by writing the option, for the same reason
 * the --brand-* bridge below exists: the theme owns the brand, the plugin
 * consumes it, and nothing has to be re-set after a plugin update.
 */
if ( ! defined( 'PGT_BRAND_PRIMARY' ) ) {
	define( 'PGT_BRAND_PRIMARY', '#1b3beb' ); // --blue-600, the A1 accent.
	define( 'PGT_BRAND_GROUND', '#f6f7fc' );  // --neutral-50, the page ground.
}

add_filter(
	'pge_pwa_manifest',
	function ( $manifest ) {
		$manifest['theme_color']      = PGT_BRAND_PRIMARY;
		$manifest['background_color'] = PGT_BRAND_GROUND;
		return $manifest;
	}
);

/**
 * The theme-color <meta> is echoed by the engine at wp_head priority 2 with
 * no filter of its own. Per the HTML spec a UA takes the FIRST theme-color
 * meta whose media matches, so emitting the correct one at priority 1 wins
 * without removing the plugin's other head tags.
 */
add_action(
	'wp_head',
	function () {
		echo '<meta name="theme-color" content="' . esc_attr( PGT_BRAND_PRIMARY ) . '">' . "\n";
	},
	1
);

/**
 * Same problem, wider blast radius: the login, signup, password-reset and
 * email-verification screens all call
 *   get_option( Storage_Map::option('primary_color'), '#2563eb' )
 * directly and bake the result into inline gradients/buttons/focus rings —
 * there is no CSS layer between that PHP value and the rendered page, so no
 * stylesheet can repaint it. Six call sites, one option, same unset-option
 * problem as the PWA manifest above; filtering the option once fixes the
 * brand color on every one of them without editing the plugin.
 *
 * `Storage_Map::OPTION_PREFIX` is `pge_`, so the stored option name is
 * `pge_primary_color` — confirmed against includes/Storage/class-storage-map.php
 * rather than assumed, since a wrong option name here fails silently (the
 * filter just never fires).
 */
add_filter(
	'option_pge_primary_color',
	function () {
		return PGT_BRAND_PRIMARY;
	}
);

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
			 * The plugin declares its --pge-ui-* tokens on :root; declaring the same
			 * tokens on body wins for the whole page subtree regardless of
			 * stylesheet load order, so the dashboard/exam UI restyles without
			 * touching a single plugin file (survives plugin updates).
			 * Alias tokens (--pge-ui-neu-shadow*) are re-declared too because var()
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
				--pge-ui-neu-shl: 255,255,255;
				--pge-ui-neu-shd: 3,5,15;
				--pge-ui-neu-out:    var(--pge-shadow-lg);
				--pge-ui-neu-out-sm: var(--pge-shadow-md);
				--pge-ui-neu-out-xs: var(--pge-shadow-sm);
				--pge-ui-neu-in:      inset 0 2px 5px rgba(3,5,15,.08);
				--pge-ui-neu-in-deep: inset 0 3px 7px rgba(3,5,15,.12);
				--pge-ui-neu-shadow-sm:         var(--pge-shadow-sm);
				--pge-ui-neu-shadow:            var(--pge-shadow-md);
				--pge-ui-neu-shadow-lg:         var(--pge-shadow-lg);
				--pge-ui-neu-shadow-xl:         var(--pge-shadow-lg);
				--pge-ui-neu-shadow-inset:      inset 0 2px 5px rgba(3,5,15,.08);
				--pge-ui-neu-shadow-inset-sm:   inset 0 2px 5px rgba(3,5,15,.08);
				--pge-ui-neu-shadow-inset-deep: inset 0 3px 7px rgba(3,5,15,.12);
				--pge-ui-neu-accent-out: 0 8px 24px -12px rgba(18,38,200,.35);
				--pge-ui-neu-coral-out:  0 8px 24px -12px rgba(220,38,38,.30);
				--pge-ui-neu-halo-primary: var(--pge-focus-ring);

				/* Kit hairlines */
				--pge-ui-neu-border:        var(--pge-color-line);
				--pge-ui-neu-border-strong: rgba(3,5,15,.30);

				/* Kit surfaces + ink ramp */
				--pge-ui-neu-bg-warm:   var(--pge-color-surface);
				--pge-ui-neu-ink-soft:  var(--pge-color-body);
				--pge-ui-neu-ink-faint: var(--pge-color-muted);

				/* Status accents from the kit ramp (no coral/teal) */
				--pge-ui-neu-coral:      var(--pge-red-600);
				--pge-ui-neu-coral-deep: var(--red-700);
				--pge-ui-neu-coral-soft: var(--pge-red-50);
				--pge-ui-neu-good: var(--pge-color-success);
				--pge-ui-neu-focus-ring: var(--pge-focus-ring);

				/* Flat (non-neumorphic) component tokens used by newer screens.
				   The engine's assets/css/elevate/elevate.css derives its own
				   --pge-primary/--pge-ink from these, so they carry the palette
				   into the Elevate module too. */
				--pge-ui-bg: var(--pge-gray-50);
				--pge-ui-surface: var(--pge-white);
				--pge-ui-border: var(--pge-color-line);
				--pge-ui-border-hover: var(--pge-ink-400);
				--pge-ui-text-main: var(--pge-color-ink);
				--pge-ui-text-secondary: var(--pge-color-body);
				--pge-ui-text-muted: var(--pge-color-muted);
				--pge-ui-primary: var(--pge-color-primary);
				--pge-ui-font-family: var(--pge-font-sans);
				--pge-ui-shadow-sm: var(--pge-shadow-sm);
				--pge-ui-shadow:    var(--pge-shadow-md);
				--pge-ui-shadow-lg: var(--pge-shadow-lg);
				--pge-ui-shadow-xl: var(--pge-shadow-lg);
				--pge-ui-radius: var(--pge-radius-md);
				--pge-ui-radius-lg: 16px;
				--pge-ui-radius-xl: var(--pge-radius-lg);
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

		// Stylesheet version = the file's own mtime, so editing a stylesheet
		// always busts the browser cache. Versioning by PGT_VERSION meant a CSS
		// change shipped without a constant bump was invisible until a hard
		// reload — a silent failure worth designing out.
		$pgt_ver = static function ( $file ) {
			$path = get_template_directory() . '/assets/css/' . $file;
			return file_exists( $path ) ? (string) filemtime( $path ) : PGT_VERSION;
		};

		// Main theme stylesheet — loaded after the token bundle (pge-tokens).
		wp_enqueue_style(
			'pgt-theme',
			PGT_URI . '/assets/css/theme.css',
			array( 'pge-tokens' ),
			$pgt_ver( 'theme.css' )
		);

		// Page-scoped stylesheets (2026-08 redesign). Each screen's CSS lives in
		// its own file and is loaded only where it is used, so no page pays for
		// another page's rules. Keep this table in step with the classes in
		// inc/ that render those screens — a missing row here renders the page
		// as unstyled markup, which is silent and easy to miss.
		$pgt_screens = array(
			'pgt-home'    => array(
				'file' => 'pg-home.css',
				'on'   => is_front_page(),
			),
			// Re-skins the engine's [pge_device_stage] from the retired
			// neumorphic system to the A1 flat one. Front page only, because
			// that is the only screen that embeds the stage.
			'pgt-devices' => array(
				'file' => 'pg-devicestage.css',
				'on'   => is_front_page(),
			),
			'pgt-module'  => array(
				'file' => 'pg-module.css',
				'on'   => is_page() && '' !== \PrepGro\Theme\Module_Pages::instance()->current_module(),
			),
			'pgt-pricing' => array(
				'file' => 'pg-pricing.css',
				'on'   => is_page() && \PrepGro\Theme\Pricing_Page::instance()->is_pricing_page(),
			),
			// Exam single pages get it too: the level stamp, the coverage tiles
			// and the sample badges are styled here.
			'pgt-exams'   => array(
				'file' => 'pg-exams.css',
				// The diagnostics index reuses the .pgx component system
				// (accent-swapped via .pgx--evaluate), so it needs this
				// sheet too; pg-diagnostics.css only adds its own pieces.
				'on'   => ( is_page() && \PrepGro\Theme\Exams_Page::instance()->is_exams_page() )
					|| ( is_page() && \PrepGro\Theme\Diagnostics_Page::instance()->is_diagnostics_page() )
					|| \PrepGro\Theme\Courses_Page::instance()->is_courses_page()
					|| is_singular( 'exam' )
					|| is_post_type_archive( 'exam' )
					|| is_tax( 'pricing_level' ),
			),
			'pgt-diagnostics' => array(
				'file' => 'pg-diagnostics.css',
				'on'   => is_page() && \PrepGro\Theme\Diagnostics_Page::instance()->is_diagnostics_page(),
			),
			'pgt-courses' => array(
				'file' => 'pg-courses.css',
				'on'   => \PrepGro\Theme\Courses_Page::instance()->is_courses_page(),
			),
			'pgt-blog'    => array(
				'file' => 'pg-blog.css',
				'on'   => is_home() || is_singular( 'post' ) || is_category() || is_tag()
					|| is_author() || is_date() || is_search(),
			),
		);

		foreach ( $pgt_screens as $pgt_handle => $pgt_screen ) {
			if ( ! $pgt_screen['on'] ) {
				continue;
			}
			wp_enqueue_style(
				$pgt_handle,
				PGT_URI . '/assets/css/' . $pgt_screen['file'],
				array( 'pgt-theme' ),
				$pgt_ver( $pgt_screen['file'] )
			);
		}

		// Portal reskin — restyles the engine plugin's student dashboard
		// (which injects hardcoded runtime CSS) to the Playful Bold system.
		// Loaded after pgt-theme; wins via higher selector specificity.
		wp_enqueue_style(
			'pgt-portal-reskin',
			PGT_URI . '/assets/css/portal-reskin.css',
			array( 'pgt-theme' ),
			$pgt_ver( 'portal-reskin.css' )
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

/**
 * Countdown markup for a pillar the engine has switched off.
 *
 * The date is the engine's (Settings → Modules → "Available from"); this only
 * renders it. Returns '' when the pillar is on, no date is set, or the date has
 * already passed — pge_module_available_at() folds all three into a 0, so a
 * countdown can never be caught showing a launch that has come and gone.
 *
 * The unit values are rendered SERVER-side and the absolute date is printed
 * alongside them, so the block is correct and readable with JavaScript off or
 * still loading; theme.js then ticks it. `role="timer"` with aria-live off is
 * deliberate — a value changing every second must not be announced every
 * second — and the absolute date is what a screen reader actually reads.
 *
 * @param string $key Pillar key: evaluate | elevate | excel.
 * @return string
 */
function pgt_countdown( $key ) {
	if ( ! function_exists( 'pge_module_available_at' ) ) {
		return '';
	}
	$ts = (int) \pge_module_available_at( $key );
	if ( $ts <= 0 ) {
		return '';
	}

	$left  = max( 0, $ts - time() );
	$units = array(
		'd' => array( (int) floor( $left / DAY_IN_SECONDS ), _n( 'day', 'days', (int) floor( $left / DAY_IN_SECONDS ), 'prepgro-theme' ) ),
		'h' => array( (int) floor( ( $left % DAY_IN_SECONDS ) / HOUR_IN_SECONDS ), __( 'hrs', 'prepgro-theme' ) ),
		'm' => array( (int) floor( ( $left % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ), __( 'min', 'prepgro-theme' ) ),
		's' => array( (int) ( $left % MINUTE_IN_SECONDS ), __( 'sec', 'prepgro-theme' ) ),
	);

	$cells = '';
	foreach ( $units as $slug => $u ) {
		$cells .= '<span class="pgt-cd__unit">'
			. '<b class="pgt-cd__n" data-pgt-cd="' . esc_attr( $slug ) . '">' . esc_html( (string) $u[0] ) . '</b>'
			. '<i class="pgt-cd__l" data-pgt-cd-label="' . esc_attr( $slug ) . '">' . esc_html( $u[1] ) . '</i>'
			. '</span>';
	}

	// wp_date(), not date_i18n(): the timestamp is absolute and wp_date
	// formats it in the site's timezone without the legacy offset guesswork.
	$absolute = wp_date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $ts );

	return '<div class="pgt-cd" data-pgt-countdown="' . esc_attr( (string) $ts ) . '" role="timer">'
		. '<span class="pgt-cd__lead">' . esc_html__( 'Opens in', 'prepgro-theme' ) . '</span>'
		. '<span class="pgt-cd__units">' . $cells . '</span>'
		. '<span class="pgt-cd__date">' . esc_html( $absolute ) . '</span>'
		. '</div>';
}
