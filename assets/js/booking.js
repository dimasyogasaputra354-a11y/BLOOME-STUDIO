(function () {
	'use strict';

	var form = document.getElementById('booking-form');
	var steps = document.querySelectorAll('[data-booking-step]');
	var progress = document.querySelectorAll('.booking-progress span');

	if (!form || steps.length !== 4) {
		return;
	}

	var selectedPackage = null;
	var selectedSessions = [];
	var packageNextButton = document.querySelector('[data-next-step="3"]');
	var scheduleNextButton = document.querySelector('[data-next-step="4"]');
	var sourceOtherField = document.querySelector('.other-source-field');
	var sourceOtherInput = document.querySelector('[name="source_other"]');
	var healthNotesInput = document.getElementById('health-notes');
	var injuryStatement = document.getElementById('injury-statement');
	var statementFileInput = document.querySelector('[name="statement_file"]');
	var dateInput = document.getElementById('schedule-date');
	var slotsElement = document.getElementById('schedule-slots');
	var sessionsElement = document.getElementById('selected-sessions');
	var counterElement = document.querySelector('.booking-session-counter');
	var scheduleError = document.getElementById('schedule-error');
	var lastSlots = [];

	function showStep(step) {
		for (var i = 0; i < steps.length; i++) {
			steps[i].hidden = i !== step - 1;
			progress[i].classList.toggle('is-active', i < step);
		}
		window.scrollTo(0, 0);
		steps[step - 1].querySelector('h1').focus();
	}

	function setScheduleError(message) {
		scheduleError.textContent = message;
		scheduleError.hidden = !message;
	}

	function updateInjuryStatement() {
		var hasHealthNotes = healthNotesInput.value.trim() !== '';
		injuryStatement.hidden = !hasHealthNotes;
		statementFileInput.required = hasHealthNotes;
	}

	function renderSelectedSessions() {
		sessionsElement.innerHTML = '';
		for (var i = 0; i < selectedSessions.length; i++) {
			var item = document.createElement('div');
			item.className = 'selected-session';
			item.textContent = selectedSessions[i].date + ' · ' + selectedSessions[i].start_time + ' · ' + selectedSessions[i].coach;
			var remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'selected-session-remove';
			remove.setAttribute('aria-label', 'Hapus jadwal ' + selectedSessions[i].date + ' ' + selectedSessions[i].start_time);
			remove.textContent = '×';
			remove.setAttribute('data-remove-session', String(i));
			item.appendChild(remove);
			sessionsElement.appendChild(item);

			var hidden = document.createElement('input');
			hidden.type = 'hidden';
			hidden.name = 'session_slots[]';
			hidden.value = selectedSessions[i].schedule_id + '|' + selectedSessions[i].date;
			sessionsElement.appendChild(hidden);
		}

		if (selectedPackage) {
			counterElement.textContent = 'Jadwal terpilih: ' + selectedSessions.length + ' dari ' + selectedPackage.count
				+ ' sesi. Pilih beberapa kelas di tanggal yang sama jika kamu mau.';
			scheduleNextButton.disabled = selectedSessions.length !== selectedPackage.count;
		}
	}

	function getStartDate() {
		if (!selectedSessions.length) {
			return null;
		}

		var startDate = selectedSessions[0].date;
		for (var i = 1; i < selectedSessions.length; i++) {
			if (selectedSessions[i].date < startDate) {
				startDate = selectedSessions[i].date;
			}
		}
		return startDate;
	}

	function updateDateRange() {
		if (!selectedPackage) {
			return;
		}

		if (!selectedSessions.length) {
			dateInput.min = window.bloomeBooking.today;
			dateInput.max = window.bloomeBooking.latestDate;
			document.getElementById('schedule-validity-note').textContent =
				'Pilih sesi pertama hingga dua tahun ke depan. Masa berlaku paket dihitung sejak tanggal tersebut.';
		} else {
			var startDate = getStartDate();
			var validityDays = selectedPackage.validity > 0 ? selectedPackage.validity : 365;
			var lastDate = new Date(startDate + 'T00:00:00Z');
			lastDate.setUTCDate(lastDate.getUTCDate() + validityDays);
			dateInput.min = startDate;
			dateInput.max = lastDate.toISOString().slice(0, 10);
			document.getElementById('schedule-validity-note').textContent =
				'Semua sesi harus dijadwalkan dari tanggal sesi pertama hingga ' + dateInput.max
				+ ' (masa berlaku ' + validityDays + ' hari).';
		}

		if (dateInput.value && (dateInput.value < dateInput.min || dateInput.value > dateInput.max)) {
			dateInput.value = '';
			slotsElement.innerHTML = '<p class="booking-empty-slots">Pilih tanggal untuk melihat jadwal.</p>';
		}
	}

	function packageChanged(input) {
		var packageInputs = form.querySelectorAll('input[name="package_id"]');
		for (var packageIndex = 0; packageIndex < packageInputs.length; packageIndex++) {
			packageInputs[packageIndex].closest('.booking-package').classList.toggle('is-selected', packageInputs[packageIndex] === input);
		}

		selectedPackage = {
			id: input.value,
			count: parseInt(input.getAttribute('data-count'), 10),
			validity: parseInt(input.getAttribute('data-validity'), 10),
			classType: input.getAttribute('data-class-type'),
			name: input.getAttribute('data-label'),
			price: input.getAttribute('data-price-label')
		};
		packageNextButton.disabled = false;
		selectedSessions = [];
		renderSelectedSessions();
		dateInput.value = '';
		updateDateRange();
		slotsElement.innerHTML = '<p class="booking-empty-slots">Pilih tanggal untuk melihat jadwal.</p>';
	}

	function renderSlots(slots, date) {
		lastSlots = slots;
		slotsElement.innerHTML = '';
		if (!slots.length) {
			slotsElement.innerHTML = '<p class="booking-empty-slots">Tidak ada kelas Pilates pada tanggal ini.</p>';
			return;
		}

		for (var i = 0; i < slots.length; i++) {
			var slot = slots[i];
			var isSelected = false;
			for (var j = 0; j < selectedSessions.length; j++) {
				if (selectedSessions[j].schedule_id === slot.schedule_id && selectedSessions[j].date === date) {
					isSelected = true;
				}
			}

			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'schedule-slot' + (slot.is_full ? ' is-full' : '') + (isSelected ? ' is-selected' : '');
			button.disabled = slot.is_full && !isSelected;
			button.setAttribute('data-schedule-id', String(slot.schedule_id));
			button.setAttribute('data-date', date);
			button.setAttribute('data-start', slot.start_time);
			button.setAttribute('data-coach', slot.coach);
			button.setAttribute('data-class-type', slot.class_type);
			var detail = slot.booked + '/' + slot.capacity + ' terisi';
			button.innerHTML = '<span class="schedule-slot-time"></span><span class="schedule-slot-copy"><strong></strong><small></small></span><span class="schedule-slot-status"></span>';
			button.querySelector('.schedule-slot-time').textContent = slot.start_time + (slot.end_time ? '–' + slot.end_time : '');
			button.querySelector('.schedule-slot-copy strong').textContent = slot.coach;
			button.querySelector('.schedule-slot-copy small').textContent = slot.class_type;
			button.querySelector('.schedule-slot-status').textContent = slot.is_full ? 'Penuh' : (isSelected ? 'Dipilih' : detail);
			slotsElement.appendChild(button);
		}
	}

	for (var i = 0; i < form.querySelectorAll('input[name="source"]').length; i++) {
		form.querySelectorAll('input[name="source"]')[i].addEventListener('change', function () {
			var showOther = this.value === 'Lainnya';
			sourceOtherField.hidden = !showOther;
			sourceOtherInput.required = showOther;
		});
	}

	healthNotesInput.addEventListener('input', updateInjuryStatement);
	updateInjuryStatement();

	for (var packageRadioIndex = 0; packageRadioIndex < form.querySelectorAll('input[name="package_id"]').length; packageRadioIndex++) {
		form.querySelectorAll('input[name="package_id"]')[packageRadioIndex].addEventListener('change', function () {
			packageChanged(this);
		});
	}
	var restoredPackage = form.querySelector('input[name="package_id"]:checked');
	if (restoredPackage) {
		packageChanged(restoredPackage);
	}

	form.addEventListener('click', function (event) {
		var nextStep = event.target.closest('[data-next-step]');
		var previousStep = event.target.closest('[data-previous-step]');
		var removeSession = event.target.closest('[data-remove-session]');

		if (nextStep) {
			var step = parseInt(nextStep.getAttribute('data-next-step'), 10);
			if (step === 2) {
				var requiredFields = steps[0].querySelectorAll('[required]');
				for (var i = 0; i < requiredFields.length; i++) {
					if (!requiredFields[i].checkValidity()) {
						requiredFields[i].reportValidity();
						return;
					}
				}
			}
			if (step === 3 && !selectedPackage) {
				alert('Pilih paket Pilates terlebih dahulu.');
				return;
			}
			if (step === 4 && selectedSessions.length !== selectedPackage.count) {
				alert('Pilih jadwal untuk seluruh sesi pada paket.');
				return;
			}
			if (step === 4) {
				var summary = document.querySelector('.booking-final-summary');
				summary.textContent = selectedPackage.name + ' · ' + selectedPackage.price + ' · ' + selectedSessions.length + ' jadwal';
			}
			showStep(step);
		}
		if (previousStep) {
			showStep(parseInt(previousStep.getAttribute('data-previous-step'), 10));
		}
		if (removeSession) {
			selectedSessions.splice(parseInt(removeSession.getAttribute('data-remove-session'), 10), 1);
			updateDateRange();
			renderSelectedSessions();
			if (dateInput.value) {
				dateInput.dispatchEvent(new Event('change'));
			}
		}
	});

	dateInput.addEventListener('change', function () {
		if (!selectedPackage || !dateInput.value) {
			return;
		}
		setScheduleError('');
		slotsElement.innerHTML = '<p class="booking-empty-slots">Memuat ketersediaan jadwal…</p>';
		var requestUrl = window.bloomeBooking.availabilityUrl + '?date=' + encodeURIComponent(dateInput.value)
			+ '&package_id=' + encodeURIComponent(selectedPackage.id);
		if (selectedSessions.length) {
			requestUrl += '&start_date=' + encodeURIComponent(getStartDate());
		}
		fetch(requestUrl, { credentials: 'same-origin' })
			.then(function (response) {
				if (!response.ok) {
					return response.json().then(function (data) {
						throw new Error(data.error || 'Jadwal tidak dapat dimuat.');
					});
				}
				return response.json();
			})
			.then(function (data) {
				renderSlots(data.slots, data.date);
			})
			.catch(function (error) {
				slotsElement.innerHTML = '';
				setScheduleError(error.message || 'Terjadi kesalahan saat memuat jadwal.');
			});
	});

	slotsElement.addEventListener('click', function (event) {
		var button = event.target.closest('.schedule-slot');
		if (!button || button.disabled || !selectedPackage) {
			return;
		}

		var scheduleId = parseInt(button.getAttribute('data-schedule-id'), 10);
		var date = button.getAttribute('data-date');
		var existingIndex = -1;
		for (var i = 0; i < selectedSessions.length; i++) {
			if (selectedSessions[i].schedule_id === scheduleId && selectedSessions[i].date === date) {
				existingIndex = i;
				break;
			}
		}
		if (existingIndex >= 0) {
			selectedSessions.splice(existingIndex, 1);
		} else {
			if (selectedSessions.length >= selectedPackage.count) {
				setScheduleError('Paket ' + selectedPackage.name + ' mencakup ' + selectedPackage.count
					+ ' sesi total dan semuanya sudah dipilih. Kamu boleh memilih beberapa kelas pada tanggal yang sama; hapus pilihan yang ingin diganti.');
				return;
			}
			selectedSessions.push({
				schedule_id: scheduleId,
				date: date,
				start_time: button.getAttribute('data-start'),
				coach: button.getAttribute('data-coach')
			});
		}
		setScheduleError('');
		updateDateRange();
		renderSelectedSessions();
		renderSlotsFromCurrentResponse();
	});

	function renderSlotsFromCurrentResponse() {
		if (dateInput.value) {
			renderSlots(lastSlots, dateInput.value);
		}
	}

	form.addEventListener('submit', function (event) {
		if (!selectedPackage || selectedSessions.length !== selectedPackage.count) {
			event.preventDefault();
			alert('Pilih jadwal untuk seluruh sesi pada paket.');
			showStep(3);
			return;
		}
		var paymentMethod = form.querySelector('input[name="payment_method"]:checked');
		if (!paymentMethod) {
			event.preventDefault();
			alert('Pilih metode pembayaran.');
			return;
		}
	});
}());
