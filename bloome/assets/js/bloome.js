(function () {
	'use strict';

	var toggle = document.querySelector('.menu-toggle');
	var navigation = document.querySelector('.main-nav');

	if (toggle && navigation) {
		toggle.addEventListener('click', function () {
			var isOpen = navigation.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		});

		navigation.addEventListener('click', function (event) {
			if (event.target.closest('a')) {
				navigation.classList.remove('is-open');
				toggle.setAttribute('aria-expanded', 'false');
			}
		});
	}

	var carousel = document.querySelector('[data-hero-carousel]');

	if (!carousel) {
		return;
	}

	var slides = carousel.querySelectorAll('.hero-slide');

	if (slides.length < 2) {
		return;
	}

	var activeIndex = 0;
	var autoplayTimer;
	var pointerStart = null;
	var wheelDistance = 0;
	var wheelTimer;
	var wheelLocked = false;

	function showSlide(index) {
		activeIndex = (index + slides.length) % slides.length;

		Array.prototype.forEach.call(slides, function (slide, slideIndex) {
			var isActive = slideIndex === activeIndex;
			slide.classList.toggle('is-active', isActive);
			slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
		});
	}

	function startAutoplay() {
		window.clearTimeout(autoplayTimer);
		autoplayTimer = window.setTimeout(function advanceSlide() {
			showSlide(activeIndex + 1);
			autoplayTimer = window.setTimeout(advanceSlide, 3000);
		}, 3000);
	}

	function pauseAutoplay() {
		window.clearTimeout(autoplayTimer);
	}

	function moveByGesture(direction) {
		showSlide(activeIndex + direction);
		startAutoplay();
	}

	carousel.addEventListener('pointerdown', function (event) {
		if (event.isPrimary === false) {
			return;
		}

		pointerStart = { x: event.clientX, y: event.clientY };
		carousel.classList.add('is-dragging');
		pauseAutoplay();

		if (carousel.setPointerCapture) {
			carousel.setPointerCapture(event.pointerId);
		}
	});

	carousel.addEventListener('pointerup', function (event) {
		if (!pointerStart) {
			return;
		}

		var deltaX = event.clientX - pointerStart.x;
		var deltaY = event.clientY - pointerStart.y;
		pointerStart = null;
		carousel.classList.remove('is-dragging');

		if (Math.abs(deltaX) > 45 && Math.abs(deltaX) > Math.abs(deltaY)) {
			moveByGesture(deltaX < 0 ? 1 : -1);
		} else {
			startAutoplay();
		}
	});

	carousel.addEventListener('pointercancel', function () {
		pointerStart = null;
		carousel.classList.remove('is-dragging');
		startAutoplay();
	});

	carousel.addEventListener('wheel', function (event) {
		if (Math.abs(event.deltaX) <= Math.abs(event.deltaY)) {
			return;
		}

		event.preventDefault();
		if (wheelLocked) {
			return;
		}

		wheelDistance += event.deltaX;
		window.clearTimeout(wheelTimer);
		wheelTimer = window.setTimeout(function () {
			if (Math.abs(wheelDistance) >= 20) {
				wheelLocked = true;
				moveByGesture(wheelDistance < 0 ? -1 : 1);
				window.setTimeout(function () {
					wheelLocked = false;
				}, 700);
			}

			wheelDistance = 0;
		}, 120);
	}, { passive: false });

	document.addEventListener('visibilitychange', function () {
		if (document.hidden) {
			pauseAutoplay();
		} else {
			startAutoplay();
		}
	});

	startAutoplay();
}());
