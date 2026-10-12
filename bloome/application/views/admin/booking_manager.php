<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$money = function ($amount) {
	return 'Rp ' . number_format((float) $amount, 0, ',', '.');
};
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
$status_labels = array(
	'pending_payment' => 'Menunggu pembayaran',
	'confirmed' => 'Lunas',
	'expired' => 'Kedaluwarsa',
	'cancelled' => 'Dibatalkan'
);
$weekdays = array(1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu');
$schedule_options = array();
foreach ($schedules as $schedule) {
	$schedule_options[] = array(
		'id' => (int) $schedule->id,
		'day' => (int) $schedule->day_of_week,
		'time' => substr($schedule->start_time, 0, 5),
		'class' => $schedule->class_type,
		'coach' => $schedule->coach_name ? $schedule->coach_name : 'Coach belum ditentukan'
	);
}
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title>Data Booking — Bloomè Studio</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&amp;family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&amp;display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css?v=' . filemtime(FCPATH . 'assets/css/admin.css')); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/booking-admin.css?v=' . filemtime(FCPATH . 'assets/css/booking-admin.css')); ?>">
</head>
<body class="dashboard-page">
	<aside class="admin-sidebar">
		<a class="admin-wordmark" href="<?php echo site_url('admin'); ?>">Bloomè <span>Studio</span></a>
		<p class="sidebar-caption">KELOLA WEBSITE</p>
		<nav class="sidebar-nav" aria-label="Navigasi admin">
			<a class="sidebar-link" href="<?php echo site_url('admin'); ?>#daily-report"><span>▤</span> Daily Report</a>
			<a class="sidebar-link" href="<?php echo site_url('admin'); ?>#monthly-report"><span>▥</span> Monthly Report</a>
			<a class="sidebar-link is-active" href="#booking-list"><span>▦</span> Booking</a>
			<a class="sidebar-link" href="#reschedule"><span>↻</span> Reschedule</a>
			<a class="sidebar-link" href="<?php echo site_url('admin'); ?>#services"><span>◌</span> Layanan</a>
			<a class="sidebar-link" href="<?php echo site_url('admin'); ?>#promos"><span>✳</span> Promo</a>
			<a class="sidebar-link" href="<?php echo site_url('admin'); ?>#reviews"><span>☆</span> Review</a>
			<a class="sidebar-link" href="<?php echo site_url('admin'); ?>#gallery"><span>▧</span> Gallery</a>
		</nav>
		<div class="sidebar-bottom"><span class="sidebar-avatar"><?php echo html_escape(strtoupper(substr($admin_name, 0, 1))); ?></span><span class="sidebar-user"><strong><?php echo html_escape($admin_name); ?></strong><small>Admin</small></span><a class="sidebar-logout" href="<?php echo site_url('admin'); ?>" title="Kembali ke dashboard" aria-label="Kembali ke dashboard">↗</a></div>
	</aside>
	<main class="admin-main">
		<header class="admin-topbar"><div class="admin-breadcrumb">Bloomè Studio <span>/</span> Booking</div><div class="topbar-actions"><a class="topbar-site-link" href="<?php echo site_url('admin/account'); ?>">Akun</a><a class="topbar-site-link" href="<?php echo site_url('admin'); ?>">Kembali ke dashboard</a></div></header>
		<div class="admin-content">
			<section class="dashboard-welcome" id="booking-list"><div><p class="admin-eyebrow">CUSTOMER RESERVATIONS</p><h1>Data <em>Booking</em></h1><p>Kelola booking, data pelanggan, status pembayaran, dan jadwal sesi.</p></div><a class="admin-button admin-button-dark" href="#manual-booking">＋ Booking manual</a></section>
			<?php if ($notice): ?><div class="booking-admin-notice"><?php echo html_escape($notice); ?></div><?php endif; ?>
			<?php if ($error): ?><div class="booking-admin-error"><?php echo html_escape($error); ?></div><?php endif; ?>

			<details class="admin-panel manual-booking-panel" id="manual-booking">
				<summary class="manual-booking-summary">＋ Tambahkan booking manual</summary>
				<form id="manual-booking-form" action="<?php echo site_url('admin/bookings/manual'); ?>" method="post">
					<input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
					<div class="promo-edit-fields">
						<label>Nama pelanggan<input name="full_name" maxlength="100" required></label>
						<label>Nomor WhatsApp<input name="phone" inputmode="tel" maxlength="24" required></label>
						<label>Paket Pilates<select name="package_id" id="manual-package" required><option value="">Pilih paket</option><?php foreach ($packages as $package): ?><option value="<?php echo (int) $package->id; ?>" data-session-count="<?php echo (int) $package->session_count + (int) $package->bonus_sessions; ?>"><?php echo html_escape($package->name . ' · ' . $package->class_type . ' · ' . $money($package->price)); ?></option><?php endforeach; ?></select></label>
						<label>Metode pembayaran<select name="payment_method" required><option value="transfer">Transfer BCA</option><option value="qris">QRIS</option></select></label>
						<label>Status pembayaran<select name="payment_state" required><option value="pending">Menunggu pembayaran</option><option value="paid">Lunas (manual)</option></select></label>
					</div>
					<div id="manual-booking-sessions" class="manual-booking-sessions"></div>
					<button type="submit" class="admin-button admin-button-dark">Buat booking</button>
				</form>
			</details>

			<div class="booking-admin-filters" aria-label="Filter booking">
				<label class="booking-search"><span aria-hidden="true">⌕</span><input id="booking-search" type="search" placeholder="Cari nama, kode, paket..." autocomplete="off"></label>
				<select id="booking-status-filter" aria-label="Filter status booking"><option value="">Semua status</option><?php foreach ($status_labels as $status_value => $status_label): ?><option value="<?php echo html_escape($status_value); ?>"><?php echo html_escape($status_label); ?></option><?php endforeach; ?></select>
			</div>
			<?php if (!$bookings): ?>
			<section class="admin-panel"><p class="empty-state">Belum ada booking.</p></section>
			<?php else: ?>
			<div class="section-heading"><p class="admin-eyebrow">BOOKING AKTIF &amp; RIWAYAT</p><h2 id="reschedule">Kelola booking dan reschedule</h2></div>
			<div class="booking-admin-list">
				<?php foreach ($bookings as $booking): ?>
				<?php $booking_code = 'BLM-1' . '00' . (int) $booking->id; ?>
				<section class="admin-panel booking-admin-card" data-booking-status="<?php echo html_escape($booking->booking_status); ?>" data-booking-search="<?php echo html_escape(strtolower($booking_code . ' ' . $booking->full_name . ' ' . $booking->package_name . ' ' . $booking->service . ' ' . $booking->id)); ?>">
					<header class="booking-admin-card-header"><div><p class="admin-eyebrow"><?php echo html_escape($booking_code); ?> · <?php echo html_escape(strtoupper($booking->service)); ?></p><h2><?php echo html_escape($booking->full_name); ?></h2><a href="tel:<?php echo html_escape($booking->phone); ?>"><?php echo html_escape($booking->phone); ?></a></div><span class="status-pill <?php echo $booking->booking_status === 'confirmed' ? 'status-active' : 'status-inactive'; ?>"><?php echo html_escape(isset($status_labels[$booking->booking_status]) ? $status_labels[$booking->booking_status] : $booking->booking_status); ?></span></header>
					<div class="booking-admin-details">
						<div><span>Paket</span><strong><?php echo html_escape($booking->package_name); ?> · <?php echo html_escape($booking->package_kind); ?></strong></div>
						<div><span>Total</span><strong><?php echo $money($booking->price); ?></strong></div>
						<div><span>Pembayaran</span><strong><?php echo $booking->payment_status === 'paid' ? 'Lunas' : html_escape(ucfirst($booking->payment_status)); ?> · <?php echo $booking->payment_method === 'transfer' ? 'Transfer BCA' : 'QRIS'; ?></strong></div>
						<div><span>Dibuat</span><strong><?php echo html_escape(date('d M Y H:i', strtotime($booking->created_at))); ?> WIB</strong></div>
						<div><span>Mengetahui dari</span><strong><?php echo html_escape($booking->source === 'Lainnya' ? $booking->source_other : $booking->source); ?></strong></div>
						<div><span>Riwayat cedera</span><strong><?php echo $booking->health_notes ? html_escape($booking->health_notes) : 'Tidak ada'; ?></strong></div>
					</div>
					<h3 class="booking-admin-subheading">Jadwal sesi</h3>
					<ul class="booking-admin-sessions"><?php foreach ($sessions[$booking->id] as $session): ?><li><?php echo html_escape(date('D, d M Y', strtotime($session->session_date))); ?> · <?php echo html_escape(substr($session->start_time, 0, 5) . ((int) $session->duration_minutes > 60 ? '–' . date('H:i', strtotime($session->start_time . ' +' . (int) $session->duration_minutes . ' minutes')) : '')); ?> · <?php echo html_escape($session->coach_name ? $session->coach_name : 'Coach belum ditentukan'); ?> · <?php echo html_escape(ucfirst($session->class_type)); ?></li><?php endforeach; ?></ul>
					<div class="booking-admin-files">
						<?php if ($booking->booking_status === 'pending_payment'): ?><a href="<?php echo site_url('booking/payment/' . rawurlencode($booking->public_token)); ?>" target="_blank" rel="noopener">Buka link pembayaran</a><?php endif; ?>
						<?php if ($booking->statement_file): ?><a href="<?php echo site_url('admin/bookings/statement/' . (int) $booking->id); ?>" target="_blank" rel="noopener">Lihat surat pernyataan</a><?php endif; ?>
						<?php if ($booking->receipt_file): ?><a href="<?php echo site_url('admin/bookings/receipt/' . (int) $booking->id); ?>" target="_blank" rel="noopener">Lihat bukti pembayaran</a><?php else: ?><span class="booking-admin-no-proof">Bukti pembayaran belum diunggah</span><?php endif; ?>
					</div>
					<div class="booking-admin-management">
						<details class="promo-editor"><summary>Edit booking pelanggan</summary>
							<form action="<?php echo site_url('admin/bookings/edit/' . (int) $booking->id); ?>" method="post"><input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>"><div class="promo-edit-fields"><label>Nama<input name="full_name" maxlength="100" required value="<?php echo html_escape($booking->full_name); ?>"></label><label>WhatsApp<input name="phone" maxlength="24" required value="<?php echo html_escape($booking->phone); ?>"></label></div><button type="submit" class="admin-button admin-button-dark">Simpan data</button></form>
						</details>
						<?php if (in_array($booking->booking_status, array('pending_payment', 'confirmed'), TRUE)): ?>
						<details class="promo-editor"><summary>Reschedule sesi</summary>
							<form action="<?php echo site_url('admin/bookings/reschedule/' . (int) $booking->id); ?>" method="post" class="reschedule-booking-form"><input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
								<?php foreach ($sessions[$booking->id] as $session_index => $session): ?>
								<div class="reschedule-session-row"><label>Sesi <?php echo (int) $session_index + 1; ?><input name="session_date[]" type="date" min="<?php echo html_escape(date('Y-m-d')); ?>" value="<?php echo html_escape($session->session_date); ?>" required></label><label>Slot<select name="schedule_id[]" required><?php foreach ($schedules as $schedule): ?><option value="<?php echo (int) $schedule->id; ?>" <?php echo (int) $session->schedule_id === (int) $schedule->id ? 'selected' : ''; ?> data-weekday="<?php echo (int) $schedule->day_of_week; ?>"><?php echo html_escape($weekdays[(int) $schedule->day_of_week] . ' · ' . substr($schedule->start_time, 0, 5) . ' · ' . ($schedule->coach_name ? $schedule->coach_name : 'Coach belum ditentukan')); ?></option><?php endforeach; ?></select></label></div>
								<?php endforeach; ?>
								<button type="submit" class="admin-button admin-button-dark">Simpan jadwal</button>
							</form>
						</details>
						<?php endif; ?>
						<?php if ($booking->booking_status === 'pending_payment' && $booking->expires_at > date('Y-m-d H:i:s')): ?>
						<div class="booking-admin-actions">
							<?php echo form_open('admin/bookings/payment/' . (int) $booking->id . '/paid'); ?><button class="admin-button admin-button-dark" type="submit" <?php echo !$booking->receipt_file ? 'disabled' : ''; ?>>Konfirmasi pembayaran</button><?php echo form_close(); ?>
							<?php echo form_open('admin/bookings/payment/' . (int) $booking->id . '/rejected'); ?><button class="admin-button booking-admin-reject" type="submit">Tolak booking</button><?php echo form_close(); ?>
						</div>
						<?php endif; ?>
						<?php if (in_array($booking->booking_status, array('pending_payment', 'confirmed'), TRUE)): ?>
						<form class="promo-delete-form" action="<?php echo site_url('admin/bookings/delete/' . (int) $booking->id); ?>" method="post" onsubmit="return confirm('Batalkan booking ini dan lepaskan kapasitas jadwal?');"><input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>"><button type="submit" class="promo-delete-button">Hapus / batalkan booking</button></form>
						<?php endif; ?>
					</div>
				</section>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</main>
	<script>
		(function () {
			var packages = document.getElementById('manual-package');
			var sessions = document.getElementById('manual-booking-sessions');
			var scheduleOptions = <?php echo json_encode($schedule_options); ?>;
			var weekdays = <?php echo json_encode($weekdays); ?>;
			if (!packages || !sessions) {
				return;
			}
			packages.addEventListener('change', function () {
				sessions.innerHTML = '';
				var selected = packages.options[packages.selectedIndex];
				var count = selected ? parseInt(selected.getAttribute('data-session-count'), 10) || 0 : 0;
				for (var i = 0; i < count; i++) {
					var row = document.createElement('div');
					row.className = 'reschedule-session-row';
					var dateLabel = document.createElement('label');
					dateLabel.textContent = 'Tanggal sesi ' + (i + 1);
					var date = document.createElement('input');
					date.type = 'date';
					date.name = 'session_date[]';
					date.min = <?php echo json_encode(date('Y-m-d')); ?>;
					date.required = true;
					dateLabel.appendChild(date);
					var scheduleLabel = document.createElement('label');
					scheduleLabel.textContent = 'Slot Pilates';
					var schedule = document.createElement('select');
					schedule.name = 'schedule_id[]';
					schedule.required = true;
					var empty = document.createElement('option');
					empty.value = '';
					empty.textContent = 'Pilih tanggal dahulu';
					schedule.appendChild(empty);
					scheduleLabel.appendChild(schedule);
					date.addEventListener('change', function () {
						var day = new Date(this.value + 'T00:00:00').getDay();
						day = day === 0 ? 7 : day;
						var select = this.parentNode.nextSibling.querySelector('select');
						select.innerHTML = '';
						var placeholder = document.createElement('option');
						placeholder.value = '';
						placeholder.textContent = 'Pilih slot';
						select.appendChild(placeholder);
						for (var optionIndex = 0; optionIndex < scheduleOptions.length; optionIndex++) {
							var slot = scheduleOptions[optionIndex];
							if (slot.day !== day) {
								continue;
							}
							var option = document.createElement('option');
							option.value = slot.id;
							option.textContent = slot.time + ' · ' + slot.coach + ' · ' + slot.class;
							select.appendChild(option);
						}
					});
					row.appendChild(dateLabel);
					row.appendChild(scheduleLabel);
					sessions.appendChild(row);
				}
			});
			document.querySelectorAll('.reschedule-booking-form .reschedule-session-row').forEach(function (row) {
				var date = row.querySelector('input[type="date"]');
				var select = row.querySelector('select');
				date.addEventListener('change', function () {
					var day = new Date(this.value + 'T00:00:00').getDay();
					day = day === 0 ? 7 : day;
					Array.prototype.forEach.call(select.options, function (option) {
						if (option.value) {
							option.hidden = parseInt(option.getAttribute('data-weekday'), 10) !== day;
						}
					});
					if (select.selectedOptions.length && select.selectedOptions[0].hidden) {
						select.value = '';
					}
				});
				date.dispatchEvent(new Event('change'));
			});
			var manualLink = document.querySelector('a[href="#manual-booking"]');
			var manualPanel = document.getElementById('manual-booking');
			if (manualLink && manualPanel) {
				manualLink.addEventListener('click', function () { manualPanel.open = true; });
			}
			var search = document.getElementById('booking-search');
			var statusFilter = document.getElementById('booking-status-filter');
			function filterBookings() {
				var query = search.value.trim().toLowerCase();
				var status = statusFilter.value;
				document.querySelectorAll('.booking-admin-card').forEach(function (card) {
					var matchesSearch = !query || card.getAttribute('data-booking-search').indexOf(query) !== -1;
					var matchesStatus = !status || card.getAttribute('data-booking-status') === status;
					card.hidden = !matchesSearch || !matchesStatus;
				});
			}
			search.addEventListener('input', filterBookings);
			statusFilter.addEventListener('change', filterBookings);
		}());
	</script>
</body>
</html>
