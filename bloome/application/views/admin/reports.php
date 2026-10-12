<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$max_daily_chart = 0;
foreach ($daily_chart as $point) {
	$max_daily_chart = max($max_daily_chart, (float) $point['amount']);
}
$max_monthly_chart = 0;
foreach ($monthly_chart as $point) {
	$max_monthly_chart = max($max_monthly_chart, (float) $point['amount']);
}
$monthly_days = array();
foreach ($monthly_chart as $point) {
	$monthly_days[$point['date']] = array('amount' => $point['amount'], 'transactions' => 0);
}
foreach ($monthly_rows as $row) {
	$day = date('Y-m-d', strtotime($row->paid_at));
	if (isset($monthly_days[$day])) {
		$monthly_days[$day]['transactions']++;
	}
}
$render_transaction_rows = function ($rows) use ($money) {
	if (!$rows): ?>
		<tr><td colspan="8" class="report-empty">Belum ada transaksi lunas pada periode ini.</td></tr>
	<?php else: foreach ($rows as $row): ?>
		<tr>
			<td><?php echo html_escape($row->transaction_time); ?></td>
			<td><strong><?php echo html_escape($row->full_name); ?></strong></td>
			<td><?php echo (int) $row->sessions; ?></td>
			<td><?php echo html_escape($row->phone); ?></td>
			<td><?php echo html_escape($row->package_name); ?></td>
			<td><?php echo html_escape($row->coach_name ? $row->coach_name : '—'); ?></td>
			<td><?php echo html_escape($row->health_notes ? $row->health_notes : '—'); ?></td>
			<td><strong><?php echo $money($row->amount); ?></strong></td>
		</tr>
	<?php endforeach; endif;
};
?>
<?php if ($report_filter_error !== ''): ?><p class="promo-image-feedback is-error" role="alert"><?php echo html_escape($report_filter_error); ?></p><?php endif; ?>
<section class="report-section" id="daily-report">
	<div class="report-heading">
		<div><p class="admin-eyebrow">LAPORAN PENDAPATAN</p><h2>Daily Report</h2><p>Pendapatan dihitung dari transaksi penjualan dan booking yang sudah lunas.</p></div>
		<form class="report-filter" method="get" action="<?php echo site_url('admin'); ?>">
			<input type="hidden" name="month" value="<?php echo html_escape($report_month); ?>">
			<label for="daily-date">Tanggal laporan</label><input id="daily-date" type="date" name="date" value="<?php echo html_escape($report_date); ?>">
			<button class="admin-button admin-button-dark" type="submit">Tampilkan</button>
		</form>
	</div>
	<div class="report-stat-grid">
		<article class="report-stat"><span>Pendapatan</span><strong><?php echo $money($daily_summary['revenue']); ?></strong><small><?php echo html_escape(date('d M Y', strtotime($report_date))); ?></small></article>
		<article class="report-stat"><span>Transaksi</span><strong><?php echo (int) $daily_summary['transactions']; ?></strong><small>transaksi lunas</small></article>
		<article class="report-stat"><span>Total sesi</span><strong><?php echo (int) $daily_summary['sessions']; ?></strong><small>sesi terjual</small></article>
		<article class="report-stat"><span>Rata-rata / transaksi</span><strong><?php echo $money($daily_summary['average']); ?></strong><small>nilai transaksi</small></article>
	</div>
	<div class="report-panel">
		<div class="report-panel-heading"><div><h3>Tren pendapatan harian</h3><p>14 hari hingga <?php echo html_escape(date('d M Y', strtotime($report_date))); ?></p></div></div>
		<div class="report-chart report-chart-14" role="img" aria-label="Grafik pendapatan selama 14 hari">
			<?php foreach ($daily_chart as $point): $height = $max_daily_chart > 0 ? max(3, ($point['amount'] / $max_daily_chart) * 100) : 3; ?>
				<div class="report-chart-column" title="<?php echo html_escape($point['date'] . ' · ' . $money($point['amount'])); ?>">
					<div class="report-bar-track"><span class="report-bar" style="height: <?php echo (float) $height; ?>%"></span></div>
					<small><?php echo html_escape($point['label']); ?></small>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="report-panel">
		<div class="report-panel-heading"><div><h3>Transaksi harian</h3><p>Rincian transaksi yang telah lunas</p></div><span class="panel-count"><?php echo (int) $daily_summary['transactions']; ?> transaksi</span></div>
		<div class="table-scroll report-table-scroll"><table class="report-table"><thead><tr><th>Jam</th><th>Nama</th><th>Sesi</th><th>No. HP</th><th>Paket</th><th>Coach</th><th>Catatan kesehatan</th><th>Pendapatan</th></tr></thead><tbody><?php $render_transaction_rows($daily_rows); ?></tbody></table></div>
	</div>
