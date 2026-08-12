<?php

session_start();

require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Riyadh');

$today = date('Y-m-d');

$search = trim($_GET['q'] ?? '');

$conditions = [
    "(c.actual_return_date IS NULL OR c.actual_return_date = '')"
];

$params = [];

if ($search !== '') {

    $conditions[] = "(p.name LIKE :search_name OR p.phone LIKE :search_phone)";
    $params[':search_name'] = '%' . $search . '%';
    $params[':search_phone'] = '%' . $search . '%';

}

$whereSql = implode(' AND ', $conditions);


$sql = "
    SELECT
        p.id,
        p.name,
        p.phone,

        v.type AS vehicle_type,
        v.plate_number,

        c.custody_type,
        c.start_date,
        c.expected_return_date,

        CASE
            WHEN
                c.custody_type = 'مؤقتة'
                AND c.expected_return_date IS NOT NULL
                AND c.expected_return_date <> ''
                AND date(c.expected_return_date) < date(:today_status)
            THEN 'متأخرة'
            ELSE 'نشطة'
        END AS display_status

    FROM custody c

    INNER JOIN persons p
        ON p.id = c.person_id

    INNER JOIN vehicles v
        ON v.id = c.vehicle_id

    WHERE $whereSql

    ORDER BY p.name ASC
";

$params[':today_status'] = $today;

$query = $pdo->prepare($sql);
$query->execute($params);
$rows = $query->fetchAll();


/* تصدير النتائج CSV */

if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    $filename = 'persons_active_custody_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'رقم',
        'الاسم الكامل',
        'رقم الجوال',
        'المركبة',
        'رقم اللوحة',
        'نوع العهدة',
        'تاريخ البداية',
        'تاريخ التسليم المتوقع',
        'الحالة',
    ]);

    foreach ($rows as $index => $row) {

        fputcsv($output, [
            $index + 1,
            $row['name'],
            $row['phone'],
            $row['vehicle_type'],
            $row['plate_number'],
            $row['custody_type'],
            $row['start_date'],
            $row['expected_return_date'] ?: '-',
            $row['display_status'],
        ]);

    }

    fclose($output);
    exit;

}


function displayDate($date)
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }

    return date('Y/m/d', strtotime($date));
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

.table-card {
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 18px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.025);
}

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

.filter-input {
    height: 40px;
    min-width: 280px;
    padding: 0 12px;
    border: 1px solid #d8e0ea;
    border-radius: 6px;
    background-color: white;
    color: #334155;
    font-family: Arial, Tahoma, sans-serif;
    font-size: 13px;
    outline: none;
}

.filter-input:focus {
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

.reset-btn {
    height: 40px;
    padding: 0 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #d8e0ea;
    border-radius: 6px;
    background-color: white;
    color: #475569;
    text-decoration: none;
    font-size: 13px;
}

.export-btn,
.print-btn {
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
    cursor: pointer;
}

.export-btn:hover,
.print-btn:hover,
.reset-btn:hover {
    background-color: #f8fafc;
    color: #1e293b;
}

.table-responsive {
    border: 1px solid #e5eaf0;
    border-radius: 7px;
    overflow-x: auto;
}

.report-table {
    width: 100%;
    min-width: 1000px;
    margin: 0;
    border-collapse: collapse;
}

.report-table th {
    padding: 12px 9px;
    background-color: #f8fafc;
    color: #334155;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    white-space: nowrap;
}

.report-table td {
    padding: 12px 9px;
    color: #475569;
    border-bottom: 1px solid #edf2f7;
    font-size: 12px;
    text-align: center;
    vertical-align: middle;
}

.report-table tbody tr:last-child td {
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

.empty-row {
    padding: 35px !important;
    color: #64748b !important;
    text-align: center !important;
}

@media (max-width: 900px) {
    .content { padding: 16px; }
    .filter-form { width: 100%; }
    .filter-input { width: 100%; min-width: 0; }
    .filter-btn, .reset-btn { flex: 1; }
}

@media print {
    .persons-area > .content > *:not(.table-card) { display: none; }
    .table-tools { display: none !important; }
    body, .content { background-color: white !important; padding: 0 !important; }
}

</style>


<?php include __DIR__ . '/includes/sidebar.php'; ?>


<div class="persons-area">

<main class="content">

    <div class="page-top d-flex justify-content-between align-items-center">

        <div class="page-title-wrap">
            <i class="fa-solid fa-chart-pie fs-5 text-primary"></i>
            <h5 class="fw-bold text-dark">تقرير الأشخاص ذوي العهد النشطة</h5>
        </div>

        <div>
            <img src="mod_logo.png" alt="شعار وزارة الدفاع" style="height: 35px; width: auto;" class="img-fluid">
        </div>

    </div>

    <div class="table-card">

        <div class="table-tools">

            <form method="GET" action="" class="filter-form">

                <input
                    type="text"
                    name="q"
                    class="filter-input"
                    placeholder="ابحث بالاسم أو رقم الجوال..."
                    value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                >

                <button type="submit" class="filter-btn">تصفية</button>

                <a href="person_report.php" class="reset-btn">إعادة تعيين</a>

            </form>

            <button type="button" class="print-btn" onclick="window.print()">
                <i class="fa-solid fa-print"></i>
                <span>طباعة</span>
            </button>

            <a href="?q=<?php echo urlencode($search); ?>&export=csv" class="export-btn">
                <i class="fa-solid fa-download"></i>
                <span>تصدير</span>
            </a>

        </div>

        <div class="table-responsive">

            <table class="report-table">

                <thead>
                    <tr>
                        <th>م</th>
                        <th>الاسم الكامل</th>
                        <th>رقم الجوال</th>
                        <th>المركبة</th>
                        <th>رقم اللوحة</th>
                        <th>نوع العهدة</th>
                        <th>تاريخ البداية</th>
                        <th>تاريخ التسليم المتوقع</th>
                        <th>الحالة</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($rows) === 0): ?>

                    <tr>
                        <td colspan="9" class="empty-row">
                            لا يوجد أشخاص لديهم عهد نشطة حاليًا
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($rows as $index => $row): ?>

                        <tr>

                            <td><?php echo $index + 1; ?></td>

                            <td><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?php echo htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?php echo htmlspecialchars($row['vehicle_type'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?php echo htmlspecialchars($row['plate_number'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?php echo htmlspecialchars($row['custody_type'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?php echo displayDate($row['start_date']); ?></td>

                            <td><?php echo displayDate($row['expected_return_date']); ?></td>

                            <td>

                                <?php if ($row['display_status'] === 'متأخرة'): ?>
                                    <span class="status-badge status-late">متأخرة</span>
                                <?php else: ?>
                                    <span class="status-badge status-active">نشطة</span>
                                <?php endif; ?>

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


<?php include __DIR__ . '/includes/footer.php'; ?>
