<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_model extends CI_Model
{
	public function expire_pending_bookings()
	{
		$this->db
			->where('booking_status', 'pending_payment')
			->where('expires_at <=', date('Y-m-d H:i:s'))
			->update('studio_bookings', array('booking_status' => 'expired'));
	}

	public function get_pilates_packages()
	{
		return $this->db
			->where('is_active', 1)
			->order_by('kind', 'ASC')
			->order_by('sort_order', 'ASC')
			->get('pilates_pricing')
			->result();
	}

	public function get_package($package_id)
	{
		return $this->db
			->where('id', (int) $package_id)
			->where('is_active', 1)
			->get('pilates_pricing')
			->row();
	}

	public function get_day_schedule($date)
	{
		$day_of_week = (int) date('N', strtotime($date));

		$schedules = $this->db
			->select('s.id, s.start_time, s.duration_minutes, s.class_type, c.name AS coach_name')
			->from('class_schedules s')
			->join('coaches c', 'c.id = s.coach_id', 'left')
			->where('s.discipline', 'pilates')
			->where('s.day_of_week', $day_of_week)
			->where('s.is_active', 1)
			->order_by('s.start_time', 'ASC')
			->get()
			->result();

		return $schedules;
	}

	public function get_slot_occupancy($schedule_id, $date)
	{
		$schedule = $this->db
			->select('start_time, duration_minutes')
			->where('id', (int) $schedule_id)
			->get('class_schedules')
			->row();

		if (!$schedule) {
			return (object) array('booked_attendees' => 0, 'has_exclusive' => 0);
		}

		$time_parts = explode(':', $schedule->start_time);
		$block_start = ((int) $time_parts[0] * 3600) + ((int) $time_parts[1] * 60);
		if (isset($time_parts[2])) {
			$block_start += (int) $time_parts[2];
		}
		$block_end = $block_start + ((int) $schedule->duration_minutes * 60);

		return $this->db->query(
			'SELECT COALESCE(SUM(bs.attendees), 0) AS booked_attendees,
				COALESCE(MAX(bs.is_exclusive), 0) AS has_exclusive
			FROM studio_booking_sessions bs
			JOIN studio_bookings b ON b.id = bs.booking_id
			WHERE bs.session_date = ?
				AND TIME_TO_SEC(bs.start_time) < ?
				AND (TIME_TO_SEC(bs.start_time) + bs.duration_minutes * 60) > ?
				AND (b.booking_status = ?
					OR (b.booking_status = ? AND b.expires_at > ?))',
			array($date, $block_end, $block_start, 'confirmed', 'pending_payment', date('Y-m-d H:i:s'))
		)->row();
	}

	public function create_booking($booking, $sessions)
	{
		$this->db->trans_begin();
		$is_exclusive = (int) $booking['is_exclusive'];
		unset($booking['is_exclusive']);

		foreach ($sessions as $session) {
			$schedule = $this->db->query(
				'SELECT s.*, c.name AS coach_name
				FROM class_schedules s
				LEFT JOIN coaches c ON c.id = s.coach_id
				WHERE s.id = ? AND s.discipline = ? AND s.is_active = 1
				FOR UPDATE',
				array((int) $session['schedule_id'], 'pilates')
			)->row();

			if (!$schedule) {
				$this->db->trans_rollback();
				return FALSE;
			}
			if ((int) $schedule->day_of_week !== (int) date('N', strtotime($session['session_date']))) {
				$this->db->trans_rollback();
				return FALSE;
			}
			if ($session['session_date'] === date('Y-m-d') && $schedule->start_time <= date('H:i:s')) {
				$this->db->trans_rollback();
				return FALSE;
			}

			$occupancy = $this->get_slot_occupancy($schedule->id, $session['session_date']);
			if ($occupancy->has_exclusive || ($is_exclusive && $occupancy->booked_attendees > 0)
				|| (!$is_exclusive && (int) $occupancy->booked_attendees + (int) $booking['participants'] > 7)) {
				$this->db->trans_rollback();
				return FALSE;
			}

			$booking['selected_schedules'][] = array(
				'schedule_id' => (int) $schedule->id,
				'session_date' => $session['session_date'],
				'start_time' => $schedule->start_time,
				'duration_minutes' => (int) $schedule->duration_minutes,
				'coach_name' => $schedule->coach_name,
				'class_type' => $schedule->class_type
			);
		}

		$selected_schedules = $booking['selected_schedules'];
		unset($booking['selected_schedules']);
		$this->db->insert('studio_bookings', $booking);
		$booking_id = (int) $this->db->insert_id();

		foreach ($selected_schedules as $session) {
			$this->db->insert('studio_booking_sessions', array(
				'booking_id' => $booking_id,
				'schedule_id' => $session['schedule_id'],
				'session_date' => $session['session_date'],
				'start_time' => $session['start_time'],
				'duration_minutes' => $session['duration_minutes'],
				'coach_name' => $session['coach_name'],
				'class_type' => $session['class_type'],
				'attendees' => $booking['participants'],
				'is_exclusive' => $is_exclusive
			));
		}

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return FALSE;
		}

		$this->db->trans_commit();
		return $booking_id;
	}

	public function get_booking($token)
	{
		return $this->db
			->where('public_token', $token)
			->get('studio_bookings')
			->row();
	}

	public function get_booking_sessions($booking_id)
	{
		return $this->db
			->where('bs.booking_id', (int) $booking_id)
			->from('studio_booking_sessions bs')
			->order_by('session_date', 'ASC')
			->order_by('start_time', 'ASC')
			->get()
			->result();
	}

	public function get_pending_booking_sessions($booking_id)
	{
		return $this->get_booking_sessions($booking_id);
	}

	public function add_payment_receipt($booking_id, $path, $payment_method)
	{
		return $this->db
			->where('id', (int) $booking_id)
			->where('booking_status', 'pending_payment')
			->where('payment_status', 'pending')
			->update('studio_bookings', array('receipt_file' => $path, 'payment_method' => $payment_method));
	}

	public function get_receipt_path($booking_id)
	{
		$booking = $this->db
			->select('receipt_file')
			->where('id', (int) $booking_id)
			->get('studio_bookings')
			->row();

		return $booking ? $booking->receipt_file : NULL;
	}

	public function get_statement_path($booking_id)
	{
		$booking = $this->db
			->select('statement_file')
			->where('id', (int) $booking_id)
			->get('studio_bookings')
			->row();

		return $booking ? $booking->statement_file : NULL;
	}

	public function get_pending_bookings()
	{
		return $this->db
			->where('booking_status', 'pending_payment')
			->where('expires_at >', date('Y-m-d H:i:s'))
			->order_by('created_at', 'ASC')
			->get('studio_bookings')
			->result();
	}

	public function get_admin_booking($booking_id)
	{
		return $this->db
			->where('id', (int) $booking_id)
			->get('studio_bookings')
			->row();
	}

	public function set_payment_status($booking_id, $status, $admin_id)
	{
		$update = array(
			'payment_status' => $status,
			'booking_status' => $status === 'paid' ? 'confirmed' : 'cancelled',
			'verified_by' => (int) $admin_id,
			'verified_at' => date('Y-m-d H:i:s')
		);
		if ($status === 'paid') {
			$update['paid_at'] = date('Y-m-d H:i:s');
		}

		return $this->db
			->where('id', (int) $booking_id)
			->where('booking_status', 'pending_payment')
			->where('expires_at >', date('Y-m-d H:i:s'))
			->update('studio_bookings', $update);
	}
}
