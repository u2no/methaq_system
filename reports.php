<?php
require_once 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// 1. جلب العهد المتأخرة (العهد المؤقتة النشطة التي تجاوزت تاريخ due_date ولم تُستلم)
$overdue_custodies = [];
try {
    $overdue_stmt = $pdo->query("
        SELECT a.*, v.plate_number, v.make, v.model, p.full_name 
        FROM assignments a
        LEFT JOIN vehicles v ON a.vehicle_id = v.id
        LEFT JOIN persons p ON a.person_id = p.id
        WHERE (a.returned_date IS NULL OR a.returned_date = '') 
          AND a.due_date IS NOT NULL 
          AND a.due_date < DATE('now')
    ");
    $overdue_custodies = $overdue_stmt->fetchAll();
} catch (PDOException $e) {
    // في حال وجود مشكلة في الاستعلام
}

// 2. جلب جميع العهد للتقرير التاريخي الشامل
$all_custodies = [];
try {
    $all_stmt = $pdo->query("
        SELECT a.*, v.plate_number, v.make, v.model, p.full_name 
        FROM assignments a
        LEFT JOIN vehicles v ON a.vehicle_id = v.id
        LEFT JOIN persons p ON a.person_id = p.id
        ORDER BY a.id DESC
    ");
    $all_custodies = $all_stmt->fetchAll();
} catch (PDOException $e) {
    // في حال وجود مشكلة في الاستعلام
}
?>

<div class="container-fluid mt-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-line text-primary"></i> التنبيهات والتقارير العامة</h2>
        <button onclick="window.print()" class="btn btn-dark"><i class="fas fa-print"></i> طباعة التقرير</button>
    </div>

    <!-- كروت التنبيهات السريعة -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card border-danger shadow-sm h-100">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> تنبيهات العهد المتأخرة</h5>
                    <span class="badge bg-white text-danger fs-6"><?= count($overdue_custodies) ?> عهدة</span>
                </div>
                <div class="card-body">
                    <?php if (empty($overdue_custodies)): ?>
                        <p class="text-success mb-0"><i class="fas fa-check-circle"></i> لا توجد أي عهد متأخرة حالياً.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($overdue_custodies as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <strong><?= htmlspecialchars($item['full_name'] ?? 'غير محدد') ?></strong> - 
                                        <?= htmlspecialchars(($item['make'] ?? '') . ' ' . ($item['model'] ?? '')) ?> 
                                        (<?= htmlspecialchars($item['plate_number'] ?? 'بدون لوحة') ?>)
                                        <br><small class="text-danger">تاريخ التسليم المتوقع: <?= htmlspecialchars($item['due_date'] ?? '-') ?></small>
                                    </div>
                                    <span class="badge bg-danger">متأخرة</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> متابعة حالة النظام</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">متابعة دورية للعهد المؤقتة والدائمة، ومراجعة سجلات الاستلام والملاحظات المسجلة.</p>
                    <span class="badge bg-warning text-dark">متابعة نشطة</span>
                </div>
            </div>
        </div>
    </div>

    <!-- التقرير التاريخي وسجل العهد -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> التقرير التاريخي للعهد والمركبات</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>المستلم</th>
                            <th>المركبة</th>
                            <th>رقم اللوحة</th>
                            <th>نوع العهدة</th>
                            <th>تاريخ البدء</th>
                            <th>التسليم المتوقع</th>
                            <th>الاستلام الفعلي</th>
                            <th>الحسم / رقم القرار</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_custodies)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">لا توجد بيانات مسجلة في التقرير حالياً.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_custodies as $row): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['full_name'] ?? 'غير محدد') ?></strong></td>
                                    <td><?= htmlspecialchars(($row['make'] ?? '') . ' ' . ($row['model'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($row['plate_number'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($row['assignment_type'] ?? '') === 'دائمة' ? 'info' : 'secondary' ?>">
                                            <?= htmlspecialchars($row['assignment_type'] ?? 'مؤقتة') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['start_date'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['due_date'] ?? 'دائمة / غير محدد') ?></td>
                                    <td><?= htmlspecialchars($row['returned_date'] ?? 'لم تُستلم بعد') ?></td>
                                    <td>
                                        <?php if (!empty($row['has_deduction']) || !empty($row['decision_reference'])): ?>
                                            <small class="text-danger">
                                                حسم: <?= htmlspecialchars($row['has_deduction'] ?? '0') ?> <br>
                                                قرار: <?= htmlspecialchars($row['decision_reference'] ?? '-') ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['returned_date'])): ?>
                                            <span class="badge bg-success">مستلمة (مغلقة)</span>
                                        <?php elseif (!empty($row['due_date']) && $row['due_date'] < date('Y-m-d')): ?>
                                            <span class="badge bg-danger">متأخرة</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">نشطة</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>