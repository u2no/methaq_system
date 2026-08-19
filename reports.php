<?php
require_once 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$today = date('Y-m-d');

// 1. جلب البيانات والإحصائيات الحقيقية من قاعدة البيانات
$total_active = 0;
$active_normal = 0;
$overdue_count = 0;
$perm_count = 0;
$temp_count = 0;

try {
    // إجمالي العهد النشطة
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM custody WHERE actual_return_date IS NULL OR actual_return_date = ''");
    $total_active = $stmt1 ? (int)$stmt1->fetchColumn() : 0;

    // العهد الدائمة
    $stmt2 = $pdo->query("SELECT COUNT(*) FROM custody WHERE (actual_return_date IS NULL OR actual_return_date = '') AND custody_type = 'دائمة'");
    $perm_count = $stmt2 ? (int)$stmt2->fetchColumn() : 0;

    // العهد المؤقتة
    $stmt3 = $pdo->query("SELECT COUNT(*) FROM custody WHERE (actual_return_date IS NULL OR actual_return_date = '') AND custody_type = 'مؤقتة'");
    $temp_count = $stmt3 ? (int)$stmt3->fetchColumn() : 0;

    // العهد المتأخرة
    $stmt4 = $pdo->query("
        SELECT COUNT(*) FROM custody 
        WHERE (actual_return_date IS NULL OR actual_return_date = '')
          AND custody_type = 'مؤقتة' 
          AND expected_return_date IS NOT NULL 
          AND expected_return_date != '' 
          AND date(expected_return_date) < date('$today')
    ");
    $overdue_count = $stmt4 ? (int)$stmt4->fetchColumn() : 0;

    $active_normal = max(0, $total_active - $overdue_count);

} catch (Exception $e) {}

// 2. حساب بيانات الرسوم البيانية شهرياً بشكل ديناميكي حقيقي من قاعدة البيانات (2026)
$months_labels = ['أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس'];
$perm_monthly = [0, 0, 0, 0, 0];
$temp_monthly = [0, 0, 0, 0, 0];
$delivered_monthly = [0, 0, 0, 0, 0];
$returned_monthly = [0, 0, 0, 0, 0];

try {
    for ($i = 0; $i < 5; $i++) {
        $m = sprintf("%02d", $i + 4); // الأشهر من 04 إلى 08

        // العهد الدائمة والمؤقتة شهرياً
        $s1 = $pdo->query("SELECT COUNT(*) FROM custody WHERE custody_type = 'دائمة' AND strftime('%m', start_date) = '$m'");
        if ($s1) $perm_monthly[$i] = (int)$s1->fetchColumn();

        $s2 = $pdo->query("SELECT COUNT(*) FROM custody WHERE custody_type = 'مؤقتة' AND strftime('%m', start_date) = '$m'");
        if ($s2) $temp_monthly[$i] = (int)$s2->fetchColumn();

        // العهد المسلمة والمسترجعة شهرياً
        $s3 = $pdo->query("SELECT COUNT(*) FROM custody WHERE strftime('%m', start_date) = '$m'");
        if ($s3) $delivered_monthly[$i] = (int)$s3->fetchColumn();

        $s4 = $pdo->query("SELECT COUNT(*) FROM custody WHERE actual_return_date IS NOT NULL AND actual_return_date != '' AND strftime('%m', actual_return_date) = '$m'");
        if ($s4) $returned_monthly[$i] = (int)$s4->fetchColumn();
    }
} catch (Exception $e) {}
?>

<!-- تضمين مكتبة Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid px-4 py-3" dir="rtl" style="background-color: #f8f9fa;">

    <!-- الشريط العلوي والتصفية -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-pie text-primary me-2"></i> التقارير والتنبيهات</h3>
            <p class="text-muted small mb-0">عرض تحليلي لإحصائيات العهد والتنبيهات</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-primary px-3 rounded-3 shadow-sm">
            <i class="fas fa-download me-1"></i> تصدير التقرير
        </button>
    </div>

    <!-- شريط الفلاتر -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 p-3 bg-white">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1 fw-bold">الفترة الزمنية</label>
                <input type="text" class="form-control text-center text-muted" value="01/04/2026 - 31/08/2026" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1 fw-bold">نوع العهدة</label>
                <select class="form-select">
                    <option selected>الكل</option>
                    <option>دائمة</option>
                    <option>مؤقتة</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1 fw-bold">حالة العهدة</label>
                <select class="form-select">
                    <option selected>الكل</option>
                    <option>نشطة</option>
                    <option>متأخرة</option>
                </select>
            </div>
        </div>
    </div>

    <!-- الصف الأول: الدوائر البيانية وكارت التنبيه -->
    <div class="row g-3 mb-4">
        <!-- دونات 1: حالات العهد النشطة -->
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0">حالات العهد النشطة</h6>
                    <span class="badge bg-light text-success"><i class="fas fa-check-circle"></i></span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div style="width: 130px; height: 130px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small"><i class="fas fa-circle text-success me-1"></i> نشطة</span>
                            <strong><?= $active_normal ?></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small"><i class="fas fa-circle text-danger me-1"></i> متأخرة</span>
                            <strong><?= $overdue_count ?></strong>
                        </div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between align-items-center fw-bold">
                            <span>الإجمالي</span>
                            <span><?= $total_active ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- دونات 2: العهد حسب النوع -->
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0">العهد النشطة حسب نوع العهدة</h6>
                    <span class="badge bg-light text-primary"><i class="fas fa-pie-chart"></i></span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div style="width: 130px; height: 130px;">
                        <canvas id="typeChart"></canvas>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small"><i class="fas fa-circle text-primary me-1"></i> دائمة</span>
                            <strong><?= $perm_count ?></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small"><i class="fas fa-circle text-warning me-1"></i> مؤقتة</span>
                            <strong><?= $temp_count ?></strong>
                        </div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between align-items-center fw-bold">
                            <span>الإجمالي</span>
                            <span><?= $total_active ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- كارت العهد المتأخرة العاجل -->
        <div class="col-lg-4 col-md-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 text-center d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">العهد المتأخرة</h6>
                    <span class="badge bg-danger bg-opacity-10 text-danger p-2"><i class="fas fa-exclamation-triangle"></i></span>
                </div>
                <div class="my-3">
                    <h1 class="display-4 fw-bold text-danger mb-0"><?= $overdue_count ?></h1>
                    <span class="text-muted">عهدة متأخرة</span>
                </div>
                <div class="alert alert-danger mb-0 p-2 text-danger small border-0 bg-danger bg-opacity-10 rounded-3">
                    <i class="fas fa-exclamation-circle me-1"></i> تحتاج إلى إجراء عاجل
                </div>
            </div>
        </div>
    </div>

    <!-- الصف الثاني: الأعمدة والمنحنى البياني (الديناميكية) -->
    <div class="row g-3 mb-4">
        <!-- أعمدة بيانية شهرياً -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <h6 class="fw-bold mb-3">العهد النشطة حسب نوع العهدة (شهرياً)</h6>
                <div style="height: 220px;">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>

        <!-- منحنى بياني شهرياً -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <h6 class="fw-bold mb-3">عدد العهد المسلمة والمسترجعة (شهرياً)</h6>
                <div style="height: 220px;">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- إعداد الرسوم البيانية بديناميكية تامة من قاعدة البيانات -->
<script>
// 1. دونات حالات العهد
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['نشطة', 'متأخرة'],
        datasets: [{
            data: [<?= $active_normal ?>, <?= $overdue_count ?>],
            backgroundColor: ['#198754', '#dc3545']
        }]
    },
    options: { cutout: '70%', plugins: { legend: { display: false } } }
});

// 2. دونات نوع العهدة
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: ['دائمة', 'مؤقتة'],
        datasets: [{
            data: [<?= $perm_count ?>, <?= $temp_count ?>],
            backgroundColor: ['#0d6efd', '#ffc107']
        }]
    },
    options: { cutout: '70%', plugins: { legend: { display: false } } }
});

// 3. الأعمدة الشهرية (بيانات حقيقية من قاعدة البيانات)
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($months_labels) ?>,
        datasets: [
            { label: 'دائمة', data: <?= json_encode($perm_monthly) ?>, backgroundColor: '#0d6efd' },
            { label: 'مؤقتة', data: <?= json_encode($temp_monthly) ?>, backgroundColor: '#ffc107' }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// 4. المنحنى البياني (بيانات حقيقية من قاعدة البيانات)
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months_labels) ?>,
        datasets: [
            { label: 'مسلمة', data: <?= json_encode($delivered_monthly) ?>, borderColor: '#198754', backgroundColor: '#198754', tension: 0.3 },
            { label: 'مسترجعة', data: <?= json_encode($returned_monthly) ?>, borderColor: '#0d6efd', backgroundColor: '#0d6efd', tension: 0.3 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>