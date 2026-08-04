<?php
/**
 * Journal — blog index (README §8) and article furniture (§9).
 *
 * The index is a featured card (the latest post, as a two-pane split) plus a
 * grid of the rest. Both read real featured images through Media::image(),
 * which enforces the explicit-height + object-fit rule; posts with no
 * thumbnail fall back to the brand tint wash, alternating so a grid of
 * imageless posts still has rhythm.
 *
 * The article template stays block-based so post content remains editable;
 * only the byline row and the end CTA need PHP, and they are shortcodes.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Journal index + article furniture.
 */
final class Blog {

	/** @var Blog|null */
	private static $instance = null;

	/** @return Blog */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/** @return void */
	public function init() {
		add_shortcode( 'pgt_journal', array( $this, 'render_journal' ) );
		add_shortcode( 'pgt_article_byline', array( $this, 'render_byline' ) );
		add_shortcode( 'pgt_article_cta', array( $this, 'render_article_cta' ) );
	}

	/**
	 * Estimated reading time, at 200 words per minute, floored at 1.
	 *
	 * @param \WP_Post $post Post.
	 * @return int
	 */
	private function read_minutes( $post ) {
		$words = str_word_count( wp_strip_all_tags( (string) $post->post_content ) );
		return max( 1, (int) ceil( $words / 200 ) );
	}

	/**
	 * "14 Mar 2026 · 6 min read".
	 *
	 * @param \WP_Post $post  Post.
	 * @param bool     $long  Append "read".
	 * @return string
	 */
	private function meta_line( $post, $long = false ) {
		return sprintf(
			/* translators: 1: date, 2: reading time in minutes */
			$long ? __( '%1$s · %2$d min read', 'prepgro-theme' ) : __( '%1$s · %2$d min', 'prepgro-theme' ),
			get_the_date( 'j M Y', $post ),
			$this->read_minutes( $post )
		);
	}

