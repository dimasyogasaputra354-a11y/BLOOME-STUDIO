<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$catalogs = array(
	'pilates' => array('title' => 'Pricelist Pilates', 'items' => $pilates_catalog),
	'yoga' => array('title' => 'Pricelist Yoga', 'items' => $yoga_catalog),
	'beauty' => array('title' => 'Layanan Beauty', 'items' => $beauty_catalog)
);
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
?>
<section class="admin-panel content-admin-panel" id="services">
	<div class="panel-heading"><div><p class="admin-eyebrow">TAMPILAN WEBSITE</p><h2>Kelola layanan &amp; pricelist</h2></div><span class="panel-count">Pilates · Yoga · Beauty</span></div>
	<?php if ($notice = $this->session->flashdata('catalog_notice')): ?><p class="promo-image-feedback is-success" role="status"><?php echo html_escape($notice); ?></p><?php endif; ?>
	<?php if ($error = $this->session->flashdata('catalog_error')): ?><p class="promo-image-feedback is-error" role="alert"><?php echo html_escape($error); ?></p><?php endif; ?>
	<div class="content-catalog-grid">
		<?php foreach ($catalogs as $type => $catalog): ?>
		<section class="content-catalog">
			<h3><?php echo html_escape($catalog['title']); ?> <small><?php echo count($catalog['items']); ?> data</small></h3>
			<details class="promo-editor promo-create">
				<summary>Tambah layanan</summary>
				<form action="<?php echo site_url('admin/catalog/' . $type); ?>" method="post">
					<input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
					<div class="promo-edit-fields">
						<label>Nama layanan<input name="name" maxlength="<?php echo $type === 'beauty' ? 100 : 60; ?>" required></label>
						<label>Harga (Rp)<input name="price" type="number" min="0" max="4294967295" step="1" required></label>
						<?php if ($type === 'pilates'): ?>
							<label>Tipe paket<select name="kind"><option value="session">Session</option><option value="membership">Membership</option></select></label>
							<label>Jenis kelas<select name="class_type"><option value="group">Group</option><option value="private">Private</option><option value="duo">Duo</option><option value="triple">Triple</option></select></label>
							<label>Jumlah sesi<input name="session_count" type="number" min="1" max="65535" value="1" required></label>
							<label>Bonus sesi<input name="bonus_sessions" type="number" min="0" max="65535" value="0" required></label>
							<label>Peserta<input name="participants" type="number" min="1" max="255" value="1" required></label>
							<label>Masa berlaku (hari)<input name="validity_days" type="number" min="1" max="65535"></label>
							<label>Label masa berlaku<input name="validity_label" maxlength="40"></label>
							<label>Tanpa reschedule<select name="no_reschedule"><option value="0">Tidak</option><option value="1">Ya</option></select></label>
							<label>Catatan<input name="notes" maxlength="255"></label>
						<?php elseif ($type === 'yoga'): ?>
							<label>Jumlah sesi<input name="session_count" type="number" min="1" max="65535" value="1" required></label>
							<label>Bonus sesi<input name="bonus_sessions" type="number" min="0" max="65535" value="0" required></label>
							<label>Masa berlaku (hari)<input name="validity_days" type="number" min="1" max="65535"></label>
							<label>Label masa berlaku<input name="validity_label" maxlength="40" required></label>
							<label class="promo-field-wide">Catatan<input name="notes" maxlength="255"></label>
						<?php else: ?>
							<label>Kategori<select name="category_id" required><?php foreach ($beauty_categories as $category): ?><option value="<?php echo (int) $category->id; ?>"><?php echo html_escape($category->name); ?></option><?php endforeach; ?></select></label>
							<label>Tipe harga<select name="price_type"><option value="fixed">Harga tetap</option><option value="range">Rentang</option><option value="percent">Persentase</option><option value="free">Gratis</option></select></label>
							<label>Harga maksimum<input name="price_max" type="number" min="0" max="4294967295"></label>
							<label>Persentase<input name="percent_value" type="number" min="1" max="100"></label>
							<label class="promo-field-wide">Deskripsi<input name="description" maxlength="255"></label>
							<label class="promo-field-wide">Catatan<input name="notes" maxlength="255"></label>
							<label>Tampilkan di website<select name="is_featured"><option value="0">Tidak</option><option value="1">Ya</option></select></label>
						<?php endif; ?>
						<label>Urutan<input name="sort_order" type="number" min="0" max="255" value="1" required></label>
						<label>Status<select name="is_active"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></label>
					</div>
					<button type="submit" class="admin-button admin-button-dark">Tambah layanan</button>
				</form>
			</details>
			<div class="content-item-list">
				<?php foreach ($catalog['items'] as $item): ?>
				<details class="promo-editor content-item-editor">
					<summary><span><?php echo html_escape($item->name); ?></span><small><?php echo $money($item->price); ?> · <?php echo $item->is_active ? 'Aktif' : 'Nonaktif'; ?></small></summary>
					<form action="<?php echo site_url('admin/catalog/' . $type . '/' . (int) $item->id); ?>" method="post">
						<input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
						<div class="promo-edit-fields">
							<label>Nama layanan<input name="name" maxlength="<?php echo $type === 'beauty' ? 100 : 60; ?>" required value="<?php echo html_escape($item->name); ?>"></label>
							<label>Harga (Rp)<input name="price" type="number" min="0" max="4294967295" step="1" required value="<?php echo (int) $item->price; ?>"></label>
							<?php if ($type === 'pilates'): ?>
								<label>Tipe paket<select name="kind"><option value="session" <?php echo $item->kind === 'session' ? 'selected' : ''; ?>>Session</option><option value="membership" <?php echo $item->kind === 'membership' ? 'selected' : ''; ?>>Membership</option></select></label>
								<label>Jenis kelas<select name="class_type"><?php foreach (array('group', 'private', 'duo', 'triple') as $option): ?><option value="<?php echo $option; ?>" <?php echo $item->class_type === $option ? 'selected' : ''; ?>><?php echo ucfirst($option); ?></option><?php endforeach; ?></select></label>
								<label>Jumlah sesi<input name="session_count" type="number" min="1" max="65535" required value="<?php echo (int) $item->session_count; ?>"></label>
								<label>Bonus sesi<input name="bonus_sessions" type="number" min="0" max="65535" required value="<?php echo (int) $item->bonus_sessions; ?>"></label>
								<label>Peserta<input name="participants" type="number" min="1" max="255" required value="<?php echo (int) $item->participants; ?>"></label>
								<label>Masa berlaku (hari)<input name="validity_days" type="number" min="1" max="65535" value="<?php echo $item->validity_days !== NULL ? (int) $item->validity_days : ''; ?>"></label>
								<label>Label masa berlaku<input name="validity_label" maxlength="40" value="<?php echo html_escape((string) $item->validity_label); ?>"></label>
								<label>Tanpa reschedule<select name="no_reschedule"><option value="0" <?php echo !$item->no_reschedule ? 'selected' : ''; ?>>Tidak</option><option value="1" <?php echo $item->no_reschedule ? 'selected' : ''; ?>>Ya</option></select></label>
								<label>Catatan<input name="notes" maxlength="255" value="<?php echo html_escape((string) $item->notes); ?>"></label>
							<?php elseif ($type === 'yoga'): ?>
								<label>Jumlah sesi<input name="session_count" type="number" min="1" max="65535" required value="<?php echo (int) $item->session_count; ?>"></label>
								<label>Bonus sesi<input name="bonus_sessions" type="number" min="0" max="65535" required value="<?php echo (int) $item->bonus_sessions; ?>"></label>
								<label>Masa berlaku (hari)<input name="validity_days" type="number" min="1" max="65535" value="<?php echo $item->validity_days !== NULL ? (int) $item->validity_days : ''; ?>"></label>
								<label>Label masa berlaku<input name="validity_label" maxlength="40" required value="<?php echo html_escape($item->validity_label); ?>"></label>
								<label class="promo-field-wide">Catatan<input name="notes" maxlength="255" value="<?php echo html_escape((string) $item->notes); ?>"></label>
							<?php else: ?>
								<label>Kategori<select name="category_id" required><?php foreach ($beauty_categories as $category): ?><option value="<?php echo (int) $category->id; ?>" <?php echo (int) $item->category_id === (int) $category->id ? 'selected' : ''; ?>><?php echo html_escape($category->name); ?></option><?php endforeach; ?></select></label>
								<label>Tipe harga<select name="price_type"><?php foreach (array('fixed' => 'Harga tetap', 'range' => 'Rentang', 'percent' => 'Persentase', 'free' => 'Gratis') as $option => $label): ?><option value="<?php echo $option; ?>" <?php echo $item->price_type === $option ? 'selected' : ''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></label>
								<label>Harga maksimum<input name="price_max" type="number" min="0" max="4294967295" value="<?php echo $item->price_max !== NULL ? (int) $item->price_max : ''; ?>"></label>
								<label>Persentase<input name="percent_value" type="number" min="1" max="100" value="<?php echo $item->percent_value !== NULL ? (int) $item->percent_value : ''; ?>"></label>
								<label class="promo-field-wide">Deskripsi<input name="description" maxlength="255" value="<?php echo html_escape((string) $item->description); ?>"></label>
								<label class="promo-field-wide">Catatan<input name="notes" maxlength="255" value="<?php echo html_escape((string) $item->notes); ?>"></label>
								<label>Tampilkan di website<select name="is_featured"><option value="0" <?php echo !$item->is_featured ? 'selected' : ''; ?>>Tidak</option><option value="1" <?php echo $item->is_featured ? 'selected' : ''; ?>>Ya</option></select></label>
							<?php endif; ?>
							<label>Urutan<input name="sort_order" type="number" min="0" max="255" required value="<?php echo (int) $item->sort_order; ?>"></label>
							<label>Status<select name="is_active"><option value="1" <?php echo $item->is_active ? 'selected' : ''; ?>>Aktif</option><option value="0" <?php echo !$item->is_active ? 'selected' : ''; ?>>Nonaktif</option></select></label>
						</div>
						<button type="submit" class="admin-button admin-button-dark">Simpan perubahan</button>
					</form>
					<form class="promo-delete-form" action="<?php echo site_url('admin/catalog/delete/' . $type . '/' . (int) $item->id); ?>" method="post" onsubmit="return confirm('Hapus layanan ini dari website?');">
						<input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
						<button type="submit" class="promo-delete-button">Hapus layanan</button>
					</form>
				</details>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endforeach; ?>
	</div>
