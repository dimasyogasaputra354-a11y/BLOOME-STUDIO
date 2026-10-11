<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$money = function ($amount) {
	return 'Rp ' . number_format((float) $amount, 0, ',', '.');
};
$session_packages = array();
$membership_packages = array();
foreach ($packages as $package) {
	if ($package->kind === 'session') {
		$session_packages[] = $package;
	} else {
		$membership_packages[] = $package;
	}
}
$package_detail = function ($package) {
	$details = array();
	if ($package->validity_label) {
		$details[] = $package->validity_label;
	}
	if ($package->notes) {
		$details[] = $package->notes;
	}
	if ($package->kind === 'membership' && (int) $package->participants > 1 && strpos((string) $package->notes, 'Valid for') === FALSE) {
		$details[] = 'Valid untuk ' . (int) $package->participants . ' orang';
	}
	return implode(' · ', $details);
};
$sessions_in_package = function ($package) {
	return (int) $package->session_count + (int) $package->bonus_sessions;
};
?><!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f8f5f1">
	<meta name="description" content="Booking kelas Pilates di Bloomè Studio, Cinere, Depok.">
	<title>Booking Pilates — Bloomè Studio</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;1,400;1,500&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/bloome.css?v=' . filemtime(FCPATH . 'assets/css/bloome.css')); ?>">
