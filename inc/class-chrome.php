<?php
/**
 * Chrome — the site-wide front-end header and footer, rendered as shortcodes
 * (`[pge_header]`, `[pge_footer]`) so they can live inside the block template
 * parts while staying dynamic: auth-aware account menu, capability-gated
 * items, server-rendered copyright year, and a minimal footer variant on
 * focused app surfaces.
 *
 * 2026-08 redesign (handoff README §1–§4, §14, addendum A2):
 *   - Five-item nav — Home · Evaluate · Elevate · Excel · Help. The three
 *     module names are REAL links to their landing pages; the mega panel
 *     opens on hover/focus and toggles on click. Help is click-to-open only.
 *   - Sign-in moved out of the bar and into the account menu.
 *   - The locale indicator moved out of the bar and into the footer bottom row.
 *   - The footer inverted: light, dotted texture, arrow links, no headings.
 *
 * One centralized, role-aware menu configuration feeds every region (desktop
 * nav, mega panels, mobile drawer, account menu, footer columns) — see
 * prepgro-engine/docs/nav-footer/MENU_DATA_MODEL.md. Extension points:
 *   - `pgt_header_nav_links`     (primary links)
 *   - `pgt_header_mega_panels`   (mega menu contents)
 *   - `pgt_header_account_links` (avatar menu items)
 *   - `pgt_footer_columns`       (footer link columns)
 *   - `pgt_header_country_code`  (locale line)
 *   - `pgt_search_suggestions`   (search seed chips)
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
	 * NOT owner-editable via Appearance → Menus any more. The 2026-08 redesign
	 * makes the primary bar structural rather than editorial: each of the three
	 * module names is bound to its landing page, its mega panel and its `owns`
	 * active-state map, so an arbitrary CMS menu cannot express it. A menu
	 * assigned to `pgt-header` (the engine's Menu_Seeder still seeds one) is
	 * deliberately ignored here — the `pgt_header_nav_links` filter remains the
	 * supported override. The FOOTER columns are still menu-editable.
	 *
	 * Item shape: id, label, url, owns (path prefixes that mark the item
	 * current), panel (mega panel key, optional).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function nav_links() {
		/*
		 * The `owns` map (addendum A2): a module name stays lit while the
		 * visitor is anywhere the module covers, so the readiness check keeps
		 * Evaluate lit, Pricing keeps Elevate lit, and Exams keeps Excel lit.
		 */
		$links = array(
			array(
				'id'    => 'home',
				'label' => __( 'Home', 'prepgro-theme' ),
				'url'   => home_url( '/' ),
				'owns'  => array(),
				'home'  => true,
			),
			array(
				'id'    => 'evaluate',
				'label' => __( 'Evaluate', 'prepgro-theme' ),
				'url'   => home_url( '/evaluate/' ),
				'owns'  => array( '/evaluate/', '/get-started/', '/readiness-check/', '/my-readiness-report/' ),
				'panel' => 'evaluate',
			),
			array(
				'id'    => 'elevate',
				'label' => __( 'Elevate', 'prepgro-theme' ),
				'url'   => home_url( '/elevate/' ),
				'owns'  => array( '/elevate/', '/pricing/', '/find-a-tutor/' ),
				'panel' => 'elevate',
			),
			array(
				'id'    => 'excel',
				'label' => __( 'Excel', 'prepgro-theme' ),
				'url'   => home_url( '/excel/' ),
				'owns'  => array( '/excel/', '/all-exams/', '/practice-tests/' ),
				'panel' => 'excel',
			),
			array(
				'id'    => 'help',
				'label' => __( 'Help', 'prepgro-theme' ),
				'url'   => home_url( '/contact-us/' ),
				'owns'  => array( '/contact-us/', '/about-us/', '/blog/' ),
				'panel' => 'help',
				// Help has no landing page of its own, so the label is a pure
				// disclosure trigger rather than a link (README A2).
				'toggle_only' => true,
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
	 * Mega menu contents (README §2). Each panel is three link groups plus a
	 * promo aside. Group `tone` selects the phase colour pair used for the
	 * eyebrow and the icon tiles.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function mega_panels() {
		$panels = array(
			'evaluate' => array(
				'tone'   => 'evaluate',
				'aside'  => array(
					'eyebrow' => __( 'Start here', 'prepgro-theme' ),
					'title'   => __( 'One free check, four named gaps', 'prepgro-theme' ),
					'body'    => __( 'About 20 minutes, no card. The report is ready the same day.', 'prepgro-theme' ),
					'cta'     => __( 'Take the free check', 'prepgro-theme' ),
					'url'     => home_url( '/get-started/' ),
				),
				'groups' => array(
					array(
						'eyebrow' => __( 'Diagnostic', 'prepgro-theme' ),
						'note'    => __( 'free', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'circle-check', 'title' => __( 'Free readiness check', 'prepgro-theme' ), 'sub' => __( '~20 min, adaptive', 'prepgro-theme' ), 'url' => home_url( '/get-started/' ) ),
							array( 'icon' => 'play-circle', 'title' => __( 'Resume my check', 'prepgro-theme' ), 'sub' => __( 'Picks up where you stopped', 'prepgro-theme' ), 'url' => home_url( '/get-started/' ) ),
							array( 'icon' => 'file-text', 'title' => __( 'Sample report', 'prepgro-theme' ), 'sub' => __( 'See what you get', 'prepgro-theme' ), 'url' => home_url( '/evaluate/' ) ),
						),
					),
					array(
						'eyebrow' => __( 'By exam', 'prepgro-theme' ),
						'note'    => __( 'pick yours', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'graduation-cap', 'title' => __( 'SAT · ACT · PSAT', 'prepgro-theme' ), 'sub' => __( 'College admission', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
							array( 'icon' => 'list', 'title' => __( 'AP subjects', 'prepgro-theme' ), 'sub' => __( '38 exams covered', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
							array( 'icon' => 'map-pin', 'title' => __( 'State tests & grades 3–12', 'prepgro-theme' ), 'sub' => __( 'All 50 states', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
						),
					),
					array(
						'eyebrow' => __( 'Results', 'prepgro-theme' ),
						'note'    => __( 'after the check', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'line-chart', 'title' => __( 'My readiness report', 'prepgro-theme' ), 'sub' => __( 'Skills to fix, in order', 'prepgro-theme' ), 'url' => home_url( '/my-dashboard/?tab=results' ) ),
							array( 'icon' => 'info', 'title' => __( 'How we measure', 'prepgro-theme' ), 'sub' => __( 'What the number means', 'prepgro-theme' ), 'url' => home_url( '/evaluate/' ) ),
							array( 'icon' => 'mail', 'title' => __( 'Parent digest', 'prepgro-theme' ), 'sub' => __( 'Weekly progress email', 'prepgro-theme' ), 'url' => home_url( '/evaluate/' ) ),
						),
					),
				),
			),
			'elevate'  => array(
				'tone'   => 'elevate',
				'aside'  => array(
					'eyebrow' => __( 'Live support', 'prepgro-theme' ),
					'title'   => __( '8 live 1:1 classes a month', 'prepgro-theme' ),
					'body'    => __( 'Your tutor already knows which skills are weak. No hour spent rediscovering them.', 'prepgro-theme' ),
					'cta'     => __( 'Find a tutor', 'prepgro-theme' ),
					'url'     => Pricing_Levels::url(),
				),
				'groups' => array(
					array(
						'eyebrow' => __( 'Learn', 'prepgro-theme' ),
						'note'    => __( 'LMS', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'book-open', 'title' => __( 'Lesson library', 'prepgro-theme' ), 'sub' => __( 'Mapped to every skill', 'prepgro-theme' ), 'url' => home_url( '/elevate/' ) ),
							array( 'icon' => 'clipboard', 'title' => __( 'My study plan', 'prepgro-theme' ), 'sub' => __( 'What to do this week', 'prepgro-theme' ), 'url' => home_url( '/my-dashboard/' ) ),
							array( 'icon' => 'pencil', 'title' => __( 'Assignments', 'prepgro-theme' ), 'sub' => __( 'Set by your tutor', 'prepgro-theme' ), 'url' => home_url( '/my-dashboard/' ) ),
						),
					),
					array(
						'eyebrow' => __( 'Tutor on demand', 'prepgro-theme' ),
						'note'    => __( '8 / month', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'user-check', 'title' => __( 'Find a tutor', 'prepgro-theme' ), 'sub' => __( 'Matched to your gaps', 'prepgro-theme' ), 'url' => Pricing_Levels::url() ),
							array( 'icon' => 'calendar', 'title' => __( 'Book a live class', 'prepgro-theme' ), 'sub' => __( 'Pick a slot this week', 'prepgro-theme' ), 'url' => home_url( '/my-dashboard/' ) ),
							array( 'icon' => 'file-text', 'title' => __( 'Session recaps', 'prepgro-theme' ), 'sub' => __( 'What was covered, next step', 'prepgro-theme' ), 'url' => home_url( '/my-dashboard/' ) ),
						),
					),
					array(
						'eyebrow' => __( 'Plans', 'prepgro-theme' ),
						'note'    => __( 'by level', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'dollar-sign', 'title' => __( 'Live tutor plans', 'prepgro-theme' ), 'sub' => __( 'From $129/month', 'prepgro-theme' ), 'url' => Pricing_Levels::url() ),
							array( 'icon' => 'circle-plus', 'title' => __( 'Add a second subject', 'prepgro-theme' ), 'sub' => __( 'One subject per plan', 'prepgro-theme' ), 'url' => Pricing_Levels::url() ),
							array( 'icon' => 'users', 'title' => __( 'Teach with prepGro', 'prepgro-theme' ), 'sub' => __( 'Tutor applications', 'prepgro-theme' ), 'url' => home_url( '/contact-us/' ) ),
						),
					),
				),
			),
			'excel'    => array(
				'tone'   => 'excel',
				'aside'  => array(
					'eyebrow' => __( 'Prove it', 'prepgro-theme' ),
					'title'   => __( 'Practise until it holds', 'prepgro-theme' ),
					'body'    => __( 'Unlimited attempts for one subject, with explanations and a trend by skill.', 'prepgro-theme' ),
					'cta'     => __( 'See test packs', 'prepgro-theme' ),
					'url'     => Pricing_Levels::url(),
				),
				'groups' => array(
					array(
						'eyebrow' => __( 'Practice', 'prepgro-theme' ),
						'note'    => __( 'unlimited', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'clock', 'title' => __( 'Practice tests', 'prepgro-theme' ), 'sub' => __( 'Timed and untimed', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
							array( 'icon' => 'layers', 'title' => __( 'Question banks', 'prepgro-theme' ), 'sub' => __( 'By skill, by difficulty', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
							array( 'icon' => 'file-text', 'title' => __( 'Full mock exams', 'prepgro-theme' ), 'sub' => __( 'Real structure and timing', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
						),
					),
					array(
						'eyebrow' => __( 'Review', 'prepgro-theme' ),
						'note'    => __( 'every answer', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'help-circle', 'title' => __( 'Answer explanations', 'prepgro-theme' ), 'sub' => __( 'Why the right one is right', 'prepgro-theme' ), 'url' => home_url( '/excel/' ) ),
							array( 'icon' => 'line-chart', 'title' => __( 'Progress & trend', 'prepgro-theme' ), 'sub' => __( 'Score movement by skill', 'prepgro-theme' ), 'url' => home_url( '/my-dashboard/?tab=performance' ) ),
							array( 'icon' => 'refresh-cw', 'title' => __( 'Retake weak sets', 'prepgro-theme' ), 'sub' => __( 'Until the skill holds', 'prepgro-theme' ), 'url' => home_url( '/my-dashboard/' ) ),
						),
					),
					array(
						'eyebrow' => __( 'Test packs', 'prepgro-theme' ),
						'note'    => __( 'by level', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'dollar-sign', 'title' => __( 'Unlimited test pack', 'prepgro-theme' ), 'sub' => __( 'From $9.99/month', 'prepgro-theme' ), 'url' => Pricing_Levels::url() ),
							array( 'icon' => 'list', 'title' => __( 'Browse all exams', 'prepgro-theme' ), 'sub' => __( 'Pick your subject', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
							array( 'icon' => 'circle-check', 'title' => __( 'Test-day checklist', 'prepgro-theme' ), 'sub' => __( 'The week before', 'prepgro-theme' ), 'url' => home_url( '/excel/' ) ),
						),
					),
				),
			),
			'help'     => array(
				'tone'   => 'neutral',
				'aside'  => array(
					'eyebrow' => __( 'Still stuck?', 'prepgro-theme' ),
					'title'   => __( 'Talk to a person', 'prepgro-theme' ),
					'body'    => __( 'We answer parent questions about plans, levels and matching within one working day.', 'prepgro-theme' ),
					'cta'     => __( 'Contact us', 'prepgro-theme' ),
					'url'     => home_url( '/contact-us/' ),
				),
				'groups' => array(
					array(
						'eyebrow' => __( 'Support', 'prepgro-theme' ),
						'note'    => __( 'we reply', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'mail', 'title' => __( 'Contact us', 'prepgro-theme' ), 'sub' => __( 'Email or call', 'prepgro-theme' ), 'url' => home_url( '/contact-us/' ) ),
							array( 'icon' => 'help-circle', 'title' => __( 'Parent FAQ', 'prepgro-theme' ), 'sub' => __( 'The common questions', 'prepgro-theme' ), 'url' => home_url( '/#pg-faq' ) ),
							array( 'icon' => 'credit-card', 'title' => __( 'Billing & refunds', 'prepgro-theme' ), 'sub' => __( 'Cancel anytime', 'prepgro-theme' ), 'url' => home_url( '/refund-policy/' ) ),
						),
					),
					array(
						'eyebrow' => __( 'Learn more', 'prepgro-theme' ),
						'note'    => __( 'reading', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'book-open', 'title' => __( 'Journal', 'prepgro-theme' ), 'sub' => __( 'Notes on prepping well', 'prepgro-theme' ), 'url' => $this->blog_url() ),
							array( 'icon' => 'activity', 'title' => __( 'How prepGro works', 'prepgro-theme' ), 'sub' => __( 'The three-part loop', 'prepgro-theme' ), 'url' => home_url( '/#pg-how' ) ),
							array( 'icon' => 'users', 'title' => __( 'About prepGro', 'prepgro-theme' ), 'sub' => __( 'Who we are', 'prepgro-theme' ), 'url' => home_url( '/about-us/' ) ),
						),
					),
					array(
						'eyebrow' => __( 'Trust', 'prepgro-theme' ),
						'note'    => __( 'the fine print', 'prepgro-theme' ),
						'items'   => array(
							array( 'icon' => 'shield', 'title' => __( 'Privacy policy', 'prepgro-theme' ), 'sub' => __( 'Your data stays yours', 'prepgro-theme' ), 'url' => home_url( '/privacy-policy/' ) ),
							array( 'icon' => 'file-text', 'title' => __( 'Terms of service', 'prepgro-theme' ), 'sub' => __( 'The agreement', 'prepgro-theme' ), 'url' => home_url( '/terms-of-service/' ) ),
							array( 'icon' => 'alert-triangle', 'title' => __( 'No score guarantees', 'prepgro-theme' ), 'sub' => __( 'What we do promise', 'prepgro-theme' ), 'url' => home_url( '/terms-of-service/' ) ),
						),
					),
				),
			),
		);

		/**
		 * Filter the mega menu panel contents.
		 *
		 * @param array $panels Panel map (key => {tone, aside, groups}).
		 */
		return apply_filters( 'pgt_header_mega_panels', $panels );
	}

	/**
	 * Permalink of the posts page, falling back to /blog/.
	 *
	 * @return string
	 */
	private function blog_url() {
		$page_id = (int) get_option( 'page_for_posts' );
		if ( $page_id ) {
			$url = get_permalink( $page_id );
			if ( $url ) {
				return $url;
			}
		}
		return home_url( '/blog/' );
	}

	/**
	 * Account (avatar) menu items for the logged-in user. Capability-gated
	 * items are filtered out server-side — nothing a user can't open is ever
	 * rendered for them.
	 *
	 * @return array<int,array{id:string,label:string,url:string,icon:string}>
	 */
	private function account_links() {
		$links = array(
			array(
				'id'    => 'dashboard',
				'label' => __( 'Dashboard', 'prepgro-theme' ),
				'url'   => home_url( '/my-dashboard/' ),
				'icon'  => 'layout-dashboard',
			),
			array(
				'id'    => 'report',
				'label' => __( 'My readiness report', 'prepgro-theme' ),
				'url'   => home_url( '/my-dashboard/?tab=results' ),
				'icon'  => 'file-text',
			),
			array(
				'id'    => 'profile',
				'label' => __( 'Profile & settings', 'prepgro-theme' ),
				'url'   => home_url( '/my-dashboard/?tab=profile' ),
				'icon'  => 'user',
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
				'icon'  => 'users',
			);
		}

		/**
		 * Filter the account (avatar) menu links.
		 *
		 * @param array $links Account link list.
		 */
		return apply_filters( 'pgt_header_account_links', $links );
	}

	/**
	 * Footer link columns. Three columns, no headings — every row is an arrow
	 * link (README §14). The `pgt-footer-*` menu locations still override
	 * their column's contents.
	 *
	 * @return array<string,array{title:string,links:array}>
	 */
	private function footer_columns() {
		$columns = array(
			'explore' => array(
				'title' => __( 'Explore', 'prepgro-theme' ),
				'links' => array(
					array( 'id' => 'how', 'label' => __( 'How it works', 'prepgro-theme' ), 'url' => home_url( '/#pg-how' ) ),
					array( 'id' => 'exams', 'label' => __( 'Exams', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
					array( 'id' => 'pricing', 'label' => __( 'Pricing', 'prepgro-theme' ), 'url' => Pricing_Levels::url() ),
					array( 'id' => 'journal', 'label' => __( 'Journal', 'prepgro-theme' ), 'url' => $this->blog_url() ),
				),
			),
			'support' => array(
				'title' => __( 'Product', 'prepgro-theme' ),
				'links' => array(
					array( 'id' => 'readiness', 'label' => __( 'Free readiness check', 'prepgro-theme' ), 'url' => home_url( '/get-started/' ) ),
					array( 'id' => 'practice', 'label' => __( 'Unlimited practice', 'prepgro-theme' ), 'url' => home_url( '/excel/' ) ),
					array( 'id' => 'tutor', 'label' => __( 'Find a tutor', 'prepgro-theme' ), 'url' => home_url( '/elevate/' ) ),
					array( 'id' => 'faq', 'label' => __( 'Parent FAQ', 'prepgro-theme' ), 'url' => home_url( '/#pg-faq' ) ),
				),
			),
			'legal'   => array(
				'title' => __( 'Company', 'prepgro-theme' ),
				'links' => array(
					array( 'id' => 'about', 'label' => __( 'About prepGro', 'prepgro-theme' ), 'url' => home_url( '/about-us/' ) ),
					array( 'id' => 'contact', 'label' => __( 'Contact us', 'prepgro-theme' ), 'url' => home_url( '/contact-us/' ) ),
					array( 'id' => 'teach', 'label' => __( 'Teach with prepGro', 'prepgro-theme' ), 'url' => home_url( '/contact-us/' ) ),
					array( 'id' => 'help', 'label' => __( 'Help & support', 'prepgro-theme' ), 'url' => home_url( '/contact-us/' ) ),
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
	 * Seed chips under the search field (README §3).
	 *
	 * @return string[]
	 */
	private function search_suggestions() {
		/**
		 * Filter the search suggestion chips.
		 *
		 * @param string[] $chips Suggested search terms.
		 */
		return (array) apply_filters(
			'pgt_search_suggestions',
			array(
				__( 'SAT math', 'prepgro-theme' ),
				__( 'data analysis', 'prepgro-theme' ),
				__( 'AP Biology practice', 'prepgro-theme' ),
				__( 'billing', 'prepgro-theme' ),
			)
		);
	}

	/**
	 * Read the top-level items of the WP menu assigned to a location into
	 * the chrome item shape, or null when no usable menu is assigned (the
	 * caller then falls back to its built-in defaults).
	 *
	 * Sub-items are ignored: a menu assigned here replaces the module nav with
	 * a flat link bar, panels and all.
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
			$match   = $this->derive_match( $url );
			$links[] = array(
				'id'    => 'menu-' . (int) $item->ID,
				'label' => $label,
				'url'   => $this->level_aware( $url ),
				'owns'  => $match ? array( $match ) : array(),
			);
		}

		return $links ? $links : null;
	}


	/**
	 * Route a CMS menu URL through the pricing level when it points at the
	 * pricing page without one.
	 *
	 * Footer columns stay owner-editable via Appearance → Menus, so a menu
	 * item aimed at /pricing/ would otherwise be the one link on an exam page
	 * that still shows every SKU — the exact case §6 rules out.
	 *
	 * @param string $url Menu URL.
	 * @return string
	 */
	private function level_aware( $url ) {
		if ( false === strpos( $url, '/pricing/' ) || false !== strpos( $url, 'level=' ) ) {
			return $url;
		}
		return Pricing_Levels::url();
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
	 * The current request path, home-base stripped and trailing-slashed.
	 *
	 * @return string
	 */
	private function current_path() {
		$request = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path    = (string) wp_parse_url( $request, PHP_URL_PATH );
		$base    = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( $base && 0 === strpos( $path, $base ) ) {
			$path = substr( $path, strlen( $base ) );
		}
		return trailingslashit( '/' . ltrim( $path, '/' ) );
	}

	/**
	 * Whether a nav item owns the current request (README addendum A2 `owns`).
	 *
	 * @param array $item Nav item.
	 * @return bool
	 */
	private function is_current( $item ) {
		$path = $this->current_path();

		if ( ! empty( $item['home'] ) ) {
			return '/' === $path;
		}
		if ( empty( $item['owns'] ) ) {
			return false;
		}
		foreach ( (array) $item['owns'] as $prefix ) {
			if ( $prefix && 0 === strpos( $path, $prefix ) ) {
				return true;
			}
		}
		return false;
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
	 * Whether this request is a FOCUS SURFACE — a live test or its review
	 * (README §13). The runner renders with the marketing chrome suppressed:
	 * no mega menu, no footer. Colored state chips and a mega menu are
	 * distraction during a timed adaptive test.
	 *
	 * Scoped to a running attempt rather than to the whole exam post type on
	 * purpose. An exam's landing page — no attempt open — is a marketing page
	 * and keeps its chrome, which is also where the §6 exam→pricing routing
	 * has to work.
	 *
	 * @return bool
	 */
	private function is_focus_surface() {
		$types = array( 'exam' );
		if ( class_exists( '\\PrepGro\\Engine\\Storage\\Storage_Map' ) ) {
			$diag = \PrepGro\Engine\Storage\Storage_Map::post_type( 'diagnostic' );
			if ( $diag ) {
				$types[] = $diag;
			}
		}

		$running = false;
		foreach ( array( 'attempt_id', 'tb_result', 'pge_start', 'tb_start' ) as $param ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only surface switch.
			if ( isset( $_GET[ $param ] ) ) {
				$running = true;
				break;
			}
		}

		$focus = is_singular( $types ) && $running;

		/**
		 * Filter whether the current request is a focus surface.
		 *
		 * @param bool $focus Whether to suppress the marketing chrome.
		 */
		return (bool) apply_filters( 'pgt_is_focus_surface', $focus );
	}

	/**
	 * Render the header: bar + mega panels + search band + mobile drawer.
	 *
	 * @return string
	 */
	public function render_header() {
		// A live test is a focus surface — no chrome at all.
		if ( $this->is_focus_surface() ) {
			return '';
		}

		$home      = home_url( '/' );
		$links     = $this->nav_links();
		$panels    = $this->mega_panels();
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
			$logo_html = '<a class="pgt-logo" href="' . esc_url( $home ) . '" rel="home" aria-label="' . esc_attr__( 'prepGro home', 'prepgro-theme' ) . '">' . $this->brand_kit_logo( 'pgtLogoChipGrad' ) . '</a>';
		}

		// No theme skip link: core's block-theme skip link (#wp-skip-link)
		// already targets the templates' #pgt-main landmark.
		ob_start();
		?>
		<div class="pgt-headerwrap" data-pgt-headerwrap>
			<div class="pgt-header" data-pgt-header>
				<div class="pgt-header__inner">
					<?php
					echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
					<nav class="pgt-nav" aria-label="<?php esc_attr_e( 'Primary', 'prepgro-theme' ); ?>">
						<?php echo $this->nav_items( $links, $panels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</nav>
					<div class="pgt-icons">
						<button class="pgt-iconbtn" type="button" data-pgt-search-toggle aria-expanded="false" aria-controls="pgt-search">
							<?php echo Icons::svg( 'search', array( 'size' => 17 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="pgt-visually-hidden"><?php esc_html_e( 'Search', 'prepgro-theme' ); ?></span>
						</button>
						<?php echo $this->account_cluster( $logged_in ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<a class="pgt-btn pgt-btn--primary pgt-header__cta" href="<?php echo esc_url( home_url( '/get-started/' ) ); ?>" data-nav="header:readiness-cta"><?php esc_html_e( 'Free check', 'prepgro-theme' ); ?></a>
						<button class="pgt-burger" type="button" aria-label="<?php esc_attr_e( 'Open menu', 'prepgro-theme' ); ?>" aria-expanded="false" aria-controls="pgt-drawer">
							<span></span><span></span><span></span>
						</button>
					</div>
				</div>
			</div>
			<?php echo $this->render_search(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $this->render_panels( $links, $panels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php echo $this->render_drawer( $links, $panels, $logged_in ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		return $this->minify( (string) ob_get_clean() );
	}

	/**
	 * Desktop nav items. Module names are links that ALSO disclose a panel;
	 * Help is a pure toggle. Both shapes carry aria-expanded/aria-controls.
	 *
	 * @param array $links  Nav items.
	 * @param array $panels Panel map.
	 * @return string
	 */
	private function nav_items( $links, $panels ) {
		$out = '';
		foreach ( $links as $l ) {
			if ( empty( $l['label'] ) || empty( $l['url'] ) ) {
				continue;
			}
			$id      = isset( $l['id'] ) ? $l['id'] : sanitize_title( $l['label'] );
			$key     = isset( $l['panel'] ) ? $l['panel'] : '';
			$has     = $key && isset( $panels[ $key ] );
			$current = $this->is_current( $l );
			$classes = 'pgt-nav__link' . ( $current ? ' is-current' : '' );
			$aria    = $current ? ' aria-current="page"' : '';

			$chev = $has
				? '<span class="pgt-nav__chev" aria-hidden="true">' . Icons::svg( 'chevron-down', array( 'size' => 10, 'stroke' => 2 ) ) . '</span>'
				: '';

			$disclosure = $has
				? ' aria-expanded="false" aria-controls="pgt-panel-' . esc_attr( $key ) . '" data-pgt-panel="' . esc_attr( $key ) . '"'
				: '';

			if ( $has && ! empty( $l['toggle_only'] ) ) {
				// Help: no landing page, so the label is only a disclosure.
				$out .= '<button type="button" class="' . esc_attr( $classes ) . '"' . $disclosure
					. ' data-nav="' . esc_attr( 'primary:' . $id ) . '">'
					. esc_html( $l['label'] ) . $chev . '</button>';
				continue;
			}

			$out .= '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( $l['url'] ) . '"' . $aria . $disclosure
				. ' data-nav="' . esc_attr( 'primary:' . $id ) . '">'
				. esc_html( $l['label'] ) . $chev . '</a>';
		}
		return $out;
	}

	/**
	 * The mega panels themselves — one absolutely-positioned region per
	 * module, hidden until its trigger opens it.
	 *
	 * @param array $links  Nav items.
	 * @param array $panels Panel map.
	 * @return string
	 */
	private function render_panels( $links, $panels ) {
		$keys = array();
		foreach ( $links as $l ) {
			if ( ! empty( $l['panel'] ) && isset( $panels[ $l['panel'] ] ) ) {
				$keys[] = $l['panel'];
			}
		}
		if ( ! $keys ) {
			return '';
		}

		$out = '';
		foreach ( $keys as $key ) {
			$p    = $panels[ $key ];
			$tone = isset( $p['tone'] ) ? $p['tone'] : 'neutral';

			$groups = '';
			foreach ( (array) $p['groups'] as $g ) {
				$rows = '';
				foreach ( (array) $g['items'] as $it ) {
					$rows .= '<a class="pgt-mega__row" href="' . esc_url( $it['url'] ) . '" data-nav="' . esc_attr( 'mega:' . $key ) . '">'
						. '<span class="pgt-mega__tile">' . Icons::svg( $it['icon'], array( 'size' => 16 ) ) . '</span>'
						. '<span class="pgt-mega__label">'
						. '<span class="pgt-mega__title">' . esc_html( $it['title'] ) . '</span>'
						. '<span class="pgt-mega__sub">' . esc_html( $it['sub'] ) . '</span>'
						. '</span></a>';
				}
				$groups .= '<div class="pgt-mega__group">'
					. '<p class="pgt-mega__grouphead"><span class="pgt-mega__eyebrow">' . esc_html( $g['eyebrow'] ) . '</span>'
					. '<span class="pgt-mega__note">' . esc_html( $g['note'] ) . '</span></p>'
					. $rows . '</div>';
			}

			$a     = $p['aside'];
			$aside = '<div class="pgt-mega__aside">'
				. '<p class="pgt-mega__asideeyebrow">' . esc_html( $a['eyebrow'] ) . '</p>'
				. '<p class="pgt-mega__asidetitle">' . esc_html( $a['title'] ) . '</p>'
				. '<p class="pgt-mega__asidebody">' . esc_html( $a['body'] ) . '</p>'
				. '<a class="pgt-mega__asidecta" href="' . esc_url( $a['url'] ) . '" data-nav="' . esc_attr( 'mega-cta:' . $key ) . '">'
				. esc_html( $a['cta'] ) . Icons::svg( 'arrow-right', array( 'size' => 14, 'stroke' => 2 ) ) . '</a>'
				. '</div>';

			$out .= '<div class="pgt-mega pgt-mega--' . esc_attr( $tone ) . '" id="pgt-panel-' . esc_attr( $key ) . '" data-pgt-panel-body="' . esc_attr( $key ) . '" hidden>'
				. '<div class="pgt-mega__inner"><div class="pgt-mega__groups">' . $groups . '</div>' . $aside . '</div>'
				. '</div>';
		}
		return $out;
	}

	/**
	 * Hidden search band (README §3). Posts to the WP search template.
	 *
	 * @return string
	 */
	private function render_search() {
		$chips = '';
		foreach ( $this->search_suggestions() as $term ) {
			$chips .= '<a class="pgt-search__chip" href="' . esc_url( home_url( '/?s=' . rawurlencode( $term ) ) ) . '">' . esc_html( $term ) . '</a>';
		}

		return '<div class="pgt-search" id="pgt-search" data-pgt-search hidden>'
			. '<div class="pgt-search__inner">'
			. '<form class="pgt-search__form" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '">'
			. '<span class="pgt-search__icon">' . Icons::svg( 'search', array( 'size' => 18 ) ) . '</span>'
			. '<label class="pgt-visually-hidden" for="pgt-search-input">' . esc_html__( 'Search', 'prepgro-theme' ) . '</label>'
			. '<input class="pgt-search__input" id="pgt-search-input" type="search" name="s" autocomplete="off"'
			. ' placeholder="' . esc_attr__( 'Search exams, skills, lessons or help', 'prepgro-theme' ) . '">'
			. '<span class="pgt-search__esc" aria-hidden="true">esc</span>'
			. '</form>'
			. '<div class="pgt-search__chips"><span class="pgt-search__try">' . esc_html__( 'Try', 'prepgro-theme' ) . '</span>' . $chips . '</div>'
			. '</div></div>';
	}

	/**
	 * Account button + dropdown. Signed-out and signed-in states both render
	 * from `is_user_logged_in()` — never from client state (README §4).
	 *
	 * @param bool $logged_in Current auth state.
	 * @return string
	 */
	private function account_cluster( $logged_in ) {
		if ( ! $logged_in ) {
			$menu = '<div class="pgt-account__id"><strong>' . esc_html__( 'Not signed in', 'prepgro-theme' ) . '</strong>'
				. '<span>' . esc_html__( 'Sign in to see your plan', 'prepgro-theme' ) . '</span></div>'
				. '<a class="pgt-account__item pgt-account__item--primary" href="' . esc_url( home_url( '/login/' ) ) . '" data-nav="account:signin">'
				. Icons::svg( 'log-in', array( 'size' => 16 ) ) . esc_html__( 'Sign in', 'prepgro-theme' ) . '</a>'
				. '<a class="pgt-account__item" href="' . esc_url( home_url( '/register/' ) ) . '" data-nav="account:register">'
				. Icons::svg( 'user-plus', array( 'size' => 16 ) ) . esc_html__( 'Create an account', 'prepgro-theme' ) . '</a>'
				. '<a class="pgt-account__item" href="' . esc_url( home_url( '/contact-us/' ) ) . '" data-nav="account:help">'
				. Icons::svg( 'help-circle', array( 'size' => 16 ) ) . esc_html__( 'Help & support', 'prepgro-theme' ) . '</a>';

			$avatar = '<span class="pgt-account__avatar pgt-account__avatar--anon" aria-hidden="true">?</span>';

			return $this->account_shell( $avatar, $menu );
		}

		$user  = wp_get_current_user();
		$name  = $user->display_name ? $user->display_name : $user->user_login;
		$menu  = '<div class="pgt-account__id"><strong>' . esc_html( $name ) . '</strong><span>' . esc_html( $user->user_email ) . '</span></div>';

		foreach ( $this->account_links() as $l ) {
			if ( empty( $l['label'] ) || empty( $l['url'] ) ) {
				continue;
			}
			$id    = isset( $l['id'] ) ? $l['id'] : sanitize_title( $l['label'] );
			$icon  = isset( $l['icon'] ) ? $l['icon'] : 'circle-check';
			$menu .= '<a class="pgt-account__item" href="' . esc_url( $l['url'] ) . '" data-nav="' . esc_attr( 'account:' . $id ) . '">'
				. Icons::svg( $icon, array( 'size' => 16 ) ) . esc_html( $l['label'] ) . '</a>';
		}
		$menu .= '<a class="pgt-account__item pgt-account__item--signout" href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '" data-nav="account:signout">'
			. Icons::svg( 'log-out', array( 'size' => 16 ) ) . esc_html__( 'Sign out', 'prepgro-theme' ) . '</a>';

		$avatar = '<span class="pgt-account__avatar" aria-hidden="true">' . esc_html( $this->initials( $name ) ) . '</span>';

		return $this->account_shell( $avatar, $menu );
	}

	/**
	 * The shared button + dropdown wrapper for both auth states.
	 *
	 * @param string $avatar Avatar span markup.
	 * @param string $menu   Dropdown contents.
	 * @return string
	 */
	private function account_shell( $avatar, $menu ) {
		return '<div class="pgt-account" data-pgt-account>'
			. '<button class="pgt-account__btn" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="pgt-account-menu">'
			. $avatar
			. '<span class="pgt-visually-hidden">' . esc_html__( 'Account menu', 'prepgro-theme' ) . '</span>'
			. '<span class="pgt-account__chev" aria-hidden="true">' . Icons::svg( 'chevron-down', array( 'size' => 10, 'stroke' => 2 ) ) . '</span>'
			. '</button>'
			. '<div class="pgt-account__menu" id="pgt-account-menu" hidden>' . $menu . '</div>'
			. '</div>';
	}

	/**
	 * Up to two initials from a display name, for the gradient avatar.
	 *
	 * @param string $name Display name.
	 * @return string
	 */
	private function initials( $name ) {
		$parts = preg_split( '/\s+/', trim( (string) $name ), -1, PREG_SPLIT_NO_EMPTY );
		if ( ! $parts ) {
			return '?';
		}
		$out = mb_substr( $parts[0], 0, 1 );
		if ( count( $parts ) > 1 ) {
			$out .= mb_substr( $parts[ count( $parts ) - 1 ], 0, 1 );
		}
		return mb_strtoupper( $out );
	}

	/**
	 * Full-screen mobile drawer. Module panels render as accordions inside it
	 * (README "Responsive behavior"). Focus trap, Escape and scroll-lock live
	 * in theme.js.
	 *
	 * @param array $links     Primary nav links.
	 * @param array $panels    Panel map.
	 * @param bool  $logged_in Current auth state.
	 * @return string
	 */
	private function render_drawer( $links, $panels, $logged_in ) {
		ob_start();
		?>
		<div class="pgt-drawer" id="pgt-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'prepgro-theme' ); ?>" hidden>
			<div class="pgt-drawer__head">
				<a class="pgt-drawer__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo $this->brand_kit_logo( 'pgtDrawerChipGrad', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<button class="pgt-drawer__close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'prepgro-theme' ); ?>">
					<?php echo Icons::svg( 'x', array( 'size' => 18, 'stroke' => 2 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
			<div class="pgt-drawer__body">
				<a class="pgt-btn pgt-btn--primary pgt-drawer__cta" href="<?php echo esc_url( home_url( '/get-started/' ) ); ?>" data-nav="drawer:readiness-cta"><?php esc_html_e( 'Take the free readiness check', 'prepgro-theme' ); ?></a>
				<nav class="pgt-drawer__nav" aria-label="<?php esc_attr_e( 'Menu', 'prepgro-theme' ); ?>">
					<?php
					foreach ( $links as $l ) {
						if ( empty( $l['label'] ) || empty( $l['url'] ) ) {
							continue;
						}
						$id   = isset( $l['id'] ) ? $l['id'] : sanitize_title( $l['label'] );
						$key  = isset( $l['panel'] ) ? $l['panel'] : '';
						$has  = $key && isset( $panels[ $key ] );
						$cur  = $this->is_current( $l ) ? ' aria-current="page"' : '';

						if ( ! $has ) {
							printf(
								'<a class="pgt-drawer__link" href="%s"%s data-nav="%s">%s</a>',
								esc_url( $l['url'] ),
								$cur, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								esc_attr( 'drawer:' . $id ),
								esc_html( $l['label'] )
							);
							continue;
						}

						// Accordion: the module name still navigates; the caret
						// beside it opens the sub-links.
						echo '<div class="pgt-drawer__group">';
						echo '<div class="pgt-drawer__grouphead">';
						if ( empty( $l['toggle_only'] ) ) {
							printf(
								'<a class="pgt-drawer__link pgt-drawer__link--group" href="%s"%s data-nav="%s">%s</a>',
								esc_url( $l['url'] ),
								$cur, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								esc_attr( 'drawer:' . $id ),
								esc_html( $l['label'] )
							);
						} else {
							printf( '<span class="pgt-drawer__link pgt-drawer__link--group">%s</span>', esc_html( $l['label'] ) );
						}
						printf(
							'<button class="pgt-drawer__caret" type="button" aria-expanded="false" aria-controls="pgt-drawer-%1$s" aria-label="%2$s">%3$s</button>',
							esc_attr( $key ),
							esc_attr( sprintf( /* translators: %s: module name */ __( 'Show %s links', 'prepgro-theme' ), $l['label'] ) ),
							Icons::svg( 'chevron-down', array( 'size' => 14, 'stroke' => 2 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						);
						echo '</div>';
						echo '<div class="pgt-drawer__sub" id="pgt-drawer-' . esc_attr( $key ) . '" hidden>';
						foreach ( (array) $panels[ $key ]['groups'] as $g ) {
							foreach ( (array) $g['items'] as $it ) {
								printf(
									'<a class="pgt-drawer__sublink" href="%s" data-nav="%s">%s</a>',
									esc_url( $it['url'] ),
									esc_attr( 'drawer-sub:' . $key ),
									esc_html( $it['title'] )
								);
							}
						}
						echo '</div></div>';
					}
					?>
				</nav>
				<div class="pgt-drawer__auth">
					<?php if ( ! $logged_in ) : ?>
						<a class="pgt-drawer__link" href="<?php echo esc_url( home_url( '/login/' ) ); ?>" data-nav="drawer:signin"><?php esc_html_e( 'Sign in', 'prepgro-theme' ); ?></a>
						<a class="pgt-drawer__link" href="<?php echo esc_url( home_url( '/register/' ) ); ?>" data-nav="drawer:register"><?php esc_html_e( 'Create an account', 'prepgro-theme' ); ?></a>
					<?php else : ?>
						<?php
						foreach ( $this->account_links() as $l ) {
							if ( empty( $l['label'] ) || empty( $l['url'] ) ) {
								continue;
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
		if ( $this->is_focus_surface() ) {
			return '';
		}
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
				<span class="pgt-appfooter__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> prepGro</span>
				<nav class="pgt-appfooter__nav" aria-label="<?php esc_attr_e( 'Legal and support', 'prepgro-theme' ); ?>">
					<a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>" data-nav="footer:help"><?php esc_html_e( 'Help & support', 'prepgro-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" data-nav="footer:privacy"><?php esc_html_e( 'Privacy', 'prepgro-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>" data-nav="footer:terms"><?php esc_html_e( 'Terms', 'prepgro-theme' ); ?></a>
				</nav>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Full public footer — light, dotted texture, arrow links, no column
	 * headings, tagline as the display statement (README §14).
	 *
	 * @return string
	 */
	private function public_footer() {
		ob_start();
		?>
		<div class="pgt-footer">
			<div class="pgt-footer__inner">
				<div class="pgt-footer__top">
					<div class="pgt-footer__about">
						<div class="pgt-footer__brand"><?php echo $this->brand_kit_logo( 'pgtFooterChipGrad', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<?php
						/*
						 * One module per line. The break used to be a hardcoded
						 * <br> after "Elevate.", which set the three module names
						 * as 2 + 1 and read like a wrap accident.
						 *
						 * Each line links to its module page: these three words
						 * ARE the primary IA, and they were the largest text in
						 * the footer while being the only part of it that did
						 * nothing. At rest they look exactly like the statement
						 * they replace.
						 */
						$pgt_statement = array(
							array(
								'label' => __( 'Evaluate.', 'prepgro-theme' ),
								'url'   => home_url( '/evaluate/' ),
							),
							array(
								'label' => __( 'Elevate.', 'prepgro-theme' ),
								'url'   => home_url( '/elevate/' ),
							),
							array(
								'label' => __( 'Excel.', 'prepgro-theme' ),
								'url'   => home_url( '/excel/' ),
							),
						);
						?>
						<h2 class="pgt-footer__statement">
							<?php foreach ( $pgt_statement as $pgt_line ) : ?>
								<a class="pgt-footer__statement-line" href="<?php echo esc_url( $pgt_line['url'] ); ?>"><?php echo esc_html( $pgt_line['label'] ); ?></a>
							<?php endforeach; ?>
						</h2>
					</div>
					<div class="pgt-footer__cols">
						<?php foreach ( $this->footer_columns() as $key => $col ) : ?>
							<nav class="pgt-footer__col" aria-label="<?php echo esc_attr( $col['title'] ); ?>">
								<?php
								foreach ( (array) $col['links'] as $l ) {
									if ( empty( $l['label'] ) || empty( $l['url'] ) ) {
										continue;
									}
									printf(
										'<a href="%s" data-nav="%s">%s<span class="pgt-footer__arrow" aria-hidden="true">%s</span></a>',
										esc_url( $l['url'] ),
										esc_attr( 'footer:' . ( $l['id'] ?? sanitize_title( $l['label'] ) ) ),
										esc_html( $l['label'] ),
										Icons::svg( 'arrow-right', array( 'size' => 14 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									);
								}
								?>
							</nav>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="pgt-footer__bottom">
					<span class="pgt-footer__copy">
						<?php
						printf(
							/* translators: %s: current year */
							esc_html__( '© %s prepGro. All rights reserved.', 'prepgro-theme' ),
							esc_html( gmdate( 'Y' ) )
						);
						?>
					</span>
					<span class="pgt-footer__bottomlinks">
						<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" data-nav="footer:privacy"><?php esc_html_e( 'Privacy Policy', 'prepgro-theme' ); ?></a>
						<a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>" data-nav="footer:terms"><?php esc_html_e( 'Terms of Service', 'prepgro-theme' ); ?></a>
						<?php echo $this->locale_line(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
	 * Locale indicator — text only, footer bottom row. The redesign drops the
	 * header's inline flag SVG entirely (README §14, "Assets"); the country
	 * logic itself is unchanged, only its output location and form.
	 *
	 * @return string
	 */
	private function locale_line() {
		$code = defined( 'PGE_COUNTRY' ) ? strtolower( (string) PGE_COUNTRY ) : '';

		/**
		 * Filter the country code used for the locale line.
		 *
		 * @param string $code Two-letter country code.
		 */
		$code = apply_filters( 'pgt_header_country_code', $code );

		$locales = array(
			'us' => __( 'United States · English', 'prepgro-theme' ),
			'ca' => __( 'Canada · English', 'prepgro-theme' ),
			'in' => __( 'India · English', 'prepgro-theme' ),
		);

		if ( ! isset( $locales[ $code ] ) ) {
			return '';
		}

		return '<span class="pgt-footer__locale">' . esc_html( $locales[ $code ] ) . '</span>';
	}

	/**
	 * Brand-kit chip mark + wordmark lockup. Chip gradient
	 * #0c1b9e→#0a84ff→#4d93ff at rx=18 on the 92-unit viewBox; wordmark
	 * "prep" 500 ink / "Gro" 700 brand blue, in Outfit. Real text in the DOM
	 * so it stays crisp and accessible.
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
			. '<svg class="pgt-brandlogo__chip" width="30" height="30" viewBox="0 0 92 92" aria-hidden="true">'
			. '<defs><linearGradient id="' . esc_attr( $grad_id ) . '" x1="0" y1="0" x2="1" y2="1">'
			. '<stop offset="0%" stop-color="#0c1b9e"/><stop offset="52%" stop-color="#0a84ff"/><stop offset="100%" stop-color="#4d93ff"/>'
			. '</linearGradient></defs>'
			. '<rect x="6" y="6" width="80" height="80" rx="18" fill="url(#' . esc_attr( $grad_id ) . ')"/>'
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
