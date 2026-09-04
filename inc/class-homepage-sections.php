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
		add_shortcode( 'pgt_home_floats', array( $this, 'render_home_floats' ) );
		add_shortcode( 'pgt_hero_skyline', array( $this, 'render_hero_skyline' ) );
		add_shortcode( 'pgt_home_trust', array( $this, 'render_home_trust' ) );
		add_shortcode( 'pgt_home_tutorband', array( $this, 'render_home_tutorband' ) );
		add_shortcode( 'pgt_home_kicker', array( $this, 'render_home_kicker' ) );

		// Badge the "Three parts. One loop." cards for switched-off pillars.
		add_filter( 'render_block', array( $this, 'gate_loop_cards' ), 10, 2 );
		// Strip the live-tutor promise from the Elevate card while plans are dormant.
		add_filter( 'render_block', array( $this, 'gate_tutor_promise' ), 11, 2 );
	}

	/**
	 * The homepage's three-pillar loop section (#pg-how) is authored as static
	 * markup inside one core/html block in templates/front-page.html, so it
	 * cannot be PHP-gated where it is written. This post-processes that one
	 * block: a pillar the engine has switched off keeps its card — the loop is
	 * the product, and a two-card row would say prepGro only ever had two
	 * parts — but gains a "Coming soon" badge and loses its link.
	 *
	 * Scoped tightly: it only touches core/html blocks that actually contain a
	 * .pgh-loopcard, so the common path is one strpos over a handful of blocks.
	 * The hero's .pgh-fcard mockup cards are deliberately left alone — that is
	 * a picture of the product, not navigation, and the phase names in it are
	 * labels rather than promises.
	 *
	 * @param string $content Rendered block HTML.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public function gate_loop_cards( $content, $block ) {
		if ( ! function_exists( 'pge_feature' ) ) {
			return $content;
		}
		if ( empty( $block['blockName'] ) || 'core/html' !== $block['blockName'] ) {
			return $content;
		}
		if ( false === strpos( $content, 'pgh-loopcard' ) ) {
			return $content;
		}

		return (string) preg_replace_callback(
			'#<article class="pgh-loopcard">(.*?)</article>#s',
			static function ( $m ) {
				if ( ! preg_match( '#pgh-phase--([a-z]+)#', $m[1], $phase ) ) {
					return $m[0];
				}
				$key = $phase[1];
				if ( ! in_array( $key, array( 'evaluate', 'elevate', 'excel' ), true ) || \pge_feature( $key ) ) {
					return $m[0];
				}

				$inner = $m[1];

				// Badge, alongside the phase chip it belongs to. Wrapped in a
				// row rather than dropped in as a sibling: the card is a flex
				// column, so a bare second chip would take a line of its own
				// under the first instead of sitting beside it.
				$inner = preg_replace(
					'#(<span class="pgh-phase pgh-phase--' . preg_quote( $key, '#' ) . '">.*?</span>)#s',
					'<span class="pgh-phaserow">$1<span class="pgh-soon">' . esc_html__( 'Coming soon', 'prepgro-theme' ) . '</span></span>',
					$inner,
					1
				);

				// The CTA keeps its link but changes what it promises. It points
				// at the pillar's landing page, which is written for exactly
				// this state — what the pillar is, that it is closed, and the
				// countdown to when it opens. Only the label was a lie.
				$inner = preg_replace(
					'#(<a class="pgh-linkarrow"[^>]*>).*?(<svg)#s',
					'$1' . esc_html__( 'See when it opens', 'prepgro-theme' ) . '$2',
					$inner,
					1
				);

				// Countdown between the stat block and the link, when a launch
				// date is set. Inserted through a callback, not a replacement
				// string: the countdown is arbitrary markup and preg_replace
				// would read any $-sequence in it as a backreference.
				$cd = function_exists( 'pgt_countdown' ) ? pgt_countdown( $key ) : '';
				if ( '' !== $cd ) {
					$inner = preg_replace_callback(
						'#</div>\s*<a class="pgh-linkarrow"#s',
						static function ( $hit ) use ( $cd ) {
							return '</div>' . $cd . '<a class="pgh-linkarrow"';
						},
						$inner,
						1
					);
				}

				return '<article class="pgh-loopcard pgh-loopcard--soon">' . $inner . '</article>';
			},
			$content
		);
	}

	/**
	 * The Elevate loop card promises "8 live 1:1 classes a month" — true only
	 * once the Live Tutor plans are ARMED (tutor-seed-packages.php `arm`
	 * populates `live_teacher_package_ids`). Pre-launch, that promise on the
	 * front page is a lie about a product nobody can buy, so while the plans
	 * are dormant this rewrites the card to the lessons-only truth with a
	 * waitlist line. Same render_block mechanism as gate_loop_cards, and the
	 * armed check is the engine's own — the SAME source the booking gate
	 * reads, so the front page and the checkout can never disagree.
	 *
	 * @param string              $content Rendered block HTML.
	 * @param array<string,mixed> $block   Parsed block.
	 * @return string
	 */
	public function gate_tutor_promise( $content, $block ) {
		if ( empty( $block['blockName'] ) || 'core/html' !== $block['blockName'] ) {
			return $content;
		}
		if ( false === strpos( $content, 'Lessons + tutor on demand' ) ) {
			return $content;
		}
		// Elevate off entirely → gate_loop_cards already badged the card.
		if ( ! function_exists( 'pge_feature' ) || ! \pge_feature( 'elevate' ) ) {
			return $content;
		}
		// Armed = plans on sale = the promise is true. Leave it.
		if ( class_exists( '\\PrepGro\\Engine\\Core\\Package_Bundles' )
			&& ! empty( \PrepGro\Engine\Core\Package_Bundles::live_teacher_package_ids() ) ) {
			return $content;
		}

		$content = str_replace(
			'<h3>Lessons + tutor on demand</h3>',
			'<h3>' . esc_html__( 'Lessons that close the gaps', 'prepgro-theme' ) . '</h3>',
			$content
		);
		$content = str_replace(
			'<p>A weekly plan of short lessons, plus 8 live 1:1 classes a month if you want them.</p>',
			'<p>' . esc_html__( 'A weekly plan of short lessons mapped to your diagnostic. Live 1:1 tutoring opens soon — join the waitlist from the study portal.', 'prepgro-theme' ) . '</p>',
			$content
		);
		$content = str_replace(
			'<div class="pgh-statrow"><span>Live classes</span><span class="pgh-mono">8 / month</span></div>',
			'<div class="pgh-statrow"><span>' . esc_html__( 'Live 1:1 tutoring', 'prepgro-theme' ) . '</span><span class="pgh-mono">' . esc_html__( 'Waitlist', 'prepgro-theme' ) . '</span></div>',
			$content
		);

		return $content;
	}


	/**
	 * Hero float cards ([pgt_home_floats]) — the four sample product screens
	 * beside the hero copy, moved out of templates/front-page.html so the
	 * marked strings can follow the active country pack. Everything else is
	 * the template's original markup byte for byte, and every fallback below
	 * reproduces it exactly — a site with no pack content (or no engine at
	 * all) renders today's US bytes unchanged.
	 *
	 * @return string
	 */
	/**
	 * [pgt_hero_skyline] — the pack's small landmark line-art, pinned to the
	 * hero floor behind the copy and float cards. Empty output when the
	 * active pack draws nothing, so pack-less installs render unchanged.
	 *
	 * @return string
	 */
	public function render_hero_skyline() {
		$svg = function_exists( 'pge_content' ) ? (string) pge_content( 'skyline_svg', '' ) : '';
		if ( '' === $svg ) {
			return '';
		}
		return '<div class="pgh-hero__skyline" aria-hidden="true">' . $svg . '</div>';
	}

	/**
	 * The hero's opening line ([pgt_home_kicker]). With no pack content the
	 * US status chip renders byte for byte. A pack that authors
	 * content.homepage.eyebrow ("Provincial exam prep for Canadian
	 * students") gets a country line ABOVE that chip — the flag chip the
	 * topbar already caches, plus the eyebrow — so the first thing a
	 * visitor reads on the front page names the country, not only the
	 * product. Until this existed, /ca/ and /us/ opened with identical
	 * words.
	 *
	 * @return string
	 */
	public function render_home_kicker() {
		$chip = '<span class="pgh-statuschip"><span class="pgh-statuschip__dot" aria-hidden="true"></span>Free readiness check &middot; no card</span>';

		if ( ! function_exists( 'pge_content' ) ) {
			return $chip;
		}
		$eyebrow = trim( (string) pge_content( 'homepage.eyebrow', '' ) );
		if ( '' === $eyebrow ) {
			return $chip;
		}

		$flag = class_exists( __NAMESPACE__ . '\Chrome' ) ? Chrome::instance()->country_chip_html() : '';

		return '<p class="pgh-hero__kicker">' . $flag . '<span class="pgh-hero__kicker-text">' . esc_html( $eyebrow ) . '</span></p>' . "\n      " . $chip;
	}

	public function render_home_floats() {
		$note  = 'On track for the May SAT date.';
		$tutor = 'Maya R. · SAT math';
		$score = '1340';
		$delta = '+80';

		if ( function_exists( 'pge_content' ) ) {
			$note  = (string) pge_content( 'front_page.float_card', $note );
			$tutor = (string) pge_content( 'front_page.tutor_line', $tutor );

			$card = pge_content( 'homepage.score_card', array() );
			if ( is_array( $card ) ) {
				if ( isset( $card['score'] ) && '' !== trim( (string) $card['score'] ) ) {
					$score = (string) $card['score'];
				}
				if ( isset( $card['delta'] ) && '' !== trim( (string) $card['delta'] ) ) {
					$delta = (string) $card['delta'];
				}
			}
		}

		$html  = '<div class="pgh-floats" role="group" aria-label="Sample product screens. Illustrative data.">' . "\n";
		$html .= '      <div class="pgh-fcard pgh-fcard--1">' . "\n";
		$html .= '        <div class="pgh-fcard__head">' . "\n";
		$html .= '          <span class="pgh-fcard__label">Readiness</span>' . "\n";
		$html .= '          <span class="pgh-mono pgh-fcard__pct">68%</span>' . "\n";
		$html .= '        </div>' . "\n";
		$html .= '        <div class="pgh-track"><i class="pgh-track__fill pgh-track__fill--brand" style="--w:68%"></i></div>' . "\n";
		$html .= '        <p class="pgh-fcard__note">' . esc_html( $note ) . '</p>' . "\n";
		$html .= '      </div>' . "\n";
		$html .= "\n";
		$html .= '      <div class="pgh-fcard pgh-fcard--2">' . "\n";
		$html .= '        <span class="pgh-phase pgh-phase--evaluate">Evaluate</span>' . "\n";
		$html .= '        <p class="pgh-fcard__title">4 skills to fix</p>' . "\n";
		$html .= '        <div class="pgh-skills">' . "\n";
		$html .= '          <div class="pgh-skill"><span>Linear equations</span><div class="pgh-track pgh-track--sm"><i class="pgh-track__fill" style="--w:84%;--c:var(--blue-600)"></i></div></div>' . "\n";
		$html .= '          <div class="pgh-skill"><span>Reading evidence</span><div class="pgh-track pgh-track--sm"><i class="pgh-track__fill" style="--w:71%;--c:var(--blue-600)"></i></div></div>' . "\n";
		$html .= '          <div class="pgh-skill"><span>Data analysis</span><div class="pgh-track pgh-track--sm"><i class="pgh-track__fill" style="--w:42%;--c:var(--amber-600)"></i></div></div>' . "\n";
		$html .= '        </div>' . "\n";
		$html .= '      </div>' . "\n";
		$html .= "\n";
		$html .= '      <div class="pgh-fcard pgh-fcard--3">' . "\n";
		$html .= '        <span class="pgh-phase pgh-phase--elevate">Elevate</span>' . "\n";
		$html .= '        <p class="pgh-fcard__title">This week&rsquo;s plan</p>' . "\n";
		$html .= '        <div class="pgh-tasks">' . "\n";
		$html .= '          <div class="pgh-task is-done"><span class="pgh-tick" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>3 lessons &middot; data analysis</div>' . "\n";
		$html .= '          <div class="pgh-task is-done"><span class="pgh-tick" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>12-question timed set</div>' . "\n";
		$html .= '          <div class="pgh-task"><span class="pgh-tick" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>Full practice test</div>' . "\n";
		$html .= '        </div>' . "\n";
		$html .= '      </div>' . "\n";
		$html .= "\n";
		$html .= '      <div class="pgh-fcard pgh-fcard--4">' . "\n";
		$html .= '        <span class="pgh-phase pgh-phase--excel">Excel</span>' . "\n";
		$html .= '        <div class="pgh-tutorline">' . "\n";
		$html .= '          <span class="pgh-avatar" aria-hidden="true">' . esc_html( $this->initials_from( $tutor ) ) . '</span>' . "\n";
		$html .= '          <span class="pgh-tutorline__copy">' . "\n";
		$html .= '            <span class="pgh-tutorline__name">' . $this->middot_html( $tutor ) . '</span>' . "\n";
		$html .= '            <span class="pgh-tutorline__meta">Live class &middot; Thu 4:30pm</span>' . "\n";
		$html .= '          </span>' . "\n";
		$html .= '        </div>' . "\n";
		$html .= '        <div class="pgh-fcard__foot">' . "\n";
		$html .= '          <span>Practice score</span>' . "\n";
		$html .= '          <span class="pgh-mono">' . esc_html( $score ) . ' <span class="pgh-up">' . esc_html( $delta ) . '</span></span>' . "\n";
		$html .= '        </div>' . "\n";
		$html .= '      </div>' . "\n";
		$html .= '    </div>';

		return $html;
	}

	/**
	 * Trust strip row ([pgt_home_trust]). The first three items are universal
	 * product facts and stay verbatim; the fourth ("50 / states covered") is a
	 * US claim, so on a non-US deployment it becomes the live count of that
	 * country's regions under the pack's own vocabulary ("7 emirates covered").
	 *
	 * @return string
	 */
	public function render_home_trust() {
		$html  = '<div class="pgh-trust__row">' . "\n";
		$html .= '      <div class="pgh-trust__item"><span class="pgh-mono pgh-trust__n">20 min</span><span class="pgh-trust__l">free readiness check</span></div>' . "\n";
		$html .= '      <div class="pgh-trust__item"><span class="pgh-mono pgh-trust__n">4</span><span class="pgh-trust__l">skills named, in order</span></div>' . "\n";
		$html .= '      <div class="pgh-trust__item"><span class="pgh-mono pgh-trust__n">8</span><span class="pgh-trust__l">live classes a month</span></div>' . "\n";
		$html .= '      ' . $this->trust_states_item() . "\n";
		$html .= '    </div>';

		return $html;
	}

	/**
	 * The country-aware fourth trust item.
	 *
	 * The US keeps its authored bytes on purpose: Geo_Data lists 51 rows (DC
	 * rides along for the signup dropdown) while "50 states" is the marketing
	 * truth this markup was written around — recomputing would change copy on
	 * the very install the verbatim item belongs to. A country with no
	 * sub-national data at all (e.g. Singapore) also keeps the US item, per
	 * the fall-back-to-verbatim rule.
	 *
	 * @return string
	 */
	private function trust_states_item() {
		$default = '<div class="pgh-trust__item"><span class="pgh-mono pgh-trust__n">50</span><span class="pgh-trust__l">states covered</span></div>';

		if ( ! function_exists( 'pge_content' ) || ! function_exists( 'pge_country_code' )
			|| ! function_exists( 'pge_country_val' ) || ! class_exists( '\PrepGro\Engine\Geo_Data' ) ) {
			return $default;
		}

		$code = pge_country_code();
		if ( 'US' === $code ) {
			return $default;
		}

		$states = \PrepGro\Engine\Geo_Data::get_states( $code );
		if ( empty( $states ) ) {
			return $default;
		}

		$regional = pge_country_val( 'regional', array() );
		$label    = ( is_array( $regional ) && ! empty( $regional['state_label'] ) ) ? (string) $regional['state_label'] : 'State';

		return '<div class="pgh-trust__item"><span class="pgh-mono pgh-trust__n">' . esc_html( (string) count( $states ) ) . '</span><span class="pgh-trust__l">' . esc_html( $this->plural_region_word( $label ) . ' covered' ) . '</span></div>';
	}

	/**
	 * Tutor band rows ([pgt_home_tutorband]). With no pack content the three
	 * US sample rows render byte for byte. When the pack authors
	 * front_page.tutor_rows ("Subject · skills" strings) the pack only names
	 * ONE real person (front_page.tutor_line), so row 1 carries that name and
	 * rows 2–3 lead with the subject instead — no invented people. The slot
	 * chips are generic illustrative times, not people or places, so they stay.
	 *
	 * @return string
	 */
	public function render_home_tutorband() {
		$html = '<div class="pgh-tutorrows">' . "\n";
		foreach ( $this->tutorband_rows() as $row ) {
			$html .= '        <div class="pgh-tutorrow"><span class="pgh-avatar pgh-avatar--lg" aria-hidden="true">' . $row['initials'] . '</span><div class="pgh-tutorrow__copy"><p class="pgh-tutorrow__name">' . $row['name'] . '</p>' . ( '' !== $row['detail'] ? '<p class="pgh-tutorrow__detail">' . $row['detail'] . '</p>' : '' ) . '</div><span class="pgh-tutorrow__slot">' . $row['slot'] . '</span></div>' . "\n";
		}
		$html .= '      </div>';

		return $html;
	}

	/**
	 * The tutor band's row data — US defaults, or rows derived from the pack.
	 * Values are returned escaped/entity-ready for direct concatenation.
	 *
	 * @return array<int,array{initials:string,name:string,detail:string,slot:string}>
	 */
	private function tutorband_rows() {
		$defaults = array(
			array(
				'initials' => 'MR',
				'name'     => 'Maya R.',
				'detail'   => 'SAT math &middot; data analysis, algebra',
				'slot'     => 'Thu 4:30pm',
			),
			array(
				'initials' => 'DP',
				'name'     => 'Daniel P.',
				'detail'   => 'High school math &middot; geometry',
				'slot'     => 'Tue 6:00pm',
			),
			array(
				'initials' => 'SL',
				'name'     => 'Sofia L.',
				'detail'   => 'AP statistics &middot; probability',
				'slot'     => 'Sat 10:00am',
			),
		);

		if ( ! function_exists( 'pge_content' ) ) {
			return $defaults;
		}

		$pack_rows = pge_content( 'front_page.tutor_rows', array() );
		if ( ! is_array( $pack_rows ) || empty( $pack_rows ) ) {
			return $defaults;
		}

		$tutor_line = (string) pge_content( 'front_page.tutor_line', '' );
		$tutor_name = '' !== $tutor_line ? trim( explode( '·', $tutor_line, 2 )[0] ) : '';

		$slots = array( 'Thu 4:30pm', 'Tue 6:00pm', 'Sat 10:00am' );
		$rows  = array();

		foreach ( array_slice( array_values( $pack_rows ), 0, 3 ) as $i => $pack_row ) {
			$parts   = array_map( 'trim', explode( '·', (string) $pack_row, 2 ) );
			$subject = $parts[0];
			$skills  = isset( $parts[1] ) ? $parts[1] : '';

			if ( 0 === $i && '' !== $tutor_name ) {
				// The one authored person: name up top, full subject · skills line under it.
				$name   = $tutor_name;
				$detail = (string) $pack_row;
			} else {
				$name   = $subject;
				$detail = $skills;
			}

			$rows[] = array(
				'initials' => esc_html( $this->initials_from( 0 === $i && '' !== $tutor_name ? $tutor_name : $subject ) ),
				'name'     => $this->middot_html( $name ),
				'detail'   => $this->middot_html( $detail ),
				'slot'     => esc_html( isset( $slots[ $i ] ) ? $slots[ $i ] : '' ),
			);
		}

		return $rows;
	}

	/**
	 * "Maya R. · SAT math" → "MR": the first letters of the first two words,
	 * middots stripped first so the separator never contributes an initial.
	 *
	 * @param string $text Source text.
	 * @return string
	 */
	private function initials_from( $text ) {
		$text     = str_replace( array( '·', '&middot;' ), ' ', $text );
		$initials = '';
		foreach ( preg_split( '/\s+/u', trim( $text ) ) as $part ) {
			if ( '' === $part || ! preg_match( '/\p{L}/u', $part, $m ) ) {
				continue;
			}
			$initials .= mb_strtoupper( $m[0] );
			if ( mb_strlen( $initials ) >= 2 ) {
				break;
			}
		}
		return $initials;
	}

	/**
	 * Escape pack text for output, then render any middots as the &middot;
	 * entity the surrounding template markup already uses.
	 *
	 * @param string $text Plain text (may contain "·").
	 * @return string
	 */
	private function middot_html( $text ) {
		return str_replace( '·', '&middot;', esc_html( $text ) );
	}

	/**
	 * The pack's sub-national word, lowercased ("state", "emirate",
	 * "bundesland"), for prose like "Browse by state". Falls back to "state"
	 * so a pack-less install keeps its current strings exactly.
	 *
	 * @return string
	 */
	private function region_word() {
		if ( function_exists( 'pge_country_val' ) ) {
			$regional = pge_country_val( 'regional', array() );
			if ( is_array( $regional ) && ! empty( $regional['state_label'] ) ) {
				return mb_strtolower( (string) $regional['state_label'] );
			}
		}
		return 'state';
	}

	/**
	 * Lowercase plural of a region label: State → states, Emirate → emirates,
	 * County → counties. Bundesland gets its real plural rather than an
	 * English -s bolted onto a German noun.
	 *
	 * @param string $label Region label from the pack.
	 * @return string
	 */
	private function plural_region_word( $label ) {
		$word      = mb_strtolower( $label );
		$irregular = array(
			'bundesland' => 'bundesländer',
		);
		if ( isset( $irregular[ $word ] ) ) {
			return $irregular[ $word ];
		}
		if ( preg_match( '/[^aeiou]y$/u', $word ) ) {
			return mb_substr( $word, 0, -1 ) . 'ies';
		}
		if ( preg_match( '/(s|x|z|ch|sh)$/u', $word ) ) {
			return $word . 'es';
		}
		return $word . 's';
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
		$html  = '<section class="pgh-section pgh-photoband" id="pg-band"><div class="pgh-container pgh-photoband__row">';

		$html .= '<div class="pgh-photoband__main">'
			. Media::slot(
				'pgt_home_band_main',
				array(
					'height' => 'clamp(240px, 34vw, 360px)',
					'radius' => '20px',
					'sizes'  => '(max-width: 900px) 100vw, 62vw',
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
			. Media::slot(
				'pgt_home_band_side',
				array(
					'height' => '100%',
					'radius' => '20px',
					'sizes'  => '(max-width: 900px) 100vw, 32vw',
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

		$word = $this->region_word();

		$html  = '<section class="pgt-section pgt-section--tint"><div class="pgt-container">';
		$html .= '<p class="pgt-eyebrow">' . esc_html__( 'By location', 'prepgro-theme' ) . '</p>';
		/* translators: %s: the country pack's sub-national word, e.g. "state" or "emirate". */
		$html .= '<h2 class="pgt-section__title">' . esc_html( sprintf( __( 'Browse by %s', 'prepgro-theme' ), $word ) ) . '</h2>';
		/* translators: %s: the country pack's sub-national word, e.g. "state" or "emirate". */
		$html .= '<p class="pgt-lead" style="max-width:56ch;margin:.25rem 0 2rem;">' . esc_html( sprintf( __( 'Practice tests aligned to each %s&rsquo;s assessments and standards.', 'prepgro-theme' ), $word ) ) . '</p>';
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
