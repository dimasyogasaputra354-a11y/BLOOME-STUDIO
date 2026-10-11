<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title>Admin Login — Bloomè Studio</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&amp;family=Playfair+Display:ital,wght@0,400;0,500;1,400&amp;display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css'); ?>">
</head>
<body class="admin-auth-page">
	<main class="auth-layout">
		<div class="auth-art">
			<a class="admin-wordmark" href="<?php echo site_url(); ?>">Bloomè <span>Studio</span></a>
			<div class="auth-art-copy"><span class="auth-flower">✿</span><p>YOUR SPACE, YOUR RITUAL</p><h1>A little care<br>goes a long way.</h1><a href="<?php echo site_url(); ?>">← Kembali ke website</a></div>
			<p class="auth-art-foot">Pilates · Yoga · Beauty &nbsp;—&nbsp; Cinere, Depok</p>
		</div>
		<section class="auth-card">
			<div class="auth-heading"><p class="admin-eyebrow">BLOOMÈ STUDIO · ADMIN</p><h2>Selamat datang.</h2><p>Masuk untuk melihat ringkasan studio.</p></div>
			<?php if ($error): ?><div class="form-alert" role="alert"><?php echo html_escape($error); ?></div><?php endif; ?>
			<?php echo form_open('admin/login', array('class' => 'admin-form')); ?>
				<label for="username">Username</label>
				<input id="username" name="username" type="text" autocomplete="username" maxlength="50" required value="<?php echo html_escape(set_value('username')); ?>">
				<label for="password">Password</label>
				<input id="password" name="password" type="password" autocomplete="current-password" required>
				<button class="admin-button admin-button-dark" type="submit">Masuk ke dashboard <span>→</span></button>
			<?php echo form_close(); ?>
			<p class="auth-note">Akses khusus untuk tim Bloomè Studio.</p>
		</section>
	</main>
</body>
</html>
