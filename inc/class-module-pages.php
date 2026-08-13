<?php
/**
 * Module landing pages — Evaluate · Elevate · Excel (README addendum A2).
 *
 * ONE template, three content sets. The five sections run in a fixed order:
 *   1. Dark hero (module infographic on the right)
 *   2. What's inside — three pillar cards
 *   3. Photo + proof band
 *   4. How it works — three numbered steps
 *   5. Plan CTA + "the rest of the loop" cross-links
 *
 * The hero infographics are real markup, never images, and every one is
 * rendered FROM DATA so a copy change is a data change:
 *   - Evaluate: an SVG donut driven by stroke-dasharray/dashoffset, plus
 *     four sub-skill bars.
 *   - Elevate:  a 7-column grid of stacked spans.
 *   - Excel:    a 320×150 SVG polyline with a matching polygon fill.
 * Each carries a visible "sample" note, as does the proof band.
 *
 * ── Conflict note ────────────────────────────────────────────────────
 * The slugs /evaluate/, /elevate/ and /excel/ already exist as ENGINE
 * portal pages ([pge_evaluate_portal], [pge_elevate_portal],
 * [pge_excel_catalogue]). A2 specifies these same URLs as the marketing
 * landing pages the nav points at, so this class takes the template over
 * for those slugs. Nothing in the database is modified — filter
 * `pgt_module_page_slugs` to an empty array to hand the URLs back to the
 * engine, or remap them to different slugs.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the three module landing pages.
 */
final class Module_Pages {

	/** @var Module_Pages|null */
	private static $instance = null;

