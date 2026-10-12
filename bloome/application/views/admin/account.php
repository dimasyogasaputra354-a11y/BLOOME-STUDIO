<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title>Pengaturan Akun — Bloomè Studio</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&amp;family=Playfair+Display:ital,wght@0,400;0,500;1,400&amp;display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css?v=' . filemtime(FCPATH . 'assets/css/admin.css')); ?>">
</head>
<body class="admin-auth-page">
	<main class="auth-layout">
		<div class="auth-art">
			<a class="admin-wordmark" href="<?php echo site_url('admin'); ?>">Bloomè <span>Studio</span></a>
			<div class="auth-art-copy"><span class="auth-flower">✿</span><p>ACCOUNT SETTINGS</p><h1>Manage your<br>studio account.</h1><a href="<?php echo site_url('admin'); ?>">← Kembali ke dashboard</a></div>
			<p class="auth-art-foot">Pilates · Yoga · Beauty &nbsp;—&nbsp; Cinere, Depok</p>
		</div>
		<section class="auth-card">
			<div class="auth-heading"><p class="admin-eyebrow">KEAMANAN AKUN</p><h2>Pengaturan akun.</h2><p>Perbarui username dan, bila diperlukan, password akun admin.</p></div>
			<?php if ($notice = $this->session->flashdata('account_notice')): ?><div class="account-success" role="status"><?php echo html_escape($notice); ?></div><?php endif; ?>
			<?php if ($error): ?><div class="form-alert" role="alert"><?php echo html_escape($error); ?></div><?php endif; ?>
			<?php echo form_open('admin/account', array('class' => 'admin-form')); ?>
				<label for="username">Username</label>
				<input id="username" name="username" type="text" autocomplete="username" maxlength="50" required value="<?php echo html_escape((string) $admin_username); ?>">
				<label for="current_password">Password saat ini</label>
				<input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
				<label for="new_password">Password baru <small>(opsional, minimal 12 karakter)</small></label>
				<input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="12">
				<label for="confirm_password">Konfirmasi password baru</label>
				<input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="12">
				<button class="admin-button admin-button-dark" type="submit">Simpan perubahan <span>→</span></button>
			<?php echo form_close(); ?>
			<a class="auth-back" href="<?php echo site_url('admin'); ?>">Kembali ke dashboard</a>
		</section>
	</main>
</body>
</html>
