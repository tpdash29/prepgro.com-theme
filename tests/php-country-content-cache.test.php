<?php
/**
 * Chrome's country-content cache — the bundle heals itself.
 *
 * Contract under test (inc/class-chrome.php, "Country-dependent content"):
 *   - A persisted bundle is trusted only when its 'key' matches
 *     {PGE_COUNTRY}@{PGT_VERSION}+{PGE_VERSION}:{mtime of class-chrome.php}; anything
 *     else (no key, another country, an older build) re-seeds on read.
 *   - A bundle resolved with no country declared (engine inactive) is
 *     returned for the request but never persisted.
 *   - A declared country with no flag on file (unknown code) IS persisted
 *     (blank chip is the truth for it, and the key re-seeds when a flag
 *     ships).
 *   - The US locale line is byte-identical to the pre-2026-09 output.
 *   - Flag CSS variables derive from the pack's flag_strip hexes.
 *
 * PGE_COUNTRY is a constant, so each country runs as a child process
 * (argv scenario) — one process, one country, as in real life.
 *
 * Standalone: no WordPress, no PHPUnit.
 * Run: php tests/php-country-content-cache.test.php
 */

$scenario = isset( $argv[1] ) ? $argv[1] : 'main';

if ( 'main' === $scenario ) {
	$fail = 0;
	foreach ( array( 'blank', 'ca', 'ca-noengine', 'de', 'us', 'xx', 'kicker-us', 'kicker-ca' ) as $s ) {
		$o = array(); $rc = 0;
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' ' . $s . ' 2>&1', $o, $rc );
		echo implode( "\n", $o ), "\n";
		if ( 0 !== $rc ) { $fail++; }
	}
	echo $fail ? "php-country-content-cache: $fail scenario(s) FAILED\n" : "php-country-content-cache: all scenarios passed\n";
	exit( $fail ? 1 : 0 );
}

// ── WP surface the class touches, stubbed ───────────────────────────────
define( 'ABSPATH', __DIR__ . '/' );
define( 'PGT_VERSION', '9.9.9-test' );
define( 'PGT_DIR', dirname( __DIR__ ) );
define( 'PGT_URI', 'https://example.test/wp-content/themes/prepgro-theme' );
$GLOBALS['t_options'] = array();
$GLOBALS['t_pack']    = array();   // what pge_content() answers
$GLOBALS['t_country'] = array();   // what pge_country_val() answers
$GLOBALS['t_home']    = '';        // what pge_global_home_url() answers

