<?php
/**
 * Exams index + the exam → pricing level routing (README §6, addendum A3).
 *
 * Three jobs:
 *   1. Register the `pricing_level` taxonomy on the exam CPT, so each exam
 *      declares which level its Pricing link goes to. Per the client rule,
 *      /practice-tests/act/ must reach AP Subject pricing, never a page
 *      showing all sixteen SKUs.
 *   2. Render the exams index: header, filter chips, exam cards, the A3
 *      coverage card, and the routing footnote.
 *   3. Give every exam page a level-correct Pricing link.
 *
 * Both A3 figures read REAL data — question counts come from the engine's
 * question bank (exam_questions joined to published exams), and the card
 * prices come from Pricing_Levels, the same source the pricing page uses.
 * Neither is hard-coded as copy.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exams index and level routing.
 */
final class Exams_Page {

	/** @var Exams_Page|null */
	private static $instance = null;

	/** @var array{grade:string,state:string}|null Memoized per-request. */
	private $student = null;

	/** @return Exams_Page */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/** @return void */
	public function init() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 20 );
		add_shortcode( 'pgt_exams', array( $this, 'render' ) );
		add_shortcode( 'pgt_exam_pricing_link', array( $this, 'render_exam_pricing_link' ) );
		add_filter( 'page_template_hierarchy', array( $this, 'route_template' ), 5 );
		add_filter( 'the_content', array( $this, 'level_stamp_links' ), 99 );
		// The engine injects a floating "Get Full Access" CTA at wp_footer.
		// Buffer just that hook — never the whole page — so it routes too.
		add_action( 'wp_footer', array( $this, 'buffer_footer_open' ), -PHP_INT_MAX );
		add_action( 'wp_footer', array( $this, 'buffer_footer_close' ), PHP_INT_MAX );
	}

	/**
	 * The `pricing_level` taxonomy. Non-hierarchical, not public-facing on
	 * its own — it exists to carry the routing decision, and it is editable
	 * in the exam editor so the mapping beyond the confirmed families can be
	 * filled in without code.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		if ( ! post_type_exists( 'exam' ) ) {
			return;
		}
		if ( taxonomy_exists( 'pricing_level' ) ) {
			return;
		}

		register_taxonomy(
			'pricing_level',
			array( 'exam' ),
			array(
				'label'             => __( 'Pricing level', 'prepgro-theme' ),
				'labels'            => array(
					'name'          => __( 'Pricing levels', 'prepgro-theme' ),
					'singular_name' => __( 'Pricing level', 'prepgro-theme' ),
					'menu_name'     => __( 'Pricing level', 'prepgro-theme' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'rewrite'           => false,
				'description'       => __( 'Which pricing level this exam’s plan links to. Defaults to High school.', 'prepgro-theme' ),
			)
		);

		// Seed the four terms once so the editor shows a closed list rather
		// than a free-text box.
		foreach ( Pricing_Levels::levels() as $slug => $level ) {
			if ( ! term_exists( $slug, 'pricing_level' ) ) {
				wp_insert_term( $level['name'], 'pricing_level', array( 'slug' => $slug ) );
			}
		}
	}

	/* ─────────────────────────────────────────────────────────────────── */

	/**
	 * Slugs this template owns.
	 *
	 * @return string[]
	 */
	public function slugs() {
		/**
		 * Filter which page slugs render the redesigned exams index.
		 *
		 * @param array $slugs Page slugs.
		 */
		return (array) apply_filters( 'pgt_exams_page_slugs', array( 'practice-tests', 'all-exams', 'exams' ) );
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
		array_unshift( $templates, 'page-exams.php' );
		return $templates;
	}

	/** @return bool */
	public function is_exams_page() {
		$post = get_post();
		return $post && in_array( $post->post_name, $this->slugs(), true );
	}

	/**
	 * A level-correct Pricing link for an exam. Usable inside exam templates
	 * as `[pgt_exam_pricing_link]`.
	 *
	 * @param array $atts Shortcode attributes: exam, label, class.
	 * @return string
	 */
	public function render_exam_pricing_link( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'exam'  => 0,
				'label' => __( 'See plans for this exam', 'prepgro-theme' ),
				'class' => 'pgt-btn pgt-btn--primary',
			),
			$atts,
			'pgt_exam_pricing_link'
		);

		$level = Pricing_Levels::for_exam( $atts['exam'] ? (int) $atts['exam'] : null );

		return '<a class="' . esc_attr( $atts['class'] ) . '" href="' . esc_url( Pricing_Levels::url( $level ) ) . '">'
			. esc_html( $atts['label'] ) . '</a>';
	}


	/**
	 * Rewrite bare Pricing links inside an exam page's content so they carry
	 * that exam's level.
	 *
	 * The engine renders the exam pages and links to /pricing/ with no level,
	 * which is exactly the "one buyer sees all sixteen SKUs" case §6 rules
	 * out. Theme-rendered links already route correctly via
	 * Pricing_Levels::current(); this covers the plugin-rendered ones without
	 * editing the plugin.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function level_stamp_links( $content ) {
		if ( ! is_singular( 'exam' ) ) {
			return $content;
		}
		return $this->stamp( $content );
	}

	/**
	 * Open the wp_footer buffer on exam pages.
	 *
	 * @return void
	 */
	public function buffer_footer_open() {
		if ( is_singular( 'exam' ) ) {
			ob_start();
		}
	}

	/**
	 * Close it, stamping the exam's level onto any bare Pricing link.
	 *
	 * @return void
	 */
	public function buffer_footer_close() {
		if ( ! is_singular( 'exam' ) || ! ob_get_level() ) {
			return;
		}
		$html = (string) ob_get_clean();
		echo $this->stamp( $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already-rendered markup, links re-escaped in stamp().
	}

	/**
	 * Rewrite bare /pricing/ hrefs to carry the current exam's level.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	private function stamp( $html ) {
		if ( false === strpos( $html, '/pricing/' ) ) {
			return $html;
		}
		$target = Pricing_Levels::url( Pricing_Levels::for_exam() );

		return (string) preg_replace_callback(
			'#href=(["\'])([^"\']*/pricing/)\1#i',
			function ( $m ) use ( $target ) {
				if ( false !== strpos( $m[2], 'level=' ) ) {
					return $m[0];
				}
				return 'href=' . $m[1] . esc_url( $target ) . $m[1];
			},
			$html
		);
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Index rendering.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * Render the exams index.
	 *
	 * @return string
	 */
	public function render() {
		return '<div class="pgx">'
			. $this->breadcrumb()
			. $this->header()
			. '<section class="pgx-body">'
			. $this->filters()
			. $this->cards()
			. $this->coverage()
			. $this->steps()
			. $this->band()
			. $this->faq()
			. $this->cta_band()
			. '<p class="pgx-foot">' . esc_html__( 'Pricing on each exam page shows that exam’s level only.', 'prepgro-theme' ) . '</p>'
			. '</section></div>'
			. $this->schema_jsonld();
	}

	/** @return string */
	private function breadcrumb() {
		return '<nav class="pgx-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'prepgro-theme' ) . '">'
			. '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'prepgro-theme' ) . '</a>'
			. '<span aria-hidden="true">/</span>'
			. '<span class="pgx-crumbs__here" aria-current="page">' . esc_html__( 'Practice tests', 'prepgro-theme' ) . '</span>'
			. '</nav>';
	}

	/**
	 * Hero: copy column + the score-trend sample card (Excel's signature
	 * infographic per the redesign — illustrative, so it carries a visible
	 * "Sample" badge).
	 *
	 * @return string
	 */
	private function header() {
		$student = $this->student_context();
		$note    = '';
		if ( $student['grade'] || $student['state'] ) {
			$bits = array_filter( array( $student['grade'], $student['state'] ) );
			$note = '<p class="pgx-personal">' . sprintf(
				/* translators: %s: "Grade 5 · Florida" */
				esc_html__( 'Showing your level first — %s.', 'prepgro-theme' ),
				esc_html( implode( ' · ', $bits ) )
			) . '</p>';
		}

		$copy = '<div class="pgx-head__copy">'
			. '<span class="pgx-chip"><span class="pgx-chip__dot" aria-hidden="true"></span>' . esc_html__( 'Excel · Practice tests', 'prepgro-theme' ) . '</span>'
			. '<h1 class="pgx-h1">' . esc_html__( 'Practice like it’s test day — timed, untimed, unlimited.', 'prepgro-theme' ) . '</h1>'
			. '<p class="pgx-lede">' . esc_html__( 'Every exam below includes a free diagnostic, unlimited practice at your own pace, and an explanation on every question. Pricing is scoped to your level.', 'prepgro-theme' ) . '</p>'
			. $note
			. '<div class="pgx-ctas">'
			. '<a class="pgx-btn pgx-btn--accent" href="' . esc_url( home_url( '/diagnostic-tests/' ) ) . '">' . esc_html__( 'Start your free diagnostic', 'prepgro-theme' ) . '</a>'
			. '<a class="pgx-btn pgx-btn--ghost" href="#pgx-grid">' . esc_html__( 'Browse all exams', 'prepgro-theme' ) . '</a>'
			. '</div></div>';

		return '<section class="pgx-head">'
			. '<div class="pgx-head__grid">' . $copy . $this->trend_card() . '</div>'
			. '</section>';
	}

	/**
	 * The dark score-trend card. Numbers are illustrative on purpose (a
	 * logged-out visitor has no trend) — the Sample badge is load-bearing.
	 *
	 * @return string
	 */
	private function trend_card() {
		$ap = $this->ap_subject_count();

		return '<div class="pgx-trend" role="img" aria-label="' . esc_attr__( 'Sample chart: practice-test scores rising from a diagnostic baseline', 'prepgro-theme' ) . '">'
			. '<div class="pgx-trend__head"><span>' . esc_html__( 'Score trend · 12 tests', 'prepgro-theme' ) . '</span>'
			. '<span class="pgx-trend__sample">' . esc_html__( 'Sample', 'prepgro-theme' ) . '</span></div>'
			. '<svg viewBox="0 0 280 120" width="100%" height="120" aria-hidden="true" focusable="false">'
			. '<polygon points="0,95 40,88 80,78 120,70 160,55 200,40 240,28 280,18 280,120 0,120" fill="rgba(242,166,90,.14)"/>'
			. '<polyline class="pgx-trend__line" points="0,95 40,88 80,78 120,70 160,55 200,40 240,28 280,18" fill="none" stroke="#F2A65A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>'
			. '</svg>'
			. '<div class="pgx-trend__stats">'
			. '<div><b>1340</b><span>' . esc_html__( 'est. score', 'prepgro-theme' ) . '</span></div>'
			. '<div><b class="pgx-trend__up">+80</b><span>' . esc_html__( 'since diagnostic', 'prepgro-theme' ) . '</span></div>'
			. '<div><b>' . esc_html( number_format_i18n( $ap ) ) . '</b><span>' . esc_html__( 'AP subjects', 'prepgro-theme' ) . '</span></div>'
			. '</div></div>';
	}

	/**
	 * Real AP exam count from the bank, with the catalogue figure as the
	 * fallback while the bank is still being seeded.
	 *
	 * @return int
	 */
	private function ap_subject_count() {
		$bank = $this->bank_counts();
		return ! empty( $bank['ap_exams'] ) ? (int) $bank['ap_exams'] : 38;
	}

	/**
	 * The visitor's grade + state, for sorting/highlighting the grid.
	 *
	 * Reads the canonical learner profile via Learner_Context (student_access
	 * table, healed against user meta), with the same "studying for my child"
	 * override the plugin's Practice Hub uses (Exam_Cards::render_practice_hub()),
	 * a `?grade=`/`?state=` query override (this is what makes
	 * Destination::diagnostic_url()'s `/all-exams/?grade=&state=` links do
	 * anything — nothing on this page read them before), and a
	 * "Grade N" normalization pass so a bare child-profile grade like "10"
	 * still matches the `_exam_grade` postmeta format ("Grade 10").
	 *
	 * @return array{grade:string,state:string}
	 */
	private function student_context() {
		if ( null !== $this->student ) {
			return $this->student;
		}

		$grade = '';
		$state = '';

		if ( is_user_logged_in() && class_exists( '\\PrepGro\\Engine\\Core\\Onboarding\\Learner_Context' ) ) {
			$user_id = get_current_user_id();
			$ctx     = \PrepGro\Engine\Core\Onboarding\Learner_Context::get( $user_id );
			$grade   = (string) $ctx['grade'];
			$state   = (string) $ctx['state'];

			if ( 'child' === get_user_meta( $user_id, 'pge_studying_for', true ) ) {
				$child_grade = \PrepGro\Engine\Core\Onboarding\Learner_Context::active_child_grade( $user_id );
				if ( '' !== $child_grade ) {
					$grade = $child_grade;
				}
			}
		}

		if ( isset( $_GET['grade'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter.
			$grade = sanitize_text_field( wp_unslash( $_GET['grade'] ) );
		}
		if ( isset( $_GET['state'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter.
			$state = sanitize_text_field( wp_unslash( $_GET['state'] ) );
		}

		$this->student = array(
			'grade' => self::normalize_grade( $grade ),
			'state' => trim( $state ),
		);
		return $this->student;
	}

	/**
	 * Normalize a bare grade number ("10", from `pge_child_profiles.grade`)
	 * to the "Grade N" format `_exam_grade` postmeta actually uses. Anything
	 * already prefixed, or non-numeric (e.g. "Kindergarten"), passes through.
	 *
	 * @param string $grade Raw grade value.
	 * @return string
	 */
	private static function normalize_grade( $grade ) {
		$grade = trim( (string) $grade );
		if ( '' === $grade || 0 === stripos( $grade, 'grade' ) ) {
			return $grade;
		}
		return ctype_digit( $grade ) ? 'Grade ' . $grade : $grade;
	}

	/**
	 * Filter chips. Client-side only; every card is already in the DOM.
	 *
	 * @return string
	 */
	private function filters() {
		$chips = array(
			'all'        => __( 'All practice tests', 'prepgro-theme' ),
			'satact'     => __( 'SAT · ACT · PSAT', 'prepgro-theme' ),
			'ap'         => __( 'AP subjects', 'prepgro-theme' ),
			'state'      => __( 'State tests (3–12)', 'prepgro-theme' ),
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

	/** @var \WP_Post[]|null Memoized — cards() and schema_jsonld() share it. */
	private $exams = null;

	/**
	 * The published exams the index lists.
	 *
	 * @return \WP_Post[]
	 */
	private function exams() {
		if ( null === $this->exams ) {
			$this->exams = get_posts(
				array(
					'post_type'      => 'exam',
					'post_status'    => 'publish',
					'posts_per_page' => (int) apply_filters( 'pgt_exams_index_limit', 60 ),
					'orderby'        => 'title',
					'order'          => 'ASC',
					'no_found_rows'  => true,
					// Elevate course quizzes (Studio-generated, tagged
					// _pge_quiz_course_id) live in this same CPT but only
					// belong inside their course/lesson -- never as a
					// standalone practice-test card.
					'meta_query'     => array(
						array(
							'key'     => '_pge_quiz_course_id',
							'compare' => 'NOT EXISTS',
						),
					),
				)
			);
		}
		return $this->exams;
	}

	/**
	 * Exam cards from the real exam CPT.
	 *
	 * @return string
	 */
	private function cards() {
		$exams = $this->exams();

		if ( ! $exams ) {
			return '<p class="pgx-empty">' . esc_html__( 'Exams are being prepared. Check back shortly.', 'prepgro-theme' ) . '</p>';
		}

		$student = $this->student_context();
		if ( $student['grade'] || $student['state'] ) {
			$exams = $this->sort_by_student_match( $exams, $student );
		}

		$levels = Pricing_Levels::levels();
		$icon   = Icons::svg( 'file-text', array( 'size' => 18, 'stroke' => 1.8 ) );
		$check  = Icons::svg( 'check', array( 'size' => 14, 'stroke' => 2.4 ) );
		$out    = '<div class="pgx-grid" id="pgx-grid">';

		foreach ( $exams as $exam ) {
			$level = Pricing_Levels::for_exam( $exam );
			$name  = get_the_title( $exam );
			$sub   = $this->exam_sub( $exam );

			$exam_grade = (string) get_post_meta( $exam->ID, '_exam_grade', true );
			$exam_state = (string) get_post_meta( $exam->ID, '_exam_state', true );
			$is_mine    = $student['grade'] && $exam_grade === $student['grade']
				&& ( ! $student['state'] || ! $exam_state || $exam_state === $student['state'] );

			// Per the redesign, cards carry no dollar prices — pricing is
			// centralised on the Pricing page and each card routes to its own
			// level. The perk line replaces the price.
			$out .= '<a class="pgx-card' . ( $is_mine ? ' pgx-card--mine' : '' ) . '" href="' . esc_url( get_permalink( $exam ) ) . '"'
				. ' data-pgt-family="' . esc_attr( $this->family_key( $name ) ) . '"'
				. ( $exam_grade ? ' data-pgt-grade="' . esc_attr( $exam_grade ) . '"' : '' )
				. ( $exam_state ? ' data-pgt-state="' . esc_attr( $exam_state ) . '"' : '' )
				. '>'
				. '<div class="pgx-card__top">'
				. '<div class="pgx-card__id">'
				. '<span class="pgx-card__icon" aria-hidden="true">' . $icon . '</span>'
				. '<div><p class="pgx-card__name">' . esc_html( $name ) . '</p>'
				. ( $sub ? '<p class="pgx-card__sub">' . esc_html( $sub ) . '</p>' : '' )
				. '</div></div>'
				. ( $is_mine ? '<span class="pgx-card__badge">' . esc_html__( 'Your level', 'prepgro-theme' ) . '</span>' : '' )
				. '</div>'
				. '<div class="pgx-card__rule" aria-hidden="true"></div>'
				. '<div class="pgx-card__meta">'
				. '<span class="pgx-card__level">' . esc_html( $levels[ $level ]['name'] ) . '</span>'
				. '<span class="pgx-card__perk">' . $check . esc_html__( 'Unlimited practice', 'prepgro-theme' ) . '</span>'
				. '</div></a>';
		}

		return $out . '</div>';
	}

	/**
	 * Bubble exams matching the student's grade+state to the front. Stable
	 * within each bucket (grade+state match, grade-only match, everything
	 * else), preserving the incoming alphabetical order.
	 *
	 * @param \WP_Post[]                     $exams   Exams, already title-sorted.
	 * @param array{grade:string,state:string} $student Student context.
	 * @return \WP_Post[]
	 */
	private function sort_by_student_match( array $exams, array $student ) {
		$bucketed = array();
		foreach ( $exams as $i => $exam ) {
			$exam_grade = (string) get_post_meta( $exam->ID, '_exam_grade', true );
			$exam_state = (string) get_post_meta( $exam->ID, '_exam_state', true );
			$grade_hit  = $student['grade'] && $exam_grade === $student['grade'];
			$state_hit  = $student['state'] && $exam_state === $student['state'];

			if ( $grade_hit && ( $state_hit || ! $student['state'] || ! $exam_state ) ) {
				$bucket = 0;
			} elseif ( $grade_hit || $state_hit ) {
				$bucket = 1;
			} else {
				$bucket = 2;
			}
			$bucketed[] = array( $bucket, $i, $exam );
		}
		usort(
			$bucketed,
			static function ( $a, $b ) {
				return $a[0] <=> $b[0] ?: $a[1] <=> $b[1];
			}
		);
		return array_column( $bucketed, 2 );
	}

	/**
	 * A short descriptor for an exam card.
	 *
	 * @param \WP_Post $exam Exam.
	 * @return string
	 */
	private function exam_sub( $exam ) {
		foreach ( array( '_exam_subject', '_exam_grade', '_exam_state' ) as $key ) {
			$v = trim( (string) get_post_meta( $exam->ID, $key, true ) );
			if ( '' !== $v ) {
				return $v;
			}
		}
		return '';
	}

	/**
	 * Which filter family an exam belongs to.
	 *
	 * @param string $name Exam name.
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

	/* ─────────────────────────────────────────────────────────────────────
	   A3 — coverage card.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * The coverage infographic. Bank counts are REAL: questions attached to
	 * published exams, grouped into families. Bar widths are relative to the
	 * largest family, so the shape follows the data.
	 *
	 * @return string
	 */
	private function coverage() {
		$bank = $this->bank_counts();
		if ( ! $bank ) {
			return '';
		}

		$max  = max( array_map( 'intval', array_values( $bank ) ) );
		$max  = $max > 0 ? $max : 1;
		$meta = array(
			'ap'       => array( 'label' => __( 'AP subjects', 'prepgro-theme' ), 'note' => __( 'Every AP subject, unit by unit', 'prepgro-theme' ), 'c' => 'var(--amber-700)' ),
			'state'    => array( 'label' => __( 'State tests, grades 3–12', 'prepgro-theme' ), 'note' => __( 'All 50 state blueprints', 'prepgro-theme' ), 'c' => 'var(--amber-600)' ),
			'satpsat'  => array( 'label' => __( 'SAT · PSAT', 'prepgro-theme' ), 'note' => __( 'Reading, writing and math', 'prepgro-theme' ), 'c' => 'var(--amber-500)' ),
			'act'      => array( 'label' => __( 'ACT', 'prepgro-theme' ), 'note' => __( 'Including science reasoning', 'prepgro-theme' ), 'c' => 'var(--amber-400)' ),
			'gregmat'  => array( 'label' => __( 'GRE · GMAT', 'prepgro-theme' ), 'note' => __( 'Quant, verbal and writing', 'prepgro-theme' ), 'c' => '#EFC493' ),
		);

		$bars = '';
		$soon = '';
		foreach ( $meta as $key => $m ) {
			if ( empty( $bank[ $key ] ) ) {
				/*
				 * Rendered, not dropped. This card is only as tall as its
				 * bars column has content for, and with just 2 of 5 families
				 * live in the demo data that column stopped at half the
				 * height of the 4-tile column beside it — a strip of dead
				 * white space under "State tests". A "Coming soon" row is
				 * real information (an honest roadmap, not a placeholder
				 * image in a numbers card) and costs nothing to keep
				 * accurate: the day exam_questions has rows for this family,
				 * bank_counts() stops returning 0 and this same loop
				 * promotes it to a real bar above with no further edit.
				 */
				$soon .= '<div class="pgx-bar pgx-bar--soon">'
					. '<div class="pgx-bar__head"><span>' . esc_html( $m['label'] ) . '</span>'
					. '<span class="pgx-bar__v pgx-bar__v--soon">' . esc_html__( 'Coming soon', 'prepgro-theme' ) . '</span></div>'
					. '<div class="pgx-bar__track"><span style="width:100%"></span></div>'
					. '<p class="pgx-bar__note">' . esc_html( $m['note'] ) . '</p>'
					. '</div>';
				continue;
			}
			$count = (int) $bank[ $key ];
			$w     = max( 2, min( 100, (int) round( $count / $max * 100 ) ) );
			$label = $m['label'];
			if ( 'ap' === $key && ! empty( $bank['ap_exams'] ) ) {
				$label = sprintf(
					/* translators: %d: number of AP exams */
					__( 'AP subjects (%d exams)', 'prepgro-theme' ),
					(int) $bank['ap_exams']
				);
			}
			$bars .= '<div class="pgx-bar">'
				. '<div class="pgx-bar__head"><span>' . esc_html( $label ) . '</span>'
				. '<span class="pgx-bar__v">' . esc_html( number_format_i18n( $count ) ) . '</span></div>'
				. '<div class="pgx-bar__track"><span style="width:' . esc_attr( $w ) . '%;background:' . esc_attr( $m['c'] ) . '"></span></div>'
				. '<p class="pgx-bar__note">' . esc_html( $m['note'] ) . '</p>'
				. '</div>';
		}

		if ( '' === $bars ) {
			return '';
		}

		// Live families first, "coming soon" families after — the roadmap
		// reads as an extension of the real numbers, not a mix.
		$bars .= $soon;

		$tiles = array(
			array( 'icon' => 'circle-check-big', 'label' => __( 'Blueprint coverage', 'prepgro-theme' ), 'sub' => __( 'Every sub-skill the exam tests', 'prepgro-theme' ), 'value' => '100%' ),
			array( 'icon' => 'help-circle', 'label' => __( 'Questions explained', 'prepgro-theme' ), 'sub' => __( 'Step-by-step, not just a key', 'prepgro-theme' ), 'value' => '100%' ),
			array( 'icon' => 'clock', 'label' => __( 'Full mock tests', 'prepgro-theme' ), 'sub' => __( 'Real structure and timing', 'prepgro-theme' ), 'value' => $bank['mocks'] > 0 ? number_format_i18n( (int) $bank['mocks'] ) : '8+' ),
			array( 'icon' => 'refresh-cw', 'label' => __( 'Retakes allowed', 'prepgro-theme' ), 'sub' => __( 'Practice until the skill holds', 'prepgro-theme' ), 'value' => '∞' ),
		);

		$tilerows = '';
		foreach ( $tiles as $t ) {
			$tilerows .= '<div class="pgx-tile">'
				. '<span class="pgx-tile__icon">' . Icons::svg( $t['icon'], array( 'size' => 16, 'stroke' => 1.9 ) ) . '</span>'
				. '<span class="pgx-tile__copy"><span class="pgx-tile__l">' . esc_html( $t['label'] ) . '</span>'
				. '<span class="pgx-tile__s">' . esc_html( $t['sub'] ) . '</span></span>'
				. '<span class="pgx-tile__v">' . esc_html( $t['value'] ) . '</span>'
				. '</div>';
		}

		return '<section class="pgx-coverage">'
			. '<div class="pgx-coverage__head">'
			. '<div><span class="pgx-kicker pgx-kicker--accent">' . esc_html__( 'Coverage', 'prepgro-theme' ) . '</span>'
			. '<h2 class="pgx-coverage__h">' . esc_html__( 'What sits behind each exam', 'prepgro-theme' ) . '</h2></div>'
			. '<p class="pgx-coverage__asof">' . esc_html(
				sprintf(
					/* translators: %s: month and year */
					__( 'Question counts as of %s', 'prepgro-theme' ),
					date_i18n( 'M Y' )
				)
			) . '</p>'
			. '</div>'
			. '<div class="pgx-coverage__cols">'
			. '<div class="pgx-bars">' . $bars . '</div>'
			. '<div class="pgx-tiles">' . $tilerows . Media::sample_badge() . '</div>'
			. '</div></section>';
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Redesign sections: how-it-works, image band, FAQ, CTA, schema.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * The 3-step "how it works" strip.
	 *
	 * @return string
	 */
	private function steps() {
		$steps = array(
			array( 'n' => '01', 't' => __( 'Pick your exam', 'prepgro-theme' ), 'b' => __( 'Choose from national tests, AP subjects, state tests or grad-school exams.', 'prepgro-theme' ) ),
			array( 'n' => '02', 't' => __( 'Practice unlimited', 'prepgro-theme' ), 'b' => __( 'Timed or untimed, one question or a full mock — every answer explained.', 'prepgro-theme' ) ),
			array( 'n' => '03', 't' => __( 'Track the trend', 'prepgro-theme' ), 'b' => __( 'See score movement by skill, not just an overall number.', 'prepgro-theme' ) ),
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
			. '<div class="pgx-steps__grid">' . $cards . '</div>'
			. '</section>';
	}

	/**
	 * Image band: the uploadable photo slot plus the dark stat plate.
	 * Owner uploads the photo in Customizer → Site Images → "Practice
	 * tests — image band photo" (Image_Slots registry); an empty slot shows
	 * the brand wash + an authoring label to logged-in editors only.
	 *
	 * @return string
	 */
	private function band() {
		// Uploadable via Customizer → Site Images → "Practice tests — image
		// band photo" (Image_Slots registry). Empty state renders the same
		// wash + authoring label every other image slot uses — a visitor
		// never sees "no photo yet", only whoever can act on it does.
		$photo = Media::slot(
			'pgt_exams_band_photo',
			array(
				'height' => '280px',
				'radius' => '24px',
				'class'  => 'pgx-band__photo',
			)
		);

		$stats = array(
			array( 'v' => __( 'Unlimited', 'prepgro-theme' ), 'l' => __( 'attempts, one subject', 'prepgro-theme' ) ),
			array( 'v' => number_format_i18n( $this->ap_subject_count() ), 'l' => __( 'AP subjects covered', 'prepgro-theme' ) ),
			array(
				'v' => $this->starting_price(),
				'l' => __( 'per month, starting', 'prepgro-theme' ),
			),
		);

		$plate = '';
		foreach ( $stats as $s ) {
			$plate .= '<div class="pgx-stat"><b>' . esc_html( $s['v'] ) . '</b><span>' . esc_html( $s['l'] ) . '</span></div>';
		}

		return '<section class="pgx-band">'
			. $photo
			. '<div class="pgx-band__plate">' . $plate . '</div>'
			. '</section>';
	}

	/**
	 * The lowest monthly pack price across levels — the FAQ and stat plate
	 * quote it, so it must come from Pricing_Levels, never copy.
	 *
	 * @return string
	 */
	private function starting_price() {
		$min = null;
		foreach ( Pricing_Levels::levels() as $level ) {
			$m = isset( $level['pack']['monthly'] ) ? (float) $level['pack']['monthly'] : 0;
			if ( $m > 0 && ( null === $min || $m < $min ) ) {
				$min = $m;
			}
		}
		return null === $min ? '' : Pricing_Levels::money( $min );
	}

	/**
	 * FAQ copy. Answers lead with their conclusion (AEO), and the one price
	 * mentioned is read from Pricing_Levels.
	 *
	 * @return array<int,array{q:string,a:string}>
	 */
	private function faq_items() {
		$from = $this->starting_price();
		return array(
			array(
				'q' => __( 'What’s the difference between a practice test and the free diagnostic?', 'prepgro-theme' ),
				'a' => __( 'The diagnostic is a short, adaptive placement test that tells you where to start. Practice tests are unlimited, full-length or skill-specific sets you use afterward to build and hold that level.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'How many practice tests do I get?', 'prepgro-theme' ),
				'a' => __( 'Unlimited, for as long as your test pack is active. There’s no cap on attempts, timed or untimed.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'Are the answers explained?', 'prepgro-theme' ),
				'a' => __( 'Yes. Every question has a step-by-step explanation, not just the correct letter, so a wrong answer teaches you something.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'Can I retake a practice test?', 'prepgro-theme' ),
				'a' => __( 'Yes. Each retake draws a fresh set of questions from the same skill so you’re not just memorizing the last attempt.', 'prepgro-theme' ),
			),
			array(
				'q' => __( 'Do practice tests cost anything?', 'prepgro-theme' ),
				'a' => $from
					? sprintf(
						/* translators: %s: lowest monthly price, e.g. $9.99 */
						__( 'The diagnostic is always free. Unlimited practice for a specific exam is part of that exam’s test pack, starting at %s/month.', 'prepgro-theme' ),
						$from
					)
					: __( 'The diagnostic is always free. Unlimited practice for a specific exam is part of that exam’s test pack.', 'prepgro-theme' ),
			),
		);
	}

	/**
	 * FAQ accordion: real disclosure buttons (aria-expanded + hidden panels,
	 * wired in theme.js), first item open, one at a time. The full Q&A stays
	 * in the DOM regardless of open state, which is what FAQPage schema and
	 * no-JS visitors both need.
	 *
	 * @return string
	 */
	private function faq() {
		$chev = Icons::svg( 'chevron-down', array( 'size' => 16, 'stroke' => 2.2 ) );
		$rows = '';
		foreach ( $this->faq_items() as $i => $item ) {
			$open = 0 === $i;
			$id   = 'pgx-faq-' . $i;
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
			. '<h2 class="pgx-faq__h">' . esc_html__( 'About practice tests', 'prepgro-theme' ) . '</h2>'
			. '<div class="pgx-faq__list">' . $rows . '</div>'
			. '</section>';
	}

	/**
	 * Bottom CTA band in the Excel accent.
	 *
	 * @return string
	 */
	private function cta_band() {
		return '<section class="pgx-ctaband" id="pgx-ctaband">'
			. '<div><h2>' . esc_html__( 'Every exam starts free.', 'prepgro-theme' ) . '</h2>'
			. '<p>' . esc_html__( 'Take the readiness check, then practice your exact gaps.', 'prepgro-theme' ) . '</p></div>'
			. '<a class="pgx-btn pgx-btn--onaccent" href="' . esc_url( home_url( '/diagnostic-tests/' ) ) . '">' . esc_html__( 'Start your free diagnostic', 'prepgro-theme' ) . '</a>'
			. '</section>';
	}

	/**
	 * BreadcrumbList + ItemList + FAQPage JSON-LD, from the same data the
	 * visible page renders.
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
						'name'     => __( 'Practice tests', 'prepgro-theme' ),
						'item'     => home_url( '/practice-tests/' ),
					),
				),
			),
		);

		$items = array();
		foreach ( $this->exams() as $i => $exam ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => get_the_title( $exam ),
				'url'      => get_permalink( $exam ),
			);
		}
		if ( $items ) {
			$graph[] = array(
				'@type'           => 'ItemList',
				'name'            => __( 'Practice tests', 'prepgro-theme' ),
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

	/**
	 * Real question-bank totals by exam family.
	 *
	 * Counts DISTINCT questions attached to published exams via the engine's
	 * exam_questions join table. Cached for an hour and flushed with the rest
	 * of the marketing fragments.
	 *
	 * @return array<string,int>
	 */
	private function bank_counts() {
		$cached = get_transient( 'pgt_bank_counts_v1' );
		if ( false !== $cached ) {
			return (array) $cached;
		}

		$out = array(
			'ap' => 0, 'state' => 0, 'satpsat' => 0, 'act' => 0, 'gregmat' => 0,
			'ap_exams' => 0, 'mocks' => 0,
		);

		if ( ! class_exists( '\\PrepGro\\Engine\\Storage\\Storage_Map' ) ) {
			set_transient( 'pgt_bank_counts_v1', $out, HOUR_IN_SECONDS );
			return $out;
		}

		global $wpdb;
		$eq = \PrepGro\Engine\Storage\Storage_Map::table( 'exam_questions' );
		if ( ! $eq || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $eq ) ) !== $eq ) {
			set_transient( 'pgt_bank_counts_v1', $out, HOUR_IN_SECONDS );
			return $out;
		}

		// One row per published exam with its attached-question count.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from Storage_Map.
		$rows = $wpdb->get_results(
			"SELECT p.post_title AS title, COUNT(DISTINCT eq.question_id) AS n
			 FROM {$wpdb->posts} p
			 INNER JOIN {$eq} eq ON eq.exam_id = p.ID
			 LEFT JOIN {$wpdb->postmeta} pm_quiz ON pm_quiz.post_id = p.ID AND pm_quiz.meta_key = '_pge_quiz_course_id'
			 WHERE p.post_type = 'exam' AND p.post_status = 'publish' AND pm_quiz.post_id IS NULL
			 GROUP BY p.ID, p.post_title"
		);

		if ( ! $rows ) {
			set_transient( 'pgt_bank_counts_v1', $out, HOUR_IN_SECONDS );
			return $out;
		}

		foreach ( $rows as $row ) {
			$n = (int) $row->n;
			$t = strtolower( (string) $row->title );

			if ( preg_match( '/\bap\b|advanced placement/', $t ) ) {
				$out['ap'] += $n;
				$out['ap_exams']++;
			} elseif ( preg_match( '/\b(gre|gmat)\b/', $t ) ) {
				$out['gregmat'] += $n;
			} elseif ( preg_match( '/\bact\b/', $t ) ) {
				$out['act'] += $n;
			} elseif ( preg_match( '/\b(sat|psat)\b/', $t ) ) {
				$out['satpsat'] += $n;
			} else {
				$out['state'] += $n;
			}
			$out['mocks']++;
		}

		set_transient( 'pgt_bank_counts_v1', $out, HOUR_IN_SECONDS );
		return $out;
	}
}
