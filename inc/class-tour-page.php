<?php
/**
 * "How prepGro works" — the page that explains the whole product.
 *
 * The front page became exam-first in 2026-09 (the visitor's state and its
 * tests; engine Core\Journey\Home_Page). The page it replaced — "Know the
 * gaps. Close them by test day.", the Evaluate → Elevate → Excel loop, the
 * device showcase, the sample report and the tutor band — lives on here, at
 * /how-prepgro-works/, rendered by templates/page-how-prepgro-works.html
 * (the block template hierarchy picks it by the page's slug).
 *
 * The page is created once, published, with no content of its own (the
 * template is the content). If the owner later deletes or renames it, it is
 * not created again: the option records that the job ran.
 *
 * This class also answers the engine homepage's two questions: which photo
 * goes in a slot (`pge_home_image`, from the Customizer image slots) and
 * where this page is (`pge_home_tour_url`).
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates and links the "How prepGro works" page.
 */
final class Tour_Page {

	const SLUG   = 'how-prepgro-works';
	const OPTION = 'pgt_tour_page_id';

	/**
	 * Singleton.
	 *
	 * @var Tour_Page|null
	 */
	private static $instance = null;

	/**
	 * @return Tour_Page
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'ensure' ), 30 );
		add_filter( 'pge_home_image', array( $this, 'home_image' ), 10, 3 );
		add_filter( 'pge_home_tour_url', array( $this, 'url' ) );
		add_action( 'wp_head', array( $this, 'meta_description' ), 2 );
	}

	/**
	 * Create the page once. add_option() is the lock, so two first requests
	 * arriving together cannot both insert it.
	 *
	 * @return void
	 */
	public function ensure() {
		if ( wp_installing() || false !== get_option( self::OPTION, false ) ) {
			return;
		}
		if ( ! add_option( self::OPTION, 0 ) ) {
			return;
		}
		$page = get_page_by_path( self::SLUG, OBJECT, 'page' );
		$id   = $page ? (int) $page->ID : (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => self::SLUG,
				'post_title'   => __( 'How prepGro works', 'prepgro-theme' ),
				'post_content' => '',
				'post_excerpt' => $this->description(),
			)
		);
		update_option( self::OPTION, $id );
	}

	/**
	 * The page's URL, or '' when it is not published.
	 *
	 * @param string $url Incoming value (filter).
	 * @return string
	 */
	public function url( $url = '' ) {
		$id = (int) get_option( self::OPTION, 0 );
		if ( $id > 0 && 'publish' === get_post_status( $id ) ) {
			return (string) get_permalink( $id );
		}
		return (string) $url;
	}

	/**
	 * The menu link: the page when it exists, else the homepage's how-it-works
	 * section, which is what these links pointed at before.
	 *
	 * @return string
	 */
	public function link() {
		$url = $this->url();
		return '' !== $url ? $url : home_url( '/#pg-how' );
	}

	/**
	 * Is this request the page?
	 *
	 * @return bool
	 */
	public function is_tour() {
		if ( ! is_page() ) {
			return false;
		}
		$id = (int) get_option( self::OPTION, 0 );
		return ( $id > 0 && (int) get_queried_object_id() === $id ) || is_page( self::SLUG );
	}

	/**
	 * A homepage photo for the engine: the slot's image, or the labelled
	 * empty box for someone who can upload one. A visitor gets nothing until
	 * there is a photo, so no section shows an empty panel.
	 *
	 * @param string $html Incoming HTML.
	 * @param string $key  Slot key.
	 * @param array  $args Box arguments.
	 * @return string
	 */
	public function home_image( $html, $key, $args = array() ) {
		if ( ! Image_Slots::get( (string) $key ) ) {
			return (string) $html;
		}
		if ( ! Image_Slots::attachments( (string) $key ) && ! Image_Slots::author_can_see() ) {
			return (string) $html;
		}
		$args = is_array( $args ) ? $args : array();
		return Media::slot(
			(string) $key,
			array(
				'height' => isset( $args['height'] ) ? (string) $args['height'] : '360px',
				'sizes'  => isset( $args['sizes'] ) ? (string) $args['sizes'] : '(max-width: 900px) 100vw, 50vw',
				'radius' => 'var(--pge-radius-xl, 20px)',
				'class'  => 'pgh-photo',
			)
		);
	}

	/**
	 * @return string
	 */
	private function description() {
		return __( 'How prepGro works: practice papers for your state test, an optional skills check, short lessons, live tutors and unlimited practice. Evaluate, Elevate and Excel in one loop.', 'prepgro-theme' );
	}

	/**
	 * The page's meta description, unless an SEO plugin writes one.
	 *
	 * @return void
	 */
	public function meta_description() {
		if ( ! $this->is_tour() ) {
			return;
		}
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
			return;
		}
		echo '<meta name="description" content="' . esc_attr( $this->description() ) . '">' . "\n";
	}
}
