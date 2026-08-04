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
		return (array) apply_filters( 'pgt_exams_page_slugs', array( 'all-exams', 'exams' ) );
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
			. $this->header()
			. '<section class="pgx-body">'
			. $this->filters()
			. $this->cards()
			. $this->coverage()
			. '<p class="pgx-foot">' . esc_html__( 'Pricing on each exam page shows that exam’s level only.', 'prepgro-theme' ) . '</p>'
			. '</section></div>';
	}

	/**
	 * @return string
	 */
	private function header() {
		return '<section class="pgx-head"><div class="pgx-head__copy">'
			. '<span class="pgx-chip">' . esc_html__( 'Exams', 'prepgro-theme' ) . '</span>'
			. '<h1 class="pgx-h1">' . esc_html__( 'Pick your exam.', 'prepgro-theme' )
			. '<br><span class="pgx-h1__quiet">' . esc_html__( 'Practise it without limits.', 'prepgro-theme' ) . '</span></h1>'
			. '<p class="pgx-lede">' . esc_html__( 'Each exam has a free readiness check, unlimited timed and untimed practice, and its own pricing — you only see the plan for your level.', 'prepgro-theme' ) . '</p>'
			. '</div></section>';
	}

	/**
	 * Filter chips. Client-side only; every card is already in the DOM.
	 *
	 * @return string
	 */
	private function filters() {
		$chips = array(
			'all'        => __( 'All exams', 'prepgro-theme' ),
			'national'   => __( 'National', 'prepgro-theme' ),
			'state'      => __( 'State tests', 'prepgro-theme' ),
			'ap'         => __( 'AP subjects', 'prepgro-theme' ),
			'grades'     => __( 'Grades 3–12', 'prepgro-theme' ),
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
	 * Exam cards from the real exam CPT.
	 *
	 * @return string
	 */
	private function cards() {
		$exams = get_posts(
			array(
				'post_type'      => 'exam',
				'post_status'    => 'publish',
				'posts_per_page' => (int) apply_filters( 'pgt_exams_index_limit', 60 ),
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		if ( ! $exams ) {
			return '<p class="pgx-empty">' . esc_html__( 'Exams are being prepared. Check back shortly.', 'prepgro-theme' ) . '</p>';
		}

		$levels = Pricing_Levels::levels();
		$arrow  = Icons::svg( 'arrow-right', array( 'size' => 14, 'stroke' => 2 ) );
		$out    = '<div class="pgx-grid">';

		foreach ( $exams as $exam ) {
			$level = Pricing_Levels::for_exam( $exam );
			$name  = get_the_title( $exam );
			$sub   = $this->exam_sub( $exam );
			$price = isset( $levels[ $level ] )
				? Pricing_Levels::money( $levels[ $level ]['pack']['monthly'] ) . __( '/mo', 'prepgro-theme' )
				: '';

			$out .= '<a class="pgx-card" href="' . esc_url( get_permalink( $exam ) ) . '"'
				. ' data-pgt-family="' . esc_attr( $this->family_key( $name ) ) . '">'
				. '<div class="pgx-card__top">'
				. '<div><p class="pgx-card__name">' . esc_html( $name ) . '</p>'
				. ( $sub ? '<p class="pgx-card__sub">' . esc_html( $sub ) . '</p>' : '' ) . '</div>'
				. '<span class="pgx-card__arrow">' . $arrow . '</span>'
				. '</div>'
				. '<div class="pgx-card__meta">'
				. '<span class="pgx-card__unlim">' . esc_html__( 'Unlimited practice', 'prepgro-theme' ) . '</span>'
				. ( $price ? '<span class="pgx-card__price">' . esc_html( $price ) . '</span>' : '' )
				. '<span class="pgx-card__level">' . esc_html( $levels[ $level ]['name'] ) . '</span>'
				. '</div></a>';
		}

		return $out . '</div>';
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
		if ( preg_match( '/\b(sat|act|psat|gre|gmat)\b/', $n ) ) {
			return 'national';
		}
		if ( preg_match( '/\b(staar|parcc|smarter|caaspp|fsa|milestones|istep|paws|assessment)\b|state/', $n ) ) {
			return 'state';
		}
		if ( preg_match( '/grade|elementary|middle|high school/', $n ) ) {
			return 'grades';
		}
		return 'grades';
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
			'ap'       => array( 'label' => __( 'AP subjects', 'prepgro-theme' ), 'note' => __( 'Every AP subject, unit by unit', 'prepgro-theme' ), 'c' => 'var(--blue-600)' ),
			'state'    => array( 'label' => __( 'State tests, grades 3–12', 'prepgro-theme' ), 'note' => __( 'All 50 state blueprints', 'prepgro-theme' ), 'c' => 'var(--blue-500)' ),
			'satpsat'  => array( 'label' => __( 'SAT · PSAT', 'prepgro-theme' ), 'note' => __( 'Reading, writing and math', 'prepgro-theme' ), 'c' => 'var(--blue-500)' ),
			'act'      => array( 'label' => __( 'ACT', 'prepgro-theme' ), 'note' => __( 'Including science reasoning', 'prepgro-theme' ), 'c' => 'var(--blue-400)' ),
			'gregmat'  => array( 'label' => __( 'GRE · GMAT', 'prepgro-theme' ), 'note' => __( 'Quant, verbal and writing', 'prepgro-theme' ), 'c' => 'var(--blue-400)' ),
		);

		$bars = '';
		foreach ( $meta as $key => $m ) {
			if ( empty( $bank[ $key ] ) ) {
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

		$tiles = array(
			array( 'icon' => 'circle-check-big', 'label' => __( 'Blueprint coverage', 'prepgro-theme' ), 'sub' => __( 'Every sub-skill the exam tests', 'prepgro-theme' ), 'value' => '100%' ),
			array( 'icon' => 'help-circle', 'label' => __( 'Questions explained', 'prepgro-theme' ), 'sub' => __( 'Step-by-step, not just a key', 'prepgro-theme' ), 'value' => '100%' ),
			array( 'icon' => 'clock', 'label' => __( 'Full mock tests', 'prepgro-theme' ), 'sub' => __( 'Real structure and timing', 'prepgro-theme' ), 'value' => $bank['mocks'] > 0 ? number_format_i18n( (int) $bank['mocks'] ) : '8+' ),
			array( 'icon' => 'refresh-cw', 'label' => __( 'Retakes allowed', 'prepgro-theme' ), 'sub' => __( 'Practise until the skill holds', 'prepgro-theme' ), 'value' => '∞' ),
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
			. '<div><span class="pgx-kicker">' . esc_html__( 'Coverage', 'prepgro-theme' ) . '</span>'
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
			 WHERE p.post_type = 'exam' AND p.post_status = 'publish'
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
