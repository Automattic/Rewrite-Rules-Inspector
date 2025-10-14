/**
 * Rewrite Rules Inspector Admin JavaScript
 *
 * @package automattic\rewrite-rules-inspector
 * @since 1.5.0
 */

(function($) {
	'use strict';

	/**
	 * Initialize admin functionality when document is ready.
	 */
	$(document).ready(function() {
		initSmoothScrolling();
		initAccessibilityEnhancements();
	});

	/**
	 * Initialize smooth scrolling for jump links.
	 */
	function initSmoothScrolling() {
		// Check if user prefers reduced motion.
		const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		
		if (prefersReducedMotion) {
			return; // Skip smooth scrolling if user prefers reduced motion.
		}

		// Handle jump link clicks.
		$('.jump-link').on('click', function(e) {
			const href = $(this).attr('href');
			
			// Only handle internal anchor links.
			if (href && href.startsWith('#')) {
				e.preventDefault();
				
				const target = $(href);
				if (target.length) {
					// Calculate offset for WordPress admin toolbar.
					const adminBarHeight = $('#wpadminbar').length ? $('#wpadminbar').outerHeight() : 0;
					const offset = adminBarHeight + 20; // 20px additional spacing
					
					// Smooth scroll to target.
					$('html, body').animate({
						scrollTop: target.offset().top - offset
					}, 500, 'swing');
					
					// Add focus to target for accessibility.
					target.focus();
					
					// Add a temporary highlight effect (subtle).
					target.addClass('rri-highlight');
					setTimeout(function() {
						target.removeClass('rri-highlight');
					}, 1500);
				}
			}
		});
	}

	/**
	 * Initialize accessibility enhancements.
	 */
	function initAccessibilityEnhancements() {
		// Add keyboard navigation support for jump links.
		$('.jump-link').on('keydown', function(e) {
			// Handle Enter and Space key presses.
			if (e.which === 13 || e.which === 32) { // Enter or Space
				e.preventDefault();
				$(this).click();
			}
		});

		// Add ARIA live region for dynamic content updates.
		if (!$('#rri-live-region').length) {
			$('body').append('<div id="rri-live-region" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>');
		}

		// Announce section changes when jumping.
		$('.jump-link').on('click', function() {
			const targetText = $(this).text();
			$('#rri-live-region').text('Navigated to ' + targetText);
		});
	}

	/**
	 * Utility function to check if element is in viewport.
	 */
	function isInViewport(element) {
		const rect = element.getBoundingClientRect();
		return (
			rect.top >= 0 &&
			rect.left >= 0 &&
			rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
			rect.right <= (window.innerWidth || document.documentElement.clientWidth)
		);
	}

	/**
	 * Add scroll spy functionality for better navigation.
	 */
	function initScrollSpy() {
		const sections = $('.rri-section h2[id]');
		
		if (sections.length < 2) {
			return; // No need for scroll spy with less than 2 sections.
		}

		$(window).on('scroll', function() {
			let current = '';
			
			sections.each(function() {
				if (isInViewport(this)) {
					current = $(this).attr('id');
				}
			});
			
			// Update active state of jump links.
			$('.jump-link').removeClass('rri-active');
			if (current) {
				$('.jump-link[href="#' + current + '"]').addClass('rri-active');
			}
		});
	}

	// Initialize scroll spy if there are multiple sections.
	$(document).ready(function() {
		if ($('.rri-section').length > 1) {
			initScrollSpy();
		}
	});

})(jQuery);
