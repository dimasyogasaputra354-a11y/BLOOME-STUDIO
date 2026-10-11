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

		$data = array(
			'admin_name' => $this->session->userdata('admin_full_name'),
			'role_name' => $this->session->userdata('admin_role_name'),
			'overview' => $this->admin_model->get_overview(),
			'promos' => $this->admin_model->get_recent_promos(),
			'pilates' => $this->admin_model->get_pilates_prices(),
			'yoga' => $this->admin_model->get_yoga_prices()
		);

		$this->load->view('admin/dashboard', $data);
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
		$bookings = $this->booking_model->get_pending_bookings();
		$sessions = array();
		foreach ($bookings as $booking) {
			$sessions[$booking->id] = $this->booking_model->get_booking_sessions($booking->id);
		}

		$this->load->view('admin/bookings', array(
			'admin_name' => $this->session->userdata('admin_full_name'),
			'bookings' => $bookings,
			'sessions' => $sessions,
			'notice' => $this->session->flashdata('booking_notice'),
			'error' => $this->session->flashdata('booking_error')
		));
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
}
