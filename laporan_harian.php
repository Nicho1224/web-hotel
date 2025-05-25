<?php
$today = date('Y-m-d');
$r = $conn->query("
  SELECT COUNT(*) AS jml, SUM(totalharga) AS pendapatan
  FROM transaksi WHERE tgl_booking='$today'
")->fetch_assoc();
?>
<h2>Laporan Harian (<?= $today ?>)</h2>
<p>Jumlah Booking: <?= $r['jml'] ?> | Total Pendapatan: Rp <?= number_format($r['pendapatan'],2,',','.') ?></p>
