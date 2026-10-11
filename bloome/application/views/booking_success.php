<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$money = function ($amount) {
	return 'Rp ' . number_format((float) $amount, 0, ',', '.');
};
$valid_until = NULL;
if (!empty($booking->validity_days) && !empty($booking->paid_at)) {
	$valid_until = new DateTime($booking->paid_at, new DateTimeZone('Asia/Jakarta'));
	$valid_until->modify('+' . (int) $booking->validity_days . ' days');
}
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f8f5f1">
	<title>Booking Berhasil — Bloomè Studio</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/bloome.css?v=' . filemtime(FCPATH . 'assets/css/bloome.css')); ?>">
</head>
<body class="booking-page">
	<main class="booking-flow booking-result-page">
		<p class="eyebrow">BOOKING CONFIRMED</p>
		<h1>Booking berhasil.</h1>
		<p class="booking-intro">Pembayaran sudah dikonfirmasi. Simpan detail booking ini untuk kunjunganmu.</p>
		<section class="payment-summary">
			<h2>Detail booking</h2>
			<div><span>Nama</span><strong><?php echo html_escape($booking->full_name); ?></strong></div>
			<div><span>Nomor WhatsApp</span><strong><?php echo html_escape($booking->phone); ?></strong></div>
			<div><span>Layanan</span><strong><?php echo html_escape(ucfirst($booking->service)); ?> · <?php echo html_escape($booking->package_name); ?></strong></div>
			<div><span>Pembayaran</span><strong><?php echo $money($booking->price); ?> · <?php echo $booking->payment_method === 'transfer' ? 'Transfer BCA' : 'QRIS'; ?> · Lunas</strong></div>
			<?php if ($valid_until): ?><div><span>Masa berlaku paket</span><strong>Sampai <?php echo html_escape($valid_until->format('d M Y')); ?></strong></div><?php endif; ?>
			<ul class="success-session-list"><?php foreach ($sessions as $session): ?><li><strong><?php echo html_escape(date('l, d M Y', strtotime($session->session_date))); ?> · <?php echo html_escape(substr($session->start_time, 0, 5) . ((int) $session->duration_minutes > 60 ? '–' . date('H:i', strtotime($session->start_time . ' +' . (int) $session->duration_minutes . ' minutes')) : '')); ?></strong><small><?php echo html_escape($session->coach_name ? $session->coach_name : 'Coach belum ditentukan'); ?> · <?php echo html_escape(ucfirst($session->class_type)); ?> Class</small></li><?php endforeach; ?></ul>
		</section>
		<section class="booking-terms">
			<h2>Syarat &amp; Ketentuan</h2>
			<ul>
				<li>Gunakan pakaian olahraga yang nyaman.</li>
				<li>Wajib menggunakan kaos kaki grip / shockgrip.</li>
				<li>Reschedule maksimal H-1 sebelum pukul 16:00.</li>
				<li>Paket berlaku sesuai masa validity; sesi yang belum dipakai hangus setelah expired.</li>
			</ul>
		</section>
		<div class="booking-result-actions">
			<button class="button button-outline" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
			<a class="button button-booking-next" href="<?php echo site_url(); ?>#services">Booking lagi</a>
		</div>
	</main>
</body>
</html>
