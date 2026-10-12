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
	<meta name="robots" content="noindex, nofollow">
	<title>Verifikasi Booking — Bloomè Studio</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css?v=' . filemtime(FCPATH . 'assets/css/admin.css')); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/booking-admin.css?v=' . filemtime(FCPATH . 'assets/css/booking-admin.css')); ?>">
</head>
<body class="dashboard-page">
	<aside class="admin-sidebar">
		<a class="admin-wordmark" href="<?php echo site_url('admin'); ?>">Bloomè <span>Studio</span></a>
		<p class="sidebar-caption">STUDIO MANAGEMENT</p>
		<nav class="sidebar-nav" aria-label="Navigasi admin">
			<a class="sidebar-link" href="<?php echo site_url('admin'); ?>"><span>⌂</span> Overview</a>
			<a class="sidebar-link is-active" href="<?php echo site_url('admin/bookings'); ?>"><span>▦</span> Booking &amp; Pembayaran</a>
		</nav>
		<div class="sidebar-bottom"><span class="sidebar-avatar"><?php echo html_escape(strtoupper(substr($admin_name, 0, 1))); ?></span><span class="sidebar-user"><strong><?php echo html_escape($admin_name); ?></strong><small>Admin</small></span><a class="sidebar-logout" href="<?php echo site_url('admin'); ?>" title="Kembali ke dashboard" aria-label="Kembali ke dashboard">↗</a></div>
	</aside>
	<main class="admin-main">
		<header class="admin-topbar"><div class="admin-breadcrumb">Bloomè Studio <span>/</span> Booking &amp; Pembayaran</div><div class="topbar-actions"><a class="topbar-site-link" href="<?php echo site_url('admin'); ?>">Kembali ke dashboard</a></div></header>
		<div class="admin-content">
			<section class="dashboard-welcome"><div><p class="admin-eyebrow">CUSTOMER RESERVATIONS</p><h1>Verifikasi pembayaran <span>✳</span></h1><p>Booking tetap pending sampai bukti pembayaran diperiksa admin.</p></div><span class="panel-count"><?php echo count($bookings); ?> pending</span></section>
			<?php if ($notice): ?><div class="booking-admin-notice"><?php echo html_escape($notice); ?></div><?php endif; ?>
			<?php if ($error): ?><div class="booking-admin-error"><?php echo html_escape($error); ?></div><?php endif; ?>
			<?php if (!$bookings): ?>
			<section class="admin-panel"><p class="empty-state">Tidak ada booking yang sedang menunggu pembayaran.</p></section>
			<?php else: ?>
			<div class="booking-admin-list">
				<?php foreach ($bookings as $booking): ?>
				<section class="admin-panel booking-admin-card">
					<header class="booking-admin-card-header"><div><p class="admin-eyebrow">BOOKING #<?php echo (int) $booking->id; ?> · <?php echo html_escape(strtoupper($booking->service)); ?></p><h2><?php echo html_escape($booking->full_name); ?></h2><a href="tel:<?php echo html_escape($booking->phone); ?>"><?php echo html_escape($booking->phone); ?></a></div><span class="status-pill status-inactive">PENDING</span></header>
					<div class="booking-admin-details">
						<div><span>Paket</span><strong><?php echo html_escape($booking->package_name); ?> · <?php echo html_escape($booking->package_kind); ?></strong></div>
						<div><span>Total</span><strong><?php echo $money($booking->price); ?></strong></div>
						<div><span>Metode</span><strong><?php echo $booking->payment_method === 'transfer' ? 'Transfer BCA' : 'QRIS'; ?></strong></div>
						<div><span>Batas hold</span><strong><?php echo html_escape(date('d M Y H:i', strtotime($booking->expires_at))); ?> WIB</strong></div>
						<div><span>Mengetahui dari</span><strong><?php echo html_escape($booking->source === 'Lainnya' ? $booking->source_other : $booking->source); ?></strong></div>
						<div><span>Riwayat cedera</span><strong><?php echo $booking->health_notes ? html_escape($booking->health_notes) : 'Tidak ada'; ?></strong></div>
					</div>
					<h3 class="booking-admin-subheading">Jadwal sesi</h3>
					<ul class="booking-admin-sessions"><?php foreach ($sessions[$booking->id] as $session): ?><li><?php echo html_escape(date('D, d M Y', strtotime($session->session_date))); ?> · <?php echo html_escape(substr($session->start_time, 0, 5) . ((int) $session->duration_minutes > 60 ? '–' . date('H:i', strtotime($session->start_time . ' +' . (int) $session->duration_minutes . ' minutes')) : '')); ?> · <?php echo html_escape($session->coach_name ? $session->coach_name : 'Coach belum ditentukan'); ?> · <?php echo html_escape(ucfirst($session->class_type)); ?></li><?php endforeach; ?></ul>
					<div class="booking-admin-files">
						<a href="<?php echo site_url('admin/bookings/statement/' . (int) $booking->id); ?>" target="_blank" rel="noopener">Lihat surat pernyataan</a>
						<?php if ($booking->receipt_file): ?><a href="<?php echo site_url('admin/bookings/receipt/' . (int) $booking->id); ?>" target="_blank" rel="noopener">Lihat bukti pembayaran</a><?php else: ?><span class="booking-admin-no-proof">Bukti pembayaran belum diunggah</span><?php endif; ?>
					</div>
					<div class="booking-admin-actions">
						<?php echo form_open('admin/bookings/payment/' . (int) $booking->id . '/paid'); ?><button class="admin-button admin-button-dark" type="submit" <?php echo !$booking->receipt_file ? 'disabled' : ''; ?>>Konfirmasi Pembayaran</button><?php echo form_close(); ?>
						<?php echo form_open('admin/bookings/payment/' . (int) $booking->id . '/rejected'); ?><button class="admin-button booking-admin-reject" type="submit">Tolak &amp; Lepas Jadwal</button><?php echo form_close(); ?>
					</div>
				</section>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</main>
</body>
</html>
