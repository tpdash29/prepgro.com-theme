<?php
/**
 * Homepage Sections — dynamic, data-driven blocks for the front page.
 *
 * Each section is a shortcode (same pattern as [pge_header] in class-chrome.php)
 * so templates/front-page.html can stay a static block template while these
 * blocks pull real data at render time. Every section returns '' (renders
 * nothing) when it has no real content to show — no placeholder/fake data is
 * ever fabricated here. See CLAUDE.md "Free Access Day" / testimonials option
 * pattern for the precedent this follows.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the dynamic homepage sections.
 */
final class Homepage_Sections {

	/** @var Homepage_Sections|null */
	private static $instance = null;

	/** @return Homepage_Sections */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/** @return void */
	public function init() {
		add_shortcode( 'pgt_ticker', array( $this, 'render_ticker' ) );
		add_shortcode( 'pgt_stats', array( $this, 'render_stats' ) );
		add_shortcode( 'pgt_categories', array( $this, 'render_categories' ) );
		add_shortcode( 'pgt_grades', array( $this, 'render_grades' ) );
		add_shortcode( 'pgt_states', array( $this, 'render_states' ) );
		add_shortcode( 'pgt_latest_tests', array( $this, 'render_latest_tests' ) );
		add_shortcode( 'pgt_testimonials', array( $this, 'render_testimonials' ) );
		add_shortcode( 'pgt_photo_band', array( $this, 'render_photo_band' ) );
	}


	/**
	 * Homepage photo band (README addendum A5) — a 2/1 split of two real
	 * photographs with the aggregate "+80" stat plate between them.
	 *
	 * Layout note: this is a fixed-ratio two-part row (flex 2 / flex 1), NOT
	 * `auto-fit` + `grid-column: span 2`. auto-fit cannot create a third track
	 * and the span swallows the row — the trap called out in the handoff.
	 *
	 * The +80 figure is aggregate sample copy, not live data, so it carries
	 * the standard Sample badge.
	 *
	 * @return string
	 */
	public function render_photo_band() {
		$main = Media::setting_image( 'pgt_home_band_main', 'photo-student-desk.jpg' );
		$side = Media::setting_image( 'pgt_home_band_side', 'photo-family-study.jpg' );

		$html  = '<section class="pgh-section pgh-photoband" id="pg-band"><div class="pgh-container pgh-photoband__row">';

		$html .= '<div class="pgh-photoband__main">'
			. Media::image(
				array(
					'attachment' => $main['attachment'],
					'url'        => $main['url'],
					'alt'        => __( 'A student working through a practice set on a laptop.', 'prepgro-theme' ),
					'height'     => 'clamp(240px, 34vw, 360px)',
					'radius'     => '20px',
					'sizes'      => '(max-width: 900px) 100vw, 62vw',
				)
			)
			. '</div>';

		$html .= '<div class="pgh-photoband__side">'
			. '<div class="pgh-plate">'
			. '<p class="pgh-plate__figure">+80</p>'
			. '<p class="pgh-plate__label">' . esc_html__( 'average estimated-score gain in the first 12 practice tests.', 'prepgro-theme' ) . '</p>'
			. Media::sample_badge( '', 'pgt-sample--on-dark' )
			. '</div>'
			. '<div class="pgh-photoband__sidephoto">'
			. Media::image(
				array(
					'attachment' => $side['attachment'],
					'url'        => $side['url'],
					'alt'        => __( 'A tutor mid-explanation on a video call.', 'prepgro-theme' ),
					'height'     => '100%',
					'radius'     => '20px',
					'sizes'      => '(max-width: 900px) 100vw, 32vw',
				)
			)
			. '</div>'
			. '</div>';

		$html .= '</div></section>';

		return $html;
	}

