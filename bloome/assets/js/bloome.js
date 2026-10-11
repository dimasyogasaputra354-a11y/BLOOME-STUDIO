(function () {
	'use strict';

	var toggle = document.querySelector('.menu-toggle');
	var navigation = document.querySelector('.main-nav');

	if (!toggle || !navigation) {
		return;
	}

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
}());
