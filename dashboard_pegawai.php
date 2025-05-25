<?php
// Start output buffering to prevent headers already sent error
ob_start();

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php'; // Make sure config.php is included

// Function to get current date
function getCurrentDate() {
    return isset($_SESSION['test_date']) ? $_SESSION['test_date'] : date('Y-m-d');
}

// Handle all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle date testing
        if (isset($_POST['set_test_date'])) {
            if (!empty($_POST['test_date'])) {
                $_SESSION['test_date'] = $_POST['test_date'];
                $_SESSION['success'] = "Tanggal testing berhasil diatur ke: " . date('d/m/Y', strtotime($_POST['test_date']));
                header("Location: index.php?page=dashboard_pegawai");
                ob_end_clean();
                exit;
            }
        } 
        // Handle reset date
        if (isset($_POST['reset_test_date'])) {
            unset($_SESSION['test_date']);
            $_SESSION['success'] = "Mode testing direset ke tanggal sekarang";
            header("Location: index.php?page=dashboard_pegawai");
            ob_end_clean();
            exit;
        }
        // Handle status updates
        elseif (isset($_POST['update_test_status'])) {
            if (!empty($_POST['test_kamar']) && !empty($_POST['test_status'])) {
                $stmt = $conn->prepare("UPDATE kamar SET status = ? WHERE id_kamar = ?");
                $stmt->execute([$_POST['test_status'], $_POST['test_kamar']]);
                $_SESSION['success'] = "Status kamar berhasil diupdate!";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: index.php?page=dashboard_pegawai");
        ob_end_clean();
        exit;
    }
}

// Get current date (real or test)
$currentDate = getCurrentDate();

// Debug information (temporarily add this to check session)
echo "<!-- Debug: Test Date = " . ($_SESSION['test_date'] ?? 'not set') . " -->";
echo "<!-- Debug: Current Date = " . $currentDate . " -->";

// Show success/error messages if any
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

