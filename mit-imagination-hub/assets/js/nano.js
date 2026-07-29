/*
 * MIT Imagination Hub — front-end behaviour.
 *
 * The mobile hamburger is provided by the core Navigation block (overlayMenu),
 * so this file only handles video: lazy-play looping muted clips when they
 * scroll into view and pause them when they leave (saves battery/bandwidth and
 * keeps many tiles from playing at once). Honours prefers-reduced-motion.
 */
( function () {
	'use strict';

	var reduceMotion =
		window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	// Data-saver or reduced-motion: never auto-load a clip — the poster stands in.
	var saveData = !! ( navigator.connection && navigator.connection.saveData );
	var lite = reduceMotion || saveData;
	// Phones: the hero shows its poster only, so the full background video is
	// never pulled over cellular (kinder on CampusPress bandwidth).
	var smallScreen = !! ( window.matchMedia && window.matchMedia( '(max-width: 781px)' ).matches );

	function tryPlay( video ) {
		if ( lite ) {
			return;
		}
		var p = video.play();
		if ( p && typeof p.catch === 'function' ) {
			p.catch( function () {
				/* Autoplay can be blocked; poster/first frame stays visible. */
			} );
		}
	}

	// Detach a video's source so the browser drops its decoder + decoded frames
	// (a page full of muted videos otherwise keeps them ALL decoded at once, which
	// can exhaust GPU memory and crash the tab). The poster stays visible.
	function releaseVideo( video ) {
		var source = video.querySelector( 'source' );
		if ( source && source.getAttribute( 'src' ) ) {
			video.setAttribute( 'data-nano-src', source.getAttribute( 'src' ) );
			source.removeAttribute( 'src' );
			video.load();
		}
	}

	function restoreVideo( video ) {
		var saved = video.getAttribute( 'data-nano-src' );
		if ( ! saved ) {
			return;
		}
		var source = video.querySelector( 'source' );
		if ( source ) {
			source.setAttribute( 'src', saved );
			video.removeAttribute( 'data-nano-src' );
			video.load();
		}
	}

	// Hard cap on how many clips may be DECODED at once. Many simultaneous video
	// decoders exhaust the GPU and crash the tab on modest hardware, so we keep a
	// tiny most-recently-seen pool and release everything else (poster shows).
	var MAX_ACTIVE_VIDEOS = 2;
	var activeVideos = [];

	function activateVideo( video ) {
		var i = activeVideos.indexOf( video );
		if ( i !== -1 ) {
			activeVideos.splice( i, 1 );
		}
		activeVideos.unshift( video ); // most-recent at the front
		restoreVideo( video );
		tryPlay( video );
		// Evict the least-recently-seen beyond the cap.
		while ( activeVideos.length > MAX_ACTIVE_VIDEOS ) {
			var evicted = activeVideos.pop();
			evicted.pause();
			releaseVideo( evicted );
		}
	}

	function deactivateVideo( video ) {
		var i = activeVideos.indexOf( video );
		if ( i !== -1 ) {
			activeVideos.splice( i, 1 );
		}
		video.pause();
		releaseVideo( video );
	}

	function init() {
		// Manage every clip (hero included) so nothing decodes off-screen.
		var videos = Array.prototype.slice.call(
			document.querySelectorAll( 'video[data-nano-video]' )
		);

		if ( ! videos.length ) {
			return;
		}

		// Data-saver / reduced-motion: leave every clip as its poster image.
		if ( lite ) {
			return;
		}

		// The hero is poster-only on phones. Its source is detached in markup, so
		// dropping it here means the clip is never fetched on small screens.
		var managed = videos.filter( function ( video ) {
			return ! ( smallScreen && 'hero' === video.getAttribute( 'data-nano-video' ) );
		} );

		if ( ! ( 'IntersectionObserver' in window ) ) {
			managed.slice( 0, MAX_ACTIVE_VIDEOS ).forEach( activateVideo );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						activateVideo( entry.target );
					} else {
						deactivateVideo( entry.target );
					}
				} );
			},
			{ rootMargin: '0px', threshold: 0.35 }
		);

		managed.forEach( function ( video ) {
			observer.observe( video );
		} );
	}

	// News slider: the arrow advances one page and loops the cards in a circle —
	// the track is cloned so it keeps moving forward, then resets seamlessly once
	// it reaches the cloned copy (never scrolling backward).
	function initSliders() {
		Array.prototype.forEach.call(
			document.querySelectorAll( '[data-nano-slide]' ),
			function ( btn ) {
				var section = btn.closest( 'section' );
				var viewport = section && section.querySelector( '[data-nano-slider]' );
				var track = viewport && viewport.querySelector( '.nano-news__grid' );
				if ( ! viewport || ! track ) {
					return;
				}

				var originalCount = track.children.length;
				if ( originalCount < 2 ) {
					return;
				}

				// Clone the cards once so forward motion wraps around seamlessly.
				for ( var i = 0; i < originalCount; i++ ) {
					var clone = track.children[ i ].cloneNode( true );
					clone.setAttribute( 'aria-hidden', 'true' );
					Array.prototype.forEach.call(
						clone.querySelectorAll( 'a, button, input' ),
						function ( el ) {
							el.setAttribute( 'tabindex', '-1' );
						}
					);
					// Swap the cloned <video>s for their poster image — the clones
					// are duplicates only shown mid-loop, and extra <video> elements
					// are a big GPU/memory cost (a page full of them can crash the tab).
					Array.prototype.forEach.call(
						clone.querySelectorAll( 'video' ),
						function ( v ) {
							var img = document.createElement( 'img' );
							img.src = v.getAttribute( 'poster' ) || '';
							img.alt = '';
							img.className = ( v.className || '' ).replace(
								'nano-media--video',
								'nano-media--image'
							);
							v.parentNode.replaceChild( img, v );
						}
					);
					track.appendChild( clone );
				}

				function loopWidth() {
					// scrollLeft where the cloned set begins == the same view as 0.
					return track.children[ originalCount ].offsetLeft;
				}

				// Jump back by one set (no animation) once we're inside the cloned
				// copy, so the forward loop is seamless and never scrolls backward.
				function normalize() {
					var lw = loopWidth();
					if ( lw && viewport.scrollLeft >= lw - 2 ) {
						var prev = viewport.style.scrollBehavior;
						viewport.style.scrollBehavior = 'auto';
						viewport.scrollLeft -= lw;
						viewport.style.scrollBehavior = prev;
					}
				}

				// For touch / trackpad swipes (scrollend where supported, else a
				// debounced scroll fallback).
				viewport.addEventListener( 'scrollend', normalize );
				var settle;
				viewport.addEventListener( 'scroll', function () {
					clearTimeout( settle );
					settle = setTimeout( normalize, 140 );
				} );

				// For the arrow: advance a page, then normalize after it settles
				// (doesn't depend on scroll events firing).
				btn.addEventListener( 'click', function () {
					viewport.scrollBy( { left: viewport.clientWidth, behavior: 'smooth' } );
					setTimeout( normalize, 650 );
				} );
			}
		);
	}

	// Expose the screen's aspect ratio so the initiative media panels can match
	// the proportions of the screen (kept current on resize / orientation).
	function setScreenRatio() {
		document.documentElement.style.setProperty(
			'--nano-screen-ratio',
			window.innerWidth + ' / ' + window.innerHeight
		);
	}

	// Secondary pages: the compact wordmark fades out as the page scrolls down and
	// drifts up slower than the scroll (a light parallax), while the hamburger stays
	// fixed. Tied to scrollY, so scrolling back up restores it. Home is untouched.
	function initHeaderScroll() {
		if ( document.body.classList.contains( 'home' ) ) {
			return;
		}
		var logo = document.querySelector( '.nano-header .nano-brand' );
		if ( ! logo ) {
			return;
		}

		var FADE = 260;                         // px of scroll over which it fades out
		var drift = reduceMotion ? 0 : 0.35;    // parallax factor (0 = no movement)
		var ticking = false;

		function update() {
			var y =
				window.pageYOffset || document.documentElement.scrollTop || 0;
			var opacity = 1 - y / FADE;
			if ( opacity < 0 ) {
				opacity = 0;
			} else if ( opacity > 1 ) {
				opacity = 1;
			}
			logo.style.opacity = opacity;
			logo.style.transform = drift
				? 'translateY(' + ( -y * drift ).toFixed( 1 ) + 'px)'
				: '';
			// Once invisible, don't let the ghost logo swallow clicks.
			logo.style.pointerEvents = opacity < 0.05 ? 'none' : '';
			ticking = false;
		}

		function onScroll() {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		}

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		update();
	}

	// Overlay menu submenus: keep a dropdown open while the cursor travels from the
	// parent down to its items. Pure CSS :hover drops the moment the pointer crosses
	// a gap; a short close-delay ("hover intent") tolerates that so the items stay
	// reachable. Keyboard focus is handled by :focus-within in CSS.
	function initMenuHover() {
		var parents = Array.prototype.slice.call(
			document.querySelectorAll( '.nano-nav .wp-block-navigation-item.has-child' )
		);

		function closeNow( li ) {
			clearTimeout( li.nanoTimer );
			li.classList.remove( 'nano-subopen' );
		}

		parents.forEach( function ( li ) {
			var link = li.querySelector( ':scope > .wp-block-navigation-item__content' );
			if ( link ) {
				// Stop a mouse click from focusing the parent — otherwise
				// :focus-within pins its flyout open and it won't collapse when the
				// pointer moves to another category. Keyboard focus is untouched, so
				// tab-through still opens the flyout.
				link.addEventListener( 'mousedown', function ( e ) {
					e.preventDefault();
				} );
				link.addEventListener( 'click', function ( e ) {
					var href = link.getAttribute( 'href' ) || '';
					// Placeholder parents (href="#") are just flyout triggers: don't
					// jump to the top of the page.
					if ( '#' === href || '' === href ) {
						e.preventDefault();
					}
					parents.forEach( function ( other ) {
						if ( other !== li ) {
							closeNow( other );
						}
					} );
					clearTimeout( li.nanoTimer );
					li.classList.add( 'nano-subopen' );
				} );
			}

			li.addEventListener( 'mouseenter', function () {
				// Switching to another category closes the others instantly, so two
				// flyouts never overlap. The delay below only covers reaching this
				// category's own items.
				parents.forEach( function ( other ) {
					if ( other !== li ) {
						closeNow( other );
					}
				} );
				clearTimeout( li.nanoTimer );
				li.classList.add( 'nano-subopen' );
			} );
			li.addEventListener( 'mouseleave', function () {
				clearTimeout( li.nanoTimer );
				li.nanoTimer = setTimeout( function () {
					li.classList.remove( 'nano-subopen' );
				}, 350 );
			} );
		} );
	}

	// Archive page: a mixed grid of Events + News filtered by three combinable
	// dropdowns (Type / Initiative / Year). The dropdowns start from the URL
	// (?type=&initiative=&year=, set server-side) so links can deep-link into a
	// pre-filtered view, and the URL is kept in sync as the filters change.
	function initArchive() {
		var root = document.querySelector( '[data-nano-archive]' );
		if ( ! root ) {
			return;
		}
		var typeSel = root.querySelector( '[data-nano-archive-type]' );
		var initSel = root.querySelector( '[data-nano-archive-initiative]' );
		var yearSel = root.querySelector( '[data-nano-archive-year]' );
		var grid = root.querySelector( '[data-nano-archive-grid]' );
		var classesWrap = root.querySelector( '[data-nano-archive-classes]' );
		var note = root.querySelector( '[data-nano-archive-note]' );
		var chronoCards = grid ? Array.prototype.slice.call( grid.querySelectorAll( '.nano-card' ) ) : [];
		var termGroups = classesWrap ? Array.prototype.slice.call( classesWrap.querySelectorAll( '[data-nano-archive-termgroup]' ) ) : [];
		var empty = root.querySelector( '.nano-archive__empty' );
		var emptyGeneric = empty ? empty.getAttribute( 'data-empty-generic' ) : '';
		var emptyClasses = empty ? empty.getAttribute( 'data-empty-classes' ) : '';
		var prevType = typeSel ? typeSel.value : 'all';

		function syncUrl( ty, ini, yr ) {
			if ( ! window.history || ! window.history.replaceState || ! window.URLSearchParams ) {
				return;
			}
			var params = new URLSearchParams( window.location.search );
			function put( key, val ) {
				if ( val && 'all' !== val ) {
					params.set( key, val );
				} else {
					params.delete( key );
				}
			}
			put( 'ftype', ty );
			// In Classes mode the Initiative is auto-constrained to Pedagogies, so
			// it is implied rather than written into the URL.
			put( 'finit', 'classes' === ty ? 'all' : ini );
			put( 'fyear', yr );
			var qs = params.toString();
			window.history.replaceState(
				null,
				'',
				window.location.pathname + ( qs ? '?' + qs : '' ) + window.location.hash
			);
		}

		function apply() {
			var ty = typeSel ? typeSel.value : 'all';
			var yr = yearSel ? yearSel.value : 'all';

			// Type=Classes constrains the Initiative filter to Pedagogies and
			// disables the control (conveyed accessibly, not only greyed). Leaving
			// Classes re-enables it and resets to All.
			if ( initSel ) {
				if ( 'classes' === ty ) {
					initSel.value = 'pedagogies';
					initSel.disabled = true;
					initSel.setAttribute( 'aria-disabled', 'true' );
				} else {
					if ( 'classes' === prevType ) {
						initSel.value = 'all';
					}
					initSel.disabled = false;
					initSel.removeAttribute( 'aria-disabled' );
				}
			}
			if ( note ) {
				note.hidden = 'classes' !== ty;
			}
			var ini = ( 'classes' === ty ) ? 'pedagogies' : ( initSel ? initSel.value : 'all' );
			var shown = 0;

			// Chronological grid — News + Events.
			var showGrid = 'classes' !== ty;
			if ( grid ) {
				grid.hidden = ! showGrid;
			}
			if ( showGrid ) {
				chronoCards.forEach( function ( card ) {
					var okType = 'all' === ty || card.getAttribute( 'data-type' ) === ty;
					var okInit = 'all' === ini || card.getAttribute( 'data-initiative' ) === ini;
					var okYear = 'all' === yr || card.getAttribute( 'data-year' ) === yr;
					var show = okType && okInit && okYear;
					card.hidden = ! show;
					if ( show ) {
						shown++;
					}
				} );
			}

			// Classes listing — grouped by term. Shown under Classes, and under
			// All when the Initiative filter is All or Pedagogies.
			var showClasses = 'classes' === ty || ( 'all' === ty && ( 'all' === ini || 'pedagogies' === ini ) );
			var classesShown = 0;
			if ( showClasses ) {
				termGroups.forEach( function ( g ) {
					var okYear = 'all' === yr || g.getAttribute( 'data-year' ) === yr;
					g.hidden = ! okYear;
					if ( okYear ) {
						classesShown += g.querySelectorAll( '.nano-card' ).length;
					}
				} );
			}
			if ( classesWrap ) {
				classesWrap.hidden = ! showClasses || 0 === classesShown;
			}
			shown += classesShown;

			if ( empty ) {
				empty.textContent = 'classes' === ty ? emptyClasses : emptyGeneric;
				empty.hidden = shown > 0;
			}

			prevType = ty;
			syncUrl( ty, ini, yr );
		}

		[ typeSel, initSel, yearSel ].forEach( function ( sel ) {
			if ( sel ) {
				sel.addEventListener( 'change', apply );
			}
		} );
		apply();
	}

	// Event-gallery videos: click (or Enter/Space on the button) swaps the poster
	// for a playing <video>. Nothing autoplays — the media stays a poster image
	// until the visitor asks for it.
	function initGalleryVideos() {
		Array.prototype.forEach.call(
			document.querySelectorAll( '.nano-gallery__play' ),
			function ( btn ) {
				btn.addEventListener( 'click', function () {
					var src = btn.getAttribute( 'data-nano-video-src' );
					if ( ! src ) {
						return;
					}
					var img = btn.querySelector( 'img' );
					var poster = img ? img.getAttribute( 'src' ) : '';
					var video = document.createElement( 'video' );
					video.className = 'nano-media nano-media--video';
					video.setAttribute( 'controls', '' );
					video.setAttribute( 'playsinline', '' );
					video.setAttribute( 'preload', 'metadata' );
					if ( poster ) {
						video.setAttribute( 'poster', poster );
					}
					var source = document.createElement( 'source' );
					source.setAttribute( 'src', src );
					source.setAttribute( 'type', 'video/mp4' );
					video.appendChild( source );
					btn.parentNode.replaceChild( video, btn );
					var p = video.play();
					if ( p && typeof p.catch === 'function' ) {
						p.catch( function () {} );
					}
				} );
			}
		);
	}

	function boot() {
		setScreenRatio();
		initSliders(); // clone slider cards first…
		init();        // …then observe lazy videos (originals + clones)
		initHeaderScroll();
		initMenuHover();
		initArchive();
		initGalleryVideos();
		window.addEventListener( 'resize', setScreenRatio );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
