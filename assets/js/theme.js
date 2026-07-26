/**
 * PrepGro Theme — chrome interactions.
 * Mobile menu toggle + scroll reveals + animated stat counters.
 * All motion respects prefers-reduced-motion.
 */
( function () {
	'use strict';

	var REDUCED = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* ---------- Header scroll compaction ----------
	 * Adds .is-scrolled past a small threshold; CSS shrinks the bar and adds
	 * a shadow. rAF-throttled; the transition itself is disabled by CSS under
	 * prefers-reduced-motion. */
	function initHeaderCompaction() {
		var header = document.querySelector( '[data-pgt-header]' );
		if ( ! header ) {
			return;
		}
		var ticking = false;
		function apply() {
			header.classList.toggle( 'is-scrolled', window.scrollY > 12 );
			ticking = false;
		}
		window.addEventListener( 'scroll', function () {
			if ( ! ticking ) {
				ticking = true;
				requestAnimationFrame( apply );
			}
		}, { passive: true } );
		apply();
	}

	/* ---------- Mobile drawer ----------
	 * Full-screen dialog: scroll-lock, focus trap, Escape, focus restore. */
	function initDrawer() {
		var burger = document.querySelector( '.pgt-burger' );
		var drawer = document.getElementById( 'pgt-drawer' );
		if ( ! burger || ! drawer ) {
			return;
		}
		var closeBtn = drawer.querySelector( '.pgt-drawer__close' );

		function focusables() {
			return drawer.querySelectorAll( 'a[href], button:not([disabled])' );
		}

		function open() {
			burger.setAttribute( 'aria-expanded', 'true' );
			drawer.removeAttribute( 'hidden' );
			// Force a style flush so the opening transition runs from the
			// hidden state instead of being skipped.
			void drawer.offsetHeight;
			drawer.classList.add( 'is-open' );
			document.documentElement.classList.add( 'pgt-no-scroll' );
			track( 'drawer:open' );
			if ( closeBtn ) {
				closeBtn.focus();
			}
			document.addEventListener( 'keydown', onKeydown, true );
		}

		function close( restoreFocus ) {
			burger.setAttribute( 'aria-expanded', 'false' );
			drawer.classList.remove( 'is-open' );
			drawer.setAttribute( 'hidden', '' );
			document.documentElement.classList.remove( 'pgt-no-scroll' );
			document.removeEventListener( 'keydown', onKeydown, true );
			if ( restoreFocus !== false ) {
				burger.focus();
			}
		}

		function onKeydown( e ) {
			if ( e.key === 'Escape' ) {
				e.preventDefault();
				close();
				return;
			}
			if ( e.key !== 'Tab' ) {
				return;
			}
			var f = focusables();
			if ( ! f.length ) {
				return;
			}
			var first = f[ 0 ];
			var last = f[ f.length - 1 ];
			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}

		burger.addEventListener( 'click', function () {
			if ( burger.getAttribute( 'aria-expanded' ) === 'true' ) {
				close();
			} else {
				open();
			}
		} );
		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', function () {
				close();
			} );
		}
		// A tapped link navigates; unlock scroll without stealing focus.
		drawer.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( 'a' ) ) {
				close( false );
			}
		} );
		// Crossing to the desktop layout while open would strand the
		// scroll-lock (CSS hides the drawer above 920px).
		var mq = window.matchMedia( '(min-width: 921px)' );
		var onChange = function ( e ) {
			if ( e.matches && burger.getAttribute( 'aria-expanded' ) === 'true' ) {
				close( false );
			}
		};
		if ( mq.addEventListener ) {
			mq.addEventListener( 'change', onChange );
		}
	}

	/* ---------- Account (avatar) disclosure menu ---------- */
	function initAccountMenu() {
		var wrap = document.querySelector( '[data-pgt-account]' );
		if ( ! wrap ) {
			return;
		}
		var btn = wrap.querySelector( '.pgt-account__btn' );
		var menu = wrap.querySelector( '.pgt-account__menu' );
		if ( ! btn || ! menu ) {
			return;
		}

		function open() {
			btn.setAttribute( 'aria-expanded', 'true' );
			menu.removeAttribute( 'hidden' );
			var first = menu.querySelector( 'a' );
			if ( first ) {
				first.focus();
			}
			document.addEventListener( 'keydown', onKeydown, true );
			document.addEventListener( 'click', onOutside, true );
		}

		function close( restoreFocus ) {
			btn.setAttribute( 'aria-expanded', 'false' );
			menu.setAttribute( 'hidden', '' );
			document.removeEventListener( 'keydown', onKeydown, true );
			document.removeEventListener( 'click', onOutside, true );
			if ( restoreFocus !== false ) {
				btn.focus();
			}
		}

		function onKeydown( e ) {
			if ( e.key === 'Escape' ) {
				e.preventDefault();
				close();
			}
		}

		function onOutside( e ) {
			if ( ! wrap.contains( e.target ) ) {
				close( false );
			}
		}

		btn.addEventListener( 'click', function () {
			if ( btn.getAttribute( 'aria-expanded' ) === 'true' ) {
				close();
			} else {
				open();
			}
		} );
	}

	/* ---------- Navigation analytics ----------
	 * Pushes intent events to a tag manager IF one is present; a hard no-op
	 * otherwise. Consent handling stays the tag manager's job; no PII —
	 * region:id labels only (see MENU_DATA_MODEL.md). */
	function track( label ) {
		if ( ! window.dataLayer || typeof window.dataLayer.push !== 'function' ) {
			return;
		}
		var parts = String( label ).split( ':' );
		window.dataLayer.push( {
			event: label === 'drawer:open' ? 'mobile_menu_opened'
				: ( parts[ 1 ] === 'readiness-cta' ? 'cta_readiness_check_clicked' : 'nav_item_clicked' ),
			nav_region: parts[ 0 ],
			nav_id: parts[ 1 ] || ''
		} );
	}

	function initNavAnalytics() {
		document.addEventListener( 'click', function ( e ) {
			var el = e.target.closest( '[data-nav]' );
			if ( el ) {
				track( el.getAttribute( 'data-nav' ) );
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
		initHeaderCompaction();
		initDrawer();
		initAccountMenu();
		initNavAnalytics();
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
