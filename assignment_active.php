<?php

session_start();

/* Keep PHP warnings/notices out of CSV downloads. */
$isCsvExport = isset($_GET['export']) && $_GET['export'] === 'csv';
$csvBufferLevel = ob_get_level();

if ($isCsvExport) {
    ob_start();
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Riyadh');

$today = date('Y-m-d');

$search = trim($_GET['q'] ?? '');
$custody_type = trim($_GET['custody_type'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$success = (
    isset($_GET['updated']) &&
    $_GET['updated'] === '1'
) ? 'تم تعديل بيانات العهدة بنجاح' : '';


/* filtering conditions */

$conditions = [
    "(c.actual_return_date IS NULL OR c.actual_return_date = '')"
];

$params = [];


/* search by the name, phone number, or plate number */


if ($search !== '') {

    $conditions[] = "(
        p.name LIKE :search_name
        OR p.phone LIKE :search_phone
        OR v.plate_number LIKE :search_plate
    )";

    $params[':search_name'] = '%' . $search . '%';
    $params[':search_phone'] = '%' . $search . '%';
    $params[':search_plate'] = '%' . $search . '%';

}


/* filter by custody type */


if (
    $custody_type === 'دائمة' ||
    $custody_type === 'مؤقتة'
) {

    $conditions[] = "c.custody_type = :custody_type";
    $params[':custody_type'] = $custody_type;

}


/* filter by custody status */


if ($status_filter === 'متأخرة') {

    $conditions[] = "(
        c.custody_type = 'مؤقتة'
        AND c.expected_return_date IS NOT NULL
        AND c.expected_return_date <> ''
        AND date(c.expected_return_date) < date(:today_late)
    )";

    $params[':today_late'] = $today;

} elseif ($status_filter === 'نشطة') {

    $conditions[] = "NOT (
        c.custody_type = 'مؤقتة'
        AND c.expected_return_date IS NOT NULL
        AND c.expected_return_date <> ''
        AND date(c.expected_return_date) < date(:today_active)
    )";

    $params[':today_active'] = $today;

}


$whereSql = implode("\nAND ", $conditions);


/* get active custodies */

$sql = "
    SELECT
        c.id,
        c.custody_type,
        c.start_date,
        c.expected_return_date,
        c.has_deduction,
        c.decision_reference,
        c.notes,

        p.name,
        p.phone,

        v.type,
        v.plate_number,

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

    ORDER BY c.id DESC
";

$params[':today_status'] = $today;

$query = $pdo->prepare($sql);
$query->execute($params);
$assignments = $query->fetchAll();


/* export results to CSV */

if ($isCsvExport) {

    $filename = 'active_custody_' . date('Y-m-d') . '.csv';

    /* Remove anything buffered before the CSV headers (warnings, spaces, etc.). */
    if (ob_get_level() > $csvBufferLevel) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    $output = fopen('php://output', 'w');

    /* UTF-8 BOM so Arabic displays correctly in Excel. */
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'رقم',
        'اسم المستلم',
        'رقم الجوال',
        'نوع المركبة',
        'رقم اللوحة',
        'نوع العهدة',
        'تاريخ التسليم',
        'تاريخ الاستلام',
        'هل تم الحسم؟',
        'رقم القرار المرجعي',
        'الحالة',
        'ملاحظات'
    ], ',', '"', '', "\r\n");

    foreach ($assignments as $index => $row) {

        fputcsv($output, [
            $index + 1,
            $row['name'],
            $row['phone'],
            $row['type'],
            $row['plate_number'],
            $row['custody_type'],
            $row['start_date'],
            $row['expected_return_date'] ?: '-',
            $row['has_deduction'] == 1 ? 'نعم' : 'لا',
            $row['decision_reference'] ?: '-',
            $row['display_status'],
            $row['notes'] ?: '-'
        ], ',', '"', '', "\r\n");

    }

    fclose($output);
    exit;

}


/* display date function*/

