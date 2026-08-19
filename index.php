<?php
require_once 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$today = date('Y-m-d');

$total_vehicles = 0;
$delivered_vehicles = 0;
$active_custodies = 0;
$overdue_custodies = 0;
$overdue_list = [];

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

    // جلب قائمة العهد المتأخرة التي تحتاج إلى إجراء
    $stmt_list = $pdo->query("
        SELECT c.*, v.type as vehicle_type, v.model, v.plate_number, p.name as person_name
        FROM custody c
        LEFT JOIN vehicles v ON c.vehicle_id = v.id
        LEFT JOIN persons p ON c.person_id = p.id
        WHERE (c.actual_return_date IS NULL OR c.actual_return_date = '')
          AND c.custody_type = 'مؤقتة'
          AND c.expected_return_date IS NOT NULL
          AND c.expected_return_date != ''
          AND date(c.expected_return_date) < date('$today')
        ORDER BY c.expected_return_date ASC
    ");
    $overdue_list = $stmt_list ? $stmt_list->fetchAll(PDO::FETCH_ASSOC) : [];

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

    <!-- العهد المتأخرة التي تحتاج إلى إجراء -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-danger"><i class="fas fa-exclamation-circle me-1"></i> العهد المتأخرة (تحتاج إلى إجراء)</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center small">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم المستلم</th>
                                <th>نوع المركبة</th>
                                <th>رقم اللوحة</th>
                                <th>نوع العهدة</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>تأخر (أيام)</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($overdue_list)): ?>
                                <?php foreach ($overdue_list as $index => $item):
                                    $diff = (strtotime($today) - strtotime($item['expected_return_date'])) / (60 * 60 * 24);
                                    $days = max(1, floor($diff));
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($item['person_name'] ?? 'غير محدد') ?></strong></td>
                                    <td><?= htmlspecialchars($item['vehicle_type'] ?? 'مركبة') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['plate_number'] ?? '-') ?></span></td>
                                    <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($item['custody_type'] ?? 'مؤقتة') ?></span></td>
                                    <td><?= htmlspecialchars($item['expected_return_date']) ?></td>
                                    <td><span class="text-danger fw-bold"><?= $days ?></span></td>
                                    <td><a href="assignment_active.php" class="btn btn-sm btn-outline-secondary py-0">عرض</a></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-muted py-3">لا توجد عهد متأخرة حالياً.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>