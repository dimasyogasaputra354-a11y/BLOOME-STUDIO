<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking extends CI_Controller
{
	const PILATES_DEAL_CODE = 'PILATES-1010';
	const PILATES_DEAL_START = '2026-10-10 00:00:00';
	const PILATES_DEAL_END = '2026-10-31 23:59:59';
	const PILATES_DEAL_LAST_SESSION = '2026-10-30';

	private $upload_directory;

	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Asia/Jakarta');
		$this->load->model('Booking_model', 'booking_model');
		$this->upload_directory = APPPATH . 'storage' . DIRECTORY_SEPARATOR . 'booking_uploads' . DIRECTORY_SEPARATOR;
	}

	public function index()
	{
		$service = $this->input->get('service', TRUE);
		if (!is_string($service) || !in_array($service, array('pilates', 'yoga', 'beauty'), TRUE)) {
			redirect(site_url() . '#services');
			return;
		}

		if ($service !== 'pilates') {
			$this->load->view('booking_contact', array('service' => ucfirst($service)));
			return;
		}

		$this->booking_model->expire_pending_bookings();
		$this->load->view('booking_landing', $this->booking_page_data());
	}

	public function pilates_deal()
	{
		$this->booking_model->expire_pending_bookings();
		$admin_access = $this->has_promo_admin_access();
		if (!$this->is_pilates_deal_period_open() && !$admin_access) {
			show_404();
			return;
		}

		$promo = $this->booking_model->get_active_pilates_promo(self::PILATES_DEAL_CODE, $admin_access);
		if (!$promo) {
			show_404();
			return;
		}

		$package = $this->booking_model->get_pilates_promo_package(self::PILATES_DEAL_CODE, $admin_access);
		if (!$package) {
			log_message('error', 'Active Pilates promo has no valid group package: ' . self::PILATES_DEAL_CODE);
			show_error('Paket promo Pilates sedang tidak tersedia.', 500);
			return;
		}

		$this->load->view('booking_landing', $this->booking_page_data(array(), self::PILATES_DEAL_CODE));
	}

	public function statement()
	{
		$statement_path = FCPATH . 'SURAT PERNYATAAN YOGA PILATES STUDIO BLOOME.pdf';
		if (!is_file($statement_path) || !is_readable($statement_path)) {
			log_message('error', 'Pilates statement PDF is missing or unreadable: ' . $statement_path);
			show_error('Dokumen surat pernyataan tidak tersedia.', 500);
			return;
		}

		$pdf = file_get_contents($statement_path);
		if ($pdf === FALSE) {
			log_message('error', 'Could not read Pilates statement PDF: ' . $statement_path);
			show_error('Dokumen surat pernyataan tidak dapat dibaca.', 500);
			return;
		}

		$this->output
			->set_content_type('application/pdf')
			->set_header('Content-Disposition: attachment; filename="SURAT PERNYATAAN YOGA PILATES STUDIO BLOOME.pdf"')
			->set_output($pdf);
	}

	public function availability()
	{
		$this->booking_model->expire_pending_bookings();
		$date = $this->input->get('date', TRUE);
		$start_date = $this->input->get('start_date', TRUE);
		$promo_code = $this->input->get('promo_code', TRUE);
		$promo_code = is_string($promo_code) && $promo_code !== '' ? $promo_code : NULL;
		if ($promo_code !== NULL && !$this->has_promo_access()) {
			return $this->json_response(array('error' => 'Periode promo Pilates sudah berakhir.'), 403);
		}
		$package_id = (int) $this->input->get('package_id', TRUE);
		$package = $promo_code === NULL
			? $this->booking_model->get_package($package_id)
			: $this->get_promo_booking_package($package_id, $promo_code, $this->has_promo_admin_access());

		if (!$this->valid_date($date) || !$package || !$this->date_in_package_range($date, $package, $start_date)) {
			return $this->json_response(array('error' => 'Tanggal atau paket booking tidak valid.'), 400);
		}

		$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
		$slots = array();
		foreach ($this->booking_model->get_day_schedule($date) as $schedule) {
			$occupancy = $this->booking_model->get_slot_occupancy($schedule->id, $date);
			$is_exclusive = $package->class_type !== 'group';
			$is_full = $occupancy->has_exclusive
				|| ($is_exclusive && (int) $occupancy->booked_attendees > 0)
				|| (!$is_exclusive && (int) $occupancy->booked_attendees + (int) $package->participants > 7);

			if ($date === $now->format('Y-m-d') && $schedule->start_time <= $now->format('H:i:s')) {
				$is_full = TRUE;
			}

			$end = clone $now;
			$end->setTime((int) substr($schedule->start_time, 0, 2), (int) substr($schedule->start_time, 3, 2));
			$end->modify('+' . (int) $schedule->duration_minutes . ' minutes');

			$slots[] = array(
				'schedule_id' => (int) $schedule->id,
				'start_time' => substr($schedule->start_time, 0, 5),
				'end_time' => (int) $schedule->duration_minutes > 60 ? $end->format('H:i') : NULL,
				'coach' => $schedule->coach_name ? $schedule->coach_name : 'Coach belum ditentukan',
				'class_type' => ucfirst($schedule->class_type) . ' Class',
				'booked' => (int) $occupancy->booked_attendees,
				'capacity' => $is_exclusive ? 1 : 7,
				'is_full' => (bool) $is_full
			);
		}

		return $this->json_response(array('date' => $date, 'slots' => $slots));
	}

	public function create()
	{
		if ($this->input->method(TRUE) !== 'POST') {
			show_404();
		}

		$this->booking_model->expire_pending_bookings();
		$promo_code = $this->input->post('promo_code', TRUE);
		$promo_code = is_string($promo_code) && $promo_code !== '' ? $promo_code : NULL;
		if ($promo_code !== NULL && !$this->has_promo_access()) {
			show_404();
			return;
		}
		$package_id = (int) $this->input->post('package_id', TRUE);
		$package = $promo_code === NULL
			? $this->booking_model->get_package($package_id)
			: $this->get_promo_booking_package($package_id, $promo_code, $this->has_promo_admin_access());
		$errors = array();
		$full_name = trim((string) $this->input->post('full_name', TRUE));
		$phone = preg_replace('/[^0-9+]/', '', (string) $this->input->post('phone', TRUE));
		$health_notes = trim((string) $this->input->post('health_notes', TRUE));
		$source = (string) $this->input->post('source', TRUE);
		$source_other = trim((string) $this->input->post('source_other', TRUE));
		$payment_method = (string) $this->input->post('payment_method', TRUE);
		$selected_slots = $this->input->post('session_slots');
		$allowed_sources = array('Instagram', 'TikTok', 'Threads', 'Teman / Keluarga', 'Google', 'Lainnya');

		if (!$package) {
			$errors[] = 'Paket booking tidak ditemukan. Silakan pilih paket kembali.';
		}
		if ($full_name === '' || strlen($full_name) > 100) {
			$errors[] = 'Nama lengkap wajib diisi (maksimal 100 karakter).';
		}
		if (strlen($phone) < 9 || strlen($phone) > 18) {
			$errors[] = 'Masukkan nomor WhatsApp yang valid.';
		}
		if (strlen($health_notes) > 1000) {
			$errors[] = 'Riwayat cedera maksimal 1000 karakter.';
		}
		if (!in_array($source, $allowed_sources, TRUE)) {
			$errors[] = 'Pilih sumber informasi Bloomè.';
		}
		if ($source === 'Lainnya' && ($source_other === '' || strlen($source_other) > 120)) {
			$errors[] = 'Isi informasi sumber lainnya (maksimal 120 karakter).';
		}
		if (!in_array($payment_method, array('qris', 'transfer'), TRUE)) {
			$errors[] = 'Pilih metode pembayaran.';
		}
		if (!is_array($selected_slots) || !$package || count($selected_slots) !== (int) $package->session_count + (int) $package->bonus_sessions) {
			$errors[] = 'Pilih seluruh jadwal sesuai jumlah sesi pada paket.';
		}

		$sessions = array();
		$unique_slots = array();
		if (is_array($selected_slots) && $package) {
			foreach ($selected_slots as $selected_slot) {
				if (!is_string($selected_slot) || !preg_match('/^([0-9]+)\|([0-9]{4}-[0-9]{2}-[0-9]{2})$/', $selected_slot, $match)) {
					$errors[] = 'Pilihan jadwal tidak valid.';
					continue;
				}
				$date = $match[2];
				$key = $match[1] . '|' . $date;
				if (isset($unique_slots[$key]) || !$this->valid_date($date)) {
					$errors[] = 'Tanggal atau jadwal booking tidak valid.';
					continue;
				}
				$unique_slots[$key] = TRUE;
				$sessions[] = array('schedule_id' => (int) $match[1], 'session_date' => $date);
			}
		}

		if ($sessions && $package) {
			$start_date = $sessions[0]['session_date'];
			foreach ($sessions as $session) {
				if ($session['session_date'] < $start_date) {
					$start_date = $session['session_date'];
				}
			}
			if (!$this->date_in_package_range($start_date, $package)) {
				$errors[] = 'Tanggal sesi pertama harus dipilih dari hari ini hingga dua tahun ke depan.';
			} else {
				foreach ($sessions as $session) {
					if (!$this->date_in_package_range($session['session_date'], $package, $start_date)) {
						$errors[] = 'Semua sesi harus dijadwalkan dalam masa berlaku paket sejak tanggal sesi pertama.';
						break;
					}
				}
			}
		}

		$statement_path = NULL;
		if ($health_notes !== '') {
			if (!$errors && empty($_FILES['statement_file']['name'])) {
				$errors[] = 'Karena mengisi riwayat cedera, surat pernyataan yang sudah ditandatangani wajib diunggah.';
			} elseif (!$errors) {
				$statement_path = $this->upload_private_file('statement_file', 'statement', $errors);
			}
		}

		if ($errors) {
			if ($statement_path) {
				@unlink($this->upload_directory . basename($statement_path));
			}
			$this->output->set_status_header(422);
			$this->load->view('booking_landing', $this->booking_page_data($errors, $promo_code));
			return;
		}

		if (!function_exists('openssl_random_pseudo_bytes')) {
			show_error('Sistem tidak dapat membuat referensi booking yang aman.', 500);
			return;
		}
		$strong = FALSE;
		$token = bin2hex(openssl_random_pseudo_bytes(20, $strong));
		if (!$strong) {
			if ($statement_path) {
				@unlink($this->upload_directory . basename($statement_path));
			}
			show_error('Sistem tidak dapat membuat referensi booking yang aman.', 500);
			return;
		}

		$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
		$expires = clone $now;
		$expires->modify('+24 hours');
		if ($promo_code !== NULL && !$this->has_promo_admin_access()) {
			$promo_deadline = new DateTime(self::PILATES_DEAL_END, new DateTimeZone('Asia/Jakarta'));
			if ($expires > $promo_deadline) {
				$expires = $promo_deadline;
			}
		}
		$booking = array(
			'public_token' => $token,
			'service' => 'pilates',
			'package_id' => (int) $package->id,
			'promo_code' => $promo_code,
			'package_name' => $package->name,
			'package_kind' => $package->kind . ' · ' . $package->class_type,
			'session_count' => (int) $package->session_count,
			'bonus_sessions' => (int) $package->bonus_sessions,
			'participants' => (int) $package->participants,
			'validity_days' => $package->validity_days ? (int) $package->validity_days : NULL,
			'price' => (int) $package->price,
			'full_name' => $full_name,
			'phone' => $phone,
			'health_notes' => $health_notes !== '' ? $health_notes : NULL,
			'source' => $source,
			'source_other' => $source === 'Lainnya' ? $source_other : NULL,
			'statement_file' => $statement_path,
			'payment_method' => $payment_method,
			'payment_status' => 'pending',
			'booking_status' => 'pending_payment',
			'expires_at' => $expires->format('Y-m-d H:i:s'),
			'created_at' => $now->format('Y-m-d H:i:s'),
			'is_exclusive' => $package->class_type === 'group' ? 0 : 1
		);

		$booking_id = $this->booking_model->create_booking($booking, $sessions);
		if (!$booking_id) {
			if ($statement_path) {
				@unlink($this->upload_directory . basename($statement_path));
			}
			$errors[] = 'Jadwal yang dipilih baru saja terisi atau tidak lagi tersedia. Silakan pilih jadwal lain.';
			$this->output->set_status_header(409);
			$this->load->view('booking_landing', $this->booking_page_data($errors, $promo_code));
			return;
		}

		redirect(site_url('booking/payment/' . $token));
	}

	public function payment($token)
	{
		$this->booking_model->expire_pending_bookings();
		$booking = $this->booking_model->get_booking($token);
		if (!$booking) {
			show_404();
		}
		if (!empty($booking->promo_code) && !$this->has_promo_access()) {
			show_404();
			return;
		}
		if ($booking->booking_status === 'confirmed') {
			redirect('booking/success/' . $token);
			return;
		}
		if ($booking->booking_status !== 'pending_payment') {
			$this->load->view('booking_expired');
			return;
		}

		$errors = array();
		if ($this->input->method(TRUE) === 'POST') {
			$payment_method = (string) $this->input->post('actual_payment_method', TRUE);
			if (!in_array($payment_method, array('qris', 'transfer'), TRUE)) {
				$payment_method = $booking->payment_method;
			}
			$receipt_path = $this->upload_private_file('receipt_file', 'receipt', $errors);
			if ($receipt_path) {
				$old_receipt = $this->booking_model->get_receipt_path($booking->id);
				if ($this->booking_model->add_payment_receipt($booking->id, $receipt_path, $payment_method)) {
					if ($old_receipt) {
						@unlink($this->upload_directory . basename($old_receipt));
					}
					redirect('booking/payment/' . $token);
					return;
				}
				@unlink($this->upload_directory . basename($receipt_path));
				$errors[] = 'Bukti pembayaran tidak dapat disimpan. Coba kembali.';
			}
		}

		$booking = $this->booking_model->get_booking($token);
		$this->load->view('booking_payment', array(
			'booking' => $booking,
			'sessions' => $this->booking_model->get_booking_sessions($booking->id),
			'errors' => $errors
		));
	}

	public function status($token)
	{
		$this->booking_model->expire_pending_bookings();
		$booking = $this->booking_model->get_booking($token);
		if (!$booking) {
			return $this->json_response(array('error' => 'Booking tidak ditemukan.'), 404);
		}

		return $this->json_response(array(
			'status' => $booking->booking_status,
			'success_url' => $booking->booking_status === 'confirmed' ? site_url('booking/success/' . $token) : NULL
		));
	}

	public function success($token)
	{
		$booking = $this->booking_model->get_booking($token);
		if (!$booking || $booking->booking_status !== 'confirmed') {
			redirect('booking/payment/' . $token);
			return;
		}

		$this->load->view('booking_success', array(
			'booking' => $booking,
			'sessions' => $this->booking_model->get_booking_sessions($booking->id)
		));
	}

	private function booking_page_data($errors = array(), $promo_code = NULL)
	{
		$this->load->model('Booking_model', 'booking_model');
		$health_notes = $this->input->post('health_notes', TRUE);
		$health_notes = is_string($health_notes) ? trim($health_notes) : '';
		$today = new DateTime('today', new DateTimeZone('Asia/Jakarta'));
		$latest = clone $today;
		$latest->modify('+2 years');
		$promo_package = NULL;
		if ($promo_code === self::PILATES_DEAL_CODE) {
			$promo_package = $this->booking_model->get_pilates_promo_package($promo_code, $this->has_promo_admin_access());
			if (!$promo_package) {
				log_message('error', 'Could not load Pilates promo package for booking page: ' . $promo_code);
				show_error('Paket promo Pilates sedang tidak tersedia.', 500);
				return array();
			}
		}
		return array(
			'service_name' => 'Pilates',
			'selected_service' => 'pilates',
			'packages' => $promo_package ? array($promo_package) : $this->booking_model->get_pilates_packages(),
			'promo_offer' => $promo_package ? $promo_package->promo_offer : NULL,
			'promo_code' => $promo_package ? $promo_code : NULL,
			'errors' => $errors,
			'health_notes' => $health_notes,
			'today' => $today->format('Y-m-d'),
			'latest_date' => $latest->format('Y-m-d'),
			'promo_last_session_date' => $promo_package ? self::PILATES_DEAL_LAST_SESSION : NULL,
			'csrf_name' => $this->security->get_csrf_token_name(),
			'csrf_hash' => $this->security->get_csrf_hash(),
			'availability_url' => site_url('booking/availability'),
			'statement_url' => site_url('booking/statement'),
			'create_url' => site_url('booking/create')
		);
	}

	private function get_promo_booking_package($package_id, $promo_code, $include_outside_validity = FALSE)
	{
		if ($promo_code !== self::PILATES_DEAL_CODE) {
			return NULL;
		}

		$package = $this->booking_model->get_pilates_promo_package($promo_code, $include_outside_validity);
		return $package && (int) $package->id === (int) $package_id ? $package : NULL;
	}

	private function has_promo_admin_access()
	{
		return (bool) $this->session->userdata('admin_user_id')
			&& $this->session->userdata('admin_role_name') === 'admin'
			&& !$this->session->userdata('admin_must_change_password');
	}

	private function has_promo_access()
	{
		return $this->is_pilates_deal_period_open() || $this->has_promo_admin_access();
	}

	private function is_pilates_deal_period_open()
	{
		$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
		$start = new DateTime(self::PILATES_DEAL_START, new DateTimeZone('Asia/Jakarta'));
		$end = new DateTime(self::PILATES_DEAL_END, new DateTimeZone('Asia/Jakarta'));
		return $now >= $start && $now <= $end;
	}

	private function valid_date($date)
	{
		if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return FALSE;
		}
		$parsed = DateTime::createFromFormat('Y-m-d', $date, new DateTimeZone('Asia/Jakarta'));
		return $parsed && $parsed->format('Y-m-d') === $date;
	}

	private function date_in_package_range($date, $package, $start_date = NULL)
	{
		$today = new DateTime('today', new DateTimeZone('Asia/Jakarta'));
		$chosen = new DateTime($date, new DateTimeZone('Asia/Jakarta'));

		if ($start_date === NULL || $start_date === '') {
			$latest_start = clone $today;
			$latest_start->modify('+2 years');
			$is_in_promo_window = !isset($package->promo_offer)
				|| $chosen <= new DateTime(self::PILATES_DEAL_LAST_SESSION, new DateTimeZone('Asia/Jakarta'));
			return $chosen >= $today && $chosen <= $latest_start && $is_in_promo_window;
		}

		if (!$this->valid_date($start_date)) {
			return FALSE;
		}
		$start = new DateTime($start_date, new DateTimeZone('Asia/Jakarta'));
		$latest_start = clone $today;
		$latest_start->modify('+2 years');
		if ($start < $today || $start > $latest_start || $chosen < $start) {
			return FALSE;
		}

		$validity_days = $package->validity_days && (int) $package->validity_days > 0
			? (int) $package->validity_days
			: 365;
		$latest = clone $start;
		$latest->modify('+' . $validity_days . ' days');
		if (isset($package->promo_offer)) {
			$promo_latest = new DateTime(self::PILATES_DEAL_LAST_SESSION, new DateTimeZone('Asia/Jakarta'));
			if ($latest > $promo_latest) {
				$latest = $promo_latest;
			}
		}
		return $chosen <= $latest;
	}

	private function upload_private_file($field, $prefix, &$errors)
	{
		if (empty($_FILES[$field]['name'])) {
			$errors[] = $prefix === 'statement'
				? 'Surat pernyataan yang sudah ditandatangani wajib diunggah.'
				: 'Pilih bukti pembayaran untuk diunggah.';
			return NULL;
		}

		if (!is_dir($this->upload_directory) && !mkdir($this->upload_directory, 0750, TRUE)) {
			log_message('error', 'Could not create private booking upload directory.');
			$errors[] = 'Penyimpanan file sedang tidak tersedia. Coba kembali.';
			return NULL;
		}

		$config = array(
			'upload_path' => $this->upload_directory,
			'allowed_types' => 'pdf|jpg|jpeg|png',
			'max_size' => 5120,
			'file_name' => $prefix . '-' . sha1(uniqid(mt_rand(), TRUE)) . '.' . strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION)),
			'file_ext_tolower' => TRUE,
			'remove_spaces' => TRUE,
			'detect_mime' => TRUE,
			'mod_mime_fix' => TRUE,
			'overwrite' => FALSE
		);
		$this->load->library('upload', $config);
		if (!$this->upload->do_upload($field)) {
			$errors[] = strip_tags($this->upload->display_errors('', ''));
			return NULL;
		}

		$file = $this->upload->data();
		return $file['file_name'];
	}

	private function json_response($data, $status = 200)
	{
		return $this->output
			->set_status_header($status)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($data));
	}

}