function displayDate($date)
{
    if (
        empty($date) ||
        $date === '0000-00-00'
    ) {
        return '-';
    }

    return date(
        'Y/m/d',
        strtotime($date)
    );
}

?>


<?php
include __DIR__ . '/includes/header.php';
?>


<style>

/* page area */

.assignment-area {
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


/* title bar */

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


.page-title-wrap svg {
    width: 23px;
    height: 23px;
    color: #198754;
    flex-shrink: 0;
}


.page-title-wrap h5 {
    margin: 0;
    font-size: 20px;
}


/* table container */

.table-card {
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 18px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.025);
}


/* success message */

.success-message {
    padding: 13px 16px;
    margin-bottom: 18px;
    background-color: #ecfdf3;
    border: 1px solid #bbf7d0;
    border-radius: 7px;
    color: #15803d;
    font-size: 13px;
}


/* search, filter, and export tools */

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
    min-width: 280px;
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


/* table */

.table-responsive {
    border: 1px solid #e5eaf0;
    border-radius: 7px;
    overflow-x: auto;
}


.active-table {
    width: 100%;
    min-width: 1500px;
    margin: 0;
    border-collapse: collapse;
}


.active-table th {
    padding: 12px 9px;
    background-color: #f8fafc;
    color: #334155;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    white-space: nowrap;
}


.active-table td {
    padding: 12px 9px;
    color: #475569;
    border-bottom: 1px solid #edf2f7;
    font-size: 12px;
    text-align: center;
    vertical-align: middle;
}


.active-table tbody tr:last-child td {
    border-bottom: none;
}


.active-table tbody tr:hover {
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


.status-active {
    background-color: #dcfce7;
    color: #15803d;
}


.status-late {
    background-color: #fee2e2;
    color: #b91c1c;
}


.deduction-badge {
    display: inline-block;
    min-width: 52px;
    padding: 5px 10px;
    border-radius: 14px;
    font-size: 11px;
    font-weight: bold;
}


.deduction-yes {
    background-color: #fff3cd;
    color: #8a6500;
}


.deduction-no {
    background-color: #e9ecef;
    color: #495057;
}


.edit-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 11px;
    background-color: #eff6ff;
    border: 1px solid #93c5fd;
    border-radius: 5px;
    color: #1d4ed8;
    text-decoration: none;
    font-size: 11px;
    font-weight: bold;
    white-space: nowrap;
}


.edit-btn:hover {
    background-color: #dbeafe;
    color: #1e40af;
}


.empty-row {
    padding: 35px !important;
    color: #64748b !important;
    text-align: center !important;
}


/* small screen */

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


<div class="assignment-area">


