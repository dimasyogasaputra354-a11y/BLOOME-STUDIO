<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title>Ganti Password — Bloomè Studio</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&amp;family=Playfair+Display:ital,wght@0,400;0,500;1,400&amp;display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css?v=' . filemtime(FCPATH . 'assets/css/admin.css')); ?>">
</head>
<body class="admin-auth-page">
	<main class="auth-layout">
		<div class="auth-art">
			<a class="admin-wordmark" href="<?php echo site_url(); ?>">Bloomè <span>Studio</span></a>
			<div class="auth-art-copy"><span class="auth-flower">✿</span><p>ONE LAST LITTLE STEP</p><h1>Keep your<br>account safe.</h1><a href="<?php echo site_url(); ?>">← Kembali ke website</a></div>
			<p class="auth-art-foot">Pilates · Yoga · Beauty &nbsp;—&nbsp; Cinere, Depok</p>
		</div>
		<section class="auth-card">
			<div class="auth-heading"><p class="admin-eyebrow">KEAMANAN AKUN</p><h2>Ganti password.</h2><p><?php echo $is_required ? 'Password awal wajib diganti sebelum melanjutkan.' : 'Gunakan password baru yang kuat dan tidak digunakan di tempat lain.'; ?></p></div>
			<?php if ($error): ?><div class="form-alert" role="alert"><?php echo html_escape($error); ?></div><?php endif; ?>
			<?php echo form_open('admin/change-password', array('class' => 'admin-form')); ?>
				<label for="current_password">Password saat ini</label>
				<input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
				<label for="new_password">Password baru</label>
				<input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="12" required>
				<label for="confirm_password">Konfirmasi password baru</label>
				<input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="12" required>
				<button class="admin-button admin-button-dark" type="submit">Simpan password <span>→</span></button>
			<?php echo form_close(); ?>
			<?php if (!$is_required): ?><a class="auth-back" href="<?php echo site_url('admin'); ?>">Kembali ke dashboard</a><?php endif; ?>
			<?php echo form_open('admin/logout', array('class' => 'auth-logout-form')); ?><button class="auth-logout-button" type="submit">Keluar dari akun</button><?php echo form_close(); ?>
		</section>
	</main>
</body>
</html>