</section>

<section class="report-section" id="monthly-report">
	<div class="report-heading">
		<div><p class="admin-eyebrow">REKAP BULANAN</p><h2>Monthly Report</h2><p>Grafik dan rincian pendapatan per hari untuk bulan yang dipilih.</p></div>
		<form class="report-filter" method="get" action="<?php echo site_url('admin'); ?>">
			<input type="hidden" name="date" value="<?php echo html_escape($report_date); ?>">
			<label for="report-month">Bulan laporan</label><input id="report-month" type="month" name="month" value="<?php echo html_escape($report_month); ?>">
			<button class="admin-button admin-button-dark" type="submit">Tampilkan</button>
		</form>
	</div>
	<div class="report-stat-grid report-stat-grid-month">
		<article class="report-stat"><span>Pendapatan bulanan</span><strong><?php echo $money($monthly_summary['revenue']); ?></strong><small><?php echo html_escape(date('F Y', strtotime($report_month . '-01'))); ?></small></article>
		<article class="report-stat"><span>Transaksi</span><strong><?php echo (int) $monthly_summary['transactions']; ?></strong><small>transaksi lunas</small></article>
		<article class="report-stat"><span>Total sesi</span><strong><?php echo (int) $monthly_summary['sessions']; ?></strong><small>sesi terjual</small></article>
		<article class="report-stat"><span>Rata-rata / transaksi</span><strong><?php echo $money($monthly_summary['average']); ?></strong><small>nilai transaksi</small></article>
	</div>
	<div class="report-panel">
		<div class="report-panel-heading"><div><h3>Pendapatan per hari</h3><p><?php echo html_escape(date('F Y', strtotime($report_month . '-01'))); ?></p></div></div>
		<div class="report-chart report-chart-month" role="img" aria-label="Grafik pendapatan harian pada bulan <?php echo html_escape($report_month); ?>">
			<?php foreach ($monthly_chart as $point): $height = $max_monthly_chart > 0 ? max(3, ($point['amount'] / $max_monthly_chart) * 100) : 3; ?>
				<div class="report-chart-column" title="<?php echo html_escape($point['date'] . ' · ' . $money($point['amount'])); ?>">
					<div class="report-bar-track"><span class="report-bar" style="height: <?php echo (float) $height; ?>%"></span></div>
					<small><?php echo (int) date('j', strtotime($point['date'])); ?></small>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="report-panel">
		<div class="report-panel-heading"><div><h3>Rekap pendapatan harian</h3><p>Total dan jumlah transaksi lunas per tanggal</p></div></div>
		<div class="table-scroll report-table-scroll"><table class="report-table"><thead><tr><th>Tanggal</th><th>Jumlah transaksi</th><th>Pendapatan</th></tr></thead><tbody>
			<?php foreach ($monthly_days as $date => $day): ?>
				<tr><td><?php echo html_escape(date('d M Y', strtotime($date))); ?></td><td><?php echo (int) $day['transactions']; ?></td><td><strong><?php echo $money($day['amount']); ?></strong></td></tr>
			<?php endforeach; ?>
		</tbody></table></div>
	</div>
	<details class="report-panel report-detail-panel">
		<summary>Rincian seluruh transaksi bulan ini <span><?php echo (int) $monthly_summary['transactions']; ?> transaksi</span></summary>
		<div class="table-scroll report-table-scroll"><table class="report-table"><thead><tr><th>Jam</th><th>Nama</th><th>Sesi</th><th>No. HP</th><th>Paket</th><th>Coach</th><th>Catatan kesehatan</th><th>Pendapatan</th></tr></thead><tbody><?php $render_transaction_rows($monthly_rows); ?></tbody></table></div>
	</details>
</section>