	/** @return Module_Pages */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/** @return void */
	public function init() {
		add_shortcode( 'pgt_module_page', array( $this, 'render' ) );
		add_filter( 'page_template_hierarchy', array( $this, 'route_template' ), 5 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Tag the body with the module so pg-module.css can scope the --pgm-*
	 * accent variables per pillar (README "each pillar should feel like its
	 * own place, not three copies of the same page"). These pages are
	 * routed by page-template, not a shortcode the engine's App_Shell can
	 * see, so they carry no pg-module-* class today without this.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( $classes ) {
		$module = $this->current_module();
		if ( $module ) {
			// Both: `pg-module-{key}` carries the per-pillar accent values,
			// while the bare `pg-module` lets a rule say "any module page"
			// once instead of repeating a three-way selector for every
			// chrome element that adopts the accent.
			$classes[] = 'pg-module';
			$classes[] = 'pg-module-' . $module;
		}
		return $classes;
	}

	/**
	 * Slug → module key. Filterable so the takeover can be disabled or the
	 * landing pages moved to different URLs without touching this file.
	 *
	 * @return array<string,string>
	 */
	public function slugs() {
		/**
		 * Filter which page slugs render a module landing page.
		 *
		 * @param array $slugs slug => module key.
		 */
		return (array) apply_filters(
			'pgt_module_page_slugs',
			array(
				'evaluate' => 'evaluate',
				'elevate'  => 'elevate',
				'excel'    => 'excel',
			)
		);
	}

	/**
	 * Route the three slugs to the module template ahead of everything else,
	 * including the standard-page fallback in functions.php.
	 *
	 * @param array $templates Template hierarchy.
	 * @return array
	 */
	public function route_template( $templates ) {
		$post = get_post();
		if ( ! $post || 'page' !== $post->post_type ) {
			return $templates;
		}
		// A template chosen by hand in the editor always wins.
		if ( get_page_template_slug( $post ) ) {
			return $templates;
		}
		if ( ! isset( $this->slugs()[ $post->post_name ] ) ) {
			return $templates;
		}
		array_unshift( $templates, 'page-module.php' );
		return $templates;
	}

	/**
	 * Which module the current request is, or '' when it is not one.
	 *
	 * @return string
	 */
	public function current_module() {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}
		$map = $this->slugs();
		return isset( $map[ $post->post_name ] ) ? $map[ $post->post_name ] : '';
	}

	/**
	 * Is a pillar switched on in the engine (Settings → Modules)?
	 *
	 * These landing pages are THEME-rendered — route_template() swaps the page
	 * template for page-module.html, which prints [pgt_module_page] instead of
	 * the page's own content. That is why switching a pillar off in the engine
	 * did nothing here: the engine's disabled-shortcode fallback never sees
	 * these pages, so /elevate/ went on selling a pillar whose runtime surface
	 * had been unregistered, CTAs and all.
	 *
	 * Defaults to TRUE with the engine deactivated — same reasoning as
	 * Chrome::pillar_on(): the theme must stand on its own.
	 *
	 * @param string $key Pillar key.
	 * @return bool
	 */
	private function pillar_on( $key ) {
		if ( ! function_exists( 'pge_feature' ) ) {
			return true;
		}
		return (bool) \pge_feature( $key );
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Content sets. Copy is final — lifted from the prototype's moduleVals().
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * @return array<string,array<string,mixed>>
	 */
	private function modules() {
		$modules = array(
			'evaluate' => array(
				'eyebrow' => __( 'Evaluate', 'prepgro-theme' ),
				'icon'    => 'circle-check-big',
				'title'   => __( 'Find the gaps before they cost you points.', 'prepgro-theme' ),
				'body'    => __( 'One free adaptive check, about 20 minutes. It scores every sub-skill separately and names the ones actually costing you marks.', 'prepgro-theme' ),
				'cta'     => array( 'label' => __( 'Start the free check', 'prepgro-theme' ), 'url' => home_url( '/get-started/' ) ),
				// Was the SAME url as 'cta' — a "See a sample report" button
				// that started a new diagnostic instead of showing anything.
				// Now points at the full sample report (engine page, real
				// PGAssessmentResultPage component with static demo data —
				// see class-sample-report-page.php in the plugin).
				'alt'     => array( 'label' => __( 'See a sample report', 'prepgro-theme' ), 'url' => home_url( '/sample-report/' ) ),
				'stats'   => array(
					array( 'value' => __( '~20 min', 'prepgro-theme' ), 'label' => __( 'one adaptive check', 'prepgro-theme' ) ),
					array( 'value' => __( '4 gaps', 'prepgro-theme' ), 'label' => __( 'named in your report', 'prepgro-theme' ) ),
					array( 'value' => '$0', 'label' => __( 'no card needed', 'prepgro-theme' ), 'price' => true ),
				),
				'fig'     => array( 'title' => __( 'Your readiness report', 'prepgro-theme' ), 'note' => __( 'sample', 'prepgro-theme' ) ),
				'inside'  => __( 'A diagnostic, a per-skill breakdown, and a report a parent can read in two minutes.', 'prepgro-theme' ),
				'pillars' => array(
					array(
						'icon'    => 'bar-chart',
						'title'   => __( 'Adaptive diagnostic', 'prepgro-theme' ),
						'body'    => __( 'Questions get harder or easier as you answer, so the check finds your ceiling fast.', 'prepgro-theme' ),
						'bullets' => array( __( '~20 minutes, one sitting', 'prepgro-theme' ), __( 'Pause and resume anytime', 'prepgro-theme' ), __( 'No card, no account wall', 'prepgro-theme' ) ),
					),
					array(
						'icon'    => 'line-chart',
						'title'   => __( 'Sub-skill scoring', 'prepgro-theme' ),
						'body'    => __( 'Not one number. Every skill in the exam blueprint gets its own score and confidence band.', 'prepgro-theme' ),
						'bullets' => array( __( 'Scored against the real blueprint', 'prepgro-theme' ), __( 'Weak skills ranked by point cost', 'prepgro-theme' ), __( 'Shows where guessing happened', 'prepgro-theme' ) ),
					),
					array(
						'icon'    => 'file-text',
						'title'   => __( 'Readiness report', 'prepgro-theme' ),
						'body'    => __( 'A plain-English report: where you stand today, and the four things to fix first.', 'prepgro-theme' ),
						'bullets' => array( __( 'Estimated score range', 'prepgro-theme' ), __( 'Four gaps, in priority order', 'prepgro-theme' ), __( 'Weekly digest for parents', 'prepgro-theme' ) ),
					),
				),
				'photo'   => array(
					'mod'  => 'pgt_module_photo_evaluate',
					'file' => 'photo-student-desk.jpg',
					'alt'  => __( 'A student taking the diagnostic on a laptop.', 'prepgro-theme' ),
				),
				'proof'   => array(
					'eyebrow' => __( 'What the check finds', 'prepgro-theme' ),
					'title'   => __( 'The average first check surfaces four fixable gaps.', 'prepgro-theme' ),
					'body'    => __( 'Across 12,400 diagnostics, most lost points cluster in a handful of sub-skills — not across the whole exam.', 'prepgro-theme' ),
					'bars'    => array(
						array( 'label' => __( 'Data analysis', 'prepgro-theme' ), 'value' => __( '38% of gaps', 'prepgro-theme' ), 'w' => 76, 'c' => 'var(--pgm-l1)' ),
						array( 'label' => __( 'Word problems', 'prepgro-theme' ), 'value' => __( '24% of gaps', 'prepgro-theme' ), 'w' => 48, 'c' => 'var(--pgm-l2)' ),
						array( 'label' => __( 'Reading evidence', 'prepgro-theme' ), 'value' => __( '21% of gaps', 'prepgro-theme' ), 'w' => 42, 'c' => 'var(--pgm-l3)' ),
						array( 'label' => __( 'Grammar & usage', 'prepgro-theme' ), 'value' => __( '17% of gaps', 'prepgro-theme' ), 'w' => 34, 'c' => 'var(--neutral-300)' ),
					),
				),
				'steps'   => array(
					array( 'n' => '01', 'title' => __( 'Take the check', 'prepgro-theme' ), 'body' => __( 'Pick your exam and answer until the check settles on your level.', 'prepgro-theme' ) ),
					array( 'n' => '02', 'title' => __( 'Get the report', 'prepgro-theme' ), 'body' => __( 'Same day. Every sub-skill scored, four gaps named in priority order.', 'prepgro-theme' ) ),
					array( 'n' => '03', 'title' => __( 'Choose your path', 'prepgro-theme' ), 'body' => __( 'Practice on your own, or get matched to a tutor for the weak skills.', 'prepgro-theme' ) ),
				),
				'plan'    => array(
					'tag'   => __( 'Always free', 'prepgro-theme' ),
					'price' => '$0',
					'unit'  => __( 'per check', 'prepgro-theme' ),
					'body'  => __( 'The diagnostic and the readiness report cost nothing. Retake it monthly to watch the gaps close.', 'prepgro-theme' ),
				),
				'next'    => array( 'elevate', 'excel' ),
			),
			'elevate'  => array(
				'eyebrow' => __( 'Elevate', 'prepgro-theme' ),
				'icon'    => 'book-open',
				'title'   => __( 'Learn the fix, with a tutor who already knows the gap.', 'prepgro-theme' ),
				'body'    => __( 'Lessons mapped to every sub-skill, plus eight live 1:1 classes a month with a tutor who read your report before the first session.', 'prepgro-theme' ),
				// A2 routing: Elevate's primary CTA goes to Pricing at the
				// visitor's level, never an all-SKU page.
				'cta'     => array( 'label' => __( 'Find a tutor', 'prepgro-theme' ), 'url' => '', 'pricing' => true ),
				'alt'     => array( 'label' => __( 'Browse the lesson library', 'prepgro-theme' ), 'url' => home_url( '/my-dashboard/' ) ),
				'stats'   => array(
					array( 'value' => '8', 'label' => __( 'live classes a month', 'prepgro-theme' ) ),
					array( 'value' => '1:1', 'label' => __( 'never a group call', 'prepgro-theme' ) ),
					array( 'value' => '$129', 'label' => __( 'per month, one subject', 'prepgro-theme' ), 'price' => true ),
				),
				'fig'     => array( 'title' => __( 'A week on your plan', 'prepgro-theme' ), 'note' => __( 'sample', 'prepgro-theme' ) ),
				'inside'  => __( 'A study plan you can follow without deciding what to do next, and a tutor for the parts that need a human.', 'prepgro-theme' ),
				'pillars' => array(
					array(
						'icon'    => 'book-open',
						'title'   => __( 'Lesson library', 'prepgro-theme' ),
						'body'    => __( 'Short lessons tied to each sub-skill, so the fix for a gap is one click from the report.', 'prepgro-theme' ),
						'bullets' => array( __( 'Mapped to the exam blueprint', 'prepgro-theme' ), __( 'Worked examples, then practice', 'prepgro-theme' ), __( 'Unlocks as skills close', 'prepgro-theme' ) ),
					),
					array(
						'icon'    => 'user-check',
						'title'   => __( 'Tutor on demand', 'prepgro-theme' ),
						'body'    => __( 'Matched on your weak skills, not on whoever is free. Eight live classes every month.', 'prepgro-theme' ),
						'bullets' => array( __( 'Tutor reads your report first', 'prepgro-theme' ), __( 'Book slots the same week', 'prepgro-theme' ), __( 'Swap tutors free, anytime', 'prepgro-theme' ) ),
					),
					array(
						'icon'    => 'calendar',
						'title'   => __( 'Weekly plan', 'prepgro-theme' ),
						'body'    => __( 'Every Monday: what to study, what your tutor assigned, and how long it should take.', 'prepgro-theme' ),
						'bullets' => array( __( 'Built from your last results', 'prepgro-theme' ), __( 'Session recaps after each class', 'prepgro-theme' ), __( 'Parent digest every Sunday', 'prepgro-theme' ) ),
					),
				),
				'photo'   => array(
					'mod'  => 'pgt_module_photo_elevate',
					'file' => 'photo-family-study.jpg',
					'alt'  => __( 'A tutor and student on a video call.', 'prepgro-theme' ),
				),
				'proof'   => array(
					'eyebrow' => __( 'Where the hours go', 'prepgro-theme' ),
					'title'   => __( 'Tutoring time spent on the gap, not on finding it.', 'prepgro-theme' ),
					'body'    => __( 'Because the tutor starts from your report, the first session opens on the weak skill instead of a placement quiz.', 'prepgro-theme' ),
					'bars'    => array(
						array( 'label' => __( 'Live 1:1 classes', 'prepgro-theme' ), 'value' => __( '8 / month', 'prepgro-theme' ), 'w' => 70, 'c' => 'var(--pgm-l1)' ),
						array( 'label' => __( 'Tutor-set assignments', 'prepgro-theme' ), 'value' => __( '4 / month', 'prepgro-theme' ), 'w' => 40, 'c' => 'var(--pgm-l2)' ),
						array( 'label' => __( 'Self-study lessons', 'prepgro-theme' ), 'value' => __( 'unlimited', 'prepgro-theme' ), 'w' => 92, 'c' => 'var(--pgm-l3)' ),
						array( 'label' => __( 'Time spent on placement', 'prepgro-theme' ), 'value' => __( '0 min', 'prepgro-theme' ), 'w' => 3, 'c' => 'var(--neutral-300)' ),
					),
				),
				'steps'   => array(
					array( 'n' => '01', 'title' => __( 'Share your report', 'prepgro-theme' ), 'body' => __( 'Your diagnostic goes to the tutor before you ever meet.', 'prepgro-theme' ) ),
					array( 'n' => '02', 'title' => __( 'Meet your match', 'prepgro-theme' ), 'body' => __( 'A tutor for your exam and your weak skills. Swap free if the fit is wrong.', 'prepgro-theme' ) ),
					array( 'n' => '03', 'title' => __( 'Work the plan', 'prepgro-theme' ), 'body' => __( 'Eight live classes a month, with lessons and assignments in between.', 'prepgro-theme' ) ),
				),
				'plan'    => array(
					'tag'   => __( 'Live tutor plan', 'prepgro-theme' ),
					'price' => '$129',
					'unit'  => __( '/ month', 'prepgro-theme' ),
					'body'  => __( 'Eight live 1:1 classes, the full lesson library and unlimited practice for one subject. Cancel anytime.', 'prepgro-theme' ),
				),
				'next'    => array( 'evaluate', 'excel' ),
			),
			'excel'    => array(
				'eyebrow' => __( 'Excel', 'prepgro-theme' ),
				'icon'    => 'trend-up',
				'title'   => __( 'Practice until the skill holds under time.', 'prepgro-theme' ),
				'body'    => __( 'Unlimited timed and untimed practice for your exam, every answer explained, and a trend line that shows whether a fixed skill stayed fixed.', 'prepgro-theme' ),
				'cta'     => array( 'label' => __( 'See test packs', 'prepgro-theme' ), 'url' => '', 'pricing' => true ),
				'alt'     => array( 'label' => __( 'Browse all exams', 'prepgro-theme' ), 'url' => home_url( '/all-exams/' ) ),
				'stats'   => array(
					array( 'value' => __( 'Unlimited', 'prepgro-theme' ), 'label' => __( 'attempts, one subject', 'prepgro-theme' ) ),
					array( 'value' => '38', 'label' => __( 'AP exams covered', 'prepgro-theme' ) ),
					array( 'value' => '$9.99', 'label' => __( 'per month', 'prepgro-theme' ), 'price' => true ),
				),
				'fig'     => array( 'title' => __( 'Score trend, 12 tests', 'prepgro-theme' ), 'note' => __( 'sample', 'prepgro-theme' ) ),
				'inside'  => __( 'Real exam structure and timing, explanations on every question, and progress tracked per skill.', 'prepgro-theme' ),
				'pillars' => array(
					array(
						'icon'    => 'file-text',
						'title'   => __( 'Practice & mock tests', 'prepgro-theme' ),
						'body'    => __( 'Full mocks with real structure and timing, or short sets aimed at one skill.', 'prepgro-theme' ),
						'bullets' => array( __( 'Timed and untimed modes', 'prepgro-theme' ), __( 'Flag, skip and review like the real test', 'prepgro-theme' ), __( 'Unlimited retakes', 'prepgro-theme' ) ),
					),
					array(
						'icon'    => 'help-circle',
						'title'   => __( 'Answer explanations', 'prepgro-theme' ),
						'body'    => __( 'Every question explained — why the right answer is right and what your pick assumed.', 'prepgro-theme' ),
						'bullets' => array( __( 'Step-by-step working', 'prepgro-theme' ), __( 'Linked back to the lesson', 'prepgro-theme' ), __( 'Common-trap notes', 'prepgro-theme' ) ),
					),
					array(
						'icon'    => 'trend-up',
						'title'   => __( 'Progress by skill', 'prepgro-theme' ),
						'body'    => __( 'A trend per sub-skill, so you can see a fix hold — or slip back under time pressure.', 'prepgro-theme' ),
						'bullets' => array( __( 'Score movement per skill', 'prepgro-theme' ), __( 'Speed and accuracy split out', 'prepgro-theme' ), __( 'Retake prompts on slipping skills', 'prepgro-theme' ) ),
					),
				),
				'photo'   => array(
					'mod'  => 'pgt_module_photo_excel',
					'file' => 'photo-student-desk.jpg',
					'alt'  => __( 'A student mid practice test, timer visible.', 'prepgro-theme' ),
				),
				'proof'   => array(
					'eyebrow' => __( 'What practice moves', 'prepgro-theme' ),
					'title'   => __( 'Twelve tests in, the weak skills look different.', 'prepgro-theme' ),
					'body'    => __( 'Sample student, SAT math: the two gaps from the first diagnostic are now the two strongest sections.', 'prepgro-theme' ),
					'bars'    => array(
						array( 'label' => __( 'Data analysis', 'prepgro-theme' ), 'value' => '42% → 79%', 'w' => 79, 'c' => 'var(--pgm-l1)' ),
						array( 'label' => __( 'Word problems', 'prepgro-theme' ), 'value' => '38% → 74%', 'w' => 74, 'c' => 'var(--pgm-l2)' ),
						array( 'label' => __( 'Linear equations', 'prepgro-theme' ), 'value' => '84% → 91%', 'w' => 91, 'c' => 'var(--pgm-l3)' ),
						array( 'label' => __( 'Est. score', 'prepgro-theme' ), 'value' => '1260 → 1340', 'w' => 68, 'c' => 'var(--green-600)' ),
					),
				),
				'steps'   => array(
					array( 'n' => '01', 'title' => __( 'Pick a set', 'prepgro-theme' ), 'body' => __( 'A full mock, or a 12-question set aimed at one weak skill.', 'prepgro-theme' ) ),
					array( 'n' => '02', 'title' => __( 'Review every miss', 'prepgro-theme' ), 'body' => __( 'Explanations on each question, linked back to the lesson that fixes it.', 'prepgro-theme' ) ),
					array( 'n' => '03', 'title' => __( 'Retake until it holds', 'prepgro-theme' ), 'body' => __( 'Unlimited attempts. The trend tells you when to stop.', 'prepgro-theme' ) ),
				),
				'plan'    => array(
					'tag'   => __( 'Unlimited test pack', 'prepgro-theme' ),
					'price' => '$9.99',
					'unit'  => __( '/ month', 'prepgro-theme' ),
					'body'  => __( 'Unlimited practice and mock tests for one subject, with explanations and progress tracking. Cancel anytime.', 'prepgro-theme' ),
				),
				'next'    => array( 'evaluate', 'elevate' ),
			),
		);

		/**
		 * Filter the module landing page content sets.
		 *
		 * @param array $modules Module key => content.
		 */
		return apply_filters( 'pgt_module_page_content', $modules );
	}

	/**
	 * Cross-link labels for "the rest of the loop".
	 *
	 * @return array<string,array<string,string>>
	 */
	private function cross_links() {
		return array(
			'evaluate' => array(
				'label' => __( 'Evaluate', 'prepgro-theme' ),
				'sub'   => __( 'Start with the free readiness check', 'prepgro-theme' ),
				'icon'  => 'circle-check-big',
				'url'   => home_url( '/evaluate/' ),
			),
			'elevate'  => array(
				'label' => __( 'Elevate', 'prepgro-theme' ),
				'sub'   => __( 'Lessons and a tutor for those gaps', 'prepgro-theme' ),
				'icon'  => 'book-open',
				'url'   => home_url( '/elevate/' ),
			),
			'excel'    => array(
				'label' => __( 'Excel', 'prepgro-theme' ),
				'sub'   => __( 'Unlimited practice until it holds', 'prepgro-theme' ),
				'icon'  => 'trend-up',
				'url'   => home_url( '/excel/' ),
			),
		);
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Render.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * Render a module landing page.
	 *
	 * @param array $atts Shortcode attributes: module.
	 * @return string
	 */
	public function render( $atts = array() ) {
		$atts   = shortcode_atts( array( 'module' => '' ), $atts, 'pgt_module_page' );
		$key    = $atts['module'] ? $atts['module'] : $this->current_module();
		$mods   = $this->modules();
		if ( ! isset( $mods[ $key ] ) ) {
			return '';
		}
		$m = $mods[ $key ];

		// Pillar switched off: keep the URL and the hero (the page still has
		// to answer "what is Excel?" for anyone who lands on it, and the URL
		// keeps its inbound links and search presence), but nothing below it.
		// The sections that go are the ones that make promises the site can no
		// longer keep — what's inside, the proof band, the how, and above all
		// the plan with its price and its buy button.
		if ( ! $this->pillar_on( $key ) ) {
			return $this->hero( $key, $m, true ) . $this->coming_soon( $key, $m );
		}

		return $this->hero( $key, $m )
			. $this->inside( $m )
			. $this->proof_band( $m )
			. $this->how( $m )
			. $this->plan( $key, $m );
	}

	/**
	 * The body a switched-off pillar gets instead of its five sections: one
	 * panel that says so plainly, plus the pillars that ARE live so the
	 * visitor leaves with somewhere to go rather than a dead end.
	 *
	 * @param string $key Module key.
	 * @param array  $m   Content.
	 * @return string
	 */
	private function coming_soon( $key, $m ) {
		$links = $this->cross_links();
		$rest  = '';
		foreach ( (array) $m['next'] as $other ) {
			if ( ! isset( $links[ $other ] ) || ! $this->pillar_on( $other ) ) {
				continue;
			}
			$l     = $links[ $other ];
			$rest .= '<a class="pgm-next pgm-next--' . esc_attr( $other ) . '" href="' . esc_url( $l['url'] ) . '">'
				. '<span class="pgm-next__icon">' . Icons::svg( $l['icon'], array( 'size' => 17, 'stroke' => 1.9 ) ) . '</span>'
				. '<span class="pgm-next__copy"><span class="pgm-next__label">' . esc_html( $l['label'] ) . '</span>'
				. '<span class="pgm-next__sub">' . esc_html( $l['sub'] ) . '</span></span>'
				. '<span class="pgm-next__arrow">' . Icons::svg( 'arrow-right', array( 'size' => 15, 'stroke' => 2 ) ) . '</span>'
				. '</a>';
		}

		$onward = $rest
			? '<div class="pgm-nextcol"><p class="pgm-kicker">' . esc_html__( 'Available now', 'prepgro-theme' ) . '</p>' . $rest . '</div>'
			: '';

		// Countdown, when the admin has set a launch date for this pillar.
		// With one, the body copy can name the date instead of saying "soon".
		$countdown = function_exists( 'pgt_countdown' ) ? pgt_countdown( $key ) : '';
		$body      = $countdown
			? __( 'We are still building this part of prepGro. Nothing here can be bought or booked yet — this page is where it lands when it opens.', 'prepgro-theme' )
			: __( 'We are still building this part of prepGro. Nothing here can be bought or booked yet — when it opens, this page is where it lands.', 'prepgro-theme' );

		return '<section class="pgm-section pgm-section--last"><div class="pgm-inner pgm-soonrow">'
			. '<div class="pgm-soonpanel">'
			. '<span class="pgm-soon">' . esc_html__( 'Coming soon', 'prepgro-theme' ) . '</span>'
			. '<h2 class="pgm-soonpanel__title">'
			/* translators: %s: pillar name, e.g. Excel. */
			. esc_html( sprintf( __( '%s is not open yet', 'prepgro-theme' ), $m['eyebrow'] ) )
			. '</h2>'
			. '<p class="pgm-soonpanel__body">' . esc_html( $body ) . '</p>'
			. $countdown
			. '</div>'
			. $onward
			. '</div></section>';
	}

	/**
	 * Resolve a CTA to a URL. `pricing => true` routes through the pricing
	 * level rule (§6/§7) rather than an all-SKU page.
	 *
	 * @param array $cta CTA spec.
	 * @return string
	 */
	private function cta_url( $cta ) {
		if ( ! empty( $cta['pricing'] ) ) {
			return Pricing_Levels::url();
		}
		return isset( $cta['url'] ) ? $cta['url'] : home_url( '/' );
	}

	/**
	 * Section 1 — dark hero. Same --neutral-950 surface and the same two
	 * radial washes as the homepage dark band; no new colours.
	 *
	 * @param string $key  Module key.
	 * @param array  $m    Content.
	 * @param bool   $soon Pillar is switched off: badge the eyebrow and drop
	 *                     the CTA pair. The stats and the infographic stay —
	 *                     they describe what the pillar IS, which is still
	 *                     true, and a hero stripped to a headline reads like
	 *                     a broken page rather than a deliberate one.
	 * @return string
	 */
	private function hero( $key, $m, $soon = false ) {
		$stats = '';
		foreach ( $m['stats'] as $st ) {
			// A closed pillar shows no price. The stat is flagged in modules()
			// rather than sniffed for a '$' so it survives translation, a
			// currency change and the country profile — and so "$0 · no card
			// needed" goes too: on a pillar nobody can sign up for, a free
			// price is still a price, and the panel below already says nothing
			// here can be bought yet.
			if ( $soon && ! empty( $st['price'] ) ) {
				continue;
			}
			$stats .= '<div class="pgm-stat"><p class="pgm-stat__v">' . esc_html( $st['value'] ) . '</p>'
				. '<p class="pgm-stat__l">' . esc_html( $st['label'] ) . '</p></div>';
		}

		$badge = $soon
			? '<span class="pgm-soon pgm-soon--onhero">' . esc_html__( 'Coming soon', 'prepgro-theme' ) . '</span>'
			: '';

		$cta = $soon
			? ''
			: '<div class="pgm-hero__cta">'
				. '<a class="pgm-btn pgm-btn--primary" href="' . esc_url( $this->cta_url( $m['cta'] ) ) . '">' . esc_html( $m['cta']['label'] ) . '</a>'
				. '<a class="pgm-btn pgm-btn--quiet" href="' . esc_url( $this->cta_url( $m['alt'] ) ) . '">' . esc_html( $m['alt']['label'] ) . '</a>'
				. '</div>';

		return '<section class="pgm-hero">'
			. '<div class="pgm-hero__wash" aria-hidden="true"></div>'
			. '<div class="pgm-hero__inner">'
			. '<div class="pgm-hero__copy">'
			. '<div class="pgm-eyebrowrow">'
			. '<span class="pgm-iconchip">' . Icons::svg( $m['icon'], array( 'size' => 16, 'stroke' => 1.9 ) ) . '</span>'
			. '<span class="pgm-eyebrow">' . esc_html( $m['eyebrow'] ) . '</span>'
			. $badge
			. '</div>'
			. '<h1 class="pgm-hero__title">' . esc_html( $m['title'] ) . '</h1>'
			. '<p class="pgm-hero__body">' . esc_html( $m['body'] ) . '</p>'
			. $cta
			. '<div class="pgm-stats">' . $stats . '</div>'
			. '</div>'
			. '<figure class="pgm-fig" id="' . esc_attr( 'pgm-fig-' . $key ) . '">'
			. '<figcaption class="pgm-fig__head">'
			. '<span class="pgm-fig__title">' . esc_html( $m['fig']['title'] ) . '</span>'
			. '<span class="pgm-fig__note">' . esc_html( $m['fig']['note'] ) . '</span>'
			. '</figcaption>'
			. $this->infographic( $key )
			. '</figure>'
			. '</div></section>';
	}

	/**
	 * The hero infographic — markup, not an image, rendered from data.
	 *
	 * @param string $key Module key.
	 * @return string
	 */
	private function infographic( $key ) {
		switch ( $key ) {
			case 'evaluate':
				return $this->donut_figure(
					68,
					array(
						array( 'label' => __( 'Linear equations', 'prepgro-theme' ), 'v' => 84, 'c' => 'var(--pgm-g3)' ),
						array( 'label' => __( 'Reading evidence', 'prepgro-theme' ), 'v' => 71, 'c' => 'var(--pgm-g3)' ),
						array( 'label' => __( 'Data analysis', 'prepgro-theme' ), 'v' => 42, 'c' => 'var(--amber-500)' ),
						array( 'label' => __( 'Word problems', 'prepgro-theme' ), 'v' => 38, 'c' => 'var(--red-500)' ),
					),
					__( 'Two skills are holding the score down. Both are fixable in four weeks.', 'prepgro-theme' )
				);
			case 'elevate':
				return $this->week_figure(
					array(
						// Each day: stacked segments, bottom-up, as % of the column.
						array( array( 'h' => 34, 'k' => 'self' ) ),
						array( array( 'h' => 22, 'k' => 'class' ), array( 'h' => 30, 'k' => 'self' ) ),
						array( array( 'h' => 44, 'k' => 'self' ) ),
						array( array( 'h' => 38, 'k' => 'assign' ), array( 'h' => 26, 'k' => 'self' ) ),
						array( array( 'h' => 30, 'k' => 'self' ) ),
						array( array( 'h' => 56, 'k' => 'self' ) ),
						array( array( 'h' => 12, 'k' => 'rest' ) ),
					)
				);
			case 'excel':
				return $this->trend_figure(
					array( 116, 104, 88, 66, 52, 34 ),
					array(
						array( 'v' => '1340', 'l' => __( 'est. score now', 'prepgro-theme' ), 'up' => false ),
						array( 'v' => '+80', 'l' => __( 'since diagnostic', 'prepgro-theme' ), 'up' => true ),
						array( 'v' => '12', 'l' => __( 'tests taken', 'prepgro-theme' ), 'up' => false ),
					)
				);
		}
		return '';
	}

	/**
	 * Evaluate — an SVG donut plus sub-skill bars.
	 *
	 * The ring is one circle: r=50 gives a circumference of ~314, so the
	 * dash offset is 314 × (1 − pct/100).
	 *
	 * @param int    $pct   Readiness percentage.
	 * @param array  $bars  Sub-skill rows.
	 * @param string $note  Closing note.
	 * @return string
	 */
	private function donut_figure( $pct, $bars, $note ) {
		$circ   = 314;
		$offset = round( $circ * ( 1 - ( max( 0, min( 100, (int) $pct ) ) / 100 ) ) );

		$rows = '';
		foreach ( $bars as $b ) {
			$rows .= '<div class="pgm-bar">'
				. '<div class="pgm-bar__head"><span>' . esc_html( $b['label'] ) . '</span>'
				. '<span class="pgm-mono">' . esc_html( $b['v'] ) . '%</span></div>'
				. '<div class="pgm-bar__track"><span style="width:' . esc_attr( (int) $b['v'] ) . '%;background:' . esc_attr( $b['c'] ) . '"></span></div>'
				. '</div>';
		}

		return '<div class="pgm-donutwrap">'
			. '<div class="pgm-donut">'
			. '<svg viewBox="0 0 120 120" aria-hidden="true">'
			. '<circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.14)" stroke-width="12"></circle>'
			. '<circle cx="60" cy="60" r="50" fill="none" stroke="var(--pgm-g2, var(--blue-500))" stroke-width="12" stroke-linecap="round"'
			. ' stroke-dasharray="' . esc_attr( $circ ) . '" stroke-dashoffset="' . esc_attr( $offset ) . '"></circle>'
			. '</svg>'
			. '<div class="pgm-donut__label">'
			. '<span class="pgm-donut__pct pgm-mono">' . esc_html( $pct ) . '<span>%</span></span>'
			. '<span class="pgm-donut__cap">' . esc_html__( 'readiness', 'prepgro-theme' ) . '</span>'
			. '</div></div>'
			. '<div class="pgm-bars">' . $rows . '</div>'
			. '</div>'
			. '<p class="pgm-fig__foot">' . esc_html( $note ) . '</p>';
	}

	/**
	 * Elevate — a 7-column week chart of stacked spans.
	 *
	 * @param array $week Seven days, each an array of {h, k} segments.
	 * @return string
	 */
	private function week_figure( $week ) {
		$days = array(
			__( 'M', 'prepgro-theme' ),
			__( 'T', 'prepgro-theme' ),
			__( 'W', 'prepgro-theme' ),
			__( 'T', 'prepgro-theme' ),
			__( 'F', 'prepgro-theme' ),
			__( 'S', 'prepgro-theme' ),
			__( 'S', 'prepgro-theme' ),
		);

		$cols = '';
		foreach ( $week as $segments ) {
			$stack = '';
			foreach ( $segments as $seg ) {
				$stack .= '<span class="pgm-week__seg pgm-week__seg--' . esc_attr( $seg['k'] ) . '" style="height:' . esc_attr( (int) $seg['h'] ) . '%"></span>';
			}
			$cols .= '<div class="pgm-week__col">' . $stack . '</div>';
		}

		$labels = '';
		foreach ( $days as $d ) {
			$labels .= '<span>' . esc_html( $d ) . '</span>';
		}

		return '<div class="pgm-week" role="img" aria-label="' . esc_attr__( 'A sample week: live classes, tutor assignments and self study.', 'prepgro-theme' ) . '">' . $cols . '</div>'
			. '<div class="pgm-week__labels">' . $labels . '</div>'
			. '<div class="pgm-legend">'
			. '<span class="pgm-legend__item"><i class="pgm-legend__sw pgm-legend__sw--class"></i>' . esc_html__( 'Live 1:1 class', 'prepgro-theme' ) . '</span>'
			. '<span class="pgm-legend__item"><i class="pgm-legend__sw pgm-legend__sw--assign"></i>' . esc_html__( 'Tutor assignment', 'prepgro-theme' ) . '</span>'
			. '<span class="pgm-legend__item"><i class="pgm-legend__sw pgm-legend__sw--self"></i>' . esc_html__( 'Self study', 'prepgro-theme' ) . '</span>'
			. '</div>';
	}

	/**
	 * Excel — a 320×150 SVG area + line trend.
	 *
	 * @param array $ys    Six y-values on the 150-unit canvas (lower = higher score).
	 * @param array $stats Three closing stats.
	 * @return string
	 */
	private function trend_figure( $ys, $stats ) {
		$xs     = array( 14, 74, 134, 194, 254, 306 );
		$points = array();
		foreach ( $ys as $i => $y ) {
			$points[] = $xs[ $i ] . ',' . (int) $y;
		}
		$line = implode( ' ', $points );
		$area = $line . ' ' . end( $xs ) . ',140 ' . $xs[0] . ',140';

		$dots = '';
		foreach ( array( 0, 2, 4 ) as $i ) {
			$dots .= '<circle cx="' . esc_attr( $xs[ $i ] ) . '" cy="' . esc_attr( (int) $ys[ $i ] ) . '" r="4" fill="var(--pgm-g2, var(--blue-500))"></circle>';
		}
		$dots .= '<circle cx="' . esc_attr( end( $xs ) ) . '" cy="' . esc_attr( (int) end( $ys ) ) . '" r="5.5" fill="#fff"></circle>';

		$facts = '';
		foreach ( $stats as $s ) {
			$facts .= '<div class="pgm-trendstat">'
				. '<p class="pgm-trendstat__v pgm-mono' . ( $s['up'] ? ' is-up' : '' ) . '">' . esc_html( $s['v'] ) . '</p>'
				. '<p class="pgm-trendstat__l">' . esc_html( $s['l'] ) . '</p></div>';
		}

		return '<svg class="pgm-trend" viewBox="0 0 320 150" role="img" aria-label="' . esc_attr__( 'A sample score trend rising across twelve practice tests.', 'prepgro-theme' ) . '">'
			. '<line x1="0" y1="30" x2="320" y2="30" stroke="rgba(255,255,255,.09)" stroke-width="1"></line>'
			. '<line x1="0" y1="70" x2="320" y2="70" stroke="rgba(255,255,255,.09)" stroke-width="1"></line>'
			. '<line x1="0" y1="110" x2="320" y2="110" stroke="rgba(255,255,255,.09)" stroke-width="1"></line>'
			. '<polygon points="' . esc_attr( $area ) . '" fill="var(--pgm-area, rgba(10,132,255,.16))"></polygon>'
			. '<polyline points="' . esc_attr( $line ) . '" fill="none" stroke="var(--pgm-g2, var(--blue-500))" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>'
			. $dots
			. '</svg>'
			. '<div class="pgm-trend__labels"><span>' . esc_html__( 'Test 1', 'prepgro-theme' ) . '</span><span>' . esc_html__( 'Test 4', 'prepgro-theme' ) . '</span>'
			. '<span>' . esc_html__( 'Test 8', 'prepgro-theme' ) . '</span><span>' . esc_html__( 'Test 12', 'prepgro-theme' ) . '</span></div>'
			. '<div class="pgm-trendstats">' . $facts . '</div>';
	}

	/**
	 * Section 2 — what's inside.
	 *
	 * @param array $m Content.
	 * @return string
	 */
	private function inside( $m ) {
		$cards = '';
		foreach ( $m['pillars'] as $p ) {
			$bullets = '';
			foreach ( $p['bullets'] as $b ) {
				$bullets .= '<div class="pgm-check">'
					. Icons::svg( 'circle-check-big', array( 'size' => 13, 'stroke' => 2.6 ) )
					. '<span>' . esc_html( $b ) . '</span></div>';
			}
			$cards .= '<article class="pgm-pillar">'
				. '<span class="pgm-pillar__icon">' . Icons::svg( $p['icon'], array( 'size' => 19, 'stroke' => 1.9 ) ) . '</span>'
				. '<h3>' . esc_html( $p['title'] ) . '</h3>'
				. '<p>' . esc_html( $p['body'] ) . '</p>'
				. '<div class="pgm-pillar__list">' . $bullets . '</div>'
				. '</article>';
		}

		return '<section class="pgm-section"><div class="pgm-inner">'
			. '<h2 class="pgm-h2">' . esc_html__( 'What’s inside', 'prepgro-theme' ) . '</h2>'
			. '<p class="pgm-sub">' . esc_html( $m['inside'] ) . '</p>'
			. '<div class="pgm-pillars">' . $cards . '</div>'
			. '</div></section>';
	}

	/**
	 * Section 3 — photo + proof band. A fixed two-part row, not auto-fit.
	 *
	 * @param array $m Content.
	 * @return string
	 */
	private function proof_band( $m ) {
		// A featured image on the page itself wins, per A5; otherwise the slot
		// rotates between whatever the owner uploaded in the Customizer.
		$thumb = (int) get_post_thumbnail_id();

		$bars = '';
		foreach ( $m['proof']['bars'] as $b ) {
			$bars .= '<div class="pgm-proofbar">'
				. '<div class="pgm-proofbar__head"><span>' . esc_html( $b['label'] ) . '</span>'
				. '<span class="pgm-mono">' . esc_html( $b['value'] ) . '</span></div>'
				. '<div class="pgm-proofbar__track"><span style="width:' . esc_attr( (int) $b['w'] ) . '%;background:' . esc_attr( $b['c'] ) . '"></span></div>'
				. '</div>';
		}

		return '<section class="pgm-band"><div class="pgm-band__row">'
			. '<div class="pgm-band__photo">'
			. Media::slot(
				$m['photo']['mod'],
				array(
					'attachment' => $thumb,
					'alt'        => $m['photo']['alt'],
					'height'     => '340px',
					'radius'     => '20px',
					'sizes'      => '(max-width: 900px) 100vw, 48vw',
				)
			)
			. '</div>'
			. '<div class="pgm-band__copy">'
			. '<span class="pgm-band__eyebrow">' . esc_html( $m['proof']['eyebrow'] ) . '</span>'
			. '<h2 class="pgm-band__title">' . esc_html( $m['proof']['title'] ) . '</h2>'
			. '<p class="pgm-band__body">' . esc_html( $m['proof']['body'] ) . '</p>'
			. '<div class="pgm-proofbars">' . $bars . '</div>'
			. '<p class="pgm-band__sample">' . Media::sample_badge() . '</p>'
			. '</div></div></section>';
	}

	/**
	 * Section 4 — how it works.
	 *
	 * @param array $m Content.
	 * @return string
	 */
	private function how( $m ) {
		$steps = '';
		foreach ( $m['steps'] as $s ) {
			$steps .= '<div class="pgm-step">'
				. '<span class="pgm-step__n pgm-mono">' . esc_html( $s['n'] ) . '</span>'
				. '<p class="pgm-step__t">' . esc_html( $s['title'] ) . '</p>'
				. '<p class="pgm-step__b">' . esc_html( $s['body'] ) . '</p>'
				. '</div>';
		}

		return '<section class="pgm-section"><div class="pgm-inner">'
			. '<span class="pgm-kicker">' . esc_html__( 'How it works', 'prepgro-theme' ) . '</span>'
			. '<div class="pgm-steps">' . $steps . '</div>'
			. '</div></section>';
	}

	/**
	 * Section 5 — plan CTA + the rest of the loop.
	 *
	 * @param string $key Module key.
	 * @param array  $m   Content.
	 * @return string
	 */
	private function plan( $key, $m ) {
		$links = $this->cross_links();
		$rest  = '';
		foreach ( $m['next'] as $other ) {
			if ( ! isset( $links[ $other ] ) ) {
				continue;
			}
			$l    = $links[ $other ];
			$open = $this->pillar_on( $other );

			// pgm-next--{module}: this card links TO $other, so its icon
			// takes $other's colour (not the current page's) — a preview of
			// what you're headed to, and one more place the three pillars
			// read as distinct rather than one repeated blue.
			//
			// A switched-off pillar keeps its card AND its link — the loop is
			// the product, and its landing page is the one page in that pillar
			// still written to be read: it says what the pillar is, that it is
			// not open, and when it opens. The card is muted and badged so the
			// state is clear before the click rather than after it.
			$rest .= '<a class="pgm-next pgm-next--' . esc_attr( $other ) . ( $open ? '' : ' pgm-next--soon' ) . '" href="' . esc_url( $l['url'] ) . '">'
				. '<span class="pgm-next__icon">' . Icons::svg( $l['icon'], array( 'size' => 17, 'stroke' => 1.9 ) ) . '</span>'
				. '<span class="pgm-next__copy"><span class="pgm-next__label">' . esc_html( $l['label'] )
				. ( $open ? '' : ' <span class="pgm-soon">' . esc_html__( 'Coming soon', 'prepgro-theme' ) . '</span>' )
				. '</span>'
				. '<span class="pgm-next__sub">' . esc_html( $open ? $l['sub'] : __( 'See what it is and when it opens', 'prepgro-theme' ) ) . '</span></span>'
				. '<span class="pgm-next__arrow">' . Icons::svg( 'arrow-right', array( 'size' => 15, 'stroke' => 2 ) ) . '</span>'
				. '</a>';
		}

		return '<section class="pgm-section pgm-section--last"><div class="pgm-inner pgm-planrow">'
			. '<div class="pgm-plan">'
			. '<span class="pgm-plan__tag">' . esc_html( $m['plan']['tag'] ) . '</span>'
			. '<div class="pgm-plan__price"><span class="pgm-plan__figure">' . esc_html( $m['plan']['price'] ) . '</span>'
			. '<span class="pgm-plan__unit">' . esc_html( $m['plan']['unit'] ) . '</span></div>'
			. '<p class="pgm-plan__body">' . esc_html( $m['plan']['body'] ) . '</p>'
			. '<a class="pgm-plan__cta" href="' . esc_url( $this->cta_url( $m['cta'] ) ) . '">' . esc_html( $m['cta']['label'] ) . '</a>'
			. '</div>'
			. '<div class="pgm-nextcol">'
			. '<p class="pgm-kicker">' . esc_html__( 'The rest of the loop', 'prepgro-theme' ) . '</p>'
			. $rest
			. '</div>'
			. '</div></section>';
	}
}
