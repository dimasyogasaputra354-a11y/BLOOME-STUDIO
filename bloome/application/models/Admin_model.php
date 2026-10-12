<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model
{
	public function authenticate($username, $password)
	{
		$user = $this->db
			->select('u.id, u.username, u.full_name, u.password_salt, u.password_hash, u.must_change_password, r.name AS role_name')
			->from('users u')
			->join('roles r', 'r.id = u.role_id')
			->where('u.username', $username)
			->where('u.is_active', 1)
			->limit(1)
			->get()
			->row();

		if (!$user || !hash_equals($user->password_hash, hash('sha256', $user->password_salt . $password))) {
			return FALSE;
		}

		$this->db->where('id', $user->id)->update('users', array('last_login_at' => date('Y-m-d H:i:s')));

		return $user;
	}

	public function get_username($user_id)
	{
		$user = $this->db
			->select('username')
			->where('id', (int) $user_id)
			->where('is_active', 1)
			->limit(1)
			->get('users')
			->row();

		return $user ? $user->username : '';
	}

	public function change_password($user_id, $current_password, $new_password)
	{
		$user = $this->db
			->select('password_salt, password_hash')
			->where('id', $user_id)
			->where('is_active', 1)
			->limit(1)
			->get('users')
			->row();

		if (!$user || !hash_equals($user->password_hash, hash('sha256', $user->password_salt . $current_password))) {
			return FALSE;
		}

		$salt_bytes = openssl_random_pseudo_bytes(8, $is_crypto_strong);
		if ($salt_bytes === FALSE || !$is_crypto_strong) {
			log_message('error', 'Unable to generate a cryptographically secure password salt.');
			return FALSE;
		}
		$salt = bin2hex($salt_bytes);
		$this->db
			->where('id', $user_id)
			->update('users', array(
				'password_salt' => $salt,
				'password_hash' => hash('sha256', $salt . $new_password),
				'must_change_password' => 0
			));

		return $this->db->affected_rows() === 1;
	}

	public function update_account_credentials($user_id, $current_password, $username, $new_password = NULL)
	{
		$user = $this->db
			->select('username, password_salt, password_hash')
			->where('id', (int) $user_id)
			->where('is_active', 1)
			->limit(1)
			->get('users')
			->row();

		if (!$user || !hash_equals($user->password_hash, hash('sha256', $user->password_salt . $current_password))) {
			return FALSE;
		}

		$this->db->where('username', $username)->where('id !=', (int) $user_id);
		if ($this->db->count_all_results('users') > 0) {
			return FALSE;
		}

		$data = array('username' => $username);
		if ($new_password !== NULL) {
			$salt_bytes = openssl_random_pseudo_bytes(8, $is_crypto_strong);
			if ($salt_bytes === FALSE || !$is_crypto_strong) {
				log_message('error', 'Unable to generate a cryptographically secure password salt.');
				return FALSE;
			}
			$salt = bin2hex($salt_bytes);
			$data['password_salt'] = $salt;
			$data['password_hash'] = hash('sha256', $salt . $new_password);
			$data['must_change_password'] = 0;
		}

		$this->db->where('id', (int) $user_id)->update('users', $data);
		return $this->db->affected_rows() === 1 || ($new_password === NULL && $user->username === $username);
	}

	public function get_paid_report_rows($start_datetime, $end_datetime)
	{
		$sql = "
			SELECT t.trx_datetime AS paid_at,
				DATE_FORMAT(t.trx_datetime, '%H:%i') AS transaction_time,
				m.full_name, m.phone, (t.sessions_total * t.qty) AS sessions,
				t.item_name AS package_name, c.name AS coach_name,
				t.complaint_injury AS health_notes, t.amount AS amount
			FROM transactions t
			LEFT JOIN members m ON m.id = t.member_id
			LEFT JOIN coaches c ON c.id = t.coach_id
			WHERE t.trx_type = 'sale' AND t.status = 'paid'
				AND t.trx_datetime >= ? AND t.trx_datetime < ?
			UNION ALL
			SELECT COALESCE(b.paid_at, b.created_at) AS paid_at,
				DATE_FORMAT(COALESCE(b.paid_at, b.created_at), '%H:%i') AS transaction_time,
				b.full_name, b.phone, (b.session_count + b.bonus_sessions) AS sessions,
				b.package_name, booking_sessions.coach_name,
				b.health_notes, b.price AS amount
			FROM studio_bookings b
			LEFT JOIN (
				SELECT booking_id, MIN(coach_name) AS coach_name
				FROM studio_booking_sessions
				GROUP BY booking_id
			) booking_sessions ON booking_sessions.booking_id = b.id
			WHERE b.payment_status = 'paid'
				AND COALESCE(b.paid_at, b.created_at) >= ?
				AND COALESCE(b.paid_at, b.created_at) < ?
			ORDER BY paid_at DESC";

		return $this->db->query($sql, array(
			$start_datetime,
			$end_datetime,
			$start_datetime,
			$end_datetime
		))->result();
	}

	public function get_overview()
	{
		return array(
			'users' => $this->db->count_all('users'),
			'promos' => $this->db->where('is_active', 1)->count_all_results('promos'),
			'pilates' => $this->db->where('is_active', 1)->count_all_results('pilates_pricing'),
			'yoga' => $this->db->where('is_active', 1)->count_all_results('yoga_pricing'),
			'beauty' => $this->db->where('is_active', 1)->count_all_results('beauty_services'),
			'schedules' => $this->db->where('is_active', 1)->count_all_results('class_schedules'),
			'coaches' => $this->db->where('is_active', 1)->count_all_results('coaches')
		);
	}

	public function get_promos()
	{
		return $this->db
			->order_by('id', 'ASC')
			->limit(4)
			->get('promos')
			->result();
	}

	public function get_promo_by_id($promo_id)
	{
		return $this->db
			->where('id', (int) $promo_id)
			->limit(1)
			->get('promos')
			->row();
	}

	public function promo_code_exists($code, $except_id = NULL)
	{
		$this->db->where('code', $code);
		if ($except_id !== NULL) {
			$this->db->where('id !=', (int) $except_id);
		}
		return $this->db->count_all_results('promos') > 0;
	}

	public function count_promos()
	{
		return $this->db->count_all('promos');
	}

	public function save_promo($data, $promo_id = NULL)
	{
		if ($promo_id === NULL) {
			return $this->db->insert('promos', $data);
		}
		return $this->db->where('id', (int) $promo_id)->update('promos', $data);
	}

	public function delete_promo($promo_id)
	{
		return $this->db->where('id', (int) $promo_id)->delete('promos');
	}

	public function get_promo_for_image($promo_id)
	{
		return $this->db
			->select('id, image_path')
			->where('id', (int) $promo_id)
			->limit(1)
			->get('promos')
			->row();
	}

	public function set_promo_image($promo_id, $image_path)
	{
		$this->db
			->where('id', (int) $promo_id)
			->update('promos', array('image_path' => $image_path));

		return $this->db->affected_rows() === 1;
	}

	public function get_pilates_prices()
	{
		return $this->db
			->select('name, class_type, price, kind')
			->where('is_active', 1)
			->order_by('kind', 'ASC')
			->order_by('sort_order', 'ASC')
			->limit(5)
			->get('pilates_pricing')
			->result();
	}

	public function get_yoga_prices()
	{
		return $this->db
			->select('name, price, validity_label')
			->where('is_active', 1)
			->order_by('sort_order', 'ASC')
			->limit(5)
			->get('yoga_pricing')
			->result();
	}

	public function get_catalog_items($type)
	{
		$tables = array(
			'pilates' => 'pilates_pricing',
			'yoga' => 'yoga_pricing',
			'beauty' => 'beauty_services'
		);
		if (!isset($tables[$type])) {
			return array();
		}
		$this->db->from($tables[$type]);
		if ($type === 'beauty') {
			$this->db->select('beauty_services.*, beauty_categories.name AS category_name')
				->join('beauty_categories', 'beauty_categories.id = beauty_services.category_id');
		}
		$id_column = $type === 'beauty' ? 'beauty_services.id' : 'id';
		$sort_column = $type === 'beauty' ? 'beauty_services.sort_order' : 'sort_order';
		return $this->db->order_by($sort_column, 'ASC')->order_by($id_column, 'ASC')->get()->result();
	}

	public function get_beauty_categories()
	{
		return $this->db
			->order_by('sort_order', 'ASC')
			->get('beauty_categories')
			->result();
	}

	public function save_catalog_item($type, $data, $item_id = NULL)
	{
		$tables = array(
			'pilates' => 'pilates_pricing',
			'yoga' => 'yoga_pricing',
			'beauty' => 'beauty_services'
		);
		if (!isset($tables[$type])) {
			return FALSE;
		}
		if ($item_id === NULL) {
			return $this->db->insert($tables[$type], $data);
		}
		return $this->db->where('id', (int) $item_id)->update($tables[$type], $data);
	}

	public function delete_catalog_item($type, $item_id)
	{
		$tables = array(
			'pilates' => 'pilates_pricing',
			'yoga' => 'yoga_pricing',
			'beauty' => 'beauty_services'
		);
		if (!isset($tables[$type])) {
			return FALSE;
		}
		if ($type === 'pilates') {
			$referenced = $this->db->where('package_id', (int) $item_id)->count_all_results('studio_bookings') > 0
				|| $this->db->where('pilates_pricing_id', (int) $item_id)->count_all_results('transactions') > 0;
			if ($referenced) {
				return $this->db->where('id', (int) $item_id)->update('pilates_pricing', array('is_active' => 0));
			}
		} elseif ($type === 'yoga') {
			if ($this->db->where('yoga_pricing_id', (int) $item_id)->count_all_results('transactions') > 0) {
				return $this->db->where('id', (int) $item_id)->update('yoga_pricing', array('is_active' => 0));
			}
		} elseif ($type === 'beauty' && $this->db->where('beauty_service_id', (int) $item_id)->count_all_results('transactions') > 0) {
			return $this->db->where('id', (int) $item_id)->update('beauty_services', array('is_active' => 0));
		}
		return $this->db->where('id', (int) $item_id)->delete($tables[$type]);
	}

	public function get_reviews()
	{
		return $this->db->order_by('sort_order', 'ASC')->order_by('id', 'ASC')->get('studio_reviews')->result();
	}

	public function save_review($data, $review_id = NULL)
	{
		if ($review_id === NULL) {
			return $this->db->insert('studio_reviews', $data);
		}
		return $this->db->where('id', (int) $review_id)->update('studio_reviews', $data);
	}

	public function delete_review($review_id)
	{
		return $this->db->where('id', (int) $review_id)->delete('studio_reviews');
	}

	public function get_gallery()
	{
		return $this->db->order_by('sort_order', 'ASC')->order_by('id', 'ASC')->limit(4)->get('studio_gallery')->result();
	}

	public function count_gallery()
	{
		return $this->db->count_all('studio_gallery');
	}

	public function get_gallery_item($gallery_id)
	{
		return $this->db->where('id', (int) $gallery_id)->get('studio_gallery')->row();
	}

	public function save_gallery_item($data, $gallery_id = NULL)
	{
		if ($gallery_id === NULL) {
			return $this->db->insert('studio_gallery', $data);
		}
		return $this->db->where('id', (int) $gallery_id)->update('studio_gallery', $data);
	}

	public function delete_gallery_item($gallery_id)
	{
		return $this->db->where('id', (int) $gallery_id)->delete('studio_gallery');
	}
}
