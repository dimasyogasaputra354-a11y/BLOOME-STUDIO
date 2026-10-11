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

	public function get_recent_promos()
	{
		return $this->db
			->select('id, code, discipline, title, price, price_unit, is_active, image_path')
			->order_by('created_at', 'DESC')
			->limit(5)
			->get('promos')
			->result();
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
}
