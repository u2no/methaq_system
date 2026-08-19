<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/config/db.php';

$username = $_SESSION['username'] ?? 'المستخدم';

date_default_timezone_set('Asia/Riyadh');

$today = date('Y-m-d');

$error = '';
$success = '';

$search = trim($_GET['q'] ?? '');
$selected_id = $_GET['assignment_id'] ?? '';

$results = [];
$selectedAssignment = null;


/* success message */

if (
    isset($_GET['returned']) &&
    $_GET['returned'] == '1'
) {
    $success = 'تم استرجاع العهدة بنجاح';
}


/* process custody return */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $assignment_id = $_POST['assignment_id'] ?? '';

    if (empty($assignment_id)) {

        $error = 'يرجى اختيار العهدة أولاً';

    } else {

        try {

            $pdo->beginTransaction();

            $check = $pdo->prepare("
                SELECT c.*
                FROM custody c
                WHERE c.id = ?
                AND (
                    c.actual_return_date IS NULL
                    OR c.actual_return_date = ''
                )
            ");

            $check->execute([
                $assignment_id
            ]);

            $assignment = $check->fetch();

            if (!$assignment) {
                throw new Exception(
                    'العهدة غير موجودة أو تم استرجاعها مسبقاً'
                );
            }


            /* update custody as return */

            $update = $pdo->prepare("
                UPDATE custody
                SET
                    actual_return_date = :actual_return_date,
                    status = 'مكتملة'
                WHERE id = :id
            ");

            $update->execute([
                ':actual_return_date' => $today,
                ':id' => $assignment_id
            ]);

            $pdo->commit();

            header(
                'Location: assignment_return.php?returned=1'
            );

            exit;

        }

        catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e->getMessage();

        }

    }

}


/* search by name, or phone number */

