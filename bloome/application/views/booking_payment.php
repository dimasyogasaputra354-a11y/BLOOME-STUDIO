<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$money = function ($amount) {
	return 'Rp ' . number_format((float) $amount, 0, ',', '.');
};
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f8f5f1">
	<title>Pembayaran Booking — Bloomè Studio</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/bloome.css?v=' . filemtime(FCPATH . 'assets/css/bloome.css')); ?>">
</head>
<body class="booking-page">
	<main class="booking-flow booking-payment-page" data-status-url="<?php echo html_escape(site_url('booking/status/' . $booking->public_token)); ?>">
		<a class="booking-back-link" href="<?php echo site_url(); ?>#services">Bloomè Studio</a>
		<p class="eyebrow">BOOKING <?php echo html_escape(strtoupper($booking->service)); ?></p>
		<h1>Menunggu Pembayaran</h1>
		<div class="booking-status-pending"><span></span> PENDING · Menunggu verifikasi admin</div>
		<p class="booking-intro">Selesaikan pembayaran dan unggah bukti transfer. Slot ditahan sampai <strong><?php echo html_escape(date('d M Y, H:i', strtotime($booking->expires_at))); ?> WIB</strong>.</p>

		<section class="payment-summary">
			<h2>Ringkasan booking</h2>
			<div><span>Nama</span><strong><?php echo html_escape($booking->full_name); ?></strong></div>
			<div><span>Paket</span><strong><?php echo html_escape($booking->package_name); ?></strong></div>
			<div><span>Total pembayaran</span><strong><?php echo $money($booking->price); ?></strong></div>
			<div><span>Metode dipilih</span><strong><?php echo $booking->payment_method === 'transfer' ? 'Transfer BCA' : 'QRIS'; ?></strong></div>
			<?php if (!empty($booking->validity_days)): ?><div><span>Masa berlaku paket</span><strong><?php echo (int) $booking->validity_days; ?> hari setelah pembayaran dikonfirmasi</strong></div><?php endif; ?>
		</section>
		<p class="booking-status-error" role="status" hidden></p>

		<section class="payment-instructions">
			<h2><?php echo $booking->payment_method === 'transfer' ? 'Transfer Bank BCA' : 'Pembayaran QRIS'; ?></h2>
			<?php if ($booking->payment_method === 'transfer'): ?>
			<p>Transfer tepat sejumlah <strong><?php echo $money($booking->price); ?></strong> ke rekening:</p>
			<div class="bank-account"><small>BCA · BLOOME SEJAHTERA ABADI</small><strong>0012998811</strong></div>
			<?php else: ?>
			<div class="qris-placeholder"><span>QRIS BLOOMÈ STUDIO</span><strong>Gambar QRIS akan ditambahkan</strong><small>Untuk sementara, pilih Transfer BCA saat membuat booking atau hubungi admin studio.</small></div>
			<div class="bank-account"><small>ALTERNATIF TRANSFER · BCA · BLOOME SEJAHTERA ABADI</small><strong>0012998811</strong></div>
			<?php endif; ?>
			<p>Booking baru dikonfirmasi setelah admin memverifikasi pembayaran. Jika sudah membayar, unggah bukti pembayaran di bawah.</p>
		</section>

		<section class="payment-sessions">
			<h2>Jadwal yang ditahan</h2>
			<ul><?php foreach ($sessions as $session): ?><li><span><?php echo html_escape(date('l, d M Y', strtotime($session->session_date))); ?> · <?php echo html_escape(substr($session->start_time, 0, 5) . ((int) $session->duration_minutes > 60 ? '–' . date('H:i', strtotime($session->start_time . ' +' . (int) $session->duration_minutes . ' minutes')) : '')); ?></span><small><?php echo html_escape($session->coach_name ? $session->coach_name : 'Coach belum ditentukan'); ?> · <?php echo html_escape(ucfirst($session->class_type)); ?> Class</small></li><?php endforeach; ?></ul>
		</section>

		<?php if ($errors): ?><div class="booking-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?php echo html_escape($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
		<?php if ($booking->receipt_file): ?>
		<div class="receipt-uploaded">Bukti pembayaran sudah diunggah. Admin sedang melakukan verifikasi. Halaman ini akan diperbarui otomatis setelah status berubah.</div>
		<?php else: ?>
		<form class="receipt-upload-form" action="<?php echo html_escape(site_url('booking/payment/' . $booking->public_token)); ?>" method="post" enctype="multipart/form-data">
			<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
			<?php if ($booking->payment_method === 'qris'): ?><label class="booking-field"><span>Metode pembayaran yang digunakan</span><select name="actual_payment_method" required><option value="transfer">Transfer BCA</option><option value="qris">QRIS</option></select></label><?php endif; ?>
			<label class="booking-field"><span>Unggah Bukti Pembayaran</span><input type="file" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required><small>Format PDF/JPG/PNG, maksimal 5 MB.</small></label>
			<button class="button button-booking-next" type="submit">Kirim Bukti untuk Diverifikasi <span aria-hidden="true">›</span></button>
		</form>
		<?php endif; ?>
	</main>
	<script src="<?php echo base_url('assets/js/booking-status.js?v=' . filemtime(FCPATH . 'assets/js/booking-status.js')); ?>"></script>
</body>
</html>