</section>

<section class="admin-panel content-admin-panel" id="reviews">
	<div class="panel-heading"><div><p class="admin-eyebrow">TAMPILAN WEBSITE</p><h2>Kelola review</h2></div><span class="panel-count"><?php echo count($reviews); ?> review</span></div>
	<?php if ($notice = $this->session->flashdata('review_notice')): ?><p class="promo-image-feedback is-success" role="status"><?php echo html_escape($notice); ?></p><?php endif; ?>
	<?php if ($error = $this->session->flashdata('review_error')): ?><p class="promo-image-feedback is-error" role="alert"><?php echo html_escape($error); ?></p><?php endif; ?>
	<details class="promo-editor promo-create"><summary>Tambah review</summary>
		<form action="<?php echo site_url('admin/review'); ?>" method="post"><input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
			<div class="promo-edit-fields"><label>Nama<input name="name" maxlength="100" required></label><label>Sumber<input name="source" maxlength="40" value="Google Review" required></label><label>Rating<select name="rating"><?php for ($rating = 5; $rating >= 1; $rating--): ?><option value="<?php echo $rating; ?>"><?php echo $rating; ?> bintang</option><?php endfor; ?></select></label><label>Urutan<input name="sort_order" type="number" min="0" max="65535" value="<?php echo count($reviews) + 1; ?>" required></label><label class="promo-field-wide">Isi review<textarea name="review_text" maxlength="1000" rows="3" required></textarea></label><label>Status<select name="is_active"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></label></div>
			<button type="submit" class="admin-button admin-button-dark">Tambah review</button>
		</form>
	</details>
	<div class="content-item-list">
		<?php foreach ($reviews as $review): ?>
		<details class="promo-editor content-item-editor"><summary><span><?php echo html_escape($review->name); ?></span><small><?php echo (int) $review->rating; ?> ★ · <?php echo $review->is_active ? 'Aktif' : 'Nonaktif'; ?></small></summary>
			<form action="<?php echo site_url('admin/review/' . (int) $review->id); ?>" method="post"><input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
				<div class="promo-edit-fields"><label>Nama<input name="name" maxlength="100" required value="<?php echo html_escape($review->name); ?>"></label><label>Sumber<input name="source" maxlength="40" required value="<?php echo html_escape($review->source); ?>"></label><label>Rating<select name="rating"><?php for ($rating = 5; $rating >= 1; $rating--): ?><option value="<?php echo $rating; ?>" <?php echo (int) $review->rating === $rating ? 'selected' : ''; ?>><?php echo $rating; ?> bintang</option><?php endfor; ?></select></label><label>Urutan<input name="sort_order" type="number" min="0" max="65535" required value="<?php echo (int) $review->sort_order; ?>"></label><label class="promo-field-wide">Isi review<textarea name="review_text" maxlength="1000" rows="3" required><?php echo html_escape($review->review_text); ?></textarea></label><label>Status<select name="is_active"><option value="1" <?php echo $review->is_active ? 'selected' : ''; ?>>Aktif</option><option value="0" <?php echo !$review->is_active ? 'selected' : ''; ?>>Nonaktif</option></select></label></div>
				<button type="submit" class="admin-button admin-button-dark">Simpan perubahan</button>
			</form>
			<form class="promo-delete-form" action="<?php echo site_url('admin/review/delete/' . (int) $review->id); ?>" method="post" onsubmit="return confirm('Hapus review ini dari website?');"><input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>"><button type="submit" class="promo-delete-button">Hapus review</button></form>
		</details>
		<?php endforeach; ?>
	</div>
