<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$whatsapp_number = '628218000958';
$whatsapp_url = 'https://wa.me/' . $whatsapp_number . '?text=';
$booking_message = rawurlencode('Halo Bloomè Studio, saya ingin booking sesi Pilates / Yoga / Beauty.');
$booking_url = site_url('booking');
$money = function ($amount) {
	return 'Rp ' . number_format((float) $amount, 0, ',', '.');
};
$service_booking_url = function ($service) use ($booking_url) {
	$service_key = strtolower($service);
	foreach (array('pilates', 'yoga', 'beauty') as $available_service) {
		if (strpos($service_key, $available_service) !== FALSE) {
			return $booking_url . '?service=' . $available_service;
		}
	}

	return $booking_url;
};
$promo_image_url = function ($promo) {
	$image_path = !empty($promo->image_path) ? $promo->image_path : NULL;
	if ($image_path === NULL) {
		$image_defaults = array(
			'PILATES-1010' => '10.10-pilates.jpeg',
			'YOGA-AFTER-OFFICE' => 'yoga-promo.jpeg'
		);
		$image_path = isset($image_defaults[$promo->code]) ? $image_defaults[$promo->code] : NULL;
	}

	if ($image_path === NULL || !preg_match('#^(?:promo-uploads/)?[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png)$#i', $image_path)) {
		return NULL;
	}

	return base_url('img/' . $image_path);
};
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f8f5f1">
	<meta name="description" content="Ritual kecil untuk merawat diri. Studio Pilates, Yoga, dan Beauty di Cinere, Depok.">
	<title>Bloomè Studio — Ritual kecil untuk merawat diri</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/bloome.css?v=' . filemtime(FCPATH . 'assets/css/bloome.css')); ?>">
