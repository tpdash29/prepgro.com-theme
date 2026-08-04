/**
 * PrepGro Theme — chrome interactions.
 * Mobile menu toggle + scroll reveals + animated stat counters.
 * All motion respects prefers-reduced-motion.
 */
( function () {
	'use strict';

	var REDUCED = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* ---------- Header disclosures ----------
	 * One controller for the three mutually exclusive header regions: the mega
	 * panels, the search band and the account menu. Opening any one closes the
	 * other two (README "Interactions & Behavior").
	 *
	 * Modules open on mouseenter and toggle on click; the whole header wrapper
	 * closes the open panel on mouseleave. Touch and keyboard never depend on
	 * hover — click/Enter is always an opener, and the module name stays a real
	 * link, so a tap on it navigates rather than only disclosing.
	 */
	function initHeaderDisclosures() {
		var wrap = document.querySelector( '[data-pgt-headerwrap]' );
		if ( ! wrap ) {
			return;
		}

		var panelTriggers = Array.prototype.slice.call( wrap.querySelectorAll( '[data-pgt-panel]' ) );
		var searchToggle = wrap.querySelector( '[data-pgt-search-toggle]' );
		var searchBand = wrap.querySelector( '[data-pgt-search]' );
		var accountWrap = wrap.querySelector( '[data-pgt-account]' );
		var accountBtn = accountWrap && accountWrap.querySelector( '.pgt-account__btn' );
		var accountMenu = accountWrap && accountWrap.querySelector( '.pgt-account__menu' );

		var openPanel = null;   // panel key, or null
		var hoverIntent = null; // pointer-type guard

		function panelBody( key ) {
			return wrap.querySelector( '[data-pgt-panel-body="' + key + '"]' );
		}

		function setPanel( key ) {
			panelTriggers.forEach( function ( t ) {
				var mine = t.getAttribute( 'data-pgt-panel' ) === key;
				t.setAttribute( 'aria-expanded', mine ? 'true' : 'false' );
			} );
			panelTriggers.forEach( function ( t ) {
				var body = panelBody( t.getAttribute( 'data-pgt-panel' ) );
				if ( ! body ) {
					return;
				}
				if ( t.getAttribute( 'data-pgt-panel' ) === key ) {
					body.removeAttribute( 'hidden' );
				} else {
					body.setAttribute( 'hidden', '' );
				}
			} );
			openPanel = key;
			if ( key ) {
				closeSearch( false );
				closeAccount( false );
			}
			bindGlobal();
		}

		function closePanels( restoreFocus ) {
			if ( ! openPanel ) {
				return;
			}
			var prev = openPanel;
			setPanel( null );
			if ( restoreFocus ) {
				var trigger = wrap.querySelector( '[data-pgt-panel="' + prev + '"]' );
				if ( trigger ) {
					trigger.focus();
				}
			}
		}

		function openSearch() {
			if ( ! searchBand || ! searchToggle ) {
				return;
			}
			searchBand.removeAttribute( 'hidden' );
			searchToggle.setAttribute( 'aria-expanded', 'true' );
			setPanel( null );
			closeAccount( false );
			var input = searchBand.querySelector( '.pgt-search__input' );
			if ( input ) {
				input.focus();
			}
			bindGlobal();
		}

		function closeSearch( restoreFocus ) {
			if ( ! searchBand || ! searchToggle || searchBand.hasAttribute( 'hidden' ) ) {
				return;
			}
			searchBand.setAttribute( 'hidden', '' );
			searchToggle.setAttribute( 'aria-expanded', 'false' );
			if ( restoreFocus ) {
				searchToggle.focus();
			}
		}

		function openAccount() {
			if ( ! accountMenu || ! accountBtn ) {
				return;
			}
			accountMenu.removeAttribute( 'hidden' );
			accountBtn.setAttribute( 'aria-expanded', 'true' );
			setPanel( null );
			closeSearch( false );
			var first = accountMenu.querySelector( 'a' );
			if ( first ) {
				first.focus();
			}
			bindGlobal();
		}

		function closeAccount( restoreFocus ) {
			if ( ! accountMenu || ! accountBtn || accountMenu.hasAttribute( 'hidden' ) ) {
				return;
			}
			accountMenu.setAttribute( 'hidden', '' );
			accountBtn.setAttribute( 'aria-expanded', 'false' );
			if ( restoreFocus ) {
				accountBtn.focus();
			}
		}

		function anythingOpen() {
			return !! openPanel
				|| ( searchBand && ! searchBand.hasAttribute( 'hidden' ) )
				|| ( accountMenu && ! accountMenu.hasAttribute( 'hidden' ) );
		}

		function onKeydown( e ) {
			if ( e.key !== 'Escape' ) {
				return;
			}
			e.preventDefault();
			if ( openPanel ) {
				closePanels( true );
			} else if ( searchBand && ! searchBand.hasAttribute( 'hidden' ) ) {
				closeSearch( true );
			} else {
				closeAccount( true );
			}
			bindGlobal();
		}

		function onOutside( e ) {
			if ( ! wrap.contains( e.target ) ) {
				closePanels( false );
				closeSearch( false );
				closeAccount( false );
				bindGlobal();
			}
		}

		var globalBound = false;
		function bindGlobal() {
			var want = anythingOpen();
			if ( want && ! globalBound ) {
				document.addEventListener( 'keydown', onKeydown, true );
				document.addEventListener( 'click', onOutside, true );
				globalBound = true;
			} else if ( ! want && globalBound ) {
				document.removeEventListener( 'keydown', onKeydown, true );
				document.removeEventListener( 'click', onOutside, true );
				globalBound = false;
			}
		}

		panelTriggers.forEach( function ( t ) {
			var key = t.getAttribute( 'data-pgt-panel' );

			t.addEventListener( 'mouseenter', function () {
				if ( hoverIntent === 'touch' ) {
					return;
				}
				setPanel( key );
			} );

			t.addEventListener( 'pointerdown', function ( e ) {
				hoverIntent = e.pointerType === 'mouse' ? 'mouse' : 'touch';
			} );

			t.addEventListener( 'click', function ( e ) {
				// On a link trigger, a plain click should navigate to the module
				// landing page — EXCEPT on touch, where there is no hover to
				// reveal the panel, so the first tap discloses instead.
				var isLink = t.tagName === 'A';
				if ( ! isLink ) {
					e.preventDefault();
					setPanel( openPanel === key ? null : key );
					return;
				}
				if ( hoverIntent === 'touch' && openPanel !== key ) {
					e.preventDefault();
					setPanel( key );
				}
			} );

			t.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'ArrowDown' ) {
					e.preventDefault();
					setPanel( key );
					var body = panelBody( key );
					var first = body && body.querySelector( 'a' );
					if ( first ) {
						first.focus();
					}
				}
			} );
		} );

		// Focus moving out of the header closes whatever it left open.
		wrap.addEventListener( 'focusout', function ( e ) {
			if ( ! e.relatedTarget || ! wrap.contains( e.relatedTarget ) ) {
				closePanels( false );
				closeAccount( false );
				bindGlobal();
			}
		} );

		wrap.addEventListener( 'mouseleave', function () {
			if ( hoverIntent !== 'touch' ) {
				closePanels( false );
				bindGlobal();
			}
		} );

		if ( searchToggle ) {
			searchToggle.addEventListener( 'click', function () {
				if ( searchToggle.getAttribute( 'aria-expanded' ) === 'true' ) {
					closeSearch( true );
					bindGlobal();
				} else {
					openSearch();
				}
			} );
		}

		if ( accountBtn ) {
			accountBtn.addEventListener( 'click', function () {
				if ( accountBtn.getAttribute( 'aria-expanded' ) === 'true' ) {
					closeAccount( true );
					bindGlobal();
				} else {
					openAccount();
				}
			} );
		}
	}

	/* ---------- Drawer accordions ----------
	 * The mobile drawer renders each module panel as an accordion; the module
	 * name itself stays a link, so only the caret toggles. */
	function initDrawerAccordions() {
		var carets = document.querySelectorAll( '.pgt-drawer__caret' );
		Array.prototype.forEach.call( carets, function ( btn ) {
			btn.addEventListener( 'click', function () {
				var id = btn.getAttribute( 'aria-controls' );
				var sub = id && document.getElementById( id );
				if ( ! sub ) {
					return;
				}
				var open = btn.getAttribute( 'aria-expanded' ) === 'true';
				btn.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
				if ( open ) {
					sub.setAttribute( 'hidden', '' );
				} else {
					sub.removeAttribute( 'hidden' );
				}
			} );
		} );
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
		var mq = window.matchMedia( '(min-width: 861px)' );
		var onChange = function ( e ) {
			if ( e.matches && burger.getAttribute( 'aria-expanded' ) === 'true' ) {
				close( false );
			}
		};
		if ( mq.addEventListener ) {
			mq.addEventListener( 'change', onChange );
		}
	}


	/* ---------- Pricing level tabs ----------
	 * All four levels are already in the DOM, so switching is a visibility
	 * swap plus a history update — no request, and the chart re-renders with
	 * the cards because both read the same server-rendered markup. Tabs are
	 * real links to ?level=…, so this only enhances: with JS off the click
	 * still navigates and the server picks the level. */
	function initPricingLevels() {
		var root = document.querySelector( '[data-pgt-pricing]' );
		if ( ! root ) {
			return;
		}
		var tabs = Array.prototype.slice.call( root.querySelectorAll( '[data-pgt-level]' ) );
		if ( ! tabs.length ) {
			return;
		}

		function show( level ) {
			root.setAttribute( 'data-level', level );

			tabs.forEach( function ( t ) {
				var on = t.getAttribute( 'data-pgt-level' ) === level;
				t.classList.toggle( 'is-on', on );
				t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			} );

			// Price sets (both cards) and the "Showing prices for…" name.
			[ 'data-pgt-price', 'data-pgt-levelname' ].forEach( function ( attr ) {
				Array.prototype.forEach.call( root.querySelectorAll( '[' + attr + ']' ), function ( el ) {
					if ( el.getAttribute( attr ) === level ) {
						el.removeAttribute( 'hidden' );
					} else {
						el.setAttribute( 'hidden', '' );
					}
				} );
			} );

			// The chart highlights the selected level's row.
			Array.prototype.forEach.call( root.querySelectorAll( '[data-pgt-chartrow]' ), function ( el ) {
				el.classList.toggle( 'is-on', el.getAttribute( 'data-pgt-chartrow' ) === level );
			} );
		}

		tabs.forEach( function ( t ) {
			t.addEventListener( 'click', function ( e ) {
				if ( e.metaKey || e.ctrlKey || e.shiftKey || e.button ) {
					return; // let the browser open it however the user asked
				}
				e.preventDefault();
				var level = t.getAttribute( 'data-pgt-level' );
				show( level );
				if ( window.history && window.history.pushState ) {
					window.history.pushState( { pgtLevel: level }, '', t.getAttribute( 'href' ) );
				}
			} );
		} );

		window.addEventListener( 'popstate', function () {
			var m = /[?&]level=([a-z]+)/.exec( window.location.search );
			var level = m ? m[ 1 ] : 'high';
			if ( root.querySelector( '[data-pgt-level="' + level + '"]' ) ) {
				show( level );
			}
		} );
	}

	/* ---------- Exam index filters ----------
	 * Every card is already in the DOM; the chips just hide the ones that do
	 * not match. Progressive: with JS off all exams show, which is the honest
	 * default for an index. */
	function initExamFilters() {
		var chips = document.querySelectorAll( '[data-pgt-filter]' );
		if ( ! chips.length ) {
			return;
		}
		var cards = document.querySelectorAll( '[data-pgt-family]' );

		Array.prototype.forEach.call( chips, function ( chip ) {
			chip.addEventListener( 'click', function () {
				var want = chip.getAttribute( 'data-pgt-filter' );

				Array.prototype.forEach.call( chips, function ( c ) {
					var on = c === chip;
					c.classList.toggle( 'is-on', on );
					c.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
				} );

				Array.prototype.forEach.call( cards, function ( card ) {
					if ( want === 'all' || card.getAttribute( 'data-pgt-family' ) === want ) {
						card.removeAttribute( 'hidden' );
					} else {
						card.setAttribute( 'hidden', '' );
					}
				} );
			} );
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
		initHeaderDisclosures();
		initDrawer();
		initDrawerAccordions();
		initPricingLevels();
		initExamFilters();
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