if ($search !== '') {

    $searchQuery = $pdo->prepare("
        SELECT
            c.id,
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
            END AS display_status,
            p.name,
            p.phone,
            v.type,
            v.plate_number
        FROM custody c
        INNER JOIN persons p
            ON p.id = c.person_id
        INNER JOIN vehicles v
            ON v.id = c.vehicle_id
        WHERE
        (
            p.name LIKE :name_search
            OR p.phone LIKE :phone_search
        )
        AND (
            c.actual_return_date IS NULL
            OR c.actual_return_date = ''
        )
        ORDER BY c.id DESC
    ");

    $searchQuery->execute([
        ':name_search' => '%' . $search . '%',
        ':phone_search' => '%' . $search . '%',
        ':today_status' => $today
    ]);

    $results = $searchQuery->fetchAll();

}


/* get chosen custody */

if (!empty($selected_id)) {

    $selectedQuery = $pdo->prepare("
        SELECT
            c.id,
            c.custody_type,
            c.start_date,
            c.expected_return_date,
            CASE
                WHEN
                    c.custody_type = 'مؤقتة'
                    AND c.expected_return_date IS NOT NULL
                    AND c.expected_return_date <> ''
                    AND date(c.expected_return_date) < date(?)
                THEN 'متأخرة'
                ELSE 'نشطة'
            END AS display_status,
            p.name,
            p.phone,
            v.type,
            v.plate_number
        FROM custody c
        INNER JOIN persons p
            ON p.id = c.person_id
        INNER JOIN vehicles v
            ON v.id = c.vehicle_id
        WHERE c.id = ?
        AND (
            c.actual_return_date IS NULL
            OR c.actual_return_date = ''
        )
    ");

    $selectedQuery->execute([
        $today,
        $selected_id
    ]);

    $selectedAssignment = $selectedQuery->fetch();

}


/* addition function */

function assignmentTypeArabic($type)
{
    if (
        $type === 'temporary' ||
        $type === 'مؤقتة'
    ) {
        return 'مؤقتة';
    }

    return 'دائمة';
}


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

/* custody page area */

.assignment-area {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background-color: #f5f7fa;
}


/* page content */

.content {
    flex: 1;
    width: 100%;
    min-width: 0;
    padding: 18px 22px 30px;
    background-color: #f5f7fa;
}


/* page title */

.page-heading {
    margin-bottom: 14px;
}

.page-heading-main {
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-heading-main svg {
    width: 28px;
    height: 28px;
    color: #000000;
}

.page-heading h1 {
    margin: 0;
    color: #000000;
    font-size: 25px;
}

.breadcrumb {
    margin-top: 6px;
    color: #64748b;
    font-size: 12px;
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

.page-title-wrap h5 {
    margin: 0;
    font-size: 20px;
}


/* messages */

.success-message {
    padding: 12px 16px;
    margin-bottom: 14px;
    background-color: #ecfdf3;
    border: 1px solid #bbf7d0;
    border-radius: 7px;
    color: #166534;
    font-size: 13px;
}

.error-message {
    padding: 12px 16px;
    margin-bottom: 14px;
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 7px;
    color: #b91c1c;
    font-size: 13px;
}


/* white container */

.return-card {
    background-color: white;
    border: 1px solid #dfe7f1;
    border-radius: 8px;
    padding: 18px;
    margin-bottom: 14px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.025);
}


/* section titles */

.section-title {
    margin: 0 0 10px;
    color: #000000;
    font-size: 16px;
    font-weight: bold;
}

.section-description {
    margin-bottom: 12px;
    color: #475569;
    font-size: 12px;
}


/* search */

.search-form {
    width: 100%;
    display: flex;
}

.search-input {
    flex: 1;
    height: 42px;
    padding: 0 13px;
    border: 1px solid #cbd5e1;
    border-left: none;
    border-radius: 0 5px 5px 0;
    outline: none;
    font-family: Arial, Tahoma, sans-serif;
    font-size: 13px;
}

.search-input:focus {
    border-color: #1457c5;
}

.search-button {
    width: 50px;
    border: none;
    border-radius: 5px 0 0 5px;
    background-color: #0756b8;
    color: white;
    cursor: pointer;
}

.search-button svg {
    width: 19px;
    height: 19px;
}

.search-hint {
    margin: 10px 0 14px;
    color: #64748b;
    font-size: 11px;
}


/* search result */

.results-box {
    border: 1px solid #d7e2ef;
    border-radius: 7px;
    overflow: hidden;
}

.results-header {
    padding: 12px 14px;
    background-color: #f8fbff;
    color: #000000;
    font-size: 15px;
    font-weight: bold;
}

.results-table {
    width: 100%;
    border-collapse: collapse;
}

.results-table th {
    padding: 12px 7px;
    background-color: white;
    border-bottom: 1px solid #e2e8f0;
    color: #334155;
    font-size: 11px;
    text-align: center;
}

.results-table td {
    padding: 12px 7px;
    border-bottom: 1px solid #edf2f7;
    text-align: center;
    font-size: 11px;
}

.results-table tr:last-child td {
    border-bottom: none;
}


/* no search status */

.empty-search {
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    background: linear-gradient(180deg, #f9fbff, #f3f7fd);
    color: #475569;
}

.empty-search svg {
    width: 34px;
    height: 34px;
    margin-bottom: 8px;
    color: #000000;
}

.empty-search strong {
    font-size: 13px;
}

.empty-search span {
    margin-top: 7px;
    font-size: 11px;
    color: #64748b;
}


/* buttons and status */

.select-button {
    display: inline-block;
    padding: 7px 15px;
    background-color: #1457c5;
    border-radius: 5px;
    color: white;
    text-decoration: none;
    font-size: 11px;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    background-color: #dcfce7;
    color: #15803d;
    font-size: 10px;
}


/* custody details */

.details-title {
    margin-bottom: 18px;
    color: #000000;
    font-size: 16px;
    font-weight: bold;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    margin-bottom: 18px;
}

.detail-item {
    text-align: center;
}

.detail-label {
    display: block;
    margin-bottom: 8px;
    color: #334155;
    font-size: 11px;
    font-weight: bold;
}

.detail-value {
    color: #334155;
    font-size: 11px;
}


/* information message */

.info-box {
    margin-top: 12px;
    padding: 11px 15px;
    border: 1px solid #b8d5fa;
    border-radius: 5px;
    background-color: #eff6ff;
    color: #1457c5;
    font-size: 12px;
    text-align: center;
}


/* buttons */

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 12px;
    direction: ltr;
    justify-content: flex-end;
}

.return-button {
    padding: 11px 25px;
    border: none;
    border-radius: 5px;
    background-color: #0756b8;
    color: white;
    font-family: Arial, Tahoma, sans-serif;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
}

.cancel-button {
    padding: 11px 23px;
    border: 1px solid #cbd5e1;
    border-radius: 5px;
    background-color: white;
    color: #334155;
    font-family: Arial, Tahoma, sans-serif;
    font-size: 13px;
    cursor: pointer;
}


/* small screens */

@media (max-width: 1000px) {

    .details-grid {
        grid-template-columns: repeat(3, 1fr);
    }

}

</style>


<?php
include __DIR__ . '/includes/sidebar.php';
?>


<div class="assignment-area">

<main class="content">


    <!-- page title-->

    <div class="page-top d-flex justify-content-between align-items-center">

        <!-- right -->
        <div class="page-title-wrap">

            <i class="fa-solid fa-rotate-left text-primary"></i>

            <h5 class="fw-bold text-dark">
                استلام العهدة
            </h5>

        </div>

        <!-- left -->
        <div>
            <img
                src="mod_logo.png"
                alt="شعار وزارة الدفاع"
                style="height: 35px; width: auto;"
                class="img-fluid"
            >
        </div>

    </div>


    <!-- success message -->

    <?php if (!empty($success)): ?>

        <div class="success-message">
            ✓
            <?php
            echo htmlspecialchars(
                $success,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
        </div>

    <?php endif; ?>


    <!-- error message-->

    <?php if (!empty($error)): ?>

        <div class="error-message">
            <?php
            echo htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
        </div>

    <?php endif; ?>


    <!-- search for custody -->

    <section class="return-card">

        <div class="section-title">
            البحث عن عهدة
        </div>

        <div class="section-description">
            ابحث عن العهدة باستخدام اسم المستلم أو رقم الجوال
        </div>

        <form
            method="GET"
            action=""
            class="search-form"
        >

            <input
                type="text"
                name="q"
                class="search-input"
                placeholder="اكتب اسم المستلم أو رقم الجوال للبحث..."
                value="<?php
                    echo htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>"
            >

            <button
                type="submit"
                class="search-button"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <circle
                        cx="11"
                        cy="11"
                        r="7"
                        stroke-width="2"
                    />
                    <path
                        d="m16 16 5 5"
                        stroke-width="2"
                    />
                </svg>
            </button>

        </form>

        <div class="search-hint">
            ابدأ بكتابة اسم المستلم أو رقم الجوال ثم اختر العهدة المطلوبة من النتائج
        </div>


        <!-- search result-->

        <div class="results-box">

            <div class="results-header">
                نتائج البحث
            </div>

            <?php if ($search === ''): ?>

                <div class="empty-search">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            cx="11"
                            cy="11"
                            r="7"
                            stroke-width="2"
                        />
                        <path
                            d="m16 16 5 5"
                            stroke-width="2"
                        />
                    </svg>

                    <strong>
                        ابدأ البحث عن عهدة
                    </strong>

                    <span>
                        اكتب اسم المستلم أو رقم الجوال في مربع البحث أعلاه لعرض النتائج
                    </span>

                </div>

            <?php else: ?>

                <table class="results-table">

                    <thead>
                        <tr>
                            <th>رقم العهدة</th>
                            <th>اسم المستلم</th>
                            <th>المركبة</th>
                            <th>نوع العهدة</th>
                            <th>تاريخ التسليم</th>
                            <th>تاريخ الاستلام</th>
                            <th>حالة العهدة</th>
                            <th>اختيار</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($results) === 0): ?>

                        <tr>
                            <td colspan="8">
                                لا توجد عهد نشطة بهذا الاسم أو رقم الجوال
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($results as $row): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $row['id']
                                    );
                                    ?>
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
                                        $row['type']
                                        . ' - '
                                        . $row['plate_number'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo assignmentTypeArabic(
                                        $row['custody_type']
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
                                    <span class="status-badge">
                                        <?php
                                        echo htmlspecialchars(
                                            $row['display_status'] ?: 'نشطة',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        class="select-button"
                                        href="?q=<?php
                                            echo urlencode(
                                                $search
                                            );
                                        ?>&assignment_id=<?php
                                            echo urlencode(
                                                $row['id']
                                            );
                                        ?>"
                                    >
                                        اختيار
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

    </section>


    <!-- chosen custody details -->

    <section class="return-card">

        <div class="details-title">
            تفاصيل العهدة المختارة
        </div>

        <div class="details-grid">

            <div class="detail-item">
                <span class="detail-label">
                    رقم العهدة
                </span>
                <span class="detail-value">
                    <?php
                    if ($selectedAssignment) {
                        echo htmlspecialchars(
                            $selectedAssignment['id']
                        );
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">
                    اسم المستلم
                </span>
                <span class="detail-value">
                    <?php
                    if ($selectedAssignment) {
                        echo htmlspecialchars(
                            $selectedAssignment['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">
                    المركبة
                </span>
                <span class="detail-value">
                    <?php
                    if ($selectedAssignment) {
                        echo htmlspecialchars(
                            $selectedAssignment['type']
                            . ' - '
                            . $selectedAssignment['plate_number'],
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">
                    نوع العهدة
                </span>
                <span class="detail-value">
                    <?php
                    if ($selectedAssignment) {
                        echo assignmentTypeArabic(
                            $selectedAssignment['custody_type']
                        );
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">
                    تاريخ التسليم
                </span>
                <span class="detail-value">
                    <?php
                    if ($selectedAssignment) {
                        echo displayDate(
                            $selectedAssignment['start_date']
                        );
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">
                    تاريخ الاستلام
                </span>
                <span class="detail-value">
                    <?php
                    if ($selectedAssignment) {
                        echo displayDate(
                            $selectedAssignment['expected_return_date']
                        );
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">
                    حالة العهدة
                </span>

                <?php if ($selectedAssignment): ?>
                    <span class="status-badge">
                        <?php
                        echo htmlspecialchars(
                            $selectedAssignment['display_status'] ?: 'نشطة',
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </span>
                <?php else: ?>
                    <span class="detail-value">-</span>
                <?php endif; ?>
            </div>

        </div>


        <?php if ($selectedAssignment): ?>

            <form
                method="POST"
                action=""
                onsubmit="
                    return confirm(
                        'هل أنت متأكد من استرجاع هذه العهدة؟'
                    );
                "
            >

                <input
                    type="hidden"
                    name="assignment_id"
                    value="<?php
                        echo htmlspecialchars(
                            $selectedAssignment['id']
                        );
                    ?>"
                >

                <div class="info-box">
                    سيتم تنفيذ عملية استرجاع العهدة وتحديث حالة المركبة والعهدة بعد الحفظ والتأكيد
                </div>


                <div class="action-buttons">

                    <button
                        type="button"
                        class="cancel-button"
                        onclick="window.location.href='assignment_return.php'"
                    >
                         إلغاء
                    </button>

                    <button
                        type="submit"
                        class="return-button"
                    >
                         استرجاع العهدة
                    </button>

                </div>

            </form>

        <?php endif; ?>

    </section>


<?php
include __DIR__ . '/includes/footer.php';
?>