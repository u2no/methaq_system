<?php

session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/vehicle_helpers.php';

date_default_timezone_set('Asia/Riyadh');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$vehicle = fetch_vehicle_by_id($pdo, $id);

if (!$vehicle) {
    header('Location: vehicles.php');
    exit;
}

$history = fetch_vehicle_custody_history($pdo, $id);

function displayDate($date)
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date('Y/m/d', strtotime($date));
}

?>


<?php
include __DIR__ . '/includes/header.php';
?>


<style>

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


.white-box {
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 22px;
    margin-bottom: 18px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.025);
}


.details-title {
    margin-bottom: 18px;
    color: #000000;
    font-size: 16px;
    font-weight: bold;
}


.details-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 10px;
}


.detail-item {
    background-color: #f8fafc;
    border-radius: 6px;
    padding: 12px;
    text-align: center;
}


.detail-label {
    display: block;
    margin-bottom: 6px;
    color: #334155;
    font-size: 11px;
    font-weight: bold;
}


.detail-value {
    color: #243b53;
    font-size: 13px;
    font-weight: bold;
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


.table-responsive {
    border: 1px solid #e5eaf0;
    border-radius: 7px;
    overflow-x: auto;
}


.vehicles-table {
    width: 100%;
    min-width: 900px;
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


.empty-row {
    padding: 35px !important;
    color: #64748b !important;
    text-align: center !important;
}


.empty-value {
    color: #94a3b8;
}


.edit-btn {
    height: 40px;
    padding: 0 16px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: none;
    border-radius: 6px;
    background-color: #1457c5;
    color: white;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
}


.edit-btn:hover {
    background-color: #0f46a2;
    color: white;
}


@media (max-width: 900px) {

    .details-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}

</style>


<?php
include __DIR__ . '/includes/sidebar.php';
?>


<div class="vehicles-area">


<main class="content">


    <div class="page-top d-flex justify-content-between align-items-center">

        <div class="page-title-wrap">
            <i class="fa-solid fa-car"></i>
            <h5 class="fw-bold text-dark">
                تفاصيل المركبة
                <span class="text-muted fs-6">— <?php echo htmlspecialchars($vehicle['plate_number'], ENT_QUOTES, 'UTF-8'); ?></span>
            </h5>
        </div>

        <a href="vehicle_form.php?id=<?php echo (int) $vehicle['id']; ?>" class="edit-btn">
            <i class="fa-solid fa-pen"></i>
            <span>تعديل البيانات</span>
        </a>

    </div>


    <!-- بيانات المركبة -->

    <div class="white-box">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="details-title mb-0"><?php echo htmlspecialchars($vehicle['plate_number'], ENT_QUOTES, 'UTF-8'); ?></div>
            <span class="<?php echo vehicle_status_badge_class($vehicle['status']); ?>">
                <?php echo htmlspecialchars($vehicle['status'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>

        <div class="details-grid">

            <div class="detail-item">
                <span class="detail-label">نوع المركبة</span>
                <span class="detail-value"><?php echo htmlspecialchars($vehicle['type'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">الموديل</span>
                <span class="detail-value"><?php echo htmlspecialchars($vehicle['model'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">اللون</span>
                <span class="detail-value"><?php echo htmlspecialchars($vehicle['color'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">المستلم الحالي</span>
                <span class="detail-value"><?php echo $vehicle['current_holder'] ? htmlspecialchars($vehicle['current_holder'], ENT_QUOTES, 'UTF-8') : '-'; ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">نوع العهدة الحالية</span>
                <span class="detail-value"><?php echo $vehicle['current_custody_type'] ? htmlspecialchars($vehicle['current_custody_type'], ENT_QUOTES, 'UTF-8') : '-'; ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">ملاحظات</span>
                <span class="detail-value"><?php echo $vehicle['notes'] ? htmlspecialchars($vehicle['notes'], ENT_QUOTES, 'UTF-8') : '-'; ?></span>
            </div>

        </div>

    </div>


    <!-- سجل عهد المركبة -->

    <div class="white-box">

        <div class="details-title">
            <i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i>
            سجل عهد المركبة
        </div>

        <div class="table-responsive">

            <table class="vehicles-table">

                <thead>
                    <tr>
                        <th>م</th>
                        <th>رقم العهدة</th>
                        <th>المستلم</th>
                        <th>النوع</th>
                        <th>تاريخ البداية</th>
                        <th>تاريخ الاسترجاع</th>
                        <th>الحالة</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($history)): ?>

                        <tr>
                            <td colspan="7" class="empty-row">لا يوجد سجل عهد لهذه المركبة بعد.</td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($history as $index => $record): ?>

                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>#<?php echo (int) $record['id']; ?></td>
                                <td><?php echo htmlspecialchars($record['person_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($record['custody_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo displayDate($record['start_date']); ?></td>
                                <td>
                                    <?php if (!empty($record['actual_return_date'])): ?>
                                        <?php echo displayDate($record['actual_return_date']); ?>
                                    <?php else: ?>
                                        <span class="empty-value">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($record['display_status'], ENT_QUOTES, 'UTF-8'); ?></td>
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