</head>
<body class="booking-page">
	<main class="booking-flow">
		<a class="booking-back-link" href="<?php echo site_url(); ?>#services">← Kembali ke pilihan layanan</a>
		<div class="booking-progress" aria-label="Langkah booking"><span class="is-active"></span><span></span><span></span><span></span></div>
		<?php if ($errors): ?>
		<div class="booking-errors" role="alert"><strong>Booking belum dapat dilanjutkan:</strong><ul><?php foreach ($errors as $error): ?><li><?php echo html_escape($error); ?></li><?php endforeach; ?></ul></div>
		<?php endif; ?>

		<form id="booking-form" action="<?php echo html_escape($create_url); ?>" method="post" enctype="multipart/form-data" novalidate>
			<input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
			<section class="booking-step is-active" data-booking-step="1">
				<p class="eyebrow">BOOKING PILATES</p>
				<div class="booking-title-with-icon">
					<svg class="pilates-barbell" viewBox="0 0 120 64" role="img" aria-label="Ilustrasi barbel Pilates">
						<defs><linearGradient id="barbell-metal" x1="0" x2="1"><stop offset="0" stop-color="#b58d68"/><stop offset=".5" stop-color="#e0c4a5"/><stop offset="1" stop-color="#98724f"/></linearGradient></defs>
						<path d="M25 32h70" fill="none" stroke="url(#barbell-metal)" stroke-width="7" stroke-linecap="round"/>
						<path d="M29 17h-8v30h8m-10-23h-7v16h7m80-23h8v30h-8m10-23h7v16h-7" fill="none" stroke="#84674e" stroke-width="5" stroke-linejoin="round"/>
						<circle cx="60" cy="32" r="6" fill="#b58d68"/>
					</svg>
					<h1>Informasi Data Diri</h1>
				</div>

				<label class="booking-field">
					<span>Nama Lengkap <b>*</b></span>
					<input type="text" name="full_name" maxlength="100" placeholder="Nama lengkap kamu" autocomplete="name" required>
				</label>
				<label class="booking-field">
					<span>Nomor WhatsApp <b>*</b></span>
					<input type="tel" name="phone" maxlength="18" placeholder="Contoh: 081234567890" autocomplete="tel" required>
				</label>
				<label class="booking-field">
					<span>Riwayat Cedera / Kondisi Medis</span>
					<small>Kosongkan jika tidak ada</small>
					<textarea id="health-notes" name="health_notes" maxlength="1000" rows="3" placeholder="Contoh: Nyeri lutut kiri, post-operasi 2022..."><?php echo html_escape($health_notes); ?></textarea>
				</label>
				<div id="injury-statement" <?php echo $health_notes !== '' ? '' : 'hidden'; ?>>
					<a class="booking-document-link" href="<?php echo html_escape($statement_url); ?>">Unduh surat pernyataan peserta Pilates (PDF) <span aria-hidden="true">↓</span></a>
					<label class="booking-field booking-file-field">
						<span>Unggah Surat Pernyataan yang Sudah Diisi <b>*</b></span>
						<input type="file" name="statement_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" <?php echo $health_notes !== '' ? 'required' : ''; ?>>
						<small>Wajib jika mengisi riwayat cedera. Format PDF/JPG/PNG, maksimal 5 MB. File hanya dapat diakses admin.</small>
					</label>
				</div>
				<fieldset class="booking-field booking-source">
					<legend>Dari mana kamu tahu Bloomè? <b>*</b></legend>
					<div class="source-options">
						<?php foreach (array('Instagram', 'TikTok', 'Threads', 'Teman / Keluarga', 'Google', 'Lainnya') as $source): ?>
						<label><input type="radio" name="source" value="<?php echo html_escape($source); ?>" required><span><?php echo html_escape($source); ?></span></label>
						<?php endforeach; ?>
					</div>
					<label class="booking-field other-source-field" hidden>
						<span>Darimana kamu mendapat informasi tentang Bloomè? <b>*</b></span>
						<input type="text" name="source_other" maxlength="120" placeholder="Ceritakan sumber informasinya">
					</label>
				</fieldset>
				<button class="button button-booking-next" type="button" data-next-step="2">Pilih Paket <span aria-hidden="true">›</span></button>
			</section>

			<section class="booking-step" data-booking-step="2" hidden>
				<p class="eyebrow">BOOKING PILATES · 02</p>
				<h1>Pilih Paket</h1>
				<p class="booking-intro">Pilih jenis sesi atau membership. Seluruh sesi dalam paket dipilih tanggalnya pada langkah berikutnya.</p>
				<div class="booking-package-section">
					<h2>Pilates Class Price <small>Harga per sesi</small></h2>
					<div class="booking-packages">
						<?php foreach ($session_packages as $index => $package): ?>
						<label class="booking-package">
							<input type="radio" name="package_id" value="<?php echo (int) $package->id; ?>" data-count="<?php echo $sessions_in_package($package); ?>" data-validity="<?php echo (int) $package->validity_days; ?>" data-class-type="<?php echo html_escape($package->class_type); ?>" data-label="<?php echo html_escape($package->name); ?>" data-price="<?php echo (int) $package->price; ?>" data-price-label="<?php echo html_escape($money($package->price)); ?>" <?php echo $index === 0 && !$membership_packages ? 'required' : ''; ?>>
							<span class="booking-package-copy"><strong><?php echo html_escape($package->name); ?></strong><small><?php echo html_escape($package_detail($package)); ?></small></span>
							<strong class="booking-package-price"><?php echo $money($package->price); ?></strong>
						</label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="booking-package-section">
					<h2>Pilates Membership Price</h2>
					<?php foreach (array('group' => 'Membership Group', 'private' => 'Membership Private', 'duo' => 'Membership Duo (Valid 2 Person)', 'triple' => 'Membership Triple (Valid 3 Person)') as $class_type => $heading): ?>
					<?php
						$class_packages = array();
						foreach ($membership_packages as $package) {
							if ($package->class_type === $class_type) {
								$class_packages[] = $package;
							}
						}
					?>
					<?php if ($class_packages): ?>
					<h3 class="booking-package-subheading"><?php echo html_escape($heading); ?></h3>
					<div class="booking-packages">
						<?php foreach ($class_packages as $package): ?>
						<label class="booking-package">
							<input type="radio" name="package_id" value="<?php echo (int) $package->id; ?>" data-count="<?php echo $sessions_in_package($package); ?>" data-validity="<?php echo (int) $package->validity_days; ?>" data-class-type="<?php echo html_escape($package->class_type); ?>" data-label="<?php echo html_escape($package->name); ?>" data-price="<?php echo (int) $package->price; ?>" data-price-label="<?php echo html_escape($money($package->price)); ?>">
							<span class="booking-package-copy"><strong><?php echo html_escape($package->name); ?></strong><small><?php echo html_escape($package_detail($package)); ?></small></span>
							<strong class="booking-package-price"><?php echo $money($package->price); ?></strong>
						</label>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<button class="booking-back-step" type="button" data-previous-step="1">← Kembali ke data diri</button>
				<button class="button button-booking-next" type="button" data-next-step="3" disabled>Pilih Jadwal <span aria-hidden="true">›</span></button>
			</section>

			<section class="booking-step" data-booking-step="3" hidden>
				<p class="eyebrow">BOOKING PILATES · 03</p>
				<h1>Pilih Jadwal</h1>
				<p class="booking-intro">Pilih seluruh sesi paket. Kamu boleh memilih beberapa kelas di tanggal yang sama; slot penuh tidak dapat dipilih.</p>
				<div class="booking-session-counter" aria-live="polite">Pilih paket terlebih dahulu.</div>
				<label class="booking-field booking-date-card">
					<span class="booking-date-heading">
						<span class="booking-date-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5.5" width="17" height="15" rx="3" stroke="currentColor" stroke-width="1.5"/><path d="M7.5 3.5v4M16.5 3.5v4M4 9.5h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M8 13h.01M12 13h.01M16 13h.01M8 16.5h.01M12 16.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
						</span>
						<span class="booking-date-copy"><strong>Pilih tanggal kelas <b>*</b></strong><small>Tentukan hari yang paling nyaman untukmu</small></span>
						<span class="booking-date-tag">PILATES</span>
					</span>
					<input class="booking-date-input" id="schedule-date" type="date" min="<?php echo html_escape($today); ?>" max="<?php echo html_escape($latest_date); ?>">
					<small class="booking-date-note" id="schedule-validity-note">Pilih sesi pertama hingga dua tahun ke depan. Masa berlaku paket dihitung sejak tanggal tersebut.</small>
				</label>
				<div id="schedule-error" class="booking-inline-error" role="alert" hidden></div>
				<div id="schedule-slots" class="schedule-slots" aria-live="polite"><p class="booking-empty-slots">Pilih paket dan tanggal untuk melihat jadwal.</p></div>
				<div id="selected-sessions" class="selected-sessions"></div>
				<button class="booking-back-step" type="button" data-previous-step="2">← Kembali ke paket</button>
				<button class="button button-booking-next" type="button" data-next-step="4" disabled>Lanjut ke Pembayaran <span aria-hidden="true">›</span></button>
			</section>

			<section class="booking-step" data-booking-step="4" hidden>
				<p class="eyebrow">BOOKING PILATES · 04</p>
				<h1>Pembayaran</h1>
				<div class="booking-final-summary"></div>
				<p class="booking-intro">Pilih metode pembayaran. Slot akan ditahan selama 24 jam dan berstatus pending sampai admin memverifikasi pembayaran.</p>
				<fieldset class="booking-payment-methods">
					<legend class="sr-only">Pilih metode pembayaran</legend>
					<label><input type="radio" name="payment_method" value="transfer" required><span><strong>Transfer Bank BCA</strong><small>0012998811 · BCA BLOOME SEJAHTERA ABADI</small></span></label>
					<label><input type="radio" name="payment_method" value="qris"><span><strong>QRIS · Gambar menyusul</strong><small>Jika belum tersedia, kamu dapat transfer BCA dan memilih metode aktual saat mengunggah bukti.</small></span></label>
				</fieldset>
				<p class="booking-payment-note">Booking berstatus pending sampai admin memverifikasi bukti pembayaran.</p>
				<button class="booking-back-step" type="button" data-previous-step="3">← Kembali ke jadwal</button>
				<button class="button button-booking-next" type="submit">Buat Booking &amp; Lanjut Bayar <span aria-hidden="true">›</span></button>
			</section>
		</form>
	</main>
	<script>
		window.bloomeBooking = {
			availabilityUrl: <?php echo json_encode($availability_url); ?>,
			today: <?php echo json_encode($today); ?>,
			latestDate: <?php echo json_encode($latest_date); ?>
		};
	</script>
	<script src="<?php echo base_url('assets/js/booking.js?v=' . filemtime(FCPATH . 'assets/js/booking.js')); ?>"></script>
</body>
</html>
