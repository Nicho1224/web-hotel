<?php
$res = $conn->query("
  SELECT status, COUNT(*) AS jml
  FROM kamar GROUP BY status
");
?>
<h2>Status Kamar</h2>
<ul class="list-group">
  <?php while($row=$res->fetch_assoc()): ?>
    <li class="list-group-item d-flex justify-content-between">
      <?= ucfirst($row['status']) ?>
      <span class="badge bg-primary"><?= $row['jml'] ?></span>
    </li>
  <?php endwhile; ?>
</ul>
