<?php
require_once 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$today = date('Y-m-d');

$total_vehicles = 0;
$delivered_vehicles = 0;
$active_custodies = 0;
$overdue_custodies = 0;

try {
    // 1. إجمالي المركبات
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM vehicles");
    $total_vehicles = $stmt1 ? (int)$stmt1->fetchColumn() : 0;

    // 2. العهد النشطة (نفس شرط ملف assignment_active.php بالملي)
    $stmt2 = $pdo->query("SELECT COUNT(*) FROM custody WHERE actual_return_date IS NULL OR actual_return_date = ''");
    $active_custodies = $stmt2 ? (int)$stmt2->fetchColumn() : 0;

    // 3. المركبات المسلمة (عدد السيارات الفريدة المسلمة حالياً بعهد نشطة)
    $stmt3 = $pdo->query("SELECT COUNT(DISTINCT vehicle_id) FROM custody WHERE actual_return_date IS NULL OR actual_return_date = ''");
    $delivered_vehicles = $stmt3 ? (int)$stmt3->fetchColumn() : 0;

    // 4. العهد المتأخرة (نفس شرط الفلتر بالملي)
    $stmt4 = $pdo->query("
        SELECT COUNT(*) FROM custody 
        WHERE (actual_return_date IS NULL OR actual_return_date = '')
          AND custody_type = 'مؤقتة' 
          AND expected_return_date IS NOT NULL 
          AND expected_return_date != '' 
          AND date(expected_return_date) < date('$today')
    ");
    $overdue_custodies = $stmt4 ? (int)$stmt4->fetchColumn() : 0;

} catch (Exception $e) {
    // في حال وجود أي خطأ
}
?>

<div class="container-fluid px-3 py-2" dir="rtl">
    <!-- عنوان الصفحة -->
    <div class="d-flex align-items-center mb-3">
        <h3 class="fw-bold mb-0 me-2 fs-4">الصفحة الرئيسية</h3>
        <i class="fas fa-home text-primary fs-4 ms-2"></i>
    </div>

    <!-- كروت الإحصائيات الأربعة -->
    <div class="row g-2.5 mb-3">
        <!-- كارت إجمالي المركبات -->
        <div class="col-md-6 col-lg-6 mb-2">
            <div class="card border-0 shadow-sm rounded-3 p-2 px-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-end">
                        <span class="text-muted d-block mb-1 small">إجمالي المركبات</span>
                        <h3 class="fw-bold text-dark mb-0 fs-3"><?= $total_vehicles ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-car text-primary fs-5"></i>
                    </div>
                </div>
                <div class="mt-2" style="height: 3px; background-color: #0d6efd; border-radius: 2px;"></div>
            </div>
        </div>

        <!-- كارت المركبات المسلمة -->
        <div class="col-md-6 col-lg-6 mb-2">
            <div class="card border-0 shadow-sm rounded-3 p-2 px-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-end">
                        <span class="text-muted d-block mb-1 small">المركبات المسلمة</span>
                        <h3 class="fw-bold text-dark mb-0 fs-3"><?= $delivered_vehicles ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-car-side text-success fs-5"></i>
                    </div>
                </div>
                <div class="mt-2" style="height: 3px; background-color: #198754; border-radius: 2px;"></div>
            </div>
        </div>

        <!-- كارت العهد النشطة -->
        <div class="col-md-6 col-lg-6 mb-2">
            <div class="card border-0 shadow-sm rounded-3 p-2 px-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-end">
                        <span class="text-muted d-block mb-1 small">العهد النشطة</span>
                        <h3 class="fw-bold text-dark mb-0 fs-3"><?= $active_custodies ?></h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-folder-open text-warning fs-5"></i>
                    </div>
                </div>
                <div class="mt-2" style="height: 3px; background-color: #ffc107; border-radius: 2px;"></div>
            </div>
        </div>

        <!-- كارت العهد المتأخرة -->
        <div class="col-md-6 col-lg-6 mb-2">
            <div class="card border-0 shadow-sm rounded-3 p-2 px-3 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-end">
                        <span class="text-muted d-block mb-1 small">العهد المتأخرة</span>
                        <h3 class="fw-bold text-dark mb-0 fs-3"><?= $overdue_custodies ?></h3>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-exclamation-triangle text-danger fs-5"></i>
                    </div>
                </div>
                <div class="mt-2" style="height: 3px; background-color: #dc3545; border-radius: 2px;"></div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>