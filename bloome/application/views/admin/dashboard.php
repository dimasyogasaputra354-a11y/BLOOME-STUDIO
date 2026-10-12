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
	<link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css?v=' . filemtime(FCPATH . 'assets/css/admin.css')); ?>">
</head>
<body class="dashboard-page">
	<aside class="admin-sidebar">
		<a class="admin-wordmark" href="<?php echo site_url('admin'); ?>">Bloomè <span>Studio</span></a>
		<p class="sidebar-caption">STUDIO MANAGEMENT</p>
		<nav class="sidebar-nav" aria-label="Navigasi admin">
			<a class="sidebar-link is-active" href="#daily-report"><span>▤</span> Daily Report</a>
			<a class="sidebar-link" href="#monthly-report"><span>▥</span> Monthly Report</a>
			<p class="sidebar-caption">KELOLA WEBSITE</p>
			<a class="sidebar-link" href="<?php echo site_url('admin/bookings'); ?>"><span>▦</span> Booking</a>
			<a class="sidebar-link" href="<?php echo site_url('admin/bookings#reschedule'); ?>"><span>↻</span> Reschedule</a>
			<a class="sidebar-link" href="#services"><span>◌</span> Layanan</a>
			<a class="sidebar-link" href="#promos"><span>✳</span> Promo</a>
			<a class="sidebar-link" href="#reviews"><span>☆</span> Review</a>
			<a class="sidebar-link" href="#gallery"><span>▧</span> Gallery</a>
			<p class="sidebar-caption">STUDIO MANAGEMENT</p>
			<a class="sidebar-link" href="#pricing"><span>◈</span> Pricelist</a>
			<a class="sidebar-link" href="#studio"><span>☼</span> Studio</a>
		</nav>
		<div class="sidebar-bottom"><span class="sidebar-avatar"><?php echo html_escape(strtoupper(substr($admin_name, 0, 1))); ?></span><span class="sidebar-user"><strong><?php echo html_escape($admin_name); ?></strong><small><?php echo html_escape(ucfirst($role_name)); ?></small></span><a class="sidebar-logout" href="<?php echo site_url('admin/account'); ?>" title="Pengaturan akun" aria-label="Pengaturan akun">⚙</a></div>
	</aside>

	<main class="admin-main" id="overview">
		<header class="admin-topbar"><div class="admin-breadcrumb">Bloomè Studio <span>/</span> Daily Report</div><div class="topbar-actions"><a class="topbar-site-link" href="<?php echo site_url('admin/account'); ?>">Akun</a><a class="topbar-site-link" href="<?php echo site_url(); ?>" target="_blank">Lihat website ↗</a><?php echo form_open('admin/logout', array('class' => 'logout-form')); ?><button class="logout-button" type="submit">Keluar</button><?php echo form_close(); ?></div></header>
		<div class="admin-content">
			<section class="dashboard-welcome"><div><p class="admin-eyebrow">LAPORAN STUDIO · <?php echo html_escape(strtoupper(date('d M Y'))); ?></p><h1>Daily &amp; Monthly Report <span>✳</span></h1><p>Pantau transaksi lunas, jumlah sesi, dan pendapatan Bloomè Studio.</p></div><a class="admin-button admin-button-dark" href="<?php echo site_url('admin/bookings'); ?>">Kelola booking <span>→</span></a></section>

			<?php $this->load->view('admin/reports', array(
				'report_date' => $report_date,
				'report_month' => $report_month,
				'report_filter_error' => $report_filter_error,
				'daily_rows' => $daily_rows,
				'daily_summary' => $daily_summary,
				'daily_chart' => $daily_chart,
				'monthly_rows' => $monthly_rows,
				'monthly_summary' => $monthly_summary,
				'monthly_chart' => $monthly_chart,
				'money' => $money
			)); ?>

			<div class="dashboard-columns">
				<section class="admin-panel" id="promos">
					<div class="panel-heading"><div><p class="admin-eyebrow">PENAWARAN · MAKSIMAL 4 KATALOG</p><h2>Kelola promo</h2></div><a class="admin-button admin-button-dark" href="<?php echo site_url('booking/pilates-deal'); ?>" target="_blank" rel="noopener">Preview 10.10 Deal ↗</a><span class="panel-count"><?php echo (int) $promo_count; ?>/4 promo</span></div>
					<?php if ($promo_notice = $this->session->flashdata('promo_notice')): ?><p class="promo-image-feedback is-success" role="status"><?php echo html_escape($promo_notice); ?></p><?php endif; ?>
					<?php if ($promo_error = $this->session->flashdata('promo_error')): ?><p class="promo-image-feedback is-error" role="alert"><?php echo html_escape($promo_error); ?></p><?php endif; ?>
					<?php if ($promo_notice = $this->session->flashdata('promo_image_notice')): ?><p class="promo-image-feedback is-success" role="status"><?php echo html_escape($promo_notice); ?></p><?php endif; ?>
					<?php if ($promo_error = $this->session->flashdata('promo_image_error')): ?><p class="promo-image-feedback is-error" role="alert"><?php echo html_escape($promo_error); ?></p><?php endif; ?>
					<?php if ($promo_count < 4): ?>
					<details class="promo-editor promo-create">
						<summary>Tambah promo ke slot katalog yang tersedia</summary>
						<form action="<?php echo site_url('admin/promo'); ?>" method="post" enctype="multipart/form-data">
							<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
							<div class="promo-edit-fields">
								<label>Kode promo<input name="code" maxlength="40" required placeholder="Contoh: YOGA-OCTOBER"></label>
								<label>Disiplin<select name="discipline" required><option value="pilates">Pilates</option><option value="yoga">Yoga</option><option value="beauty">Beauty</option><option value="general">General</option></select></label>
								<label>Nama promo<input name="title" maxlength="120" required></label>
								<label>Tagline<input name="tagline" maxlength="160"></label>
								<label>Harga (Rp)<input name="price" type="number" min="1" step="1" required></label>
								<label>Satuan harga<input name="price_unit" maxlength="40" required placeholder="Contoh: 2 session / group"></label>
								<label>Jumlah sesi<input name="session_count" type="number" min="1" max="65535"></label>
								<label>Minimum peserta<input name="participants_min" type="number" min="1" max="255"></label>
								<label>Maksimum peserta<input name="participants_max" type="number" min="1" max="255"></label>
								<label>Hari tersedia<input name="available_days" maxlength="60" placeholder="Contoh: Monday-Friday"></label>
								<label>Mulai promo<input name="valid_from" type="date"></label>
								<label>Akhir promo<input name="valid_until" type="date"></label>
								<label class="promo-field-wide">Syarat &amp; catatan<textarea name="terms_note" maxlength="255" rows="2"></textarea></label>
								<label>Status<select name="is_active" required><option value="1">Aktif</option><option value="0">Nonaktif</option></select></label>
								<label>Gambar promo (JPG/PNG, maks. 5 MB)<input type="file" name="promo_image" accept=".jpg,.jpeg,.png,image/jpeg,image/png"></label>
							</div>
							<button type="submit" class="admin-button admin-button-dark">Tambah ke katalog</button>
						</form>
					</details>
					<?php endif; ?>
					<?php if ($promos): ?>
					<div class="table-scroll"><table><thead><tr><th>Nama promo</th><th>Disiplin</th><th>Harga</th><th>Status</th><th>Gambar promo</th><th>Aksi</th></tr></thead><tbody>
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
							</td>
							<td>
								<details class="promo-editor">
									<summary>Edit katalog</summary>
									<form action="<?php echo site_url('admin/promo/' . (int) $promo->id); ?>" method="post" enctype="multipart/form-data">
										<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
										<div class="promo-edit-fields">
											<label>Kode promo<input name="code" maxlength="40" required value="<?php echo html_escape($promo->code); ?>" <?php echo $promo->code === 'PILATES-1010' ? 'readonly' : ''; ?>></label>
											<label>Disiplin<select name="discipline" required><?php foreach (array('pilates' => 'Pilates', 'yoga' => 'Yoga', 'beauty' => 'Beauty', 'general' => 'General') as $discipline_value => $discipline_label): ?><option value="<?php echo $discipline_value; ?>" <?php echo $promo->discipline === $discipline_value ? 'selected' : ''; ?>><?php echo $discipline_label; ?></option><?php endforeach; ?></select></label>
											<label>Nama promo<input name="title" maxlength="120" required value="<?php echo html_escape($promo->title); ?>"></label>
											<label>Tagline<input name="tagline" maxlength="160" value="<?php echo html_escape((string) $promo->tagline); ?>"></label>
											<label>Harga (Rp)<input name="price" type="number" min="1" step="1" required value="<?php echo (int) $promo->price; ?>"></label>
											<label>Satuan harga<input name="price_unit" maxlength="40" required value="<?php echo html_escape($promo->price_unit); ?>"></label>
											<label>Jumlah sesi<input name="session_count" type="number" min="1" max="65535" value="<?php echo $promo->session_count !== NULL ? (int) $promo->session_count : ''; ?>"></label>
											<label>Minimum peserta<input name="participants_min" type="number" min="1" max="255" value="<?php echo $promo->participants_min !== NULL ? (int) $promo->participants_min : ''; ?>"></label>
											<label>Maksimum peserta<input name="participants_max" type="number" min="1" max="255" value="<?php echo $promo->participants_max !== NULL ? (int) $promo->participants_max : ''; ?>"></label>
											<label>Hari tersedia<input name="available_days" maxlength="60" value="<?php echo html_escape((string) $promo->available_days); ?>"></label>
											<label>Mulai promo<input name="valid_from" type="date" value="<?php echo html_escape((string) $promo->valid_from); ?>"></label>
											<label>Akhir promo<input name="valid_until" type="date" value="<?php echo html_escape((string) $promo->valid_until); ?>"></label>
											<label class="promo-field-wide">Syarat &amp; catatan<textarea name="terms_note" maxlength="255" rows="2"><?php echo html_escape((string) $promo->terms_note); ?></textarea></label>
											<label>Status<select name="is_active" required><option value="1" <?php echo $promo->is_active ? 'selected' : ''; ?>>Aktif</option><option value="0" <?php echo !$promo->is_active ? 'selected' : ''; ?>>Nonaktif</option></select></label>
											<label>Ganti gambar (opsional)<input type="file" name="promo_image" accept=".jpg,.jpeg,.png,image/jpeg,image/png"></label>
										</div>
										<?php if ($promo->code === 'PILATES-1010'): ?><small class="promo-code-note">Kode dikunci karena terhubung dengan alur booking 10.10.</small><?php endif; ?>
										<button type="submit" class="admin-button admin-button-dark">Simpan perubahan</button>
									</form>
									<form class="promo-delete-form" action="<?php echo site_url('admin/promo/delete/' . (int) $promo->id); ?>" method="post" onsubmit="return confirm('Hapus promo ini dari katalog?');">
										<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
										<button type="submit" class="promo-delete-button">Hapus promo</button>
									</form>
								</details>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody></table></div>
					<?php else: ?><p class="empty-state">Belum ada data promo.</p><?php endif; ?>
					<div class="panel-footnote">Kelola maksimal 4 promo. Gambar JPG/PNG maksimal 5 MB; promo yang dihapus dapat diganti melalui slot katalog.</div>
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

			<?php $this->load->view('admin/public_content', array(
				'pilates_catalog' => $pilates_catalog,
				'yoga_catalog' => $yoga_catalog,
				'beauty_catalog' => $beauty_catalog,
				'beauty_categories' => $beauty_categories,
				'reviews' => $reviews,
				'gallery' => $gallery,
				'gallery_count' => $gallery_count,
				'money' => $money
			)); ?>

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