	/**
	 * Live activity ticker — most recently published exams.
	 * Hidden entirely when there is no real activity to report.
	 *
	 * @return string
	 */
	public function render_ticker() {
		$cached = get_transient( 'pgt_hp_ticker_v3' );
		if ( false !== $cached ) {
			return $cached;
		}

		$exams = get_posts(
			array(
				'post_type'      => 'exam',
				'post_status'    => 'publish',
				'posts_per_page' => 6,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $exams ) ) {
			set_transient( 'pgt_hp_ticker_v3', '', HOUR_IN_SECONDS );
			return '';
		}

		$items = array();
		foreach ( $exams as $exam ) {
			$items[] = sprintf(
				'<span class="pgt-ticker__item">%s <a href="%s">%s</a></span>',
				esc_html__( 'New:', 'prepgro-theme' ),
				esc_url( get_permalink( $exam ) ),
				esc_html( get_the_title( $exam ) )
			);
		}

		$html  = '<div class="pgt-ticker"><div class="pgt-ticker__track">';
		$html .= implode( '', $items );
		// Duplicate once for a seamless CSS marquee loop.
		$html .= implode( '', $items );
		$html .= '</div></div>';

		set_transient( 'pgt_hp_ticker_v3', $html, HOUR_IN_SECONDS );
		return $html;
	}

