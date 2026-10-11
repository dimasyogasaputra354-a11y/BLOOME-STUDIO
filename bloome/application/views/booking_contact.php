<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Booking <?php echo html_escape($service); ?> — Bloomè Studio</title>
	<link rel="stylesheet" href="<?php echo base_url('assets/css/bloome.css?v=' . filemtime(FCPATH . 'assets/css/bloome.css')); ?>">
</head>
<body class="booking-page">
	<main class="booking-flow booking-contact-flow">
		<a class="booking-back-link" href="<?php echo site_url(); ?>#services">← Kembali ke pilihan layanan</a>
		<p class="eyebrow">BOOKING <?php echo html_escape(strtoupper($service)); ?></p>
		<h1>Hubungi kami untuk booking <?php echo html_escape($service); ?>.</h1>
		<p>Booking mandiri untuk layanan <?php echo html_escape($service); ?> belum tersedia. Hubungi Bloomè Studio untuk memeriksa paket dan jadwal.</p>
		<a class="button button-booking-next" href="https://wa.me/628218000958?text=<?php echo rawurlencode('Halo Bloomè Studio, saya ingin booking ' . $service . '.'); ?>">Hubungi Bloomè Studio di WhatsApp <span aria-hidden="true">↗</span></a>
	</main>
</body>
</html>