function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['t_options'] ) ? $GLOBALS['t_options'][ $k ] : $d; }
function update_option( $k, $v, $autoload = null ) { $GLOBALS['t_options'][ $k ] = $v; return true; }
function apply_filters( $tag, $value ) { return $value; }
function add_action() { return true; }
function add_filter() { return true; }
function add_shortcode() { return true; }
function __( $s, $d = null ) { return $s; }
function esc_html__( $s, $d = null ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_url( $s ) { return (string) $s; }
if ( 'ca-noengine' !== $scenario ) { // that scenario = engine bailed before its helpers loaded
	function pge_content( $key, $default = null ) { return array_key_exists( $key, $GLOBALS['t_pack'] ) ? $GLOBALS['t_pack'][ $key ] : $default; }
	function pge_country_val( $key, $default = null ) { return array_key_exists( $key, $GLOBALS['t_country'] ) ? $GLOBALS['t_country'][ $key ] : $default; }
	function pge_global_home_url() { return $GLOBALS['t_home']; }
	function pge_pack_locale() { return isset( $GLOBALS['t_pack_locale'] ) ? $GLOBALS['t_pack_locale'] : ''; }
}

if ( 'blank' !== $scenario ) {
	define( 'PGE_COUNTRY', array( 'ca-noengine' => 'ca', 'kicker-us' => 'us', 'kicker-ca' => 'ca' )[ $scenario ] ?? $scenario );
}

require dirname( __DIR__ ) . '/inc/class-chrome.php';

$chrome = \PrepGro\Theme\Chrome::instance();
$ref    = new ReflectionClass( $chrome );
$read   = $ref->getMethod( 'country_content' );
$keyfn  = $ref->getMethod( 'country_content_key' );
$memo   = $ref->getProperty( 'country_content_memo' );
$key    = $keyfn->invoke( $chrome );

$pass = 0; $fail = 0;
function check( $label, $cond ) {
	global $pass, $fail;
	if ( $cond ) { $pass++; } else { $fail++; echo "  FAIL: $label\n"; }
}
function fresh( $chrome, $memo ) { $memo->setValue( $chrome, null ); }

$stamp = \PrepGro\Theme\Chrome::COUNTRY_CONTENT_SCHEMA . '.' . (int) filesize( dirname( __DIR__ ) . '/inc/class-chrome.php' );

switch ( $scenario ) {

	case 'blank':
		check( 'key has an empty code (and no engine version in this harness)', "@9.9.9-test+:$stamp" === $key );
		$b = $read->invoke( $chrome );
		check( 'blank code → empty chip', '' === $b['country_chip_html'] );
		check( 'blank code → empty locale line', '' === $b['locale_line_html'] );
		check( 'blank code is NOT persisted', ! array_key_exists( 'pgt_country_content', $GLOBALS['t_options'] ) );
		check( 'bundle still carries the key', $key === $b['key'] );
		$b2 = $read->invoke( $chrome );
		check( 'second read in the request is the memo', $b === $b2 );
		break;

	case 'ca':
		$GLOBALS['t_pack']    = array( 'flag_strip' => array( '#d52b1e', '#FFF', '#d52b1e', 'not-a-colour' ) );
		$GLOBALS['t_country'] = array( 'locale' => 'en_CA' );
		$GLOBALS['t_home']    = 'https://prepgro.com/';

		// 1. The production poison: a bundle seeded with no country, no key.
		$GLOBALS['t_options']['pgt_country_content'] = array(
			'code' => '', 'topbar_phrases' => array( 'x' ), 'country_chip_html' => '', 'locale_line_html' => '',
		);
		$b = $read->invoke( $chrome );
		check( 'poisoned bundle re-seeds on read', false !== strpos( $b['country_chip_html'], 'title="Canada"' ) );
		check( 'chip carries the flag svg', false !== strpos( $b['country_chip_html'], 'pgt-countrychip__flag' ) );
		check( 'persisted with the current key', $key === $GLOBALS['t_options']['pgt_country_content']['key'] );
		check( 'key names the country', 0 === strpos( $key, 'ca@' ) );
		check( 'locale line: Canada · English', false !== strpos( $b['locale_line_html'], '<span class="pgt-footer__locale">Canada · English</span>' ) );
		check( 'locale line: Change country link', false !== strpos( $b['locale_line_html'], 'class="pgt-footer__locale-switch" href="https://prepgro.com/"' ) );
		check( 'no bare whitespace between the two (minify trap)', false === strpos( $b['locale_line_html'], '</span> <a' ) );
		check( 'flag vars: a', false !== strpos( $b['flag_vars_css'], '--pgt-flag-a:#d52b1e;--pgt-flag-a-rgb:213,43,30;' ) );
		check( 'flag vars: 3-digit hex expands', false !== strpos( $b['flag_vars_css'], '--pgt-flag-b:#ffffff;--pgt-flag-b-rgb:255,255,255;' ) );
		check( 'flag vars: junk skipped, 3 bands', false !== strpos( $b['flag_vars_css'], '--pgt-flag-bands:3;' ) );
		check( 'flag vars wrapped in :root', 0 === strpos( $b['flag_vars_css'], ':root{' ) );
		check( 'tint slots skip the white band', false !== strpos( $b['flag_vars_css'], '--pgt-flag-tint-1-rgb:213,43,30;--pgt-flag-tint-2-rgb:213,43,30;' ) );
		check( 'public accessor matches the bundle', $chrome->country_chip_html() === $b['country_chip_html'] );

		// 2. A stale key (older build) re-seeds too.
		fresh( $chrome, $memo );
		$GLOBALS['t_options']['pgt_country_content'] = array( 'key' => 'ca@0.0.1:1', 'code' => 'ca', 'country_chip_html' => 'STALE' );
		$b = $read->invoke( $chrome );
		check( 'older build re-seeds', 'STALE' !== $b['country_chip_html'] );

		// 3. Another country's key (cloned DB) re-seeds.
		fresh( $chrome, $memo );
		$GLOBALS['t_options']['pgt_country_content'] = array( 'key' => "us@9.9.9-test+:$stamp", 'code' => 'us', 'country_chip_html' => 'USA' );
		$b = $read->invoke( $chrome );
		check( 'cloned-from-US bundle re-seeds to CA', false !== strpos( $b['country_chip_html'], 'Canada' ) );

		// 4. A current key is trusted verbatim — no rebuild on the hot path.
		fresh( $chrome, $memo );
		$GLOBALS['t_options']['pgt_country_content'] = array( 'key' => $key, 'code' => 'ca', 'country_chip_html' => 'TRUSTED' );
		$b = $read->invoke( $chrome );
		check( 'current key is trusted, not rebuilt', 'TRUSTED' === $b['country_chip_html'] );

		// 5. Flag vars: no strip → nothing (no accent), still persisted.
		fresh( $chrome, $memo );
		unset( $GLOBALS['t_options']['pgt_country_content'] );
		$GLOBALS['t_pack'] = array();
		$b = $read->invoke( $chrome );
		check( 'no flag strip → no vars', '' === $b['flag_vars_css'] );
		check( 'no flag strip still persists (it is a real answer)', isset( $GLOBALS['t_options']['pgt_country_content'] ) );
		break;

	case 'ca-noengine':
		// PGE_COUNTRY says ca, but the engine bailed before its helpers
		// (country-conflict guard on a cloned DB): a real-looking key with a
		// half-built bundle must not be persisted.
		$b = $read->invoke( $chrome );
		check( 'chip still resolves (theme-owned)', false !== strpos( $b['country_chip_html'], 'Canada' ) );
		check( 'no flag vars without the engine', '' === $b['flag_vars_css'] );
		check( 'NOT persisted while the engine is absent', ! array_key_exists( 'pgt_country_content', $GLOBALS['t_options'] ) );
		break;

	case 'de':
		$GLOBALS['t_pack']        = array( 'flag_strip' => array( '#000000', '#dd0000', '#ffce00' ) );
		$GLOBALS['t_country']     = array( 'locale' => 'de_DE' );
		$GLOBALS['t_pack_locale'] = 'en_DE'; // the engine's English-content rule
		$b = $read->invoke( $chrome );
		check( 'black band is not a tint', false !== strpos( $b['flag_vars_css'], '--pgt-flag-tint-1-rgb:221,0,0;--pgt-flag-tint-2-rgb:255,206,0;' ) );
		check( 'band a is still black for anyone who wants it', false !== strpos( $b['flag_vars_css'], '--pgt-flag-a:#000000;' ) );
		check( 'Germany · English (content language, not the locale tag)', false !== strpos( $b['locale_line_html'], 'Germany · English' ) );
		break;

	case 'kicker-us':
	case 'kicker-ca':
		require dirname( __DIR__ ) . '/inc/class-homepage-sections.php';
		$hs      = \PrepGro\Theme\Homepage_Sections::instance();
		$literal = '<span class="pgh-statuschip"><span class="pgh-statuschip__dot" aria-hidden="true"></span>Free readiness check &middot; no card</span>';
		if ( 'kicker-us' === $scenario ) {
			check( 'US front page: the exact former template line, byte for byte', $literal === $hs->render_home_kicker() );
		} else {
			$GLOBALS['t_pack'] = array( 'homepage.eyebrow' => 'Provincial exam prep for Canadian students' );
			$out = $hs->render_home_kicker();
			check( 'CA: kicker carries the cached flag chip', false !== strpos( $out, 'pgt-countrychip" title="Canada"' ) );
			check( 'CA: kicker carries the eyebrow, escaped', false !== strpos( $out, '<span class="pgh-hero__kicker-text">Provincial exam prep for Canadian students</span>' ) );
			check( 'CA: the status chip still follows, unchanged', substr( $out, -strlen( $literal ) ) === $literal );
		}
		break;

	case 'us':
		$GLOBALS['t_country'] = array( 'locale' => 'en_US' );
		$GLOBALS['t_home']    = ''; // root install: no landing known
		$b = $read->invoke( $chrome );
		check( 'US locale line byte-identical to the pre-change output', '<span class="pgt-footer__locale">United States · English</span>' === $b['locale_line_html'] );
		check( 'US chip', false !== strpos( $b['country_chip_html'], 'title="United States"' ) );
		check( 'US phrases are the US set', in_array( 'You’ve got this.', $b['topbar_phrases'], true ) );
		check( 'no pack content → no flag vars', '' === $b['flag_vars_css'] );
		break;

	case 'xx':
		$b = $read->invoke( $chrome );
		check( 'unknown country → empty chip', '' === $b['country_chip_html'] );
		check( 'unknown country → empty locale line', '' === $b['locale_line_html'] );
		check( 'unknown country IS persisted (keyed, re-seeds when a flag ships)', isset( $GLOBALS['t_options']['pgt_country_content']['key'] ) && 0 === strpos( $GLOBALS['t_options']['pgt_country_content']['key'], 'xx@' ) );
		check( 'unknown country gets the idiom-light phrase set', ! in_array( 'You’ve got this.', $b['topbar_phrases'], true ) );
		break;
}

echo "scenario $scenario: $pass passed, $fail failed\n";
exit( $fail ? 1 : 0 );