	/**
	 * Stats bar — real counts only. Hidden entirely until there is at least
	 * one published exam (a bar full of zeroes hurts trust more than no bar).
	 *
	 * @return string
	 */
	public function render_stats() {
		$cached = get_transient( 'pgt_hp_stats_v3' );
		if ( false !== $cached ) {
			return $cached;
		}

		$counts      = wp_count_posts( 'exam' );
		$exam_count  = isset( $counts->publish ) ? (int) $counts->publish : 0;

		if ( $exam_count < 1 ) {
			set_transient( 'pgt_hp_stats_v3', '', HOUR_IN_SECONDS );
			return '';
		}

		global $wpdb;
		$question_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}testbook_questions WHERE status = 'published'"
		);

		$subject_count = (int) wp_count_terms(
			array(
				'taxonomy'   => 'testbook_subject',
				'hide_empty' => true,
			)
		);

		$free_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s AND pm.meta_value = %s
				 WHERE p.post_type = 'exam' AND p.post_status = 'publish'",
				'_is_premium',
				'no'
			)
		);

		$tiles = array(
			array(
				'icon'  => $this->line_icon( 'file-text' ),
				'value' => $this->format_count( $exam_count ) . '+',
				'label' => __( 'Practice tests', 'prepgro-theme' ),
			),
		);

		if ( $question_count > 0 ) {
			$tiles[] = array(
				'icon'  => $this->line_icon( 'check-square' ),
				'value' => $this->format_count( $question_count ) . '+',
				'label' => __( 'Practice questions', 'prepgro-theme' ),
			);
		}

		if ( $subject_count > 0 ) {
			$tiles[] = array(
				'icon'  => $this->line_icon( 'book-open' ),
				'value' => (string) $subject_count,
				'label' => __( 'Subjects covered', 'prepgro-theme' ),
			);
		}

		$tiles[] = array(
			'icon'  => $this->line_icon( 'unlock' ),
			'value' => $free_count > 0 ? $this->format_count( $free_count ) . '+' : __( 'Free', 'prepgro-theme' ),
			'label' => $free_count > 0 ? __( 'Free tests to start', 'prepgro-theme' ) : __( 'to start practicing', 'prepgro-theme' ),
		);

		$html = '<div class="pgt-stats">';
		foreach ( $tiles as $tile ) {
			$html .= sprintf(
				'<div class="pgt-stats__tile"><div class="pgt-stats__icon">%s</div><div><div class="pgt-stats__value">%s</div><div class="pgt-stats__label">%s</div></div></div>',
				$tile['icon'],
				esc_html( $tile['value'] ),
				esc_html( $tile['label'] )
			);
		}
		$html .= '</div>';

		set_transient( 'pgt_hp_stats_v3', $html, HOUR_IN_SECONDS );
		return $html;
	}

	/**
	 * "Choose your goal" category grid — distinct `_exam_subject` values across
	 * published exams (the taxonomy is not populated on all installs; the
	 * importer writes subject postmeta on every exam, same source as the
	 * grade/state sections). Hidden entirely when none qualify yet.
	 *
	 * @return string
	 */
	public function render_categories() {
		$cached = get_transient( 'pgt_hp_categories_v4' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT pm.meta_value AS name, COUNT(DISTINCT po.ID) AS cnt
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} po ON pm.post_id = po.ID
			     AND po.post_type = 'exam' AND po.post_status = 'publish'
			 WHERE pm.meta_key = '_exam_subject' AND pm.meta_value <> ''
			 GROUP BY pm.meta_value
			 ORDER BY cnt DESC
			 LIMIT 8"
		);

		if ( empty( $rows ) ) {
			set_transient( 'pgt_hp_categories_v4', '', HOUR_IN_SECONDS );
			return '';
		}

		$html  = '<section class="pgt-section"><div class="pgt-container">';
		$html .= '<p class="pgt-eyebrow">' . esc_html__( 'Choose your goal', 'prepgro-theme' ) . '</p>';
		$html .= '<h2 class="pgt-section__title">' . esc_html__( 'Practice by subject', 'prepgro-theme' ) . '</h2>';
		$html .= '<p class="pgt-lead" style="max-width:56ch;margin:.25rem 0 2rem;">' . esc_html__( 'Jump straight into full-length practice tests for the subjects and exams you care about most.', 'prepgro-theme' ) . '</p>';
		$html .= '<div class="pgt-categories">';
		foreach ( $rows as $row ) {
			$html .= sprintf(
				'<a class="pgt-cat-card" href="%s"><span class="pgt-cat-card__icon">%s</span><span class="pgt-cat-card__name">%s</span><span class="pgt-cat-card__count">%s</span></a>',
				esc_url( add_query_arg( 's', $row->name, home_url( '/all-exams/' ) ) ),
				$this->icon_for_subject( $row->name ),
				esc_html( $row->name ),
				esc_html(
					sprintf(
						/* translators: %d: number of practice tests */
						_n( '%d test', '%d tests', (int) $row->cnt, 'prepgro-theme' ),
						(int) $row->cnt
					)
				)
			);
		}
		$html .= '</div></div></section>';

		set_transient( 'pgt_hp_categories_v4', $html, HOUR_IN_SECONDS );
		return $html;
	}

	/**
	 * "Browse by grade" grid — grade tags (question_tags.type = 'grade') that
	 * have at least one published exam, naturally ordered (K, 1…12). Mirrors the
	 * PrepGro Engine homepage grade picker but reads the tag tables directly, in
	 * the same spirit as render_stats(). Hidden entirely until grade tags exist
	 * (e.g. before the question-bank import populates them) — no fabricated data.
	 *
	 * @return string
	 */
	public function render_grades() {
		$cached = get_transient( 'pgt_hp_grades_v5' );
		if ( false !== $cached ) {
			return $cached;
		}

		$rows = $this->tag_exam_counts( 'grade' );
		if ( empty( $rows ) ) {
			set_transient( 'pgt_hp_grades_v5', '', HOUR_IN_SECONDS );
			return '';
		}

		usort(
			$rows,
			function ( $a, $b ) {
				return $this->grade_rank( $a->name ) <=> $this->grade_rank( $b->name );
			}
		);

		$html  = '<section class="pgt-section"><div class="pgt-container">';
		$html .= '<p class="pgt-eyebrow">' . esc_html__( 'By grade level', 'prepgro-theme' ) . '</p>';
		$html .= '<h2 class="pgt-section__title">' . esc_html__( 'Browse by grade', 'prepgro-theme' ) . '</h2>';
		$html .= '<p class="pgt-lead" style="max-width:56ch;margin:.25rem 0 2rem;">' . esc_html__( 'Full-length practice tests matched to what students are learning at each grade.', 'prepgro-theme' ) . '</p>';
		$html .= '<div class="pgt-categories">';
		foreach ( $rows as $row ) {
			$html .= sprintf(
				'<a class="pgt-cat-card" href="%s"><span class="pgt-cat-card__icon">%s</span><span class="pgt-cat-card__name">%s</span><span class="pgt-cat-card__count">%s</span></a>',
				esc_url( add_query_arg( 's', $row->name, home_url( '/all-exams/' ) ) ),
				$this->line_icon( 'award' ),
				esc_html( $row->name ),
				esc_html(
					sprintf(
						/* translators: %d: number of practice tests */
						_n( '%d test', '%d tests', (int) $row->cnt, 'prepgro-theme' ),
						(int) $row->cnt
					)
				)
			);
		}
		$html .= '</div></div></section>';

		set_transient( 'pgt_hp_grades_v5', $html, HOUR_IN_SECONDS );
		return $html;
	}

	/**
	 * "Browse by state" grid — state tags (question_tags.type = 'state') with at
	 * least one published exam, alphabetical, showing live per-state exam counts.
	 * Mirrors the PrepGro Engine state picker. Hidden entirely until state tags
	 * exist — no fabricated data.
	 *
	 * @return string
	 */
	public function render_states() {
		$cached = get_transient( 'pgt_hp_states_v5' );
		if ( false !== $cached ) {
			return $cached;
		}

		$rows = $this->tag_exam_counts( 'state' );
		if ( empty( $rows ) ) {
			set_transient( 'pgt_hp_states_v5', '', HOUR_IN_SECONDS );
			return '';
		}

		usort(
			$rows,
			function ( $a, $b ) {
				return strcasecmp( $a->name, $b->name );
			}
		);

		$html  = '<section class="pgt-section pgt-section--tint"><div class="pgt-container">';
		$html .= '<p class="pgt-eyebrow">' . esc_html__( 'By location', 'prepgro-theme' ) . '</p>';
		$html .= '<h2 class="pgt-section__title">' . esc_html__( 'Browse by state', 'prepgro-theme' ) . '</h2>';
		$html .= '<p class="pgt-lead" style="max-width:56ch;margin:.25rem 0 2rem;">' . esc_html__( 'Practice tests aligned to each state&rsquo;s assessments and standards.', 'prepgro-theme' ) . '</p>';
		$html .= '<div class="pgt-categories">';
		foreach ( $rows as $row ) {
			$html .= sprintf(
				'<a class="pgt-cat-card" href="%s"><span class="pgt-cat-card__icon">%s</span><span class="pgt-cat-card__name">%s</span><span class="pgt-cat-card__count">%s</span></a>',
				esc_url( add_query_arg( 's', $row->name, home_url( '/all-exams/' ) ) ),
				$this->line_icon( 'landmark' ),
				esc_html( $row->name ),
				esc_html(
					sprintf(
						/* translators: %d: number of practice tests */
						_n( '%d test', '%d tests', (int) $row->cnt, 'prepgro-theme' ),
						(int) $row->cnt
					)
				)
			);
		}
		$html .= '</div></div></section>';

		set_transient( 'pgt_hp_states_v5', $html, HOUR_IN_SECONDS );
		return $html;
	}

	/**
	 * Shared query: distinct grade/state values across published exams, with
	 * the count of published exams each. Reads the `_exam_grade` /
	 * `_exam_state` postmeta the PrepGro Engine importer writes on every exam
	 * (the question-tag tables are not populated on all installs). Returns
	 * rows of { name, cnt }. The type is validated against a whitelist so
	 * the meta key is never interpolated from untrusted input.
	 *
	 * @param string $type Tag type ('grade' or 'state').
	 * @return array<int,object>
	 */
	private function tag_exam_counts( $type ) {
		$meta_keys = array(
			'grade' => '_exam_grade',
			'state' => '_exam_state',
		);
		if ( ! isset( $meta_keys[ $type ] ) ) {
			return array();
		}

		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS name, COUNT(DISTINCT po.ID) AS cnt
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} po ON pm.post_id = po.ID
				     AND po.post_type = 'exam' AND po.post_status = 'publish'
				 WHERE pm.meta_key = %s AND pm.meta_value <> ''
				 GROUP BY pm.meta_value",
				$meta_keys[ $type ]
			)
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Sort key for a grade label so "K" leads and numbered grades follow in
	 * order (Grade 1 … Grade 12). Unrecognised labels sort last.
	 *
	 * @param string $name Grade tag name.
	 * @return int
	 */
	private function grade_rank( $name ) {
		if ( preg_match( '/\bK\b|kindergarten/i', $name ) ) {
			return 0;
		}
		if ( preg_match( '/\d+/', $name, $m ) ) {
			return (int) $m[0];
		}
		return 99;
	}

	/**
	 * Latest practice tests grid — most recently published exams.
	 * Hidden entirely when there are none yet.
	 *
	 * @return string
	 */
	public function render_latest_tests() {
		$cached = get_transient( 'pgt_hp_latest_v3' );
		if ( false !== $cached ) {
			return $cached;
		}

		$exams = get_posts(
			array(
				'post_type'      => 'exam',
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $exams ) ) {
			set_transient( 'pgt_hp_latest_v3', '', HOUR_IN_SECONDS );
			return '';
		}

		$html  = '<section class="pgt-section pgt-section--tint"><div class="pgt-container">';
		$html .= '<div class="pgt-section__head">';
		$html .= '<div><p class="pgt-eyebrow">' . esc_html__( 'Just released', 'prepgro-theme' ) . '</p>';
		$html .= '<h2 class="pgt-section__title">' . esc_html__( 'Latest practice tests', 'prepgro-theme' ) . '</h2></div>';
		$html .= '<a class="pgt-link-arrow" href="' . esc_url( home_url( '/all-exams/' ) ) . '">' . esc_html__( 'View all practice tests', 'prepgro-theme' ) . ' →</a>';
		$html .= '</div>';
		$html .= '<div class="pgt-latest-grid">';
		foreach ( $exams as $exam ) {
			$is_new    = ( strtotime( $exam->post_date ) > strtotime( '-7 days' ) );
			$is_free   = get_post_meta( $exam->ID, '_is_premium', true ) === 'no';
			$question_count = (int) get_post_meta( $exam->ID, '_max_questions', true );

			$html .= '<a class="pgt-latest-card" href="' . esc_url( get_permalink( $exam ) ) . '">';
			if ( $is_new ) {
				$html .= '<span class="pgt-latest-card__badge">' . esc_html__( 'NEW', 'prepgro-theme' ) . '</span>';
			}
			$html .= '<span class="pgt-latest-card__icon">' . $this->line_icon( 'file-text' ) . '</span>';
			$html .= '<span class="pgt-latest-card__title">' . esc_html( get_the_title( $exam ) ) . '</span>';
			$html .= '<span class="pgt-latest-card__meta">';
			$html .= '<span>' . esc_html__( 'Timed', 'prepgro-theme' ) . '</span>';
			$html .= '<span>' . esc_html__( 'Solutions', 'prepgro-theme' ) . '</span>';
			if ( $is_free ) {
				$html .= '<span>' . esc_html__( 'Free', 'prepgro-theme' ) . '</span>';
			}
			$html .= '</span>';
			$html .= '<span class="pgt-latest-card__cta">' . esc_html__( 'Start practicing', 'prepgro-theme' ) . ' →</span>';
			$html .= '</a>';
		}
		$html .= '</div></div></section>';

		set_transient( 'pgt_hp_latest_v3', $html, HOUR_IN_SECONDS );
		return $html;
	}

	/**
	 * Testimonials — reuses the SAME settings-driven option the
	 * PrepGro Engine plugin already exposes for the (legacy) homepage
	 * shortcode: `testbook_homepage_testimonials_enabled` / `_testimonials`
	 * (JSON array of {name, grade, text, rating}). No fabricated quotes are
	 * ever rendered here — the section is invisible until an admin turns
	 * the toggle on and adds real ones via Settings.
	 *
	 * @return string
	 */
	public function render_testimonials() {
		if ( get_option( 'testbook_homepage_testimonials_enabled', 'no' ) !== 'yes' ) {
			return '';
		}

		$raw = get_option( 'testbook_homepage_testimonials', '' );
		if ( empty( $raw ) ) {
			return '';
		}

		$testimonials = json_decode( $raw, true );
		if ( ! is_array( $testimonials ) || empty( $testimonials ) ) {
			return '';
		}

		$testimonials = array_slice( $testimonials, 0, 3 );

		$html  = '<section class="pgt-section"><div class="pgt-container">';
		$html .= '<p class="pgt-eyebrow">' . esc_html__( 'Selection stories', 'prepgro-theme' ) . '</p>';
		$html .= '<h2 class="pgt-section__title">' . esc_html__( 'What students &amp; parents say', 'prepgro-theme' ) . '</h2>';
		$html .= '<div class="pgt-testimonials">';
		foreach ( $testimonials as $t ) {
			$name   = isset( $t['name'] ) ? $t['name'] : '';
			$grade  = isset( $t['grade'] ) ? $t['grade'] : '';
			$text   = isset( $t['text'] ) ? $t['text'] : '';
			$rating = isset( $t['rating'] ) ? max( 1, min( 5, (int) $t['rating'] ) ) : 5;

			if ( '' === $name || '' === $text ) {
				continue;
			}

			$initials = '';
			foreach ( explode( ' ', $name ) as $part ) {
				$part = trim( $part );
				if ( '' !== $part && ctype_alpha( $part[0] ) ) {
					$initials .= strtoupper( $part[0] );
				}
				if ( strlen( $initials ) >= 2 ) {
					break;
				}
			}

			$html .= '<div class="pgt-testimonial-card">';
			$html .= '<div class="pgt-testimonial-card__stars">' . str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) . '</div>';
			$html .= '<p class="pgt-testimonial-card__quote">&ldquo;' . esc_html( $text ) . '&rdquo;</p>';
			$html .= '<div class="pgt-testimonial-card__author"><span class="pgt-testimonial-card__avatar">' . esc_html( $initials ) . '</span><span>' . esc_html( $name );
			if ( $grade ) {
				$html .= '<span class="pgt-testimonial-card__grade"> · ' . esc_html( $grade ) . '</span>';
			}
			$html .= '</span></div></div>';
		}
		$html .= '</div></div></section>';

		return $html;
	}

	/**
	 * Format a count with thousands separators for display.
	 *
	 * @param int $n Count.
	 * @return string
	 */
	private function format_count( $n ) {
		if ( $n >= 1000 ) {
			return number_format( $n );
		}
		return (string) $n;
	}

	/**
	 * Inline SVG line icon (24px grid, stroked, inherits currentColor).
	 * Replaces the old emoji set so the marketing surface reads modern-bold
	 * rather than grade-school. Icons are lucide-style outline paths.
	 *
	 * @param string $slug Icon slug.
	 * @return string SVG markup.
	 */
	private function line_icon( $slug ) {
		$paths = array(
			'file-text'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
			'check-square' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
			'book-open'    => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
			'unlock'       => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/>',
			'calculator'   => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01M8 19h.01M12 19h.01M16 19h.01"/>',
			'flask'        => '<path d="M10 2v6L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 8V2"/><path d="M8 2h8M7 14h10"/>',
			'target'       => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
			'award'        => '<circle cx="12" cy="9" r="6"/><path d="M9 14.5L8 22l4-2 4 2-1-7.5"/>',
			'landmark'     => '<path d="M3 22h18M5 18v-7M9.5 18v-7M14.5 18v-7M19 18v-7M12 2L3 8h18z"/>',
			'globe'        => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>',
			'monitor'      => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
			'palette'      => '<path d="M12 21a9 9 0 1 1 9-9c0 2-1.5 3-3 3h-2a2 2 0 0 0-1.5 3.3c.5.6.5 1.7-.5 2.2a8 8 0 0 1-2 .5z"/><path d="M7.5 10.5h.01M12 7h.01M16.5 10.5h.01"/>',
			'music'        => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
			'chat'         => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
			'chart'        => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 14l4-4 3 3 5-6"/>',
			'pen'          => '<path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/>',
		);

		if ( ! isset( $paths[ $slug ] ) ) {
			$slug = 'book-open';
		}

		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[ $slug ] . '</svg>';
	}

	/**
	 * Best-effort line icon for a subject name.
	 *
	 * @param string $name Subject term name.
	 * @return string SVG markup.
	 */
	private function icon_for_subject( $name ) {
		$name = strtolower( $name );
		$map  = array(
			'math'      => 'calculator',
			'science'   => 'flask',
			'biology'   => 'flask',
			'chemistry' => 'flask',
			'physics'   => 'flask',
			'english'   => 'book-open',
			'reading'   => 'book-open',
			'writing'   => 'pen',
			'history'   => 'landmark',
			'social'    => 'landmark',
			'geography' => 'globe',
			'sat'       => 'target',
			'act'       => 'target',
			'ap '       => 'award',
			'civics'    => 'landmark',
			'computer'  => 'monitor',
			'art'       => 'palette',
			'music'     => 'music',
			'language'  => 'chat',
			'spanish'   => 'chat',
			'economics' => 'chart',
		);

		foreach ( $map as $needle => $icon ) {
			if ( false !== strpos( $name, $needle ) ) {
				return $this->line_icon( $icon );
			}
		}

		return $this->line_icon( 'book-open' );
	}
}

/**
 * Clear the cached homepage section fragments. Hooked to exam publish/trash
 * so the ticker, stats, categories and latest-tests grids never go stale
 * beyond the 1-hour transient TTL.
 *
 * @return void
 */
function pgt_flush_homepage_section_cache() {
	foreach ( array( 'pgt_hp_ticker_v3', 'pgt_hp_stats_v3', 'pgt_hp_categories_v4', 'pgt_hp_grades_v5', 'pgt_hp_states_v5', 'pgt_hp_latest_v3' ) as $key ) {
		delete_transient( $key );
	}
}
add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
	if ( 'exam' === $post->post_type && $new_status !== $old_status ) {
		\PrepGro\Theme\pgt_flush_homepage_section_cache();
	}
}, 10, 3 );
