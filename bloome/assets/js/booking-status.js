(function () {
	'use strict';

	var paymentPage = document.querySelector('.booking-payment-page');
	if (!paymentPage) {
		return;
	}

	function refreshStatus() {
		fetch(paymentPage.getAttribute('data-status-url'), { credentials: 'same-origin' })
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Status booking tidak dapat diperiksa.');
				}
				return response.json();
			})
			.then(function (data) {
				if (data.success_url) {
					window.location.assign(data.success_url);
				} else if (data.status === 'expired' || data.status === 'cancelled') {
					window.location.reload();
				}
			})
			.catch(function (error) {
				var message = document.querySelector('.booking-status-error');
				if (message) {
					message.textContent = error.message;
				}
			});
	}

	window.setInterval(refreshStatus, 15000);
}());