</section>

<section class="admin-panel content-admin-panel" id="gallery">
	<div class="panel-heading"><div><p class="admin-eyebrow">TAMPILAN WEBSITE · MAKSIMAL 4 FOTO</p><h2>Kelola gallery</h2></div><span class="panel-count"><?php echo (int) $gallery_count; ?>/4 foto</span></div>
	<?php if ($notice = $this->session->flashdata('gallery_notice')): ?><p class="promo-image-feedback is-success" role="status"><?php echo html_escape($notice); ?></p><?php endif; ?>
	<?php if ($error = $this->session->flashdata('gallery_error')): ?><p class="promo-image-feedback is-error" role="alert"><?php echo html_escape($error); ?></p><?php endif; ?>
	<?php if ($gallery_count < 4): ?>
	<details class="promo-editor promo-create"><summary>Tambah foto ke gallery</summary>
		<form action="<?php echo site_url('admin/gallery'); ?>" method="post" enctype="multipart/form-data"><input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
			<div class="promo-edit-fields"><label>Foto (JPG/PNG, maks. 5 MB)<input type="file" name="gallery_image" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required></label><label>Teks alternatif<input name="alt_text" maxlength="160" required></label><label>Urutan<input name="sort_order" type="number" min="0" max="65535" value="<?php echo (int) $gallery_count + 1; ?>" required></label><label>Status<select name="is_active"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></label></div>
			<button type="submit" class="admin-button admin-button-dark">Tambah foto</button>
		</form>
	</details>
	<?php else: ?><p class="panel-footnote">Galeri sudah 4/4. Edit, ganti, atau hapus foto yang ada untuk mengisi ulang slot.</p><?php endif; ?>
	<div class="content-gallery-grid">
		<?php foreach ($gallery as $image): ?>
		<article class="content-gallery-item">
			<?php if (preg_match('#^(?:gallery-uploads/)?[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png)$#i', $image->image_path)): ?><img src="<?php echo html_escape(base_url('img/' . $image->image_path)); ?>" alt="<?php echo html_escape($image->alt_text); ?>"><?php endif; ?>
			<details class="promo-editor"><summary>Edit foto</summary>
				<form action="<?php echo site_url('admin/gallery/' . (int) $image->id); ?>" method="post" enctype="multipart/form-data"><input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
					<div class="promo-edit-fields"><label>Teks alternatif<input name="alt_text" maxlength="160" required value="<?php echo html_escape($image->alt_text); ?>"></label><label>Urutan<input name="sort_order" type="number" min="0" max="65535" required value="<?php echo (int) $image->sort_order; ?>"></label><label>Status<select name="is_active"><option value="1" <?php echo $image->is_active ? 'selected' : ''; ?>>Aktif</option><option value="0" <?php echo !$image->is_active ? 'selected' : ''; ?>>Nonaktif</option></select></label><label>Ganti foto<input type="file" name="gallery_image" accept=".jpg,.jpeg,.png,image/jpeg,image/png"></label></div>
					<button type="submit" class="admin-button admin-button-dark">Simpan perubahan</button>
				</form>
				<form class="promo-delete-form" action="<?php echo site_url('admin/gallery/delete/' . (int) $image->id); ?>" method="post" onsubmit="return confirm('Hapus foto ini dari gallery?');"><input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_hash); ?>"><button type="submit" class="promo-delete-button">Hapus foto</button></form>
			</details>
		</article>
		<?php endforeach; ?>
	</div>
</section>
