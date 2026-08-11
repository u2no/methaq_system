<?php

session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/vehicle_helpers.php';

date_default_timezone_set('Asia/Riyadh');

$search = trim($_GET['q'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$deleteError = '';
$deleteSuccess = '';

/* حذف مركبة (يُنفَّذ قبل أي إخراج HTML حتى تعمل إعادة التوجيه بشكل صحيح) */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

    $deleteId = (int) ($_POST['id'] ?? 0);

    if ($deleteId > 0) {

        if (vehicle_has_custody_history($pdo, $deleteId)) {

            $deleteError = 'لا يمكن حذف هذه المركبة لأن لها سجل عهد سابق أو حالي. يجب الاحتفاظ بالسجل التاريخي.';

        } else {

            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = :id");
            $stmt->execute([':id' => $deleteId]);

            header('Location: vehicles.php?deleted=1');
            exit;

        }

    }

}

if (isset($_GET['deleted'])) {
    $deleteSuccess = 'تم حذف المركبة بنجاح.';
}

$vehicleTypes = fetch_vehicle_types($pdo);
$vehicles = fetch_vehicles($pdo, $search, $typeFilter, $statusFilter);

/* -------------------------------------------------------------- */
/* تصدير النتائج الحالية إلى ملف إكسل (xls)                        */
/* -------------------------------------------------------------- */

if (isset($_GET['export']) && $_GET['export'] === 'xls') {

    $filename = 'vehicles_' . date('Y-m-d') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF"; // BOM لعرض النصوص العربية بشكل صحيح في إكسل

    echo '<html dir="rtl"><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<tr>
            <th>م</th>
            <th>رقم اللوحة</th>
            <th>نوع المركبة</th>
            <th>الموديل</th>
            <th>اللون</th>
            <th>الحالة</th>
            <th>المستلم الحالي</th>
            <th>نوع العهدة الحالية</th>
            <th>ملاحظات</th>
          </tr>';

    foreach ($vehicles as $index => $v) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . htmlspecialchars($v['plate_number'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($v['type'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($v['model'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($v['color'] ?: '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($v['status'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($v['current_holder'] ?: '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($v['current_custody_type'] ?: '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($v['notes'] ?: '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }

    echo '</table>';
    echo '</body></html>';
    exit;

}

?>

<?php
include __DIR__ . '/includes/header.php';
?>


<style>

/* منطقة الصفحة */

.vehicles-area {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background-color: #f5f7fa;
}


.content {
    flex: 1;
    width: 100%;
    min-width: 0;
    padding: 24px;
    background-color: #f5f7fa;
}


/* شريط العنوان */

.page-top {
    background-color: white;
    padding: 15px 18px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 18px;
}


.page-title-wrap {
    display: flex;
    align-items: center;
    gap: 9px;
}


.page-title-wrap i {
    color: #1457c5;
    font-size: 20px;
}


.page-title-wrap h5 {
    margin: 0;
    font-size: 20px;
}


/* حاوية الجدول */

.table-card {
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 18px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.025);
}


/* البحث والفلترة والتصدير */

.table-tools {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}


.filter-form {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    flex: 1;
}


.filter-input,
.filter-select {
    height: 40px;
    border: 1px solid #d8e0ea;
    border-radius: 6px;
    background-color: white;
    color: #334155;
    font-family: Arial, Tahoma, sans-serif;
    font-size: 13px;
    outline: none;
}


.filter-input {
    min-width: 260px;
    padding: 0 12px;
}


.filter-select {
    min-width: 140px;
    padding: 0 10px;
}


.filter-input:focus,
.filter-select:focus {
    border-color: #0d6efd;
}


.filter-btn {
    height: 40px;
    padding: 0 18px;
    border: none;
    border-radius: 6px;
    background-color: #0d6efd;
    color: white;
    font-family: Arial, Tahoma, sans-serif;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
}


.reset-btn,
.add-btn {
    height: 40px;
    padding: 0 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border: 1px solid #d8e0ea;
    border-radius: 6px;
    background-color: white;
    color: #475569;
    text-decoration: none;
    font-size: 13px;
    white-space: nowrap;
}


.add-btn {
    background-color: #1457c5;
    border-color: #1457c5;
    color: white;
    font-weight: bold;
}


.add-btn:hover {
    background-color: #0f46a2;
    color: white;
}


.export-btn {
    height: 40px;
    padding: 0 16px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px solid #d8e0ea;
    border-radius: 6px;
    background-color: white;
    color: #334155;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
    white-space: nowrap;
}


.export-btn:hover,
.reset-btn:hover {
    background-color: #f8fafc;
    color: #1e293b;
}


/* الجدول */

.table-responsive {
    border: 1px solid #e5eaf0;
    border-radius: 7px;
    overflow-x: auto;
}


.vehicles-table {
    width: 100%;
    min-width: 1100px;
    margin: 0;
    border-collapse: collapse;
}


.vehicles-table th {
    padding: 12px 9px;
    background-color: #f8fafc;
    color: #334155;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    white-space: nowrap;
}


.vehicles-table td {
    padding: 12px 9px;
    color: #475569;
    border-bottom: 1px solid #edf2f7;
    font-size: 12px;
    text-align: center;
    vertical-align: middle;
}


.vehicles-table tbody tr:last-child td {
    border-bottom: none;
}


.vehicles-table tbody tr:hover {
    background-color: #fbfdff;
}


.status-badge {
    display: inline-block;
    min-width: 62px;
    padding: 5px 10px;
    border-radius: 14px;
    font-size: 11px;
    font-weight: bold;
}


.status-delivered {
    background-color: #dbeafe;
    color: #1d4ed8;
}


.status-available {
    background-color: #dcfce7;
    color: #15803d;
}


.empty-row {
    padding: 35px !important;
    color: #64748b !important;
    text-align: center !important;
}


.empty-value {
    color: #94a3b8;
}


.row-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}


.action-icon {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: 1px solid #d8e0ea;
    background-color: white;
    color: #475569;
    text-decoration: none;
    cursor: pointer;
}


.action-icon:hover {
    background-color: #f8fafc;
}


.action-icon.edit-icon:hover {
    color: #1457c5;
    border-color: #1457c5;
}


.action-icon.view-icon:hover {
    color: #334155;
}


.action-icon.delete-icon {
    color: #b91c1c;
}


.action-icon.delete-icon:hover {
    background-color: #fef2f2;
    border-color: #fecaca;
}


.delete-form {
    display: inline;
}


/* رسائل النجاح والخطأ */

.success-message {
    padding: 12px 16px;
    margin-bottom: 16px;
    background-color: #ecfdf3;
    border: 1px solid #bbf7d0;
    border-radius: 7px;
    color: #166534;
    font-size: 13px;
}


.error-message {
    padding: 12px 16px;
    margin-bottom: 16px;
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 7px;
    color: #b91c1c;
    font-size: 13px;
}


/* شاشات صغيرة */

@media (max-width: 900px) {

    .content {
        padding: 16px;
    }

    .filter-form {
        width: 100%;
    }

    .filter-input,
    .filter-select {
        width: 100%;
        min-width: 0;
    }

    .filter-btn,
    .reset-btn {
        flex: 1;
    }

}

</style>


<?php
include __DIR__ . '/includes/sidebar.php';
?>


<div class="vehicles-area">


<main class="content">


    <!-- عنوان الصفحة + الشعار -->

    <div class="page-top d-flex justify-content-between align-items-center">

        <div class="page-title-wrap">
            <i class="fa-solid fa-car"></i>
            <h5 class="fw-bold text-dark">المركبات</h5>
        </div>

        <div>
            <img
                src="mod_logo.png"
                alt="شعار وزارة الدفاع"
                style="height: 35px; width: auto;"
                class="img-fluid"
            >
        </div>

    </div>


    <?php if ($deleteSuccess !== ''): ?>
        <div class="success-message">✓ <?php echo htmlspecialchars($deleteSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($deleteError !== ''): ?>
        <div class="error-message"><?php echo htmlspecialchars($deleteError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>


    <!-- الجدول والفلترة والتصدير -->

    <div class="table-card">

        <div class="table-tools">

            <!-- البحث والفلاتر -->

            <form method="GET" action="" class="filter-form">

                <input
                    type="text"
                    name="q"
                    class="filter-input"
                    placeholder="ابحث برقم اللوحة..."
                    value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                >

                <select name="type" class="filter-select">
                    <option value="">كل الأنواع</option>
                    <?php foreach ($vehicleTypes as $t): ?>
                        <option value="<?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $typeFilter === $t ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status" class="filter-select">
                    <option value="">كل الحالات</option>
                    <?php foreach (vehicle_status_options() as $s): ?>
                        <option value="<?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $statusFilter === $s ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="filter-btn">تصفية</button>

                <a href="vehicles.php" class="reset-btn">إعادة تعيين</a>

            </form>

            <!-- إضافة وتصدير -->

            <div class="d-flex gap-2">

                <a
                    href="?q=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&export=xls"
                    class="export-btn"
                >
                    <i class="fa-solid fa-file-excel"></i>
                    <span>تصدير</span>
                </a>

                <a href="vehicle_form.php" class="add-btn">
                    <i class="fa-solid fa-plus"></i>
                    <span>إضافة مركبة</span>
                </a>

            </div>

        </div>


        <!-- الجدول -->

        <div class="table-responsive">

            <table class="vehicles-table">

                <thead>
                    <tr>
                        <th>م</th>
                        <th>رقم اللوحة</th>
                        <th>نوع المركبة</th>
                        <th>الموديل</th>
                        <th>اللون</th>
                        <th>الحالة</th>
                        <th>المستلم الحالي</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($vehicles)): ?>

                        <tr>
                            <td colspan="8" class="empty-row">
                                لا توجد مركبات مطابقة لبحثك أو الفلاتر المحددة.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($vehicles as $index => $vehicle): ?>

                            <tr>
                                <td><?php echo $index + 1; ?></td>

                                <td><?php echo htmlspecialchars($vehicle['plate_number'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?php echo htmlspecialchars($vehicle['type'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?php echo htmlspecialchars($vehicle['model'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?php echo htmlspecialchars($vehicle['color'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>

                                <td>
                                    <span class="<?php echo vehicle_status_badge_class($vehicle['status']); ?>">
                                        <?php echo htmlspecialchars($vehicle['status'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($vehicle['current_holder'])): ?>
                                        <?php echo htmlspecialchars($vehicle['current_holder'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php else: ?>
                                        <span class="empty-value">لا يوجد</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="row-actions">

                                        <a
                                            href="vehicle_form.php?id=<?php echo (int) $vehicle['id']; ?>"
                                            class="action-icon edit-icon"
                                            title="تعديل"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <a
                                            href="vehicle_details.php?id=<?php echo (int) $vehicle['id']; ?>"
                                            class="action-icon view-icon"
                                            title="عرض التفاصيل"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        <form
                                            method="POST"
                                            action=""
                                            class="delete-form"
                                            onsubmit="return confirm('هل أنت متأكد من حذف هذه المركبة؟ لا يمكن التراجع عن هذا الإجراء.');"
                                        >
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $vehicle['id']; ?>">
                                            <button type="submit" class="action-icon delete-icon" title="حذف">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

</div>


<?php
include __DIR__ . '/includes/footer.php';
?>