</head>
<body>
	<header class="site-header">
		<a class="wordmark" href="#home" aria-label="Bloomè Studio, beranda">Bloomè <span>Studio</span></a>
		<button class="menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false">
			<span></span><span></span>
		</button>
		<nav class="main-nav" aria-label="Navigasi utama">
			<a href="#services">Layanan</a>
			<a href="#promos">Promo</a>
			<a href="#reviews">Review</a>
			<a href="#gallery">Gallery</a>
			<a class="button button-dark nav-booking" href="#services">Booking Sekarang</a>
			<a class="button button-light nav-admin" href="<?php echo site_url('admin'); ?>">Admin</a>
		</nav>
	</header>

	<main>
		<section class="hero section-wrap" id="home">
			<div class="hero-copy">
				<div class="eyebrow-pill"><span>✦</span> Pilates · Yoga · Beauty</div>
				<p class="hero-location">EST. 2026 <span>·</span> CINERE <span>·</span> DEPOK</p>
				<h1>Ritual kecil untuk <em>merawat diri.</em></h1>
				<p class="hero-description">Studio pilates, yoga &amp; beauty yang dibuat untuk kamu yang ingin berhenti sejenak, bernapas lebih pelan, dan pulang dengan perasaan lebih baik.</p>
				<div class="hero-actions">
					<a class="button button-dark" href="#services">Reservasi Sekarang <span aria-hidden="true">→</span></a>
					<a class="button button-light" href="#promos">Lihat Special Price</a>
				</div>
				<div class="hero-benefits"><span>✓ Booking online</span><span>✓ Pembayaran mudah</span><span>✓ Konfirmasi WhatsApp</span></div>
			</div>
			<div class="hero-visual" aria-label="Suasana Bloomè Studio">
				<div class="hero-image-main hero-carousel" data-hero-carousel role="region" aria-label="Galeri foto Bloomè Studio" aria-roledescription="carousel">
					<img class="hero-slide is-active" src="<?php echo base_url('img/yoga.jpeg'); ?>" alt="Studio yoga Bloomè dengan ruang latihan yang terang" aria-hidden="false">
					<img class="hero-slide" src="<?php echo base_url('img/Beauty.jpeg'); ?>" alt="Area beauty Bloomè dengan interior yang nyaman" aria-hidden="true">
				</div>
				<div class="hero-image-caption"><span class="caption-spark">✳</span><span>make a little<br>space for you</span></div>
			</div>
		</section>

		<section class="promo-section section-wrap section-space" id="promos">
			<div class="section-heading heading-row">
				<div><p class="eyebrow">SPECIAL TODAY</p><h2>Promo yang sayang <em>dilewatkan.</em></h2></div>
				<a class="text-link" href="#services">Lihat layanan <span>↗</span></a>
			</div>
			<?php if (!empty($promos)): ?>
			<div class="promo-grid">
				<?php foreach ($promos as $index => $promo): ?>
				<?php $promo_image = $promo_image_url($promo); ?>
				<article class="promo-card<?php echo $promo_image ? ' has-image' : ''; ?> <?php echo $index % 2 === 1 ? 'promo-card-sage' : ''; ?>">
					<?php if ($promo_image): ?><img class="promo-card-image" src="<?php echo html_escape($promo_image); ?>" alt="<?php echo html_escape($promo->title); ?>" loading="lazy"><?php endif; ?>
					<div class="promo-card-content">
						<div class="promo-topline"><span class="promo-label"><?php echo html_escape(strtoupper($promo->discipline)); ?></span><span class="promo-spark">✳</span></div>
						<h3><?php echo html_escape($promo->title); ?></h3>
						<?php if (!empty($promo->tagline)): ?><p class="promo-tagline"><?php echo html_escape($promo->tagline); ?></p><?php endif; ?>
						<?php if (!empty($promo->terms_note)): ?><p class="promo-description"><?php echo html_escape($promo->terms_note); ?></p><?php endif; ?>
						<div class="promo-bottom"><p class="promo-price"><?php echo $money($promo->price); ?> <small>/ <?php echo html_escape($promo->price_unit); ?></small></p><a class="button button-dark button-small" href="<?php echo $service_booking_url($promo->discipline); ?>">Gunakan Promo <span>→</span></a></div>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
			<?php else: ?>
			<div class="empty-promo">Belum ada promo aktif saat ini. Temukan paket layanan kami di bawah.</div>
			<?php endif; ?>
		</section>

		<section class="services-section section-wrap section-space" id="services">
			<div class="section-heading centered-heading"><p class="eyebrow">OUR SERVICES</p><h2>Pilih layananmu.</h2><p>Pilates &amp; yoga untuk tubuh. Beauty untuk mood.</p></div>
			<div class="service-grid">
				<article class="service-card">
					<div class="service-card-heading"><span class="service-icon icon-pilates"><svg class="pilates-barbell-card" viewBox="0 0 64 40" role="img" aria-label="Barbel Pilates"><path d="M12 20h40M16 10h-5v20h5m-8-16H4v12h4m40-16h5v20h-5m8-16h4v12h-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="32" cy="20" r="3" fill="currentColor"/></svg></span><div><h3>Pilates</h3><p>Reformer · Mat</p></div></div>
					<p class="service-intro">Gerak dengan mindful, kuatkan tubuh dengan ritmemu.</p>
					<ul class="price-list">
						<?php foreach ($pilates as $item): ?>
						<li><span><?php echo html_escape($item->name); ?><small><?php echo (int) $item->session_count; ?> sesi<?php echo !empty($item->notes) ? ' · ' . html_escape($item->notes) : ''; ?></small></span><strong><?php echo $money($item->price); ?></strong></li>
						<?php endforeach; ?>
					</ul>
					<a class="button button-outline service-cta" href="<?php echo $service_booking_url('Pilates'); ?>">Booking Pilates <span>→</span></a>
				</article>
				<article class="service-card">
					<div class="service-card-heading"><span class="service-icon icon-yoga">☼</span><div><h3>Yoga</h3><p>Move · Breathe · Balance</p></div></div>
					<p class="service-intro">Temukan ruang untuk bernapas dan kembali seimbang.</p>
					<ul class="price-list">
						<?php foreach ($yoga as $item): ?>
						<li><span><?php echo html_escape($item->name); ?><small><?php echo (int) $item->session_count; ?> sesi · <?php echo html_escape($item->validity_label); ?></small></span><strong><?php echo $money($item->price); ?></strong></li>
						<?php endforeach; ?>
					</ul>
					<a class="button button-outline service-cta" href="<?php echo $service_booking_url('Yoga'); ?>">Booking Yoga <span>→</span></a>
				</article>
				<article class="service-card service-card-beauty">
					<div class="service-card-heading"><span class="service-icon icon-beauty">✿</span><div><h3>Beauty</h3><p>Nail · Lash</p></div></div>
					<p class="service-intro">Sedikit waktu untuk diri sendiri, dengan hasil yang kamu suka.</p>
					<ul class="price-list">
						<?php foreach ($beauty as $item): ?>
						<li><span><?php echo html_escape($item->name); ?><small><?php echo html_escape($item->category_name); ?></small></span><strong><?php echo $item->price_type === 'free' ? 'Gratis' : ($item->price_type === 'percent' ? (int) $item->percent_value . '%' : ($item->price_type === 'range' ? $money($item->price) . ' – ' . $money($item->price_max) : $money($item->price))); ?></strong></li>
						<?php endforeach; ?>
					</ul>
					<a class="button button-outline service-cta" href="<?php echo $service_booking_url('Beauty Treatment'); ?>">Booking Beauty <span>→</span></a>
				</article>
			</div>
		</section>

		<section class="why-section section-space">
			<div class="section-wrap">
				<div class="section-heading centered-heading"><p class="eyebrow">WHY BLOOMÈ</p><h2>Bukan sekadar booking.</h2></div>
				<div class="benefit-grid">
					<article><span>01</span><h3>Easy Booking</h3><p>Pilih layanan, lokasi, tanggal dan jam tanpa perlu chat panjang.</p></article>
					<article><span>02</span><h3>Transparent Price</h3><p>Harga, promo, durasi dan detail layanan terlihat sebelum checkout.</p></article>
					<article><span>03</span><h3>Personal Touch</h3><p>Setelah booking, admin langsung mengonfirmasi melalui WhatsApp.</p></article>
				</div>
			</div>
		</section>

		<?php $this->load->view('partials/reviews'); ?>

		<section class="gallery-section section-wrap section-space" id="gallery">
			<div class="section-heading heading-row"><div><p class="eyebrow">BLOOMÈ MOMENTS</p><h2>Lihat <em>suasananya.</em></h2></div><span class="gallery-note">a little glimpse of our space</span></div>
			<div class="gallery-grid">
				<img src="<?php echo base_url('img/gallery-studio-01.jpg'); ?>" alt="Sesi Pilates reformer di Bloomè Studio" loading="lazy">
				<img src="<?php echo base_url('img/gallery-studio-02.jpg'); ?>" alt="Ruang kelas yoga Bloomè Studio" loading="lazy">
				<img src="<?php echo base_url('img/gallery-studio-03.jpg'); ?>" alt="Area lounge Bloomè Studio" loading="lazy">
				<img src="<?php echo base_url('img/gallery-studio-04.jpg'); ?>" alt="Area perawatan beauty Bloomè Studio" loading="lazy">
			</div>
		</section>

		<section class="contact-section section-wrap" id="booking">
			<div class="contact-copy"><p class="eyebrow">NEED HELP?</p><h2>Mau tanya dulu sebelum booking?</h2><p>Hubungi Bloomè Studio untuk cek layanan, paket, atau request khusus.</p><div class="contact-actions"><a class="button button-dark" href="<?php echo $whatsapp_url . $booking_message; ?>" target="_blank" rel="noopener"><svg class="whatsapp-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.4 11.8a8.4 8.4 0 0 1-12.5 7.3L3 20.3l1.2-4.7a8.4 8.4 0 1 1 16.2-3.8Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8.4 8.2c.2-.5.5-.5.8-.5h.5c.2 0 .4.1.5.4l.7 1.7c.1.2.1.4-.1.6l-.6.7c-.2.2-.2.4 0 .6.5.9 1.2 1.5 2.1 2 .2.1.4.1.6-.1l.7-.8c.2-.2.4-.2.6-.1l1.7.8c.3.1.4.3.4.5 0 .4-.2 1-.6 1.3-.4.4-1 .6-1.7.5-1-.1-2.4-.7-3.8-1.9-1.2-1-2-2.3-2.3-3.2-.3-.9-.1-1.7.5-2.5Z" fill="currentColor"/></svg>WhatsApp Studio <span aria-hidden="true">↗</span></a></div></div>
		</section>
	</main>

	<footer class="site-footer">
		<div class="footer-brand"><a class="wordmark" href="#home">Bloomè <span>Studio</span></a><p>Pilates · Yoga · Beauty</p></div>
		<div class="footer-column footer-location"><h3>Studio</h3><a class="location-link" href="https://maps.app.goo.gl/Ukuq2Xiimw5tQijk7" target="_blank" rel="noopener" aria-label="Buka lokasi Bloomè Studio di Google Maps"><svg viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="maps-pin" x1="4" y1="4" x2="20" y2="20" gradientUnits="userSpaceOnUse"><stop stop-color="#4285F4"/><stop offset=".35" stop-color="#34A853"/><stop offset=".68" stop-color="#FBBC04"/><stop offset="1" stop-color="#EA4335"/></linearGradient></defs><path d="M19 10.2c0 5-7 11-7 11s-7-6-7-11a7 7 0 1 1 14 0Z" fill="none" stroke="url(#maps-pin)" stroke-width="2"/><circle cx="12" cy="10" r="2.3" fill="#EA4335"/></svg><span>Ruko Cinere Terrace, Blok JA, No. 11<br>Cinere, Depok</span></a></div>
		<div class="footer-column"><h3>Contact</h3><a href="tel:+628218000958">0821-8000-958</a><a href="mailto:Bloomecinere.sejahteraabadi@gmail.com">Bloomecinere.sejahteraabadi@gmail.com</a><div class="footer-socials">
			<a class="footer-social-link instagram-link" href="https://www.instagram.com/bloomestudio.jkt" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="instagram-mark" x1="3" y1="21" x2="21" y2="3" gradientUnits="userSpaceOnUse"><stop stop-color="#FEDA75"/><stop offset=".35" stop-color="#FA7E1E"/><stop offset=".65" stop-color="#D62976"/><stop offset="1" stop-color="#4F5BD5"/></linearGradient></defs><rect x="3.2" y="3.2" width="17.6" height="17.6" rx="5.2" fill="none" stroke="url(#instagram-mark)" stroke-width="1.9"/><circle cx="12" cy="12" r="4.1" fill="none" stroke="url(#instagram-mark)" stroke-width="1.9"/><circle cx="17.8" cy="6.6" r="1.2" fill="#D62976"/></svg><span>@bloomestudio.jkt</span></a>
			<a class="footer-social-link threads-link" href="https://www.threads.net/@bloomestudio.jkt" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.9 10.8c-.5-4-2.7-6.3-6.2-6.3-4.1 0-6.6 3-6.6 7.6 0 4.7 2.4 7.6 6.6 7.6 3.2 0 5.3-1.8 5.3-4.6 0-2.4-1.7-3.9-4.3-3.9-2.2 0-3.6 1-3.6 2.6 0 1.3 1 2.2 2.5 2.2 1.7 0 2.8-1.1 2.8-3.1 0-2.7-1.4-4.3-3.7-4.3-1.2 0-2.2.4-3.1 1.2" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg><span>@bloomestudio.jkt</span></a>
		</div></div>
		<div class="footer-bottom"><span>© <?php echo date('Y'); ?> Bloomè Studio</span><span>Made with care, for your wellbeing.</span></div>
	</footer>
	<script src="<?php echo base_url('assets/js/bloome.js?v=' . filemtime(FCPATH . 'assets/js/bloome.js')); ?>"></script>
</body>
</html>
