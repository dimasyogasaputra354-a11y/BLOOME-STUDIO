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
	<title>Dashboard Admin — Bloomè Studio</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&amp;family=Playfair+Display:ital,wght@0,400;0,500;1,400&amp;display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css'); ?>">
</head>
<body class="dashboard-page">
	<aside class="admin-sidebar">
		<a class="admin-wordmark" href="<?php echo site_url('admin'); ?>">Bloomè <span>Studio</span></a>
		<p class="sidebar-caption">STUDIO MANAGEMENT</p>
		<nav class="sidebar-nav" aria-label="Navigasi admin">
			<a class="sidebar-link is-active" href="#overview"><span>⌂</span> Overview</a>
			<a class="sidebar-link" href="#promos"><span>✳</span> Promo</a>
			<a class="sidebar-link" href="#pricing"><span>◌</span> Pricelist</a>
			<a class="sidebar-link" href="#studio"><span>☼</span> Studio</a>
			<a class="sidebar-link" href="<?php echo site_url('admin/bookings'); ?>"><span>▦</span> Booking &amp; Pembayaran</a>
		</nav>
		<div class="sidebar-bottom"><span class="sidebar-avatar"><?php echo html_escape(strtoupper(substr($admin_name, 0, 1))); ?></span><span class="sidebar-user"><strong><?php echo html_escape($admin_name); ?></strong><small><?php echo html_escape(ucfirst($role_name)); ?></small></span><a class="sidebar-logout" href="<?php echo site_url(); ?>" title="Kembali ke website" aria-label="Kembali ke website">↗</a></div>
	</aside>

	<main class="admin-main" id="overview">
		<header class="admin-topbar"><div class="admin-breadcrumb">Bloomè Studio <span>/</span> Overview</div><div class="topbar-actions"><a class="topbar-site-link" href="<?php echo site_url(); ?>" target="_blank">Lihat website ↗</a><?php echo form_open('admin/logout', array('class' => 'logout-form')); ?><button class="logout-button" type="submit">Keluar</button><?php echo form_close(); ?></div></header>
		<div class="admin-content">
			<section class="dashboard-welcome"><div><p class="admin-eyebrow">STUDIO OVERVIEW · <?php echo html_escape(strtoupper(date('d M Y'))); ?></p><h1>Halo, <?php echo html_escape($admin_name); ?> <span>✳</span></h1><p>Ini ringkasan Bloomè Studio hari ini.</p></div><a class="admin-button admin-button-dark" href="<?php echo site_url(); ?>" target="_blank">Buka website <span>↗</span></a></section>

			<section class="stat-grid" aria-label="Ringkasan data studio">
				<article class="stat-card"><span class="stat-icon stat-sage">✳</span><p>Promo aktif</p><strong><?php echo (int) $overview['promos']; ?></strong><small>penawaran tersedia</small></article>
				<article class="stat-card"><span class="stat-icon stat-rose">◌</span><p>Layanan Beauty</p><strong><?php echo (int) $overview['beauty']; ?></strong><small>layanan aktif</small></article>
				<article class="stat-card"><span class="stat-icon stat-sand">☼</span><p>Jadwal kelas</p><strong><?php echo (int) $overview['schedules']; ?></strong><small>slot terjadwal</small></article>
				<article class="stat-card"><span class="stat-icon stat-lilac">♡</span><p>Coach aktif</p><strong><?php echo (int) $overview['coaches']; ?></strong><small>coach terdaftar</small></article>
			</section>

			<div class="dashboard-columns">
				<section class="admin-panel" id="promos">
					<div class="panel-heading"><div><p class="admin-eyebrow">PENAWARAN</p><h2>Promo terbaru</h2></div><span class="panel-count"><?php echo count($promos); ?> promo</span></div>
					<?php if ($promo_notice = $this->session->flashdata('promo_image_notice')): ?><p class="promo-image-feedback is-success" role="status"><?php echo html_escape($promo_notice); ?></p><?php endif; ?>
					<?php if ($promo_error = $this->session->flashdata('promo_image_error')): ?><p class="promo-image-feedback is-error" role="alert"><?php echo html_escape($promo_error); ?></p><?php endif; ?>
					<?php if ($promos): ?>
					<div class="table-scroll"><table><thead><tr><th>Nama promo</th><th>Disiplin</th><th>Harga</th><th>Status</th><th>Gambar promo</th></tr></thead><tbody>
						<?php foreach ($promos as $promo): ?>
						<?php
							$promo_image_path = $promo->image_path;
							if (!$promo_image_path) {
								$promo_defaults = array('PILATES-1010' => '10.10-pilates.jpeg', 'YOGA-AFTER-OFFICE' => 'yoga-promo.jpeg');
								$promo_image_path = isset($promo_defaults[$promo->code]) ? $promo_defaults[$promo->code] : '';
							}
						?>
						<tr>
							<td><strong><?php echo html_escape($promo->title); ?></strong><small><?php echo html_escape($promo->code); ?></small></td>
							<td><span class="discipline-tag"><?php echo html_escape(ucfirst($promo->discipline)); ?></span></td>
							<td><?php echo $money($promo->price); ?></td>
							<td><span class="status-pill <?php echo $promo->is_active ? 'status-active' : 'status-inactive'; ?>"><?php echo $promo->is_active ? 'Aktif' : 'Nonaktif'; ?></span></td>
							<td>
								<?php if ($promo_image_path && preg_match('#^(?:promo-uploads/)?[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png)$#i', $promo_image_path)): ?><img class="promo-image-preview" src="<?php echo html_escape(base_url('img/' . $promo_image_path)); ?>" alt="Gambar <?php echo html_escape($promo->title); ?>"><?php endif; ?>
								<form class="promo-image-form" action="<?php echo site_url('admin/promo-image/' . (int) $promo->id); ?>" method="post" enctype="multipart/form-data">
									<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
									<label class="sr-only" for="promo-image-<?php echo (int) $promo->id; ?>">Pilih gambar untuk <?php echo html_escape($promo->title); ?></label>
									<input id="promo-image-<?php echo (int) $promo->id; ?>" type="file" name="promo_image" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
									<button type="submit" class="admin-button admin-button-dark">Simpan gambar</button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody></table></div>
					<?php else: ?><p class="empty-state">Belum ada data promo.</p><?php endif; ?>
					<div class="panel-footnote">Kelola gambar promo di sini. Format JPG/PNG, maksimal 5 MB.</div>
				</section>
				<section class="admin-panel" id="studio">
					<div class="panel-heading"><div><p class="admin-eyebrow">KELAS &amp; TIM</p><h2>Studio snapshot</h2></div><span class="snapshot-flower">✿</span></div>
					<div class="snapshot-row"><span class="snapshot-icon stat-sage">◌</span><span><strong>Pilates</strong><small>Harga aktif</small></span><b><?php echo (int) $overview['pilates']; ?></b></div>
					<div class="snapshot-row"><span class="snapshot-icon stat-sand">☼</span><span><strong>Yoga</strong><small>Harga aktif</small></span><b><?php echo (int) $overview['yoga']; ?></b></div>
					<div class="snapshot-row"><span class="snapshot-icon stat-rose">♧</span><span><strong>Coach</strong><small>Tim studio</small></span><b><?php echo (int) $overview['coaches']; ?></b></div>
					<div class="snapshot-row"><span class="snapshot-icon stat-lilac">▦</span><span><strong>Jadwal</strong><small>Slot kelas aktif</small></span><b><?php echo (int) $overview['schedules']; ?></b></div>
					<div class="panel-footnote">Data diringkas langsung dari database.</div>
				</section>
			</div>

			<section class="admin-panel pricing-panel" id="pricing">
				<div class="panel-heading"><div><p class="admin-eyebrow">HARGA LAYANAN</p><h2>Pricelist studio</h2></div><span class="panel-count">Pilates &amp; Yoga</span></div>
				<div class="pricing-columns">
					<div class="pricing-preview"><h3><span class="pricing-dot dot-pilates"></span>Pilates <small><?php echo (int) $overview['pilates']; ?> paket aktif</small></h3><?php foreach ($pilates as $item): ?><div class="price-preview-row"><span><?php echo html_escape($item->name); ?><small><?php echo html_escape(ucfirst($item->class_type)); ?> · <?php echo html_escape(ucfirst($item->kind)); ?></small></span><strong><?php echo $money($item->price); ?></strong></div><?php endforeach; ?></div>
					<div class="pricing-preview"><h3><span class="pricing-dot dot-yoga"></span>Yoga <small><?php echo (int) $overview['yoga']; ?> paket aktif</small></h3><?php foreach ($yoga as $item): ?><div class="price-preview-row"><span><?php echo html_escape($item->name); ?><small><?php echo html_escape($item->validity_label); ?></small></span><strong><?php echo $money($item->price); ?></strong></div><?php endforeach; ?></div>
				</div>
				<div class="panel-footnote">Harga ditampilkan dari tabel pricelist yang aktif.</div>
			</section>
			<footer class="admin-footer"><span>Bloomè Studio Admin</span><a href="<?php echo site_url(); ?>">Kembali ke website publik ↗</a></footer>
		</div>
	</main>
</body>
</html>
