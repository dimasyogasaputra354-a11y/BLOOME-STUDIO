<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$whatsapp_number = '6281234567890';
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
				<div class="hero-image-main">
					<img src="https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&amp;fit=crop&amp;w=1000&amp;q=85" alt="Studio yoga yang hangat dan tenang">
				</div>
				<div class="hero-image-small">
					<img src="https://images.unsplash.com/photo-1600334129128-685c5582fd35?auto=format&amp;fit=crop&amp;w=640&amp;q=85" alt="Ruang perawatan beauty">
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
				<article class="promo-card <?php echo $index % 2 === 1 ? 'promo-card-sage' : ''; ?>">
					<div class="promo-topline"><span class="promo-label"><?php echo html_escape(strtoupper($promo->discipline)); ?></span><span class="promo-spark">✳</span></div>
					<h3><?php echo html_escape($promo->title); ?></h3>
					<?php if (!empty($promo->tagline)): ?><p class="promo-tagline"><?php echo html_escape($promo->tagline); ?></p><?php endif; ?>
					<?php if (!empty($promo->terms_note)): ?><p class="promo-description"><?php echo html_escape($promo->terms_note); ?></p><?php endif; ?>
					<div class="promo-bottom"><p class="promo-price"><?php echo $money($promo->price); ?> <small>/ <?php echo html_escape($promo->price_unit); ?></small></p><a class="button button-dark button-small" href="<?php echo $service_booking_url($promo->discipline); ?>">Gunakan Promo <span>→</span></a></div>
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

		<section class="reviews-section section-wrap section-space" id="reviews">
			<div class="section-heading centered-heading"><p class="eyebrow">REAL STORIES</p><h2>Yang sudah merasakan.</h2></div>
			<div class="review-grid">
				<figure class="review-card"><div class="stars" aria-label="5 dari 5 bintang">★★★★★</div><blockquote>“Tempatnya cantik dan tenang. Booking-nya juga gampang banget.”</blockquote><figcaption><span class="review-avatar">N</span>Nadia</figcaption></figure>
				<figure class="review-card"><div class="stars" aria-label="5 dari 5 bintang">★★★★★</div><blockquote>“Instrukturnya ramah, studio bersih, pasti balik lagi.”</blockquote><figcaption><span class="review-avatar">A</span>Alya</figcaption></figure>
				<figure class="review-card"><div class="stars" aria-label="4 dari 5 bintang">★★★★<span>★</span></div><blockquote>“Suka banget konsepnya. Admin juga cepat membalas.”</blockquote><figcaption><span class="review-avatar">R</span>Rani</figcaption></figure>
			</div>
		</section>

		<section class="gallery-section section-wrap section-space" id="gallery">
			<div class="section-heading heading-row"><div><p class="eyebrow">BLOOMÈ MOMENTS</p><h2>Lihat <em>suasananya.</em></h2></div><span class="gallery-note">a little glimpse of our space</span></div>
			<div class="gallery-grid">
				<img src="https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&amp;fit=crop&amp;w=850&amp;q=85" alt="Latihan pilates di studio" loading="lazy">
				<img src="https://images.unsplash.com/photo-1545389336-cf090694435e?auto=format&amp;fit=crop&amp;w=700&amp;q=85" alt="Ruang yoga dengan cahaya alami" loading="lazy">
				<img src="https://images.unsplash.com/photo-1604654894610-df63bc536371?auto=format&amp;fit=crop&amp;w=700&amp;q=85" alt="Perawatan kuku yang detail" loading="lazy">
				<img src="https://images.unsplash.com/photo-1515377905703-c4788e51af15?auto=format&amp;fit=crop&amp;w=850&amp;q=85" alt="Ruang perawatan yang nyaman" loading="lazy">
			</div>
		</section>

		<section class="contact-section section-wrap" id="booking">
			<div class="contact-copy"><p class="eyebrow">NEED HELP?</p><h2>Mau tanya dulu sebelum booking?</h2><p>Hubungi Bloomè Studio untuk cek layanan, paket, atau request khusus.</p><div class="contact-actions"><a class="button button-dark" href="<?php echo $whatsapp_url . $booking_message; ?>" target="_blank" rel="noopener">WhatsApp Studio <span>↗</span></a><a class="phone-link" href="tel:+6281234567890">☏ &nbsp;0812-3456-7890</a></div></div>
			<div class="social-card"><span class="social-flower">✿</span><p>Ikuti kami</p><a href="https://www.instagram.com/bloomestudio.jkt" target="_blank" rel="noopener"><span>Instagram</span><strong>@bloomestudio.jkt ↗</strong></a><a href="https://www.threads.net/@bloomestudio.jkt" target="_blank" rel="noopener"><span>Threads</span><strong>@bloomestudio.jkt ↗</strong></a></div>
		</section>
	</main>

	<footer class="site-footer">
		<div class="footer-brand"><a class="wordmark" href="#home">Bloomè <span>Studio</span></a><p>Pilates · Yoga · Beauty</p></div>
		<div class="footer-column"><h3>Studio</h3><p>Ruko Cinere Terrace, Blok JA, No. 11<br>Cinere - Depok</p></div>
		<div class="footer-column"><h3>Contact</h3><a href="tel:+6281234567890">0812-3456-7890</a><a href="mailto:hello@bloome.studio">hello@bloome.studio</a></div>
		<div class="footer-bottom"><span>© <?php echo date('Y'); ?> Bloomè Studio</span><span>Made with care, for your wellbeing.</span></div>
	</footer>
	<script src="<?php echo base_url('assets/js/bloome.js'); ?>"></script>
</body>
</html>
