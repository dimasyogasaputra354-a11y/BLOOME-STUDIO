<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Asia/Jakarta');
		$this->load->model('Admin_model', 'admin_model');
	}

	public function index()
	{
		if (!$this->session->userdata('admin_user_id')) {
			redirect('admin/login');
		}

		if ($this->session->userdata('admin_must_change_password')) {
			redirect('admin/change-password');
		}

		$report_filter_error = '';
		$selected_date = $this->input->get('date', TRUE);
		if ($selected_date === NULL || $selected_date === '') {
			$selected_date = date('Y-m-d');
		} elseif (!is_string($selected_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)
			|| !checkdate((int) substr($selected_date, 5, 2), (int) substr($selected_date, 8, 2), (int) substr($selected_date, 0, 4))) {
			$report_filter_error = 'Tanggal laporan tidak valid. Menampilkan laporan hari ini.';
			$selected_date = date('Y-m-d');
		}
		$selected_month = $this->input->get('month', TRUE);
		if ($selected_month === NULL || $selected_month === '') {
			$selected_month = date('Y-m');
		} elseif (!is_string($selected_month) || !preg_match('/^\d{4}-\d{2}$/', $selected_month)
			|| (int) substr($selected_month, 5, 2) < 1 || (int) substr($selected_month, 5, 2) > 12) {
			$report_filter_error = 'Bulan laporan tidak valid. Menampilkan laporan bulan ini.';
			$selected_month = date('Y-m');
		}

		$daily_end = date('Y-m-d', strtotime($selected_date . ' +1 day'));
		$daily_rows = $this->admin_model->get_paid_report_rows($selected_date . ' 00:00:00', $daily_end . ' 00:00:00');
		$trend_start = date('Y-m-d', strtotime($selected_date . ' -13 days'));
		$trend_rows = $this->admin_model->get_paid_report_rows($trend_start . ' 00:00:00', $daily_end . ' 00:00:00');
		$monthly_start = $selected_month . '-01';
		$monthly_end = date('Y-m-d', strtotime($monthly_start . ' +1 month'));
		$monthly_rows = $this->admin_model->get_paid_report_rows($monthly_start . ' 00:00:00', $monthly_end . ' 00:00:00');

		$daily_summary = $this->summarize_report_rows($daily_rows);
		$trend_chart = $this->build_revenue_chart($trend_rows, $trend_start, $selected_date, 'day');
		$monthly_chart = $this->build_revenue_chart($monthly_rows, $monthly_start, date('Y-m-d', strtotime($monthly_end . ' -1 day')), 'month');
		$monthly_summary = $this->summarize_report_rows($monthly_rows);

		$data = array(
			'admin_name' => $this->session->userdata('admin_full_name'),
			'admin_username' => $this->session->userdata('admin_username'),
			'role_name' => $this->session->userdata('admin_role_name'),
			'overview' => $this->admin_model->get_overview(),
			'report_date' => $selected_date,
			'report_month' => $selected_month,
			'report_filter_error' => $report_filter_error,
			'daily_rows' => $daily_rows,
			'daily_summary' => $daily_summary,
			'daily_chart' => $trend_chart,
			'monthly_rows' => $monthly_rows,
			'monthly_summary' => $monthly_summary,
			'monthly_chart' => $monthly_chart,
			'promo_count' => $this->admin_model->count_promos(),
			'gallery_count' => $this->admin_model->count_gallery(),
			'promos' => $this->admin_model->get_promos(),
			'pilates' => $this->admin_model->get_pilates_prices(),
			'yoga' => $this->admin_model->get_yoga_prices(),
			'pilates_catalog' => $this->admin_model->get_catalog_items('pilates'),
			'yoga_catalog' => $this->admin_model->get_catalog_items('yoga'),
			'beauty_catalog' => $this->admin_model->get_catalog_items('beauty'),
			'beauty_categories' => $this->admin_model->get_beauty_categories(),
			'reviews' => $this->admin_model->get_reviews(),
			'gallery' => $this->admin_model->get_gallery()
		);

		$this->load->view('admin/dashboard', $data);
	}

	public function promo_image($promo_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
		}

		$promo = $this->admin_model->get_promo_for_image((int) $promo_id);
		if (!$promo) {
			show_404();
		}
		if (empty($_FILES['promo_image']['name'])) {
			$this->session->set_flashdata('promo_image_error', 'Pilih file gambar promo terlebih dahulu.');
			redirect('admin#promos');
			return;
		}

		$upload_directory = FCPATH . 'img' . DIRECTORY_SEPARATOR . 'promo-uploads' . DIRECTORY_SEPARATOR;
		if (!is_dir($upload_directory) && !mkdir($upload_directory, 0755, TRUE) && !is_dir($upload_directory)) {
			log_message('error', 'Could not create promo image upload directory: ' . $upload_directory);
			$this->session->set_flashdata('promo_image_error', 'Folder penyimpanan gambar promo tidak dapat dibuat.');
			redirect('admin#promos');
			return;
		}

		$this->load->library('upload');
		$this->upload->initialize(array(
			'upload_path' => $upload_directory,
			'allowed_types' => 'jpg|jpeg|png',
			'max_size' => 5120,
			'max_width' => 6000,
			'max_height' => 6000,
			'encrypt_name' => TRUE,
			'file_ext_tolower' => TRUE,
			'remove_spaces' => TRUE,
			'detect_mime' => TRUE,
			'mod_mime_fix' => TRUE,
			'overwrite' => FALSE
		));

		if (!$this->upload->do_upload('promo_image')) {
			$this->session->set_flashdata('promo_image_error', strip_tags($this->upload->display_errors('', '')));
			redirect('admin#promos');
			return;
		}

		$file = $this->upload->data();
		$dimensions = getimagesize($file['full_path']);
		if ($dimensions === FALSE || !in_array($dimensions['mime'], array('image/jpeg', 'image/png'), TRUE)) {
			if (!unlink($file['full_path'])) {
				log_message('error', 'Could not remove invalid promo image: ' . $file['full_path']);
			}
			$this->session->set_flashdata('promo_image_error', 'File yang diunggah bukan gambar JPG, JPEG, atau PNG yang valid.');
			redirect('admin#promos');
			return;
		}

		$new_image_path = 'promo-uploads/' . $file['file_name'];
		if (!$this->admin_model->set_promo_image((int) $promo_id, $new_image_path)) {
			if (!unlink($file['full_path'])) {
				log_message('error', 'Could not remove unlinked promo image: ' . $file['full_path']);
			}
			$this->session->set_flashdata('promo_image_error', 'Gambar berhasil diunggah tetapi tidak dapat disimpan pada promo.');
			redirect('admin#promos');
			return;
		}

		if (strpos((string) $promo->image_path, 'promo-uploads/') === 0) {
			$old_filename = substr($promo->image_path, strlen('promo-uploads/'));
			if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png)$/i', $old_filename)) {
				$old_path = $upload_directory . $old_filename;
				if (is_file($old_path) && !unlink($old_path)) {
					log_message('error', 'Could not remove replaced promo image: ' . $old_path);
				}
			}
		}

		$this->session->set_flashdata('promo_image_notice', 'Gambar promo berhasil diperbarui.');
		redirect('admin#promos');
	}

	public function promo_save($promo_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}

		$promo_id = $promo_id === NULL ? NULL : (int) $promo_id;
		$existing = $promo_id === NULL ? NULL : $this->admin_model->get_promo_by_id($promo_id);
		if ($promo_id !== NULL && !$existing) {
			show_404();
			return;
		}
		if ($promo_id === NULL && $this->admin_model->count_promos() >= 4) {
			$this->session->set_flashdata('promo_error', 'Katalog sudah mencapai batas 4 promo. Edit atau hapus promo yang ada untuk menggantinya.');
			redirect('admin#promos');
			return;
		}

		$code = strtoupper(trim((string) $this->input->post('code', TRUE)));
		$discipline = (string) $this->input->post('discipline', TRUE);
		$title = trim((string) $this->input->post('title', TRUE));
		$tagline = trim((string) $this->input->post('tagline', TRUE));
		$price_raw = trim((string) $this->input->post('price', TRUE));
		$price_unit = trim((string) $this->input->post('price_unit', TRUE));
		$session_count_raw = trim((string) $this->input->post('session_count', TRUE));
		$participants_min_raw = trim((string) $this->input->post('participants_min', TRUE));
		$participants_max_raw = trim((string) $this->input->post('participants_max', TRUE));
		$available_days = trim((string) $this->input->post('available_days', TRUE));
		$valid_from = trim((string) $this->input->post('valid_from', TRUE));
		$valid_until = trim((string) $this->input->post('valid_until', TRUE));
		$terms_note = trim((string) $this->input->post('terms_note', TRUE));
		$is_active = (string) $this->input->post('is_active', TRUE);
		$errors = array();

		if (!preg_match('/^[A-Z0-9][A-Z0-9-]{1,39}$/', $code)) {
			$errors[] = 'Kode promo wajib 2–40 karakter, hanya huruf, angka, dan tanda hubung.';
		}
		if (!in_array($discipline, array('pilates', 'yoga', 'beauty', 'general'), TRUE)) {
			$errors[] = 'Pilih disiplin promo yang valid.';
		}
		if ($title === '' || strlen($title) > 120) {
			$errors[] = 'Nama promo wajib diisi dan maksimal 120 karakter.';
		}
		if (strlen($tagline) > 160 || strlen($price_unit) < 1 || strlen($price_unit) > 40) {
			$errors[] = 'Tagline maksimal 160 karakter dan satuan harga wajib diisi (maksimal 40 karakter).';
		}
		if (!preg_match('/^\d{1,10}$/', $price_raw) || (int) $price_raw <= 0 || (float) $price_raw > 4294967295) {
			$errors[] = 'Harga promo harus berupa angka lebih besar dari nol.';
		}
		if (($session_count_raw !== '' && (!ctype_digit($session_count_raw) || (int) $session_count_raw < 1 || (int) $session_count_raw > 65535))
			|| ($participants_min_raw !== '' && (!ctype_digit($participants_min_raw) || (int) $participants_min_raw < 1 || (int) $participants_min_raw > 255))
			|| ($participants_max_raw !== '' && (!ctype_digit($participants_max_raw) || (int) $participants_max_raw < 1 || (int) $participants_max_raw > 255))) {
			$errors[] = 'Jumlah sesi dan peserta harus berupa bilangan bulat positif yang valid.';
		}
		if ($participants_min_raw !== '' && $participants_max_raw !== '' && (int) $participants_min_raw > (int) $participants_max_raw) {
			$errors[] = 'Maksimum peserta harus sama dengan atau lebih besar dari minimum peserta.';
		}
		if (strlen($available_days) > 60 || strlen($terms_note) > 255) {
			$errors[] = 'Hari tersedia maksimal 60 karakter dan syarat & catatan maksimal 255 karakter.';
		}
		if (($valid_from !== '' && !$this->valid_promo_date($valid_from)) || ($valid_until !== '' && !$this->valid_promo_date($valid_until))) {
			$errors[] = 'Tanggal promo harus menggunakan tanggal kalender yang valid.';
		}
		if ($valid_from !== '' && $valid_until !== '' && $valid_from > $valid_until) {
			$errors[] = 'Akhir promo tidak boleh lebih awal dari mulai promo.';
		}
		if (!in_array($is_active, array('0', '1'), TRUE)) {
			$errors[] = 'Pilih status promo yang valid.';
		}
		if ($existing && $existing->code === 'PILATES-1010' && $code !== $existing->code) {
			$errors[] = 'Kode PILATES-1010 dipakai alur booking khusus dan tidak dapat diubah.';
		}
		if ($code !== '' && $this->admin_model->promo_code_exists($code, $promo_id)) {
			$errors[] = 'Kode promo sudah digunakan. Gunakan kode yang berbeda.';
		}

		if ($errors) {
			$this->session->set_flashdata('promo_error', implode(' ', $errors));
			redirect('admin#promos');
			return;
		}

		$image_error = NULL;
		$new_image_path = $this->upload_promo_image($image_error);
		if ($new_image_path === FALSE) {
			$this->session->set_flashdata('promo_error', $image_error);
			redirect('admin#promos');
			return;
		}

		$data = array(
			'code' => $code,
			'discipline' => $discipline,
			'title' => $title,
			'tagline' => $tagline !== '' ? $tagline : NULL,
			'price' => (int) $price_raw,
			'price_unit' => $price_unit,
			'session_count' => $session_count_raw !== '' ? (int) $session_count_raw : NULL,
			'participants_min' => $participants_min_raw !== '' ? (int) $participants_min_raw : NULL,
			'participants_max' => $participants_max_raw !== '' ? (int) $participants_max_raw : NULL,
			'available_days' => $available_days !== '' ? $available_days : NULL,
			'valid_from' => $valid_from !== '' ? $valid_from : NULL,
			'valid_until' => $valid_until !== '' ? $valid_until : NULL,
			'terms_note' => $terms_note !== '' ? $terms_note : NULL,
			'image_path' => $new_image_path !== NULL
				? $new_image_path
				: ($existing ? $existing->image_path : NULL),
			'is_active' => (int) $is_active
		);

		if (!$this->admin_model->save_promo($data, $promo_id)) {
			if ($new_image_path !== NULL) {
				$this->remove_uploaded_promo_image($new_image_path);
			}
			log_message('error', 'Could not save promo record: ' . ($promo_id === NULL ? 'new promo' : $promo_id));
			$this->session->set_flashdata('promo_error', 'Perubahan promo tidak dapat disimpan. Coba kembali.');
			redirect('admin#promos');
			return;
		}

		if ($new_image_path !== NULL && $existing && $existing->image_path !== $new_image_path) {
			$this->remove_uploaded_promo_image($existing->image_path);
		}
		$this->session->set_flashdata('promo_notice', $promo_id === NULL ? 'Promo berhasil ditambahkan ke katalog.' : 'Perubahan promo berhasil disimpan.');
		redirect('admin#promos');
	}

	public function promo_delete($promo_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}

		$promo = $this->admin_model->get_promo_by_id((int) $promo_id);
		if (!$promo) {
			show_404();
			return;
		}
		if (!$this->admin_model->delete_promo((int) $promo_id)) {
			log_message('error', 'Could not delete promo record: ' . (int) $promo_id);
			$this->session->set_flashdata('promo_error', 'Promo tidak dapat dihapus. Coba kembali.');
			redirect('admin#promos');
			return;
		}
		$this->remove_uploaded_promo_image($promo->image_path);
		$this->session->set_flashdata('promo_notice', 'Promo berhasil dihapus. Slot katalog kini dapat digunakan untuk promo pengganti.');
		redirect('admin#promos');
	}

	public function catalog_save($type = NULL, $item_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST' || !in_array($type, array('pilates', 'yoga', 'beauty'), TRUE)) {
			show_404();
			return;
		}
		$item_id = $item_id === NULL ? NULL : (int) $item_id;
		$items = $this->admin_model->get_catalog_items($type);
		$existing = NULL;
		if ($item_id !== NULL) {
			foreach ($items as $item) {
				if ((int) $item->id === $item_id) {
					$existing = $item;
					break;
				}
			}
			if (!$existing) {
				show_404();
				return;
			}
		}

		$name = trim((string) $this->input->post('name', TRUE));
		$price_raw = trim((string) $this->input->post('price', TRUE));
		$active = (string) $this->input->post('is_active', TRUE);
		$sort_order = (string) $this->input->post('sort_order', TRUE);
		$data = array();
		if ($name === '' || strlen($name) > ($type === 'beauty' ? 100 : 60)) {
			$this->session->set_flashdata('catalog_error', 'Nama layanan wajib diisi dan tidak boleh melebihi batas karakter.');
			redirect('admin#services');
			return;
		}
		if (!preg_match('/^\d{1,10}$/', $price_raw) || (float) $price_raw > 4294967295 || !in_array($active, array('0', '1'), TRUE)
			|| !ctype_digit($sort_order) || (int) $sort_order > 255) {
			$this->session->set_flashdata('catalog_error', 'Harga, urutan, atau status layanan tidak valid.');
			redirect('admin#services');
			return;
		}
		$data['name'] = $name;
		$data['price'] = (int) $price_raw;
		$data['sort_order'] = (int) $sort_order;
		$data['is_active'] = (int) $active;

		if ($type === 'pilates') {
			$kind = (string) $this->input->post('kind', TRUE);
			$class_type = (string) $this->input->post('class_type', TRUE);
			$session_count = (string) $this->input->post('session_count', TRUE);
			$bonus_sessions = (string) $this->input->post('bonus_sessions', TRUE);
			$participants = (string) $this->input->post('participants', TRUE);
			$validity_days = (string) $this->input->post('validity_days', TRUE);
			$validity_label = trim((string) $this->input->post('validity_label', TRUE));
			$notes = trim((string) $this->input->post('notes', TRUE));
			if (!in_array($kind, array('session', 'membership'), TRUE) || !in_array($class_type, array('group', 'private', 'duo', 'triple'), TRUE)
				|| !$this->valid_unsigned_int($session_count, 1, 65535) || !$this->valid_unsigned_int($bonus_sessions, 0, 65535)
				|| !$this->valid_unsigned_int($participants, 1, 255)
				|| ($validity_days !== '' && !$this->valid_unsigned_int($validity_days, 1, 65535))
				|| strlen($validity_label) > 40 || strlen($notes) > 255) {
				$this->session->set_flashdata('catalog_error', 'Detail paket Pilates tidak valid.');
				redirect('admin#services');
				return;
			}
			$data += array(
				'kind' => $kind,
				'class_type' => $class_type,
				'session_count' => (int) $session_count,
				'bonus_sessions' => (int) $bonus_sessions,
				'participants' => (int) $participants,
				'validity_days' => $validity_days !== '' ? (int) $validity_days : NULL,
				'validity_label' => $validity_label !== '' ? $validity_label : NULL,
				'no_reschedule' => (int) $this->input->post('no_reschedule', TRUE) === 1 ? 1 : 0,
				'notes' => $notes !== '' ? $notes : NULL
			);
		} elseif ($type === 'yoga') {
			$session_count = (string) $this->input->post('session_count', TRUE);
			$bonus_sessions = (string) $this->input->post('bonus_sessions', TRUE);
			$validity_days = (string) $this->input->post('validity_days', TRUE);
			$validity_label = trim((string) $this->input->post('validity_label', TRUE));
			$notes = trim((string) $this->input->post('notes', TRUE));
			if (!$this->valid_unsigned_int($session_count, 1, 65535) || !$this->valid_unsigned_int($bonus_sessions, 0, 65535)
				|| ($validity_days !== '' && !$this->valid_unsigned_int($validity_days, 1, 65535))
				|| $validity_label === '' || strlen($validity_label) > 40 || strlen($notes) > 255) {
				$this->session->set_flashdata('catalog_error', 'Detail paket Yoga tidak valid.');
				redirect('admin#services');
				return;
			}
			$data += array(
				'session_count' => (int) $session_count,
				'bonus_sessions' => (int) $bonus_sessions,
				'validity_days' => $validity_days !== '' ? (int) $validity_days : NULL,
				'validity_label' => $validity_label,
				'notes' => $notes !== '' ? $notes : NULL
			);
		} else {
			$category_id = (int) $this->input->post('category_id', TRUE);
			$description = trim((string) $this->input->post('description', TRUE));
			$price_type = (string) $this->input->post('price_type', TRUE);
			$price_max_raw = trim((string) $this->input->post('price_max', TRUE));
			$percent_raw = trim((string) $this->input->post('percent_value', TRUE));
			$is_featured = (string) $this->input->post('is_featured', TRUE);
			$notes = trim((string) $this->input->post('notes', TRUE));
			$categories = $this->admin_model->get_beauty_categories();
			$category_ids = array();
			foreach ($categories as $category) {
				$category_ids[] = (int) $category->id;
			}
			if (!in_array($category_id, $category_ids, TRUE) || strlen($description) > 255 || strlen($notes) > 255
				|| !in_array($price_type, array('fixed', 'range', 'percent', 'free'), TRUE)
				|| !in_array($is_featured, array('0', '1'), TRUE)
				|| ($price_type === 'range' && (!preg_match('/^\d+$/', $price_max_raw) || (int) $price_max_raw < (int) $price_raw))
				|| ($price_type === 'percent' && !$this->valid_unsigned_int($percent_raw, 1, 100))) {
				$this->session->set_flashdata('catalog_error', 'Detail layanan Beauty tidak valid.');
				redirect('admin#services');
				return;
			}
			$data += array(
				'category_id' => $category_id,
				'description' => $description !== '' ? $description : NULL,
				'price_type' => $price_type,
				'price_max' => $price_type === 'range' ? (int) $price_max_raw : NULL,
				'percent_value' => $price_type === 'percent' ? (int) $percent_raw : NULL,
				'notes' => $notes !== '' ? $notes : NULL,
				'is_featured' => (int) $is_featured
			);
		}

		if (!$this->admin_model->save_catalog_item($type, $data, $item_id)) {
			log_message('error', 'Could not save public catalog item: ' . $type . '/' . (int) $item_id);
			$this->session->set_flashdata('catalog_error', 'Layanan tidak dapat disimpan. Periksa kembali datanya.');
		} else {
			$this->session->set_flashdata('catalog_notice', 'Layanan berhasil disimpan dan diperbarui di website.');
		}
		redirect('admin#services');
	}

	public function catalog_delete($type = NULL, $item_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST' || !in_array($type, array('pilates', 'yoga', 'beauty'), TRUE)) {
			show_404();
			return;
		}
		if (!$this->admin_model->delete_catalog_item($type, (int) $item_id)) {
			log_message('error', 'Could not remove public catalog item: ' . $type . '/' . (int) $item_id);
			$this->session->set_flashdata('catalog_error', 'Layanan tidak dapat dihapus.');
		} else {
			$this->session->set_flashdata('catalog_notice', 'Layanan dihapus dari website. Data yang sudah dipakai booking tetap aman.');
		}
		redirect('admin#services');
	}

	public function review_save($review_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		$review_id = $review_id === NULL ? NULL : (int) $review_id;
		$name = trim((string) $this->input->post('name', TRUE));
		$review_text = trim((string) $this->input->post('review_text', TRUE));
		$rating = (string) $this->input->post('rating', TRUE);
		$source = trim((string) $this->input->post('source', TRUE));
		$sort_order = (string) $this->input->post('sort_order', TRUE);
		$active = (string) $this->input->post('is_active', TRUE);
		if ($name === '' || strlen($name) > 100 || $review_text === '' || strlen($review_text) > 1000
			|| !$this->valid_unsigned_int($rating, 1, 5) || $source === '' || strlen($source) > 40
			|| !$this->valid_unsigned_int($sort_order, 0, 65535) || !in_array($active, array('0', '1'), TRUE)) {
			$this->session->set_flashdata('review_error', 'Nama, isi review, rating, sumber, urutan, atau status review tidak valid.');
		} elseif ($this->admin_model->save_review(array(
			'name' => $name,
			'review_text' => $review_text,
			'rating' => (int) $rating,
			'source' => $source,
			'sort_order' => (int) $sort_order,
			'is_active' => (int) $active
		), $review_id)) {
			$this->session->set_flashdata('review_notice', 'Review berhasil disimpan dan diperbarui di website.');
		} else {
			log_message('error', 'Could not save studio review: ' . (int) $review_id);
			$this->session->set_flashdata('review_error', 'Review tidak dapat disimpan.');
		}
		redirect('admin#reviews');
	}

	public function review_delete($review_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		if (!$this->admin_model->delete_review((int) $review_id)) {
			$this->session->set_flashdata('review_error', 'Review tidak dapat dihapus.');
		} else {
			$this->session->set_flashdata('review_notice', 'Review berhasil dihapus dari website.');
		}
		redirect('admin#reviews');
	}

	public function gallery_save($gallery_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		$gallery_id = $gallery_id === NULL ? NULL : (int) $gallery_id;
		$existing = $gallery_id === NULL ? NULL : $this->admin_model->get_gallery_item($gallery_id);
		if ($gallery_id !== NULL && !$existing) {
			show_404();
			return;
		}
		if ($gallery_id === NULL && $this->admin_model->count_gallery() >= 4) {
			$this->session->set_flashdata('gallery_error', 'Galeri sudah mencapai batas 4 foto. Edit atau hapus foto yang ada untuk menggantinya.');
			redirect('admin#gallery');
			return;
		}
		$alt_text = trim((string) $this->input->post('alt_text', TRUE));
		$sort_order = (string) $this->input->post('sort_order', TRUE);
		$active = (string) $this->input->post('is_active', TRUE);
		if ($alt_text === '' || strlen($alt_text) > 160 || !$this->valid_unsigned_int($sort_order, 0, 65535)
			|| !in_array($active, array('0', '1'), TRUE) || (!$existing && empty($_FILES['gallery_image']['name']))) {
			$this->session->set_flashdata('gallery_error', 'Teks alternatif, urutan, status wajib valid; gambar wajib untuk item baru.');
			redirect('admin#gallery');
			return;
		}
		$image_error = NULL;
		$new_image_path = $this->upload_content_image('gallery_image', 'gallery-uploads', $image_error);
		if ($new_image_path === FALSE) {
			$this->session->set_flashdata('gallery_error', $image_error);
			redirect('admin#gallery');
			return;
		}
		$data = array(
			'image_path' => $new_image_path !== NULL ? $new_image_path : $existing->image_path,
			'alt_text' => $alt_text,
			'sort_order' => (int) $sort_order,
			'is_active' => (int) $active
		);
		if (!$this->admin_model->save_gallery_item($data, $gallery_id)) {
			if ($new_image_path !== NULL) {
				$this->remove_content_image($new_image_path, 'gallery-uploads');
			}
			log_message('error', 'Could not save studio gallery item: ' . (int) $gallery_id);
			$this->session->set_flashdata('gallery_error', 'Foto galeri tidak dapat disimpan.');
		} else {
			if ($new_image_path !== NULL && $existing) {
				$this->remove_content_image($existing->image_path, 'gallery-uploads');
			}
			$this->session->set_flashdata('gallery_notice', 'Galeri berhasil diperbarui di website.');
		}
		redirect('admin#gallery');
	}

	public function gallery_delete($gallery_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		$image = $this->admin_model->get_gallery_item((int) $gallery_id);
		if (!$image || !$this->admin_model->delete_gallery_item((int) $gallery_id)) {
			$this->session->set_flashdata('gallery_error', 'Foto galeri tidak dapat dihapus.');
		} else {
			$this->remove_content_image($image->image_path, 'gallery-uploads');
			$this->session->set_flashdata('gallery_notice', 'Foto galeri berhasil dihapus dari website.');
		}
		redirect('admin#gallery');
	}

	public function login()
	{
		if ($this->session->userdata('admin_user_id')) {
			redirect('admin');
		}

		$error = '';
		if ($this->input->method(TRUE) === 'POST') {
			$username = trim((string) $this->input->post('username', TRUE));
			$password = (string) $this->input->post('password');
			$user = $this->admin_model->authenticate($username, $password);

			if ($user) {
				if ($user->role_name !== 'admin') {
					$error = 'Akun ini tidak memiliki akses ke panel administrasi.';
				} else {
					$this->session->sess_regenerate(TRUE);
					$this->session->set_userdata(array(
						'admin_user_id' => (int) $user->id,
						'admin_username' => $user->username,
						'admin_full_name' => $user->full_name,
						'admin_role_name' => $user->role_name,
						'admin_must_change_password' => (bool) $user->must_change_password
					));
					redirect('admin');
				}
			} else {
				$error = 'Username atau password tidak sesuai.';
			}
		}

		$this->load->view('admin/login', array('error' => $error));
	}

	public function change_password()
	{
		if (!$this->session->userdata('admin_user_id')) {
			redirect('admin/login');
		}

		$error = '';

		if ($this->input->method(TRUE) === 'POST') {
			$current_password = (string) $this->input->post('current_password');
			$new_password = (string) $this->input->post('new_password');
			$confirm_password = (string) $this->input->post('confirm_password');

			if (strlen($new_password) < 12) {
				$error = 'Password baru harus terdiri dari minimal 12 karakter.';
			} elseif ($new_password !== $confirm_password) {
				$error = 'Konfirmasi password baru tidak sama.';
			} elseif (!$this->admin_model->change_password((int) $this->session->userdata('admin_user_id'), $current_password, $new_password)) {
				$error = 'Password saat ini tidak sesuai.';
			} else {
				$this->session->set_userdata('admin_must_change_password', FALSE);
				$this->session->sess_regenerate(TRUE);
				redirect('admin');
			}
		}

		$this->load->view('admin/change_password', array(
			'error' => $error,
			'is_required' => (bool) $this->session->userdata('admin_must_change_password')
		));
	}

	public function account()
	{
		if (!$this->session->userdata('admin_user_id')) {
			redirect('admin/login');
		}
		if ($this->session->userdata('admin_must_change_password')) {
			redirect('admin/change-password');
		}

		$admin_username = $this->session->userdata('admin_username');
		if (!is_string($admin_username) || $admin_username === '') {
			$admin_username = $this->admin_model->get_username((int) $this->session->userdata('admin_user_id'));
			$this->session->set_userdata('admin_username', $admin_username);
		}

		$error = '';
		if ($this->input->method(TRUE) === 'POST') {
			$username = trim((string) $this->input->post('username', TRUE));
			$current_password = (string) $this->input->post('current_password');
			$new_password = (string) $this->input->post('new_password');
			$confirm_password = (string) $this->input->post('confirm_password');

			if (!preg_match('/^[A-Za-z0-9._@-]{3,50}$/', $username)) {
				$error = 'Username wajib 3–50 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, @, atau tanda hubung.';
			} elseif ($current_password === '') {
				$error = 'Masukkan password saat ini untuk mengonfirmasi perubahan akun.';
			} elseif (($new_password !== '' || $confirm_password !== '') && strlen($new_password) < 12) {
				$error = 'Password baru harus terdiri dari minimal 12 karakter.';
			} elseif ($new_password !== $confirm_password) {
				$error = 'Konfirmasi password baru tidak sama.';
			} else {
				$updated = $this->admin_model->update_account_credentials(
					(int) $this->session->userdata('admin_user_id'),
					$current_password,
					$username,
					$new_password === '' ? NULL : $new_password
				);
				if ($updated) {
					$this->session->set_userdata(array(
						'admin_username' => $username,
						'admin_must_change_password' => FALSE
					));
					$this->session->sess_regenerate(TRUE);
					$this->session->set_flashdata('account_notice', 'Username dan pengaturan password berhasil diperbarui.');
					redirect('admin/account');
					return;
				}
				$error = 'Perubahan gagal. Pastikan password saat ini benar dan username belum digunakan.';
			}
		}

		$this->load->view('admin/account', array(
			'error' => $error,
			'admin_username' => $admin_username
		));
	}

	public function logout()
	{
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
		}

		$this->session->sess_destroy();
		redirect('admin/login');
	}

	public function bookings()
	{
		if (!$this->require_booking_admin()) {
			return;
		}

		$this->load->model('Booking_model', 'booking_model');
		$this->booking_model->expire_pending_bookings();
		$bookings = $this->booking_model->get_all_admin_bookings();
		$sessions = array();
		foreach ($bookings as $booking) {
			$sessions[$booking->id] = $this->booking_model->get_booking_sessions($booking->id);
		}

		$this->load->view('admin/booking_manager', array(
			'admin_name' => $this->session->userdata('admin_full_name'),
			'bookings' => $bookings,
			'sessions' => $sessions,
			'packages' => $this->booking_model->get_pilates_packages(),
			'schedules' => $this->booking_model->get_active_pilates_schedules(),
			'notice' => $this->session->flashdata('booking_notice'),
			'error' => $this->session->flashdata('booking_error')
		));
	}

	public function manual_booking()
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		$this->load->model('Booking_model', 'booking_model');
		$package = $this->booking_model->get_package((int) $this->input->post('package_id', TRUE));
		$name = trim((string) $this->input->post('full_name', TRUE));
		$phone = preg_replace('/[^0-9+]/', '', (string) $this->input->post('phone', TRUE));
		$payment_method = (string) $this->input->post('payment_method', TRUE);
		$payment_state = (string) $this->input->post('payment_state', TRUE);
		$schedule_ids = $this->input->post('schedule_id');
		$session_dates = $this->input->post('session_date');
		$expected_sessions = $package ? (int) $package->session_count + (int) $package->bonus_sessions : 0;
		$sessions = array();
		if (is_array($schedule_ids) && is_array($session_dates) && count($schedule_ids) === count($session_dates)) {
			foreach ($schedule_ids as $index => $schedule_id) {
				$date = isset($session_dates[$index]) ? $session_dates[$index] : NULL;
				if (!is_string($schedule_id) || !ctype_digit($schedule_id) || !$this->valid_promo_date($date)) {
					$sessions = array();
					break;
				}
				$sessions[] = array('schedule_id' => (int) $schedule_id, 'session_date' => $date);
			}
		}
		if (!$package || $name === '' || strlen($name) > 100 || strlen($phone) < 9 || strlen($phone) > 18
			|| !in_array($payment_method, array('qris', 'transfer'), TRUE)
			|| !in_array($payment_state, array('pending', 'paid'), TRUE) || count($sessions) !== $expected_sessions) {
			$this->session->set_flashdata('booking_error', 'Data booking manual tidak lengkap atau tidak valid.');
			redirect('admin/bookings');
			return;
		}
		$today = new DateTime('today', new DateTimeZone('Asia/Jakarta'));
		$latest_start = clone $today;
		$latest_start->modify('+2 years');
		$first_session = NULL;
		foreach ($sessions as $session) {
			$session_date = new DateTime($session['session_date'], new DateTimeZone('Asia/Jakarta'));
			if ($first_session === NULL || $session_date < $first_session) {
				$first_session = $session_date;
			}
		}
		if ($first_session < $today || $first_session > $latest_start) {
			$this->session->set_flashdata('booking_error', 'Tanggal sesi pertama booking manual harus berada dari hari ini hingga dua tahun ke depan.');
			redirect('admin/bookings');
			return;
		}
		$latest_session = clone $first_session;
		$latest_session->modify('+' . ((int) $package->validity_days > 0 ? (int) $package->validity_days : 365) . ' days');
		foreach ($sessions as $session) {
			$session_date = new DateTime($session['session_date'], new DateTimeZone('Asia/Jakarta'));
			if ($session_date < $first_session || $session_date > $latest_session) {
				$this->session->set_flashdata('booking_error', 'Jadwal sesi booking manual harus berada dalam masa berlaku paket.');
				redirect('admin/bookings');
				return;
			}
		}
		if (!function_exists('openssl_random_pseudo_bytes')) {
			show_error('Sistem tidak dapat membuat referensi booking yang aman.', 500);
			return;
		}
		$token = openssl_random_pseudo_bytes(20, $strong);
		if ($token === FALSE || !$strong) {
			show_error('Sistem tidak dapat membuat referensi booking yang aman.', 500);
			return;
		}
		$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
		$expires = clone $now;
		$expires->modify('+24 hours');
		$booking = array(
			'public_token' => bin2hex($token),
			'service' => 'pilates',
			'package_id' => (int) $package->id,
			'promo_code' => NULL,
			'package_name' => $package->name,
			'package_kind' => $package->kind . ' · ' . $package->class_type,
			'session_count' => (int) $package->session_count,
			'bonus_sessions' => (int) $package->bonus_sessions,
			'participants' => (int) $package->participants,
			'validity_days' => $package->validity_days ? (int) $package->validity_days : NULL,
			'price' => (int) $package->price,
			'full_name' => $name,
			'phone' => $phone,
			'health_notes' => NULL,
			'source' => 'Admin',
			'source_other' => NULL,
			'statement_file' => NULL,
			'payment_method' => $payment_method,
			'payment_status' => $payment_state,
			'booking_status' => $payment_state === 'paid' ? 'confirmed' : 'pending_payment',
			'expires_at' => $expires->format('Y-m-d H:i:s'),
			'created_at' => $now->format('Y-m-d H:i:s'),
			'paid_at' => $payment_state === 'paid' ? $now->format('Y-m-d H:i:s') : NULL,
			'verified_by' => $payment_state === 'paid' ? (int) $this->session->userdata('admin_user_id') : NULL,
			'verified_at' => $payment_state === 'paid' ? $now->format('Y-m-d H:i:s') : NULL,
			'is_exclusive' => $package->class_type === 'group' ? 0 : 1
		);
		if (!$this->booking_model->create_booking($booking, $sessions)) {
			$this->session->set_flashdata('booking_error', 'Jadwal tidak valid atau kapasitas kelas tidak mencukupi. Booking belum dibuat.');
		} else {
			$this->session->set_flashdata('booking_notice', $payment_state === 'paid'
				? 'Booking manual berhasil dibuat dengan status lunas.'
				: 'Booking manual berhasil dibuat dengan status menunggu pembayaran.');
		}
		redirect('admin/bookings');
	}

	public function booking_edit($booking_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		$name = trim((string) $this->input->post('full_name', TRUE));
		$phone = preg_replace('/[^0-9+]/', '', (string) $this->input->post('phone', TRUE));
		$this->load->model('Booking_model', 'booking_model');
		if ($name === '' || strlen($name) > 100 || strlen($phone) < 9 || strlen($phone) > 18) {
			$this->session->set_flashdata('booking_error', 'Nama atau nomor WhatsApp tidak valid.');
		} elseif (!$this->booking_model->get_admin_booking((int) $booking_id)) {
			show_404();
			return;
		} elseif ($this->booking_model->update_admin_booking((int) $booking_id, array('full_name' => $name, 'phone' => $phone))) {
			$this->session->set_flashdata('booking_notice', 'Data pelanggan berhasil diperbarui.');
		} else {
			$this->session->set_flashdata('booking_error', 'Data booking tidak dapat diperbarui.');
		}
		redirect('admin/bookings');
	}

	public function booking_delete($booking_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		$this->load->model('Booking_model', 'booking_model');
		if (!$this->booking_model->cancel_admin_booking((int) $booking_id)) {
			$this->session->set_flashdata('booking_error', 'Booking tidak dapat dibatalkan atau sudah tidak aktif.');
		} else {
			$this->session->set_flashdata('booking_notice', 'Booking dibatalkan dan tidak lagi menahan kapasitas jadwal.');
		}
		redirect('admin/bookings');
	}

	public function booking_reschedule($booking_id = NULL)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
			return;
		}
		$this->load->model('Booking_model', 'booking_model');
		$dates = $this->input->post('session_date');
		$schedule_ids = $this->input->post('schedule_id');
		$sessions = array();
		if (is_array($dates) && is_array($schedule_ids) && count($dates) === count($schedule_ids)) {
			foreach ($dates as $index => $date) {
				$schedule_id = $schedule_ids[$index];
				if (!is_string($date) || !$this->valid_promo_date($date) || !is_string($schedule_id) || !ctype_digit($schedule_id)) {
					$sessions = array();
					break;
				}
				$sessions[] = array('session_date' => $date, 'schedule_id' => (int) $schedule_id);
			}
		}
		if (!$sessions || !$this->booking_model->reschedule_admin_booking((int) $booking_id, $sessions)) {
			$this->session->set_flashdata('booking_error', 'Jadwal tidak dapat dipindahkan. Periksa jumlah sesi, hari kelas, dan kapasitas slot.');
		} else {
			$this->session->set_flashdata('booking_notice', 'Jadwal booking berhasil diperbarui.');
		}
		redirect('admin/bookings#reschedule');
	}

	public function booking_payment_status($booking_id, $status)
	{
		if (!$this->require_booking_admin()) {
			return;
		}
		if ($this->input->method(TRUE) !== 'POST' || !in_array($status, array('paid', 'rejected'), TRUE)) {
			show_404();
		}

		$this->load->model('Booking_model', 'booking_model');
		$this->booking_model->expire_pending_bookings();
		$booking = $this->booking_model->get_admin_booking($booking_id);
		if (!$booking) {
			show_404();
		}
		if ($status === 'paid' && !$booking->receipt_file) {
			$this->session->set_flashdata('booking_error', 'Unggah bukti pembayaran terlebih dahulu sebelum mengonfirmasi pembayaran.');
			redirect('admin/bookings');
			return;
		}

		if ($this->booking_model->set_payment_status($booking_id, $status, (int) $this->session->userdata('admin_user_id'))) {
			$this->session->set_flashdata('booking_notice', $status === 'paid'
				? 'Pembayaran sudah dikonfirmasi. Halaman pelanggan akan diperbarui otomatis.'
				: 'Booking ditolak dan slot jadwal sudah dilepas.');
		} else {
			$this->session->set_flashdata('booking_error', 'Status booking sudah berubah atau waktu penahanan booking sudah habis.');
		}
		redirect('admin/bookings');
	}

	public function booking_payment_receipt($booking_id)
	{
		$this->stream_booking_file($booking_id, 'receipt');
	}

	public function booking_statement_file($booking_id)
	{
		$this->stream_booking_file($booking_id, 'statement');
	}

	private function stream_booking_file($booking_id, $kind)
	{
		if (!$this->require_booking_admin()) {
			return;
		}

		$this->load->model('Booking_model', 'booking_model');
		$path = $kind === 'statement'
			? $this->booking_model->get_statement_path($booking_id)
			: $this->booking_model->get_receipt_path($booking_id);
		if (!$path || basename($path) !== $path) {
			show_404();
		}

		$directory = APPPATH . 'storage' . DIRECTORY_SEPARATOR . 'booking_uploads' . DIRECTORY_SEPARATOR;
		$resolved = realpath($directory . $path);
		$root = realpath($directory);
		if (!$resolved || !$root || strpos($resolved, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($resolved)) {
			show_404();
		}

		$extensions = array('pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png');
		$extension = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
		if (!isset($extensions[$extension])) {
			show_404();
		}
		$this->output
			->set_content_type($extensions[$extension])
			->set_header('Content-Disposition: inline; filename="' . $kind . '-booking-' . (int) $booking_id . '.' . $extension . '"')
			->set_output(file_get_contents($resolved));
	}

	private function require_booking_admin()
	{
		if (!$this->session->userdata('admin_user_id')) {
			redirect('admin/login');
			return FALSE;
		}
		if ($this->session->userdata('admin_must_change_password')) {
			redirect('admin/change-password');
			return FALSE;
		}
		if ($this->session->userdata('admin_role_name') !== 'admin') {
			show_error('Anda tidak memiliki izin untuk memverifikasi booking.', 403);
			return FALSE;
		}
		return TRUE;
	}

	private function summarize_report_rows($rows)
	{
		$revenue = 0;
		$sessions = 0;
		foreach ($rows as $row) {
			$revenue += (float) $row->amount;
			$sessions += (int) $row->sessions;
		}
		$count = count($rows);

		return array(
			'revenue' => $revenue,
			'transactions' => $count,
			'sessions' => $sessions,
			'average' => $count > 0 ? $revenue / $count : 0
		);
	}

	private function build_revenue_chart($rows, $start_date, $end_date, $unit)
	{
		$amounts = array();
		foreach ($rows as $row) {
			$day = date('Y-m-d', strtotime($row->paid_at));
			if (!isset($amounts[$day])) {
				$amounts[$day] = 0;
			}
			$amounts[$day] += (float) $row->amount;
		}

		$chart = array();
		$cursor = strtotime($start_date . ' 00:00:00');
		$last_day = strtotime($end_date . ' 00:00:00');
		while ($cursor <= $last_day) {
			$date = date('Y-m-d', $cursor);
			$chart[] = array(
				'date' => $date,
				'label' => $unit === 'month' ? date('j', $cursor) : date('d M', $cursor),
				'amount' => isset($amounts[$date]) ? $amounts[$date] : 0
			);
			$cursor = strtotime('+1 day', $cursor);
		}

		return $chart;
	}

	private function valid_promo_date($date)
	{
		if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return FALSE;
		}
		$parsed = DateTime::createFromFormat('Y-m-d', $date);
		return $parsed && $parsed->format('Y-m-d') === $date;
	}

	private function valid_unsigned_int($value, $minimum, $maximum)
	{
		return is_string($value) && ctype_digit($value) && (int) $value >= $minimum && (int) $value <= $maximum;
	}

	private function upload_content_image($field, $directory_name, &$error)
	{
		$error = NULL;
		if (empty($_FILES[$field]['name'])) {
			return NULL;
		}
		if (!in_array($directory_name, array('gallery-uploads'), TRUE)) {
			$error = 'Folder penyimpanan gambar tidak valid.';
			return FALSE;
		}
		$upload_directory = FCPATH . 'img' . DIRECTORY_SEPARATOR . $directory_name . DIRECTORY_SEPARATOR;
		if (!is_dir($upload_directory) && !mkdir($upload_directory, 0755, TRUE) && !is_dir($upload_directory)) {
			log_message('error', 'Could not create public image upload directory: ' . $upload_directory);
			$error = 'Folder penyimpanan gambar tidak dapat dibuat.';
			return FALSE;
		}
		$this->load->library('upload');
		$this->upload->initialize(array(
			'upload_path' => $upload_directory,
			'allowed_types' => 'jpg|jpeg|png',
			'max_size' => 5120,
			'max_width' => 6000,
			'max_height' => 6000,
			'encrypt_name' => TRUE,
			'file_ext_tolower' => TRUE,
			'remove_spaces' => TRUE,
			'detect_mime' => TRUE,
			'mod_mime_fix' => TRUE,
			'overwrite' => FALSE
		));
		if (!$this->upload->do_upload($field)) {
			$error = strip_tags($this->upload->display_errors('', ''));
			return FALSE;
		}
		$file = $this->upload->data();
		$dimensions = getimagesize($file['full_path']);
		if ($dimensions === FALSE || !in_array($dimensions['mime'], array('image/jpeg', 'image/png'), TRUE)) {
			if (!unlink($file['full_path'])) {
				log_message('error', 'Could not remove invalid public image: ' . $file['full_path']);
			}
			$error = 'File yang diunggah bukan gambar JPG, JPEG, atau PNG yang valid.';
			return FALSE;
		}
		return $directory_name . '/' . $file['file_name'];
	}

	private function remove_content_image($image_path, $directory_name)
	{
		if (!is_string($image_path) || strpos($image_path, $directory_name . '/') !== 0) {
			return;
		}
		$filename = substr($image_path, strlen($directory_name) + 1);
		if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png)$/i', $filename)) {
			return;
		}
		$path = FCPATH . 'img' . DIRECTORY_SEPARATOR . $directory_name . DIRECTORY_SEPARATOR . $filename;
		if (is_file($path) && !unlink($path)) {
			log_message('error', 'Could not remove public image: ' . $path);
		}
	}

	private function upload_promo_image(&$error)
	{
		$error = NULL;
		if (empty($_FILES['promo_image']['name'])) {
			return NULL;
		}

		$upload_directory = FCPATH . 'img' . DIRECTORY_SEPARATOR . 'promo-uploads' . DIRECTORY_SEPARATOR;
		if (!is_dir($upload_directory) && !mkdir($upload_directory, 0755, TRUE) && !is_dir($upload_directory)) {
			log_message('error', 'Could not create promo image upload directory: ' . $upload_directory);
			$error = 'Folder penyimpanan gambar promo tidak dapat dibuat.';
			return FALSE;
		}

		$this->load->library('upload');
		$this->upload->initialize(array(
			'upload_path' => $upload_directory,
			'allowed_types' => 'jpg|jpeg|png',
			'max_size' => 5120,
			'max_width' => 6000,
			'max_height' => 6000,
			'encrypt_name' => TRUE,
			'file_ext_tolower' => TRUE,
			'remove_spaces' => TRUE,
			'detect_mime' => TRUE,
			'mod_mime_fix' => TRUE,
			'overwrite' => FALSE
		));
		if (!$this->upload->do_upload('promo_image')) {
			$error = strip_tags($this->upload->display_errors('', ''));
			return FALSE;
		}

		$file = $this->upload->data();
		$dimensions = getimagesize($file['full_path']);
		if ($dimensions === FALSE || !in_array($dimensions['mime'], array('image/jpeg', 'image/png'), TRUE)) {
			if (!unlink($file['full_path'])) {
				log_message('error', 'Could not remove invalid promo image: ' . $file['full_path']);
			}
			$error = 'File yang diunggah bukan gambar JPG, JPEG, atau PNG yang valid.';
			return FALSE;
		}
		return 'promo-uploads/' . $file['file_name'];
	}

	private function remove_uploaded_promo_image($image_path)
	{
		if (!is_string($image_path) || strpos($image_path, 'promo-uploads/') !== 0) {
			return;
		}
		$filename = substr($image_path, strlen('promo-uploads/'));
		if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png)$/i', $filename)) {
			return;
		}
		$path = FCPATH . 'img' . DIRECTORY_SEPARATOR . 'promo-uploads' . DIRECTORY_SEPARATOR . $filename;
		if (is_file($path) && !unlink($path)) {
			log_message('error', 'Could not remove promo image: ' . $path);
		}
	}
}
