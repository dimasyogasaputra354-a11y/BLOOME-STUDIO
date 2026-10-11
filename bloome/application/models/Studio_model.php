<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Studio_model extends CI_Model
{
	public function get_active_promos()
	{
		$today = date('Y-m-d');

		return $this->db
			->select('code, discipline, title, tagline, price, price_unit, terms_note, image_path')
			->where('is_active', 1)
			->group_start()
				->where('valid_from IS NULL', NULL, FALSE)
				->or_where('valid_from <=', $today)
			->group_end()
			->group_start()
				->where('valid_until IS NULL', NULL, FALSE)
				->or_where('valid_until >=', $today)
			->group_end()
			->order_by('valid_until IS NULL', 'ASC', FALSE)
			->order_by('valid_until', 'ASC')
			->limit(2)
			->get('promos')
			->result();
	}

	public function get_pilates_prices()
	{
		return $this->db
			->select('name, session_count, price, notes')
			->where('kind', 'session')
			->where('is_active', 1)
			->order_by('sort_order', 'ASC')
			->limit(4)
			->get('pilates_pricing')
			->result();
	}

	public function get_yoga_prices()
	{
		return $this->db
			->select('name, session_count, validity_label, price')
			->where('is_active', 1)
			->order_by('sort_order', 'ASC')
			->limit(5)
			->get('yoga_pricing')
			->result();
	}

	public function get_beauty_preview()
	{
		return $this->db
			->select('s.name, s.price_type, s.price, s.price_max, s.percent_value, c.name AS category_name')
			->from('beauty_services s')
			->join('beauty_categories c', 'c.id = s.category_id')
			->where('s.is_active', 1)
			->where_in('c.code', array('NAIL_HAND', 'NAIL_FOOT', 'LASHLIFT'))
			->where_in('s.name', array('Manicure', 'SPA Pedicure', 'Lashlift & Tint'))
			->order_by('c.sort_order', 'ASC')
			->get()
			->result();
	}
}