// First, update any rooms that need cleaning based on checkout
try {
    $stmt = $conn->prepare("
        UPDATE kamar k
        JOIN transaksi t ON k.id_kamar = t.id_kamar
        SET k.status = 'perlu dibersihkan'
        WHERE t.tgl_checkout <= ? 
        AND t.status = 'checkout'
        AND k.status NOT IN ('perlu dibersihkan', 'sedang dibersihkan')
    ");
    $stmt->execute([$currentDate]);
} catch (Exception $e) {
    error_log("Error updating room status: " . $e->getMessage());
}

// Then get the statistics
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM kamar k
    LEFT JOIN transaksi t ON k.id_kamar = t.id_kamar
    WHERE k.status = 'perlu dibersihkan'
    OR (t.tgl_checkout <= ? AND t.status = 'checkout' AND k.status NOT IN ('tersedia', 'maintenance'))
");
$stmt->execute([$currentDate]);
$kamarPerluDibersihkan = $stmt->fetchColumn();

$kamarSedangDibersihkan = $conn->query("
    SELECT COUNT(*) FROM kamar 
    WHERE status = 'sedang dibersihkan'
")->fetchColumn();

$kamarTersedia = $conn->query("
    SELECT COUNT(*) FROM kamar 
    WHERE status = 'tersedia' OR status = ''  -- Include empty status
")->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(*) FROM transaksi 
    WHERE DATE(tgl_checkout) = DATE(?) 
    AND status = 'checkin'
");
$stmt->execute([$currentDate]);
$checkoutHariIni = $stmt->fetchColumn();

// For completeness, let's add total rooms count
$totalKamar = $conn->query("SELECT COUNT(*) FROM kamar")->fetchColumn();

// Get rooms that need attention
$stmt = $conn->prepare("
    SELECT k.nama as nama_kamar, k.status, 
           t.tgl_checkout, tm.nama as nama_tamu
    FROM kamar k
    LEFT JOIN transaksi t ON k.id_kamar = t.id_kamar
    LEFT JOIN tamu tm ON t.nik = tm.nik
    WHERE k.status IN ('perlu dibersihkan', 'sedang dibersihkan')
    ORDER BY 
        CASE 
            WHEN k.status = 'perlu dibersihkan' THEN 1
            ELSE 2
        END,
        t.tgl_checkout ASC
    LIMIT 5
");
$stmt->execute();
$kamarList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard Pegawai</h3>
        <small class="text-muted">Data per tanggal: <?= date('d F Y', strtotime($currentDate)) ?></small>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4">
        <!-- Perlu Dibersihkan -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Perlu Dibersihkan</h6>
                            <h4 class="mb-0"><?= $kamarPerluDibersihkan ?> Kamar</h4>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="bi bi-brush fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sedang Dibersihkan -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Sedang Dibersihkan</h6>
                            <h4 class="mb-0"><?= $kamarSedangDibersihkan ?> Kamar</h4>
                        </div>
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="bi bi-clock-history fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kamar Tersedia -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Kamar Tersedia</h6>
                            <h4 class="mb-0"><?= $kamarTersedia ?> Kamar</h4>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="bi bi-door-open fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout Hari Ini -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-danger border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Checkout Hari Ini</h6>
                            <h4 class="mb-0"><?= $checkoutHariIni ?> Kamar</h4>
                        </div>
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="bi bi-box-arrow-right fs-4 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Kamar -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-secondary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Kamar</h6>
                            <h4 class="mb-0"><?= $totalKamar ?> Kamar</h4>
                            <small class="text-muted">Tersedia: <?= $kamarTersedia ?></small>
                        </div>
                        <div class="rounded-circle bg-secondary bg-opacity-10 p-3">
                            <i class="bi bi-building fs-4 text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary bg-opacity-10 py-3">
                    <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Tindakan Cepat</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php if (empty($kamarList)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-check-circle text-success fs-1"></i>
                                <p class="mt-2">Semua kamar sudah bersih dan siap digunakan!</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kamar</th>
                                        <th>Status</th>
                                        <th>Tamu Terakhir</th>
                                        <th>Checkout</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kamarList as $kamar): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($kamar['nama_kamar']) ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?= $kamar['status'] === 'perlu dibersihkan' ? 'warning' : 'info' ?>">
                                                <?= ucwords($kamar['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($kamar['nama_tamu'] ?? '-') ?></td>
                                        <td><?= $kamar['tgl_checkout'] ? date('d/m/Y', strtotime($kamar['tgl_checkout'])) : '-' ?></td>
                                        <td>
                                            <a href="?page=kelola_kamar" class="btn btn-sm btn-primary">
                                                <i class="bi bi-arrow-right"></i> Kelola
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Testing Tools -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning bg-opacity-10 py-3">
                    <h5 class="mb-0"><i class="bi bi-tools"></i> Testing Tools</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Date Testing -->
                        <div class="col-md-6">
                            <div class="card border border-warning">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="bi bi-calendar-check"></i> Simulasi Tanggal
                                        <?php if (isset($_SESSION['test_date'])): ?>
                                            <span class="badge bg-warning">Testing Mode</span>
                                        <?php endif; ?>
                                    </h6>
                                    
                                    <!-- Current Date Display -->
                                    <div class="alert alert-info">
                                        Tanggal Saat Ini: <strong><?= date('d/m/Y', strtotime($currentDate)) ?></strong>
                                    </div>

                                    <form method="post" class="mt-3">
                                        <div class="input-group">
                                            <input type="date" 
                                                   name="test_date" 
                                                   class="form-control" 
                                                   value="<?= $currentDate ?>"
                                                   required>
                                            <button type="submit" 
                                                    name="set_test_date" 
                                                    class="btn btn-warning">
                                                <i class="bi bi-clock"></i> Set Tanggal
                                            </button>
                                            <?php if (isset($_SESSION['test_date'])): ?>
                                                <button type="submit" 
                                                        name="reset_test_date" 
                                                        class="btn btn-danger">
                                                    <i class="bi bi-x-circle"></i> Reset
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </form>

                                    <?php if (isset($_SESSION['test_date'])): ?>
                                        <div class="alert alert-warning mt-3 mb-0">
                                            <i class="bi bi-exclamation-triangle"></i> 
                                            Mode Testing Aktif: <?= date('d/m/Y', strtotime($_SESSION['test_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Status Update -->
                        <div class="col-md-6">
                            <div class="card border border-info">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="bi bi-arrow-repeat"></i> Update Status Cepat</h6>
                                    <form method="post" class="mt-3">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <select name="test_kamar" class="form-select" required>
                                                    <option value="">Pilih Kamar...</option>
                                                    <?php 
                                                    $kamarList = $conn->query("SELECT id_kamar, nama, status FROM kamar ORDER BY nama")->fetchAll();
                                                    foreach ($kamarList as $k): 
                                                    ?>
                                                    <option value="<?= $k['id_kamar'] ?>">
                                                        <?= htmlspecialchars($k['nama']) ?> 
                                                        (<?= ucwords($k['status'] ?: 'tersedia') ?>)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <select name="test_status" class="form-select" required>
                                                    <option value="">Pilih Status...</option>
                                                    <option value="tersedia">Tersedia</option>
                                                    <option value="perlu dibersihkan">Perlu Dibersihkan</option>
                                                    <option value="sedang dibersihkan">Sedang Dibersihkan</option>
                                                    <option value="maintenance">Maintenance</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" 
                                                        name="update_test_status" 
                                                        class="btn btn-info w-100">
                                                    <i class="bi bi-lightning"></i> Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.border-start {
    border-left-width: 4px !important;
}

.rounded-circle {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>