<main class="content">


    <! -- page title + logo -- >

    <div class="page-top d-flex justify-content-between align-items-center">

        <! -- right -- >
        <div class="page-title-wrap">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                fill="currentColor"
                class="bi bi-clipboard-check-fill"
                viewBox="0 0 16 16"
            >
                <path d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0zm3 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5z"/>
                <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1A2.5 2.5 0 0 1 9.5 5h-3A2.5 2.5 0 0 1 4 2.5zm6.854 7.354-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708.708"/>
            </svg>

            <h5 class="fw-bold text-dark">
                العهد النشطة
            </h5>

        </div>


        <! -- left -- >
        <div>
            <img
                src="mod_logo.png"
                alt="شعار وزارة الدفاع"
                style="height: 35px; width: auto;"
                class="img-fluid"
            >
        </div>

    </div>


    <?php if ($success !== ''): ?>

        <div class="success-message">
            ✓
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>

    <?php endif; ?>


    <! -- tables, filtering, and export -- >

    <div class="table-card">


        <div class="table-tools">


           <! -- filtering -- >

            <form
                method="GET"
                action=""
                class="filter-form"
            >

                <input
                    type="text"
                    name="q"
                    class="filter-input"
                    placeholder="ابحث باسم المستلم أو رقم الجوال أو رقم اللوحة..."
                    value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                >


                <select
                    name="custody_type"
                    class="filter-select"
                >

                    <option value="">
                        جميع أنواع العهد
                    </option>

                    <option
                        value="دائمة"
                        <?php echo $custody_type === 'دائمة' ? 'selected' : ''; ?>
                    >
                        دائمة
                    </option>

                    <option
                        value="مؤقتة"
                        <?php echo $custody_type === 'مؤقتة' ? 'selected' : ''; ?>
                    >
                        مؤقتة
                    </option>

                </select>


                <select
                    name="status"
                    class="filter-select"
                >

                    <option value="">
                        جميع الحالات
                    </option>

                    <option
                        value="نشطة"
                        <?php echo $status_filter === 'نشطة' ? 'selected' : ''; ?>
                    >
                        نشطة
                    </option>

                    <option
                        value="متأخرة"
                        <?php echo $status_filter === 'متأخرة' ? 'selected' : ''; ?>
                    >
                        متأخرة
                    </option>

                </select>


                <button
                    type="submit"
                    class="filter-btn"
                >
                    تصفية
                </button>


                <a
                    href="assignment_active.php"
                    class="reset-btn"
                >
                    إعادة تعيين
                </a>

            </form>


           <! -- export -- >

            <a
                href="?q=<?php echo urlencode($search); ?>&custody_type=<?php echo urlencode($custody_type); ?>&status=<?php echo urlencode($status_filter); ?>&export=csv"
                class="export-btn"
            >

                <i class="fa-solid fa-download"></i>

                <span>
                    تصدير
                </span>

            </a>


        </div>


       <! -- tables -- >

        <div class="table-responsive">

            <table class="active-table">

                <thead>

                    <tr>

                        <th>رقم</th>
                        <th>اسم المستلم</th>
                        <th>رقم الجوال</th>
                        <th>المركبة</th>
                        <th>رقم اللوحة</th>
                        <th>نوع العهدة</th>
                        <th>تاريخ التسليم</th>
                        <th>تاريخ الاستلام</th>
                        <th>هل تم الحسم؟</th>
                        <th>رقم القرار المرجعي</th>
                        <th>الحالة</th>
                        <th>ملاحظات</th>
                        <th>الإجراء</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (count($assignments) === 0): ?>

                    <tr>
                        <td
                            colspan="13"
                            class="empty-row"
                        >
                            لا توجد عهد نشطة مطابقة للتصفية الحالية
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($assignments as $index => $row): ?>

                        <tr>

                           <! -- row numbering -- >
                            <td>
                                <?php echo $index + 1; ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['phone'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['type'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['plate_number'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['custody_type'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo displayDate(
                                    $row['start_date']
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo displayDate(
                                    $row['expected_return_date']
                                );
                                ?>
                            </td>


                            <td>

                                <?php if ($row['has_deduction'] == 1): ?>

                                    <span class="deduction-badge deduction-yes">
                                        نعم
                                    </span>

                                <?php else: ?>

                                    <span class="deduction-badge deduction-no">
                                        لا
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>
                                <?php
                                if (!empty($row['decision_reference'])) {
                                    echo htmlspecialchars(
                                        $row['decision_reference'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>


                            <td>

                                <?php if ($row['display_status'] === 'متأخرة'): ?>

                                    <span class="status-badge status-late">
                                        متأخرة
                                    </span>

                                <?php else: ?>

                                    <span class="status-badge status-active">
                                        نشطة
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>
                                <?php
                                if (!empty($row['notes'])) {
                                    echo htmlspecialchars(
                                        $row['notes'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>


                            <td>
                                <a
                                    href="assignment_edit.php?id=<?php echo urlencode($row['id']); ?>"
                                    class="edit-btn"
                                >
                                    <i class="fa-solid fa-pen"></i>
                                    تعديل
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>


    </div>


<?php
include __DIR__ . '/includes/footer.php';