/**
 * PrepGro Theme — chrome interactions.
 * Mobile menu toggle + scroll reveals + animated stat counters.
 * All motion respects prefers-reduced-motion.
 */
( function () {
	'use strict';

	var REDUCED = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* ---------- Mobile menu ---------- */
	function initMenu() {
		var burger = document.querySelector( '.pgt-burger' );
		var panel = document.getElementById( 'pgt-mobile' );
		if ( ! burger || ! panel ) {
			return;
		}

		burger.addEventListener( 'click', function () {
			var open = burger.getAttribute( 'aria-expanded' ) === 'true';
			burger.setAttribute( 'aria-expanded', String( ! open ) );
			if ( open ) {
				panel.classList.remove( 'is-open' );
				panel.setAttribute( 'hidden', '' );
			} else {
				panel.removeAttribute( 'hidden' );
				panel.classList.add( 'is-open' );
			}
		} );

		// Close when a link is tapped.
		panel.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( 'a' ) ) {
				burger.setAttribute( 'aria-expanded', 'false' );
				panel.classList.remove( 'is-open' );
				panel.setAttribute( 'hidden', '' );
			}
		} );
	}

	/* ---------- Scroll reveals ----------
	 * Auto-tags marketing chrome elements (incl. shortcode-rendered sections
	 * we can't put attributes on) and reveals them on intersect, with a small
	 * stagger inside grids. */
	function initReveals() {
		if ( REDUCED || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var GRIDS = '.pgt-grid, .pgt-categories, .pgt-latest-grid, .pgt-testimonials, .pgt-faq, .pgt-stats';
		var SINGLES = '.pgt-eyebrow, .pgt-section__title, .pgt-section .pgt-lead, .pgt-section__head, .pgt-cta, .pgt-hero__copy, .pgt-hero__visual';

		var targets = [];

		document.querySelectorAll( SINGLES ).forEach( function ( el ) {
			targets.push( el );
		} );

		document.querySelectorAll( GRIDS ).forEach( function ( grid ) {
			var i = 0;
			Array.prototype.forEach.call( grid.children, function ( child ) {
				child.style.setProperty( '--pgt-reveal-delay', Math.min( i * 0.07, 0.5 ) + 's' );
				targets.push( child );
				i++;
			} );
		} );

		/* Pre-trigger: fire while the element is still ~18% below the fold so
		 * content is already settling as it scrolls into view. Users should
		 * never *see* hidden content (audit finding: sections read as broken
		 * half-faded text when reveals lag the scroll). */
		var io = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-in' );
						io.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px 18% 0px', threshold: 0 }
		);

		targets.forEach( function ( el ) {
			el.classList.add( 'pgt-reveal' );
			io.observe( el );
		} );

		/* Safety net: whatever happens (observer quirks, anchors, printing),
		 * nothing stays hidden for more than a few seconds. */
		setTimeout( function () {
			targets.forEach( function ( el ) {
				el.classList.add( 'is-in' );
			} );
		}, 4000 );
	}

	/* ---------- Animated stat counters ----------
	 * Parses rendered values like "1,240+" or "38" and counts up on first view. */
	function initCounters() {
		if ( REDUCED || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var values = document.querySelectorAll( '.pgt-stats__value' );
		if ( ! values.length ) {
			return;
		}

		var io = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}
					io.unobserve( entry.target );
					animateValue( entry.target );
				} );
			},
			{ threshold: 0.5 }
		);

		values.forEach( function ( el ) {
			io.observe( el );
		} );

		function animateValue( el ) {
			var raw = el.textContent.trim();
			var m = raw.match( /^([\d,]+)(.*)$/ );
			if ( ! m ) {
				return; // Non-numeric value (e.g. "Free") — leave as-is.
			}
			var end = parseInt( m[ 1 ].replace( /,/g, '' ), 10 );
			var suffix = m[ 2 ] || '';
			if ( ! end || end < 2 ) {
				return;
			}
			var dur = 1200;
			var start = null;

			function frame( ts ) {
				if ( ! start ) {
					start = ts;
				}
				var p = Math.min( ( ts - start ) / dur, 1 );
				// easeOutCubic
				var eased = 1 - Math.pow( 1 - p, 3 );
				var val = Math.round( end * eased );
				el.textContent = val.toLocaleString() + suffix;
				if ( p < 1 ) {
					requestAnimationFrame( frame );
				}
			}
			requestAnimationFrame( frame );
		}
	}

	/* ---------- Friendly proctor warnings ----------
	 * The engine's ProctorMonitor fires native alert("WARNING n/m: reason")
	 * on violations (tab switch, focus loss, …). Native alerts look like
	 * system crashes and panic younger students. Intercept ONLY that exact
	 * message shape and render a calm, styled overlay instead; every other
	 * alert falls through to the browser untouched. Enforcement (violation
	 * counting, auto-submit) stays entirely in the plugin.
	 */
	function initProctorAlerts() {
		var nativeAlert = window.alert.bind( window );
		var hideTimer = null;

		window.alert = function ( message ) {
			var m = /^WARNING\s+(\d+)\s*\/\s*(\d+)\s*:\s*(.+)$/.exec( String( message ) );
			if ( ! m ) {
				return nativeAlert( message );
			}
			showWarning( parseInt( m[ 1 ], 10 ), parseInt( m[ 2 ], 10 ), m[ 3 ] );
		};

		function showWarning( count, max, reason ) {
			var el = document.getElementById( 'pgt-proctor-toast' );
			if ( ! el ) {
				el = document.createElement( 'div' );
				el.id = 'pgt-proctor-toast';
				el.setAttribute( 'role', 'alertdialog' );
				el.setAttribute( 'aria-live', 'assertive' );
				document.body.appendChild( el );
			}
			var last = count >= max;
			el.innerHTML =
				'<div class="pgt-proctor-toast__card' + ( last ? ' is-final' : '' ) + '">' +
					'<div class="pgt-proctor-toast__head">' + ( last ? 'Last warning!' : 'Heads up!' ) + '</div>' +
					'<p class="pgt-proctor-toast__reason"></p>' +
					'<div class="pgt-proctor-toast__count">Warning ' + count + ' of ' + max +
						( last ? ' — one more and your exam submits automatically.' : ' — stay on this screen and you’re all good.' ) +
					'</div>' +
					'<button type="button" class="pgt-proctor-toast__ok">Got it — back to my test</button>' +
				'</div>';
			el.querySelector( '.pgt-proctor-toast__reason' ).textContent = reason;
			el.classList.add( 'is-open' );

			var close = function () {
				el.classList.remove( 'is-open' );
				clearTimeout( hideTimer );
			};
			el.querySelector( '.pgt-proctor-toast__ok' ).addEventListener( 'click', close );
			clearTimeout( hideTimer );
			hideTimer = setTimeout( close, 6000 );
		}
	}

	function init() {
		initMenu();
		initReveals();
		initCounters();
		initProctorAlerts();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
