<?php
/**
 * Chrome — the site-wide front-end header and footer, rendered as shortcodes
 * (`[pge_header]`, `[pge_footer]`) so they can live inside the block template
 * parts while staying dynamic: auth-aware buttons, capability-gated account
 * menu, server-rendered copyright year, and a minimal footer variant on
 * focused app surfaces.
 *
 * One centralized, role-aware menu configuration feeds every region (desktop
 * nav, mobile drawer, account menu, footer columns) — see
 * prepgro-engine/docs/nav-footer/MENU_DATA_MODEL.md. Extension points for the
 * engine/plugins:
 *   - `pgt_header_nav_links`     (primary links; pre-existing filter)
 *   - `pgt_header_account_links` (avatar menu items)
 *   - `pgt_footer_columns`       (footer link columns)
 *   - `pgt_header_country_code`  (country chip)
 *
 * All render output is whitespace-collapsed before returning: the shortcodes
 * run through wpautop, which would otherwise inject <br>/<p> into multi-line
 * markup.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the responsive, auth-aware site chrome.
 */
final class Chrome {

	/** @var Chrome|null */
	private static $instance = null;

	/**
	 * @return Chrome
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * @return void
	 */
	public function init() {
		add_shortcode( 'pge_header', array( $this, 'render_header' ) );
		add_shortcode( 'pge_footer', array( $this, 'render_footer' ) );
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Menu configuration — single source of truth for every chrome region.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * Primary nav links (desktop bar + mobile drawer share this list).
	 *
	 * Owner-editable: when a WP menu is assigned to the `pgt-header` location
	 * (Appearance → Menus) its top-level items are used; the hardcoded list
	 * below is only the fallback for fresh installs. Labels/URLs from the CMS
	 * are escaped at render.
	 *
	 * Item shape: id, label, url, match (optional path prefix that marks the
	 * item current — anchor links have none and are never "current").
	 *
	 * @return array<int,array{id:string,label:string,url:string,match?:string}>
	 */
	private function nav_links() {
		$menu_links = $this->menu_items_for_location( 'pgt-header' );
		if ( null !== $menu_links ) {
			/** This filter is documented below. */
			return apply_filters( 'pgt_header_nav_links', $menu_links );
		}

		$links = array(
			array(
				'id'    => 'how',
				'label' => __( 'How It Works', 'prepgro-theme' ),
				'url'   => home_url( '/#pg-how' ),
			),
			array(
				'id'    => 'exams',
				'label' => __( 'Exams', 'prepgro-theme' ),
				'url'   => home_url( '/all-exams/' ),
				'match' => '/all-exams/',
			),
			array(
				'id'    => 'tutoring',
				'label' => __( '1:1 Tutoring', 'prepgro-theme' ),
				'url'   => home_url( '/#pg-experts' ),
			),
			array(
				'id'    => 'pricing',
				'label' => __( 'Pricing', 'prepgro-theme' ),
				'url'   => home_url( '/pricing/' ),
				'match' => '/pricing/',
			),
		);

		/**
		 * Filter the header nav links.
		 *
		 * @param array $links Nav link list.
		 */
		return apply_filters( 'pgt_header_nav_links', $links );
	}

	/**
	 * Account (avatar) menu items for the logged-in user. Capability-gated
	 * items are filtered out server-side — nothing a user can't open is ever
	 * rendered for them.
	 *
	 * @return array<int,array{id:string,label:string,url:string}>
	 */
	private function account_links() {
		$links = array(
			array(
				'id'    => 'dashboard',
				'label' => __( 'Dashboard', 'prepgro-theme' ),
				'url'   => home_url( '/my-dashboard/' ),
			),
			array(
				'id'    => 'profile',
				'label' => __( 'Profile & settings', 'prepgro-theme' ),
				'url'   => home_url( '/my-dashboard/?tab=profile' ),
			),
		);

		// Excel tutors get their real teaching home. Same caps that gate the
		// [pge_excel_teacher_portal] surface itself, so this can never 403.
		// (Physical slugs from the engine's Storage_Map; engine-active only.)
		if ( function_exists( 'pge_get_template' )
			&& ( current_user_can( 'testbook_excel_teach' ) || current_user_can( 'testbook_excel_manage' ) || current_user_can( 'manage_options' ) ) ) {
			$links[] = array(
				'id'    => 'teacher-portal',
				'label' => __( 'Tutoring portal', 'prepgro-theme' ),
				'url'   => home_url( '/teacher-portal/' ),
			);
		}

		$links[] = array(
			'id'    => 'help',
			'label' => __( 'Help & support', 'prepgro-theme' ),
			'url'   => home_url( '/contact-us/' ),
		);

		/**
		 * Filter the account (avatar) menu links.
		 *
		 * @param array $links Account link list.
		 */
		return apply_filters( 'pgt_header_account_links', $links );
	}

	/**
	 * Footer link columns for the public footer.
	 *
	 * Owner-editable per column via the `pgt-footer-*` menu locations
	 * (Appearance → Menus); the lists below are the fallback for locations
	 * with no menu assigned.
	 *
	 * @return array<string,array{title:string,links:array}>
	 */
	private function footer_columns() {
		$columns = array(
			'explore' => array(
				'title' => __( 'Explore', 'prepgro-theme' ),
				'links' => array(
					array( 'id' => 'how', 'label' => __( 'How it works', 'prepgro-theme' ), 'url' => home_url( '/#pg-how' ) ),
					array( 'id' => 'exams', 'label' => __( 'Exams & practice tests', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
					array( 'id' => 'tutoring', 'label' => __( '1:1 Tutoring', 'prepgro-theme' ), 'url' => home_url( '/#pg-experts' ) ),
					array( 'id' => 'pricing', 'label' => __( 'Pricing', 'prepgro-theme' ), 'url' => home_url( '/pricing/' ) ),
				),
			),
			'support' => array(
				'title' => __( 'Support', 'prepgro-theme' ),
				'links' => array(
					array( 'id' => 'contact', 'label' => __( 'Contact us', 'prepgro-theme' ), 'url' => home_url( '/contact-us/' ) ),
					array( 'id' => 'faq', 'label' => __( 'Parent FAQ', 'prepgro-theme' ), 'url' => home_url( '/#pg-faq' ) ),
					array( 'id' => 'about', 'label' => __( 'About PrepGro', 'prepgro-theme' ), 'url' => home_url( '/about-us/' ) ),
					array( 'id' => 'teach', 'label' => __( 'Teach with PrepGro', 'prepgro-theme' ), 'url' => home_url( '/contact-us/' ) ),
				),
			),
			'legal' => array(
				'title' => __( 'Legal', 'prepgro-theme' ),
				'links' => array(
					array( 'id' => 'privacy', 'label' => __( 'Privacy Policy', 'prepgro-theme' ), 'url' => home_url( '/privacy-policy/' ) ),
					array( 'id' => 'terms', 'label' => __( 'Terms of Service', 'prepgro-theme' ), 'url' => home_url( '/terms-of-service/' ) ),
					array( 'id' => 'refunds', 'label' => __( 'Refund & Cancellation Policy', 'prepgro-theme' ), 'url' => home_url( '/refund-policy/' ) ),
				),
			),
		);

		foreach ( array( 'explore', 'support', 'legal' ) as $key ) {
			$menu_links = $this->menu_items_for_location( 'pgt-footer-' . $key );
			if ( null !== $menu_links ) {
				$columns[ $key ]['links'] = $menu_links;
			}
		}

		/**
		 * Filter the public footer columns.
		 *
		 * @param array $columns Column map (key => {title, links}).
		 */
		return apply_filters( 'pgt_footer_columns', $columns );
	}

	/**
	 * Read the top-level items of the WP menu assigned to a location into
	 * the chrome item shape, or null when no usable menu is assigned (the
	 * caller then falls back to its built-in defaults).
	 *
	 * Sub-items are ignored: the chrome renders flat, calm link lists — a
	 * dropdown tree pasted into a 68px bar is exactly the clutter the
	 * redesign removes.
	 *
	 * @param string $location Menu location slug.
	 * @return array|null
	 */
	private function menu_items_for_location( $location ) {
		$locations = get_nav_menu_locations();
		if ( empty( $locations[ $location ] ) ) {
			return null;
		}
		$menu = wp_get_nav_menu_object( $locations[ $location ] );
		if ( ! $menu ) {
			return null;
		}
		$items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );
		if ( ! $items ) {
			return null;
		}

		$links = array();
		foreach ( $items as $item ) {
			if ( 0 !== (int) $item->menu_item_parent ) {
				continue;
			}
			$label = trim( wp_strip_all_tags( (string) $item->title ) );
			$url   = trim( (string) $item->url );
			if ( '' === $label || '' === $url ) {
				continue;
			}
			$links[] = array(
				'id'    => 'menu-' . (int) $item->ID,
				'label' => $label,
				'url'   => $url,
				'match' => $this->derive_match( $url ),
			);
		}

		return $links ? $links : null;
	}

	/**
	 * Derive the aria-current path prefix for a menu URL: internal,
	 * non-anchor, non-home URLs match on their path; everything else never
	 * reads as "current".
	 *
	 * @param string $url Menu item URL.
	 * @return string Path prefix or '' for no matching.
	 */
	private function derive_match( $url ) {
		if ( false !== strpos( $url, '#' ) ) {
			return '';
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( $host && strtolower( $host ) !== strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return '';
		}
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$base = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( $base && 0 === strpos( $path, $base ) ) {
			$path = substr( $path, strlen( $base ) );
		}
		$path = '/' . ltrim( $path, '/' );
		return '/' === $path ? '' : trailingslashit( $path );
	}

	/**
	 * Whether the given item's `match` path-prefix matches the current
	 * request path (query/hash ignored).
	 *
	 * @param array $item Nav item.
	 * @return bool
	 */
	private function is_current( $item ) {
		if ( empty( $item['match'] ) ) {
			return false;
		}
		$request = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path    = (string) wp_parse_url( $request, PHP_URL_PATH );
		$base    = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( $base && 0 === strpos( $path, $base ) ) {
			$path = substr( $path, strlen( $base ) );
		}
		$path = '/' . ltrim( $path, '/' );
		return 0 === strpos( trailingslashit( $path ), $item['match'] );
	}

	/**
	 * Whether the current request is a focused app surface (dashboard,
	 * portals, booking, learning player) rather than a marketing/content
	 * page. Drives the minimal footer variant.
	 *
	 * @return bool
	 */
	private function is_app_context() {
		// Preferred: ask the engine's app shell (shortcode-detected, stays in
		// sync with the engine's own page map).
		if ( class_exists( '\\PrepGro\\Engine\\Public_Side\\App_Shell' )
			&& method_exists( '\\PrepGro\\Engine\\Public_Side\\App_Shell', 'current_module' )
			&& \PrepGro\Engine\Public_Side\App_Shell::current_module() ) {
			return true;
		}

		// Learning-player CPT singles (course / lesson / assignment).
		if ( class_exists( '\\PrepGro\\Engine\\Storage\\Storage_Map' ) ) {
			$types = array();
			foreach ( array( 'course', 'lesson', 'assignment' ) as $key ) {
				$slug = \PrepGro\Engine\Storage\Storage_Map::post_type( $key );
				if ( $slug ) {
					$types[] = $slug;
				}
			}
			if ( $types && is_singular( $types ) ) {
				return true;
			}
		}

		return false;
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Header.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * Render the header markup: skip link + bar + full-screen mobile drawer.
	 *
	 * @return string
	 */
	public function render_header() {
		$home      = home_url( '/' );
		$links     = $this->nav_links();
		$logged_in = is_user_logged_in();

		// Logo markup. get_custom_logo() ships its OWN <a class="custom-logo-link">,
		// so it must never be nested inside another anchor. We tag core's anchor
		// with the pgt-logo class instead; fall back to the bundled brand-kit
		// lockup when no custom logo is set.
		$logo_html = '';
		if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
			$logo_html = (string) get_custom_logo();
		}
		if ( '' !== trim( $logo_html ) ) {
			$logo_html = str_replace( 'class="custom-logo-link', 'class="pgt-logo custom-logo-link', $logo_html );
		} else {
			$logo_html = '<a class="pgt-logo" href="' . esc_url( $home ) . '" rel="home" aria-label="' . esc_attr__( 'PrepGro home', 'prepgro-theme' ) . '">' . $this->brand_kit_logo( 'pgtLogoChipGrad' ) . '</a>';
		}

		// No theme skip link: core's block-theme skip link (#wp-skip-link)
		// already targets the templates' #pgt-main landmark.
		ob_start();
		?>
		<div class="pgt-header" data-pgt-header>
			<div class="pgt-header__inner">
				<?php
				// Not passed through wp_kses_post: kses strips the brand-kit
				// SVG chip (svg/defs/linearGradient aren't in its allowlist).
				// Both possible values are trusted markup we generate.
				echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->country_chip(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				<nav class="pgt-nav" aria-label="<?php esc_attr_e( 'Primary', 'prepgro-theme' ); ?>">
					<?php echo $this->nav_anchor_list( $links, 'pgt-nav__link', 'primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</nav>
				<div class="pgt-auth">
					<?php echo $this->auth_cluster( $logged_in ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<button class="pgt-burger" type="button" aria-label="<?php esc_attr_e( 'Open menu', 'prepgro-theme' ); ?>" aria-expanded="false" aria-controls="pgt-drawer">
					<span></span><span></span><span></span>
				</button>
			</div>
		</div>
		<?php echo $this->render_drawer( $links, $logged_in ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		return $this->minify( (string) ob_get_clean() );
	}

	/**
	 * Anchor list for a link collection (shared by desktop nav + drawer).
	 *
	 * @param array  $links  Items from nav_links().
	 * @param string $class  Anchor class.
	 * @param string $region Analytics region tag.
	 * @return string
	 */
	private function nav_anchor_list( $links, $class, $region ) {
		$out = '';
		foreach ( $links as $l ) {
			if ( empty( $l['label'] ) || empty( $l['url'] ) ) {
				continue;
			}
			$id      = isset( $l['id'] ) ? $l['id'] : sanitize_title( $l['label'] );
			$current = $this->is_current( $l ) ? ' aria-current="page"' : '';
			$out    .= '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $l['url'] ) . '"' . $current
				. ' data-nav="' . esc_attr( $region . ':' . $id ) . '">'
				. esc_html( $l['label'] ) . '</a>';
		}
		return $out;
	}

	/**
	 * Right-side auth cluster.
	 * Logged out: Sign in + primary readiness-check CTA.
	 * Logged in:  Dashboard pill + avatar disclosure menu (no bare "Log out").
	 *
	 * @param bool $logged_in Current auth state.
	 * @return string
	 */
	private function auth_cluster( $logged_in ) {
		if ( ! $logged_in ) {
			return '<a class="pgt-btn pgt-btn--ghost" href="' . esc_url( home_url( '/login/' ) ) . '" data-nav="auth:signin">' . esc_html__( 'Sign in', 'prepgro-theme' ) . '</a>'
				. '<a class="pgt-btn pgt-btn--primary" href="' . esc_url( home_url( '/get-started/' ) ) . '" data-nav="auth:readiness-cta">' . esc_html__( 'Start a Free Readiness Check', 'prepgro-theme' ) . '</a>';
		}

		$user    = wp_get_current_user();
		$name    = $user->display_name ? $user->display_name : $user->user_login;
		$initial = mb_strtoupper( mb_substr( trim( $name ), 0, 1 ) );

		$menu = '<div class="pgt-account__id"><strong>' . esc_html( $name ) . '</strong><span>' . esc_html( $user->user_email ) . '</span></div>';
		foreach ( $this->account_links() as $l ) {
			if ( empty( $l['label'] ) || empty( $l['url'] ) ) {
				continue;
			}
			$id    = isset( $l['id'] ) ? $l['id'] : sanitize_title( $l['label'] );
			$menu .= '<a class="pgt-account__item" href="' . esc_url( $l['url'] ) . '" data-nav="' . esc_attr( 'account:' . $id ) . '">' . esc_html( $l['label'] ) . '</a>';
		}
		$menu .= '<div class="pgt-account__sep" role="separator"></div>'
			. '<a class="pgt-account__item pgt-account__item--signout" href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '" data-nav="account:signout">' . esc_html__( 'Sign out', 'prepgro-theme' ) . '</a>';

		return '<a class="pgt-btn pgt-btn--ink" href="' . esc_url( home_url( '/my-dashboard/' ) ) . '" data-nav="auth:dashboard">' . esc_html__( 'Dashboard', 'prepgro-theme' ) . '</a>'
			. '<div class="pgt-account" data-pgt-account>'
			. '<button class="pgt-account__btn" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="pgt-account-menu">'
			. '<span class="pgt-account__avatar" aria-hidden="true">' . esc_html( $initial ) . '</span>'
			. '<span class="pgt-visually-hidden">' . esc_html__( 'Account menu', 'prepgro-theme' ) . '</span>'
			. '<svg class="pgt-account__chev" width="10" height="10" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
			. '</button>'
			. '<div class="pgt-account__menu" id="pgt-account-menu" hidden>' . $menu . '</div>'
			. '</div>';
	}

	/**
	 * Full-screen mobile drawer. Structure per the nav brief: primary CTA
	 * first, then the primary links, then auth links, then help. Focus trap,
	 * Escape, and scroll-lock live in theme.js.
	 *
	 * @param array $links     Primary nav links.
	 * @param bool  $logged_in Current auth state.
	 * @return string
	 */
	private function render_drawer( $links, $logged_in ) {
		ob_start();
		?>
		<div class="pgt-drawer" id="pgt-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'prepgro-theme' ); ?>" hidden>
			<div class="pgt-drawer__head">
				<a class="pgt-drawer__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo $this->brand_kit_logo( 'pgtDrawerChipGrad', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<button class="pgt-drawer__close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'prepgro-theme' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				</button>
			</div>
			<div class="pgt-drawer__body">
				<?php if ( ! $logged_in ) : ?>
					<a class="pgt-btn pgt-btn--primary pgt-drawer__cta" href="<?php echo esc_url( home_url( '/get-started/' ) ); ?>" data-nav="drawer:readiness-cta"><?php esc_html_e( 'Start a Free Readiness Check', 'prepgro-theme' ); ?></a>
				<?php else : ?>
					<a class="pgt-btn pgt-btn--primary pgt-drawer__cta" href="<?php echo esc_url( home_url( '/my-dashboard/' ) ); ?>" data-nav="drawer:dashboard"><?php esc_html_e( 'Open your dashboard', 'prepgro-theme' ); ?></a>
				<?php endif; ?>
				<nav class="pgt-drawer__nav" aria-label="<?php esc_attr_e( 'Menu', 'prepgro-theme' ); ?>">
					<?php echo $this->nav_anchor_list( $links, 'pgt-drawer__link', 'drawer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</nav>
				<div class="pgt-drawer__auth">
					<?php if ( ! $logged_in ) : ?>
						<a class="pgt-drawer__link" href="<?php echo esc_url( home_url( '/login/' ) ); ?>" data-nav="drawer:signin"><?php esc_html_e( 'Sign in', 'prepgro-theme' ); ?></a>
					<?php else : ?>
						<?php
						foreach ( $this->account_links() as $l ) {
							if ( empty( $l['label'] ) || empty( $l['url'] ) || 'dashboard' === ( $l['id'] ?? '' ) ) {
								continue; // Dashboard already leads the drawer as the CTA.
							}
							printf(
								'<a class="pgt-drawer__link" href="%s" data-nav="%s">%s</a>',
								esc_url( $l['url'] ),
								esc_attr( 'drawer:' . ( $l['id'] ?? sanitize_title( $l['label'] ) ) ),
								esc_html( $l['label'] )
							);
						}
						?>
						<a class="pgt-drawer__link pgt-drawer__link--signout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" data-nav="drawer:signout"><?php esc_html_e( 'Sign out', 'prepgro-theme' ); ?></a>
					<?php endif; ?>
				</div>
				<?php if ( ! $logged_in ) : ?>
					<a class="pgt-drawer__help" href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>" data-nav="drawer:help"><?php esc_html_e( 'Help & contact', 'prepgro-theme' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Footer.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * Render the footer. Marketing/content pages get the full public footer;
	 * focused app surfaces (dashboard, portals, booking, learning player)
	 * get a single quiet row so chrome never competes with the work.
	 *
	 * @return string
	 */
	public function render_footer() {
		return $this->minify( $this->is_app_context() ? $this->app_footer() : $this->public_footer() );
	}

	/**
	 * Minimal authenticated-surface footer.
	 *
	 * @return string
	 */
	private function app_footer() {
		ob_start();
		?>
		<div class="pgt-appfooter">
			<div class="pgt-container pgt-appfooter__inner">
				<span class="pgt-appfooter__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> PrepGro</span>
				<nav class="pgt-appfooter__nav" aria-label="<?php esc_attr_e( 'Legal and support', 'prepgro-theme' ); ?>">
					<a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>" data-nav="footer:help"><?php esc_html_e( 'Help & Support', 'prepgro-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" data-nav="footer:privacy"><?php esc_html_e( 'Privacy', 'prepgro-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>" data-nav="footer:terms"><?php esc_html_e( 'Terms', 'prepgro-theme' ); ?></a>
				</nav>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Full public footer.
	 *
	 * @return string
	 */
	private function public_footer() {
		$logged_in = is_user_logged_in();

		ob_start();
		?>
		<div class="pgt-footer">
			<div class="pgt-container">
				<div class="pgt-footer__top">
					<div class="pgt-footer__about">
						<div class="pgt-footer__brand"><?php echo $this->brand_kit_logo( 'pgtFooterChipGrad', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<p class="pgt-footer__tagline"><?php esc_html_e( 'A clearer path from readiness check to test day.', 'prepgro-theme' ); ?></p>
						<p class="pgt-footer__motto"><?php esc_html_e( 'Evaluate. Elevate. Excel.', 'prepgro-theme' ); ?></p>
						<div class="pgt-footer__ctarow">
							<?php if ( ! $logged_in ) : ?>
								<a class="pgt-btn pgt-btn--on-dark pgt-footer__cta" href="<?php echo esc_url( home_url( '/get-started/' ) ); ?>" data-nav="footer:readiness-cta"><?php esc_html_e( 'Start a Free Readiness Check', 'prepgro-theme' ); ?></a>
							<?php else : ?>
								<a class="pgt-btn pgt-btn--on-dark pgt-footer__cta" href="<?php echo esc_url( home_url( '/my-dashboard/' ) ); ?>" data-nav="footer:dashboard"><?php esc_html_e( 'Open your dashboard', 'prepgro-theme' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
					<div class="pgt-footer__cols">
						<?php foreach ( $this->footer_columns() as $key => $col ) : ?>
							<nav class="pgt-footer__col" aria-label="<?php echo esc_attr( $col['title'] ); ?>">
								<h4><?php echo esc_html( $col['title'] ); ?></h4>
								<?php
								foreach ( (array) $col['links'] as $l ) {
									if ( empty( $l['label'] ) || empty( $l['url'] ) ) {
										continue;
									}
									printf(
										'<a href="%s" data-nav="%s">%s</a>',
										esc_url( $l['url'] ),
										esc_attr( 'footer:' . ( $l['id'] ?? sanitize_title( $l['label'] ) ) ),
										esc_html( $l['label'] )
									);
								}
								?>
							</nav>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="pgt-footer__bottom">
					<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php esc_html_e( 'PrepGro. All rights reserved.', 'prepgro-theme' ); ?></span>
					<span class="pgt-footer__bottomlinks">
						<?php if ( defined( 'PGE_COUNTRY' ) && 'us' === strtolower( (string) PGE_COUNTRY ) ) : ?>
							<span class="pgt-footer__locale"><?php esc_html_e( 'United States · English (US)', 'prepgro-theme' ); ?></span>
						<?php endif; ?>
						<?php if ( ! $logged_in ) : ?>
							<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" data-nav="footer:signin"><?php esc_html_e( 'Sign in', 'prepgro-theme' ); ?></a>
						<?php else : ?>
							<a href="<?php echo esc_url( home_url( '/my-dashboard/' ) ); ?>" data-nav="footer:dashboard-quiet"><?php esc_html_e( 'Dashboard', 'prepgro-theme' ); ?></a>
						<?php endif; ?>
					</span>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Shared pieces.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * Country chip — small flag + country code pill shown beside the logo.
	 *
	 * Reads the engine plugin's country of operation (PGE_COUNTRY, set in
	 * wp-config per the launch runbook). Flags are inline SVG. Extend $flags
	 * when launching a new country — see prepgro-engine/docs/country-theming.md.
	 *
	 * @return string
	 */
	private function country_chip() {
		$code = defined( 'PGE_COUNTRY' ) ? strtolower( (string) PGE_COUNTRY ) : '';

		/**
		 * Filter the country code used for the header chip.
		 *
		 * @param string $code Two-letter country code.
		 */
		$code = apply_filters( 'pgt_header_country_code', $code );

		$flags = array(
			'us' => array(
				'label' => 'USA',
				'svg'   => '<svg viewBox="0 0 60 42" width="22" height="15" aria-hidden="true">'
					. '<rect width="60" height="42" fill="#B22234"/>'
					. '<g fill="#F5F1E8"><rect y="6" width="60" height="6"/><rect y="18" width="60" height="6"/><rect y="30" width="60" height="6"/></g>'
					. '<rect width="27" height="24" fill="#3C3B6E"/>'
					. '<g fill="#F5F1E8"><circle cx="5.5" cy="5" r="1.7"/><circle cx="13.5" cy="5" r="1.7"/><circle cx="21.5" cy="5" r="1.7"/>'
					. '<circle cx="9.5" cy="11.5" r="1.7"/><circle cx="17.5" cy="11.5" r="1.7"/>'
					. '<circle cx="5.5" cy="18" r="1.7"/><circle cx="13.5" cy="18" r="1.7"/><circle cx="21.5" cy="18" r="1.7"/></g>'
					. '</svg>',
			),
			'ca' => array(
				'label' => 'Canada',
				'svg'   => '<svg viewBox="0 0 60 42" width="22" height="15" aria-hidden="true">'
					. '<rect width="60" height="42" fill="#F5F1E8"/>'
					. '<rect width="15" height="42" fill="#D80621"/><rect x="45" width="15" height="42" fill="#D80621"/>'
					. '<path d="M30 8 L32 15 L38 13 L34 19 L40 21 L33 23 L34 30 L30 26 L26 30 L27 23 L20 21 L26 19 L22 13 L28 15 Z" fill="#D80621"/>'
					. '</svg>',
			),
			'in' => array(
				'label' => 'India',
				'svg'   => '<svg viewBox="0 0 60 42" width="22" height="15" aria-hidden="true">'
					. '<rect width="60" height="14" fill="#FF9933"/><rect y="14" width="60" height="14" fill="#FFFFFF"/><rect y="28" width="60" height="14" fill="#138808"/>'
					. '<circle cx="30" cy="21" r="5.5" fill="none" stroke="#000080" stroke-width="1.4"/>'
					. '</svg>',
			),
		);

		if ( ! isset( $flags[ $code ] ) ) {
			return '';
		}

		return '<span class="pgt-region-chip" title="' . esc_attr( sprintf( __( 'PrepGro %s edition', 'prepgro-theme' ), $flags[ $code ]['label'] ) ) . '">'
			. $flags[ $code ]['svg']
			. '<span>' . esc_html( $flags[ $code ]['label'] ) . '</span>'
			. '</span>';
	}

	/**
	 * Brand-kit chip mark + wordmark lockup (per prepgro-logo-kit-v34:
	 * chip gradient #1A4FC4→#2D7EF5→#5299FF, wordmark "prep" ink / "Gro"
	 * brand-blue, Outfit). Real text in the DOM so it stays crisp and
	 * accessible.
	 *
	 * @param string $grad_id      Unique SVG gradient id (ids must not repeat
	 *                             when the lockup renders more than once per page).
	 * @param bool   $with_tagline Include the tagline line under the wordmark.
	 * @return string
	 */
	private function brand_kit_logo( $grad_id = 'pgtLogoChipGrad', $with_tagline = true ) {
		$copy = '<span class="pgt-brandlogo__word"><span class="pgt-brandlogo__prep">prep</span><span class="pgt-brandlogo__gro">Gro</span></span>';
		if ( $with_tagline ) {
			$copy .= '<span class="pgt-brandlogo__tagline">' . esc_html__( 'Evaluate. Elevate. Excel.', 'prepgro-theme' ) . '</span>';
		}
		return '<span class="pgt-brandlogo">'
			. '<svg class="pgt-brandlogo__chip" width="34" height="34" viewBox="0 0 92 92" aria-hidden="true">'
			. '<defs><linearGradient id="' . esc_attr( $grad_id ) . '" x1="0" y1="0" x2="1" y2="1">'
			. '<stop offset="0%" stop-color="#1A4FC4"/><stop offset="52%" stop-color="#2D7EF5"/><stop offset="100%" stop-color="#5299FF"/>'
			. '</linearGradient></defs>'
			. '<rect x="6" y="6" width="80" height="80" rx="16" fill="url(#' . esc_attr( $grad_id ) . ')"/>'
			. '<path d="M74 23 L74 43 L67 36 L33 70 L17 54 L23 48 L33 58 L61 30 L54 23 Z" fill="#FFFFFF" stroke="#FFFFFF" stroke-width="2" stroke-linejoin="round"/>'
			. '</svg>'
			. '<span class="pgt-brandlogo__copy">' . $copy . '</span>'
			. '</span>';
	}

	/**
	 * Collapse inter-tag whitespace so wpautop (which the shortcode output
	 * runs through inside the block part) cannot inject <br>/<p> between
	 * elements. Safe here: chrome markup carries no whitespace-significant
	 * inline text across tag boundaries.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	private function minify( $html ) {
		$html = preg_replace( '/\s*\n\s*/', ' ', $html );
		$html = preg_replace( '/>\s+</', '><', $html );
		return trim( (string) $html );
	}
}
