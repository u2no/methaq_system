<?php

session_start();

require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Riyadh');


/* التأكد من وجود عمودي created_at و updated_at */

$personsColumns = $pdo->query("PRAGMA table_info(persons)")->fetchAll();
$personsColumnNames = array_column($personsColumns, 'name');

if (!in_array('created_at', $personsColumnNames, true)) {
    $pdo->exec("ALTER TABLE persons ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
}

if (!in_array('updated_at', $personsColumnNames, true)) {
    $pdo->exec("ALTER TABLE persons ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP");
}


$search = trim($_GET['q'] ?? '');

$conditions = [];
$params = [];

if ($search !== '') {

    $conditions[] = "(p.name LIKE :search_name OR p.phone LIKE :search_phone)";
    $params[':search_name'] = '%' . $search . '%';
    $params[':search_phone'] = '%' . $search . '%';

}

$whereSql = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';


/* جلب الأشخاص مع بيان المركبة الحالية إن وجدت */

$sql = "
    SELECT
        p.id,
        p.name,
        p.phone,
        p.status,

        v.type      AS vehicle_type,
        v.plate_number

    FROM persons p

    LEFT JOIN custody c
        ON c.person_id = p.id
        AND (c.actual_return_date IS NULL OR c.actual_return_date = '')

    LEFT JOIN vehicles v
        ON v.id = c.vehicle_id

    $whereSql

    ORDER BY p.id DESC
";

$query = $pdo->prepare($sql);
$query->execute($params);
$persons = $query->fetchAll();

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

.reset-btn:hover {
    background-color: #f8fafc;
    color: #1e293b;
}

.add-btn {
    height: 40px;
    padding: 0 18px;
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
    white-space: nowrap;
}

.add-btn:hover {
    background-color: #0f46a2;
    color: white;
}

.table-responsive {
    border: 1px solid #e5eaf0;
    border-radius: 7px;
    overflow-x: auto;
}

.persons-table {
    width: 100%;
    min-width: 900px;
    margin: 0;
    border-collapse: collapse;
}

.persons-table th {
    padding: 12px 9px;
    background-color: #f8fafc;
    color: #334155;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    white-space: nowrap;
}

.persons-table td {
    padding: 12px 9px;
    color: #475569;
    border-bottom: 1px solid #edf2f7;
    font-size: 12px;
    text-align: center;
    vertical-align: middle;
}

.persons-table tbody tr:last-child td {
    border-bottom: none;
}

.persons-table tbody tr:hover {
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

.status-inactive {
    background-color: #f1f5f9;
    color: #64748b;
}

.status-has-vehicle {
    background-color: #dbeafe;
    color: #1d4ed8;
}

.status-no-vehicle {
    background-color: #f1f5f9;
    color: #64748b;
}

.row-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.row-actions a {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    border-radius: 5px;
    border: 1px solid #d8e0ea;
    color: #334155;
    text-decoration: none;
    font-size: 11px;
    white-space: nowrap;
}

.row-actions a:hover {
    background-color: #f8fafc;
}

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

</style>


<?php include __DIR__ . '/includes/sidebar.php'; ?>


<div class="persons-area">

<main class="content">

    <div class="page-top d-flex justify-content-between align-items-center">

        <div class="page-title-wrap">
            <i class="fa-solid fa-user-gear fs-5 text-primary"></i>
            <h5 class="fw-bold text-dark">إدارة الأشخاص</h5>
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

                <button type="submit" class="filter-btn">بحث</button>

                <a href="person_list.php" class="reset-btn">إعادة تعيين</a>

            </form>

            <a href="person_add.php" class="add-btn">
                <i class="fa-solid fa-user-plus"></i>
                <span>إضافة شخص</span>
            </a>

        </div>

        <div class="table-responsive">

            <table class="persons-table">

                <thead>
                    <tr>
                        <th>م</th>
                        <th>الاسم الكامل</th>
                        <th>رقم الجوال</th>
                        <th>المركبة الحالية</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($persons) === 0): ?>

                    <tr>
                        <td colspan="6" class="empty-row">
                            لا يوجد أشخاص مطابقون للبحث الحالي
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($persons as $index => $row): ?>

                        <tr>

                            <td><?php echo $index + 1; ?></td>

                            <td><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?php echo htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <td>

                                <?php if (!empty($row['plate_number'])): ?>

                                    <span class="status-badge status-has-vehicle">
                                        <?php echo htmlspecialchars($row['vehicle_type'] . ' - ' . $row['plate_number'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>

                                <?php else: ?>

                                    <span class="status-badge status-no-vehicle">
                                        لا يوجد
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($row['status'] === 'نشط'): ?>
                                    <span class="status-badge status-active">نشط</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">غير نشط</span>
                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="row-actions">

                                    <a href="person_detail.php?id=<?php echo $row['id']; ?>">
                                        <i class="fa-solid fa-eye"></i> تفاصيل
                                    </a>

                                    <a href="person_edit.php?id=<?php echo $row['id']; ?>">
                                        <i class="fa-solid fa-pen"></i> تعديل
                                    </a>

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


<?php include __DIR__ . '/includes/footer.php'; ?>