	/**
	 * The post's first category name, or ''.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private function category( $post ) {
		$cats = get_the_category( $post->ID );
		return $cats && ! is_wp_error( $cats ) ? $cats[0]->name : '';
	}

	/* ─────────────────────────────────────────────────────────────────────
	   §8 — index.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * Render the Journal index.
	 *
	 * @return string
	 */
	public function render_journal() {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => (int) apply_filters( 'pgt_journal_limit', 13 ),
				'no_found_rows'  => true,
			)
		);

		$head = '<section class="pgb-head"><div class="pgb-head__copy">'
			. '<span class="pgb-chip">' . esc_html__( 'Journal', 'prepgro-theme' ) . '</span>'
			. '<h1 class="pgb-h1">' . esc_html__( 'Notes on prepping well', 'prepgro-theme' ) . '</h1>'
			. '<p class="pgb-lede">' . esc_html__( 'Practical writing on readiness checks, practice habits and test-day nerves.', 'prepgro-theme' ) . '</p>'
			. '</div></section>';

		if ( ! $posts ) {
			return '<div class="pgb">' . $head
				. '<section class="pgb-body"><div class="pgb-empty">'
				. '<h2>' . esc_html__( 'Nothing here yet.', 'prepgro-theme' ) . '</h2>'
				. '<p>' . esc_html__( 'Check back soon — or jump straight into a practice test.', 'prepgro-theme' ) . '</p>'
				. '<a class="pgt-btn pgt-btn--primary" href="' . esc_url( home_url( '/all-exams/' ) ) . '">'
				. esc_html__( 'Browse all practice tests', 'prepgro-theme' ) . '</a>'
				. '</div></section></div>';
		}

		$featured = array_shift( $posts );

		return '<div class="pgb">' . $head
			. '<section class="pgb-body">'
			. $this->featured_card( $featured )
			. $this->post_grid( $posts )
			. '</section></div>';
	}

	/**
	 * The featured post — a two-pane split, copy left, photo right.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private function featured_card( $post ) {
		$cat = $this->category( $post );

		return '<a class="pgb-featured" href="' . esc_url( get_permalink( $post ) ) . '">'
			. '<div class="pgb-featured__copy">'
			. ( $cat ? '<span class="pgb-cat">' . esc_html( $cat ) . '</span>' : '' )
			. '<h2 class="pgb-featured__h">' . esc_html( get_the_title( $post ) ) . '</h2>'
			. '<p class="pgb-featured__x">' . esc_html( wp_trim_words( get_the_excerpt( $post ), 26 ) ) . '</p>'
			. '<p class="pgb-meta">' . esc_html( $this->meta_line( $post, true ) ) . '</p>'
			. '</div>'
			. '<div class="pgb-featured__media">'
			. Media::image(
				array(
					'attachment' => (int) get_post_thumbnail_id( $post ),
					'alt'        => '',
					'height'     => '100%',
					'radius'     => '0',
					'border'     => false,
					'sizes'      => '(max-width: 900px) 100vw, 50vw',
				)
			)
			. '</div></a>';
	}

	/**
	 * The rest, as cards.
	 *
	 * @param \WP_Post[] $posts Posts.
	 * @return string
	 */
	private function post_grid( $posts ) {
		if ( ! $posts ) {
			return '';
		}

		$out = '<div class="pgb-grid">';
		foreach ( $posts as $i => $post ) {
			$cat   = $this->category( $post );
			$thumb = (int) get_post_thumbnail_id( $post );

			$out .= '<a class="pgb-card' . ( 0 === $i % 2 ? '' : ' pgb-card--alt' ) . '" href="' . esc_url( get_permalink( $post ) ) . '">'
				. '<div class="pgb-card__media">'
				. Media::image(
					array(
						'attachment' => $thumb,
						'alt'        => '',
						'height'     => '150px',
						'radius'     => '0',
						'border'     => false,
						'size'       => 'medium_large',
						'sizes'      => '(max-width: 700px) 100vw, 33vw',
					)
				)
				. '</div>'
				. '<div class="pgb-card__body">'
				. ( $cat ? '<span class="pgb-cat pgb-cat--sm">' . esc_html( $cat ) . '</span>' : '' )
				. '<h3 class="pgb-card__h">' . esc_html( get_the_title( $post ) ) . '</h3>'
				. '<p class="pgb-card__x">' . esc_html( wp_trim_words( get_the_excerpt( $post ), 18 ) ) . '</p>'
				. '<p class="pgb-meta pgb-meta--sm">' . esc_html( $this->meta_line( $post ) ) . '</p>'
				. '</div></a>';
		}
		return $out . '</div>';
	}

	/* ─────────────────────────────────────────────────────────────────────
	   §9 — article furniture.
	   ──────────────────────────────────────────────────────────────────── */

	/**
	 * Back link + category + byline row.
	 *
	 * @return string
	 */
	public function render_byline() {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}

		$author   = get_the_author_meta( 'display_name', $post->post_author );
		$initials = '';
		foreach ( preg_split( '/\s+/', trim( (string) $author ), -1, PREG_SPLIT_NO_EMPTY ) as $part ) {
			$initials .= mb_substr( $part, 0, 1 );
			if ( mb_strlen( $initials ) >= 2 ) {
				break;
			}
		}
		$cat = $this->category( $post );

		return '<a class="pga-back" href="' . esc_url( $this->journal_url() ) . '">'
			. Icons::svg( 'arrow-left', array( 'size' => 15, 'stroke' => 2 ) )
			. esc_html__( 'All articles', 'prepgro-theme' ) . '</a>'
			. ( $cat ? '<p class="pga-cat">' . esc_html( $cat ) . '</p>' : '' )
			. '<div class="pga-byline">'
			. '<span class="pga-avatar" aria-hidden="true">' . esc_html( mb_strtoupper( $initials ) ) . '</span>'
			. '<span class="pga-byline__name">' . esc_html( $author ) . '</span>'
			. '<span class="pga-byline__meta">' . esc_html( $this->meta_line( $post, true ) ) . '</span>'
			. '</div>';
	}

	/**
	 * The article's closing CTA.
	 *
	 * @return string
	 */
	public function render_article_cta() {
		return '<aside class="pga-cta">'
			. '<div><p class="pga-cta__h">' . esc_html__( 'See your own four gaps', 'prepgro-theme' ) . '</p>'
			. '<p class="pga-cta__s">' . esc_html__( 'Free readiness check · about 20 minutes', 'prepgro-theme' ) . '</p></div>'
			. '<a class="pga-cta__btn" href="' . esc_url( home_url( '/get-started/' ) ) . '">'
			. esc_html__( 'Start now', 'prepgro-theme' ) . '</a>'
			. '</aside>';
	}

	/**
	 * Permalink of the posts page, falling back to /blog/.
	 *
	 * @return string
	 */
	private function journal_url() {
		$page_id = (int) get_option( 'page_for_posts' );
		if ( $page_id ) {
			$url = get_permalink( $page_id );
			if ( $url ) {
				return $url;
			}
		}
		return home_url( '/blog/' );
	}
}
