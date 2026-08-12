<?php

session_start();

require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Riyadh');

$person_id = $_GET['id'] ?? '';

if (empty($person_id)) {
    header('Location: persons.php');
    exit;
}


/* جلب بيانات الشخص */

$fetch = $pdo->prepare("SELECT * FROM persons WHERE id = ?");
$fetch->execute([$person_id]);
$person = $fetch->fetch();

if (!$person) {
    header('Location: persons.php?notfound=1');
    exit;
}


/* جلب السجل التاريخي الكامل لعهد هذا الشخص */

$historyQuery = $pdo->prepare("
    SELECT
        c.id,
        c.custody_type,
        c.start_date,
        c.expected_return_date,
        c.actual_return_date,
        c.notes,

        v.type AS vehicle_type,
        v.plate_number

    FROM custody c

    INNER JOIN vehicles v
        ON v.id = c.vehicle_id

    WHERE c.person_id = ?

    ORDER BY c.id DESC
");

$historyQuery->execute([$person_id]);
$history = $historyQuery->fetchAll();


/* تحديد المركبة الحالية إن وجدت */

$currentVehicle = null;

foreach ($history as $row) {

    if (empty($row['actual_return_date'])) {
        $currentVehicle = $row;
        break;
    }

}

$today = date('Y-m-d');


function displayDate($date)
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }

    return date('Y/m/d', strtotime($date));
}


function custodyStatusLabel($row, $today)
{
    if (!empty($row['actual_return_date'])) {
        return ['مكتملة', 'status-done'];
    }

    if (
        $row['custody_type'] === 'مؤقتة' &&
        !empty($row['expected_return_date']) &&
        $row['expected_return_date'] < $today
    ) {
        return ['متأخرة', 'status-late'];
    }

    return ['نشطة', 'status-active'];
}

?>


<?php include __DIR__ . '/includes/header.php'; ?>


<style>

.persons-area {
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

.page-title-wrap h5 {
    margin: 0;
    font-size: 20px;
}

.info-card {
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 22px;
    margin-bottom: 18px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.025);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.info-item span {
    display: block;
    color: #829ab1;
    font-size: 12px;
    margin-bottom: 5px;
}

.info-item strong {
    display: block;
    color: #102a43;
    font-size: 15px;
}

.info-actions {
    display: flex;
    gap: 8px;
    margin-top: 20px;
}

.edit-btn {
    padding: 9px 18px;
    background-color: #1457c5;
    border: none;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
}

.edit-btn:hover {
    background-color: #0f46a2;
    color: white;
}

.back-btn {
    padding: 9px 18px;
    background-color: white;
    border: 1px solid #d9e2ec;
    border-radius: 6px;
    color: #243b53;
    text-decoration: none;
    font-size: 13px;
}

.back-btn:hover {
    background-color: #f8fafc;
}

.section-title {
    margin: 24px 0 12px;
    color: #102a43;
    font-size: 16px;
    font-weight: bold;
}

.table-card {
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 18px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.025);
}

.table-responsive {
    border: 1px solid #e5eaf0;
    border-radius: 7px;
    overflow-x: auto;
}

.history-table {
    width: 100%;
    min-width: 950px;
    margin: 0;
    border-collapse: collapse;
}

.history-table th {
    padding: 12px 9px;
    background-color: #f8fafc;
    color: #334155;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    white-space: nowrap;
}

.history-table td {
    padding: 12px 9px;
    color: #475569;
    border-bottom: 1px solid #edf2f7;
    font-size: 12px;
    text-align: center;
    vertical-align: middle;
}

.history-table tbody tr:last-child td {
    border-bottom: none;
}

.status-badge {
    display: inline-block;
    min-width: 62px;
    padding: 5px 10px;
    border-radius: 14px;
    font-size: 11px;
    font-weight: bold;
}

.status-active { background-color: #dcfce7; color: #15803d; }
.status-late   { background-color: #fee2e2; color: #b91c1c; }
.status-done   { background-color: #e2e8f0; color: #475569; }

.empty-row {
    padding: 35px !important;
    color: #64748b !important;
    text-align: center !important;
}

@media (max-width: 900px) {
    .content { padding: 16px; }
    .info-grid { grid-template-columns: repeat(2, 1fr); }
}

</style>


<?php include __DIR__ . '/includes/sidebar.php'; ?>


<div class="persons-area">

<main class="content">

    <div class="page-top d-flex justify-content-between align-items-center">

        <div class="page-title-wrap">
            <i class="fa-solid fa-id-card fs-5 text-primary"></i>
            <h5 class="fw-bold text-dark">تفاصيل الشخص</h5>
        </div>

        <div>
            <img src="mod_logo.png" alt="شعار وزارة الدفاع" style="height: 35px; width: auto;" class="img-fluid">
        </div>

    </div>

    <div class="info-card">

        <div class="info-grid">

            <div class="info-item">
                <span>الاسم الكامل</span>
                <strong><?php echo htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>

            <div class="info-item">
                <span>رقم الجوال</span>
                <strong><?php echo htmlspecialchars($person['phone'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>

            <div class="info-item">
                <span>حالة الشخص</span>
                <strong>
                    <?php if ($person['status'] === 'نشط'): ?>
                        <span class="status-badge status-active">نشط</span>
                    <?php else: ?>
                        <span class="status-badge status-done">غير نشط</span>
                    <?php endif; ?>
                </strong>
            </div>

            <div class="info-item">
                <span>المركبة الحالية</span>
                <strong>
                    <?php if ($currentVehicle): ?>
                        <?php echo htmlspecialchars($currentVehicle['vehicle_type'] . ' - ' . $currentVehicle['plate_number'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php else: ?>
                        لا يوجد
                    <?php endif; ?>
                </strong>
            </div>

        </div>

        <div class="info-actions">

            <a href="person_edit.php?id=<?php echo $person['id']; ?>" class="edit-btn">
                <i class="fa-solid fa-pen"></i> تعديل البيانات
            </a>

            <a href="persons.php" class="back-btn">
                رجوع لقائمة الأشخاص
            </a>

        </div>

    </div>

    <div class="section-title">السجل التاريخي للعهد</div>

    <div class="table-card">

        <div class="table-responsive">

            <table class="history-table">

                <thead>
                    <tr>
                        <th>م</th>
                        <th>المركبة</th>
                        <th>رقم اللوحة</th>
                        <th>نوع العهدة</th>
                        <th>تاريخ البداية</th>
                        <th>تاريخ التسليم المتوقع</th>
                        <th>تاريخ الاستلام الفعلي</th>
                        <th>الحالة</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($history) === 0): ?>

                    <tr>
                        <td colspan="9" class="empty-row">
                            لا يوجد سجل عهد سابق لهذا الشخص
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($history as $index => $row): ?>

                        <?php [$statusLabel, $statusClass] = custodyStatusLabel($row, $today); ?>

                        <tr>

                            <td><?php echo $index + 1; ?></td>

                            <td><?php echo htmlspecialchars($row['vehicle_type'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?php echo htmlspecialchars($row['plate_number'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?php echo htmlspecialchars($row['custody_type'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?php echo displayDate($row['start_date']); ?></td>

                            <td><?php echo displayDate($row['expected_return_date']); ?></td>

                            <td><?php echo displayDate($row['actual_return_date']); ?></td>

                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </td>

                            <td><?php echo $row['notes'] ? htmlspecialchars($row['notes'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

</div>


<?php include __DIR__ . '/includes/footer.php'; ?>
