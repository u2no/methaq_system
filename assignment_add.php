<?php

session_start();

require_once __DIR__ . '/config/db.php';

$username = $_SESSION['username'] ?? 'المستخدم';

date_default_timezone_set('Asia/Riyadh');

$today = date('Y-m-d');

$error = '';
$success = '';

$person_id = '';
$vehicle_id = '';
$assignment_type = '';
$start_date = $today;
$due_date = '';
$notes = '';


/* Success message after saving */

if (isset($_GET['saved']) && $_GET['saved'] == '1') {

    $success = 'تم حفظ العهدة بنجاح';

}


/* when save custody button is clicked */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $person_id = $_POST['person_id'] ?? '';
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $assignment_type = $_POST['assignment_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');


    if (
        empty($person_id) ||
        empty($vehicle_id) ||
        empty($assignment_type) ||
        empty($start_date)
    ) {

        $error = 'يرجى تعبئة جميع الحقول المطلوبة';

    }

    elseif (
        $assignment_type !== 'permanent' &&
        $assignment_type !== 'temporary'
    ) {

        $error = 'نوع العهدة غير صحيح';

    }

    elseif (
        $assignment_type === 'temporary' &&
        empty($due_date)
    ) {

        $error = 'يرجى تحديد تاريخ التسليم للعهدة المؤقتة';

    }

    elseif (
        $assignment_type === 'temporary' &&
        !empty($due_date) &&
        $due_date < $start_date
    ) {

        $error = 'تاريخ التسليم لا يمكن أن يكون قبل تاريخ الاستلام';

    }

    else {

        if ($assignment_type === 'permanent') {

            $due_date = null;

        }


        try {

            $pdo->beginTransaction();


            /* check if person exists */

            $checkPerson = $pdo->prepare("
                SELECT id
                FROM persons
                WHERE id = ?
            ");

            $checkPerson->execute([
                $person_id
            ]);


            if (!$checkPerson->fetch()) {

                throw new Exception(
                    'الشخص المحدد غير موجود'
                );

            }


            /* check person has no active custody */

            $checkPersonAssignment = $pdo->prepare("
                SELECT COUNT(*)

                FROM custody

                WHERE person_id = ?

                AND (
                    actual_return_date IS NULL
                    OR actual_return_date = ''
                )
            ");

            $checkPersonAssignment->execute([
                $person_id
            ]);


            if (
                $checkPersonAssignment->fetchColumn() > 0
            ) {

                throw new Exception(
                    'هذا الشخص لديه عهدة نشطة بالفعل'
                );

            }


            /* check if vehicle exists */

            $checkVehicle = $pdo->prepare("
                SELECT id

                FROM vehicles

                WHERE id = ?
            ");

            $checkVehicle->execute([
                $vehicle_id
            ]);


            if (!$checkVehicle->fetch()) {

                throw new Exception(
                    'المركبة المحددة غير موجودة'
                );

            }


            /* check vehicle is not assigned */

            $checkVehicleAssignment = $pdo->prepare("
                SELECT COUNT(*)

                FROM custody

                WHERE vehicle_id = ?

                AND (
                    actual_return_date IS NULL
                    OR actual_return_date = ''
                )
            ");

            $checkVehicleAssignment->execute([
                $vehicle_id
            ]);


            if (
                $checkVehicleAssignment->fetchColumn() > 0
            ) {

                throw new Exception(
                    'هذه المركبة مسندة لشخص آخر حالياً'
                );

            }


            /* save custody */

            $insert = $pdo->prepare("

                INSERT INTO custody
                (
                    vehicle_id,
                    person_id,
                    custody_type,
                    start_date,
                    expected_return_date,
                    actual_return_date,
                    notes,
                    status
                )

                VALUES
                (
                    :vehicle_id,
                    :person_id,
                    :custody_type,
                    :start_date,
                    :expected_return_date,
                    NULL,
                    :notes,
                    'نشطة'
                )

            ");


            $insert->execute([

                ':vehicle_id' =>
                    $vehicle_id,

                ':person_id' =>
                    $person_id,

                ':custody_type' =>
                    $assignment_type === 'permanent'
                    ? 'دائمة'
                    : 'مؤقتة',

                ':start_date' =>
                    $start_date,

                ':expected_return_date' =>
                    $due_date,

                ':notes' =>
                    $notes

            ]);


            /* update vehicle status */

            $updateVehicleStatus = $pdo->prepare("
                UPDATE vehicles

                SET status = 'مسلمة'

                WHERE id = ?
            ");

            $updateVehicleStatus->execute([
                $vehicle_id
            ]);


            $pdo->commit();


            header(
                'Location: assignment_add.php?saved=1'
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


/* get available persons */

$personsQuery = $pdo->query("

    SELECT
        p.id,
        p.name,
        p.phone

    FROM persons p

    WHERE p.status = 'نشط'

    AND NOT EXISTS (

        SELECT 1

        FROM custody c

        WHERE c.person_id = p.id

        AND (
            c.actual_return_date IS NULL
            OR c.actual_return_date = ''
        )

    )

    ORDER BY p.name ASC

");


$persons = $personsQuery->fetchAll();


/* get available vehicles */

$vehiclesQuery = $pdo->query("

    SELECT
        v.id,
        v.type,
        v.plate_number,
        v.color,
        v.model

    FROM vehicles v

    WHERE v.status = 'متاحة'

    AND NOT EXISTS (

        SELECT 1

        FROM custody c

        WHERE c.vehicle_id = v.id

        AND (
            c.actual_return_date IS NULL
            OR c.actual_return_date = ''
        )

    )

    ORDER BY v.id DESC

");


$vehicles = $vehiclesQuery->fetchAll();

?>


<!-- header -->

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

    padding: 30px;

    background-color: #f5f7fa;

}


/* white container */

.white-box {

    width: 100%;

    min-height: 650px;

    padding: 30px;

    background-color: white;

    border: 1px solid #e2e8f0;

    border-radius: 10px;

    box-shadow:
        0 2px 5px
        rgba(0,0,0,0.03);

}


/* page title */

.page-title {

    margin-bottom: 30px;

}


.page-title h2 {

    margin: 0;

    color: #102a43;

    font-size: 24px;

}


.page-title p {

    margin-top: 7px;

    margin-bottom: 0;

    color: #829ab1;

    font-size: 14px;

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


/* success message */

.success-message {

    padding: 14px 18px;

    margin-bottom: 25px;

    background-color: #ecfdf3;

    border: 1px solid #bbf7d0;

    border-radius: 7px;

    color: #166534;

    font-size: 14px;

}


/* error message */

.error-message {

    padding: 14px 18px;

    margin-bottom: 25px;

    background-color: #fef2f2;

    border: 1px solid #fecaca;

    border-radius: 7px;

    color: #b91c1c;

    font-size: 14px;

}


/* form row */

.form-row {

    display: flex;

    gap: 25px;

    margin-bottom: 25px;

}


/* each form */

.form-group {

    flex: 1;

}


.form-group label {

    display: block;

    margin-bottom: 8px;

    color: #243b53;

    font-size: 14px;

    font-weight: bold;

}


.required {

    color: red;

}


/* forms */

.form-group input,
.form-group select,
.form-group textarea {

    width: 100%;

    padding: 13px;

    background-color: white;

    border: 1px solid #d9e2ec;

    border-radius: 6px;

    outline: none;

    color: #243b53;

    font-family:
        Arial,
        Tahoma,
        sans-serif;

    font-size: 14px;

}


.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {

    border-color: #2563eb;

}


/* due date for temp custody */

.temporary-box {

    display: none;

    padding: 20px;

    margin-bottom: 25px;

    background-color: #f2f7fd;

    border: 1px solid #d5e5f5;

    border-radius: 7px;

}


/* notes */

textarea {

    height: 100px;

    resize: none;

}


/* divider line */

.white-box hr {

    margin: 30px 0;

    border: none;

    border-top: 1px solid #e7edf3;

    opacity: 1;

}


/* buttons */

.buttons {

    display: flex;

    justify-content: space-between;

    margin-top: 40px;

}


.save-btn {

    padding: 13px 30px;

    background-color: #1457c5;

    border: none;

    border-radius: 6px;

    color: white;

    font-size: 14px;

    cursor: pointer;

}


.save-btn:hover {

    background-color: #0f46a2;

}


.save-btn:disabled {

    opacity: 0.6;

    cursor: not-allowed;

}


.cancel-btn {

    padding: 13px 25px;

    background-color: white;

    border: 1px solid #d9e2ec;

    border-radius: 6px;

    color: #243b53;

    font-size: 14px;

    cursor: pointer;

}


.cancel-btn:hover {

    background-color: #f8fafc;

}


/* notes when no data */

.empty-note {

    margin-top: 7px;

    color: #94a3b8;

    font-size: 12px;

}


/* small screens */

@media (max-width: 900px) {

    .content {

        padding: 20px;

    }


    .white-box {

        padding: 20px;

    }


    .form-row {

        flex-direction: column;

    }

}

</style>


<!-- sidebar -->

<?php
include __DIR__ . '/includes/sidebar.php';
?>


<!-- content area -->

<div class="assignment-area">


<main class="content">


    <div class="page-top d-flex justify-content-between align-items-center">

        <!-- right -->
        <div class="page-title-wrap">

            <i class="fa-solid fa-key text-primary"></i>

            <h5 class="fw-bold text-dark">
                تسليم العهدة
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


        <!-- success meesage -->

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


        <!-- error message -->

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


        <!-- custody handover form -->

        <form
            method="POST"
            action=""
        >


            <div class="form-row">


                <div class="form-group">

                    <label>

                        اسم المستلم

                        <span class="required">
                            *
                        </span>

                    </label>


                    <select
                        name="person_id"
                        required
                    >

                        <option value="">
                            اختر شخصاً
                        </option>


                        <?php if (count($persons) > 0): ?>


                            <?php foreach ($persons as $person): ?>

                                <option

                                    value="<?php
                                        echo $person['id'];
                                    ?>"

                                    <?php

                                    if (
                                        (string)$person_id ===
                                        (string)$person['id']
                                    ) {

                                        echo 'selected';

                                    }

                                    ?>

                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $person['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </option>

                            <?php endforeach; ?>


                        <?php else: ?>

                            <option
                                value=""
                                disabled
                            >
                                لا يوجد أشخاص متاحون
                            </option>

                        <?php endif; ?>

                    </select>


                    <?php if (count($persons) === 0): ?>

                        <div class="empty-note">

                            يجب تسجيل شخص أولاً
                            أو أن جميع الأشخاص لديهم عهدة حالية.

                        </div>

                    <?php endif; ?>

                </div>


                <div class="form-group">

                    <label>

                        نوع العهدة

                        <span class="required">
                            *
                        </span>

                    </label>


                    <select
                        name="assignment_type"
                        id="assignment_type"
                        required
                        onchange="toggleDeliveryDate()"
                    >

                        <option value="">
                            اختر نوع العهدة
                        </option>


                        <option
                            value="permanent"

                            <?php

                            if (
                                $assignment_type ===
                                'permanent'
                            ) {

                                echo 'selected';

                            }

                            ?>

                        >
                            دائمة
                        </option>


                        <option
                            value="temporary"

                            <?php

                            if (
                                $assignment_type ===
                                'temporary'
                            ) {

                                echo 'selected';

                            }

                            ?>

                        >
                            مؤقتة
                        </option>

                    </select>

                </div>

            </div>


            <div class="form-row">


                <!-- المركبة -->

                <div class="form-group">

                    <label>

                        المركبة

                        <span class="required">
                            *
                        </span>

                    </label>


                    <select
                        name="vehicle_id"
                        required
                    >

                        <option value="">
                            اختر مركبة
                        </option>


                        <?php if (count($vehicles) > 0): ?>


                            <?php foreach ($vehicles as $vehicle): ?>

                                <option

                                    value="<?php
                                        echo $vehicle['id'];
                                    ?>"

                                    <?php

                                    if (
                                        (string)$vehicle_id ===
                                        (string)$vehicle['id']
                                    ) {

                                        echo 'selected';

                                    }

                                    ?>

                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $vehicle['type']
                                        .
                                        ' - '
                                        .
                                        $vehicle['plate_number'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </option>

                            <?php endforeach; ?>


                        <?php else: ?>

                            <option
                                value=""
                                disabled
                            >
                                لا توجد مركبات متاحة
                            </option>

                        <?php endif; ?>

                    </select>


                    <?php if (count($vehicles) === 0): ?>

                        <div class="empty-note">

                            يجب تسجيل مركبة أولاً
                            أو أن جميع المركبات مسندة حالياً.

                        </div>

                    <?php endif; ?>

                </div>


                <div class="form-group">

                    <label>

                        تاريخ الاستلام

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input

                        type="date"

                        name="start_date"

                        id="start_date"

                        value="<?php

                            echo htmlspecialchars(
                                $start_date,
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>"

                        required

                        onchange="updateDeliveryMinimum()"

                    >

                </div>

            </div>


            <div
                class="temporary-box"
                id="delivery_date_box"
            >

                <div class="form-group">

                    <label>

                        تاريخ التسليم

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input

                        type="date"

                        name="due_date"

                        id="due_date"

                        value="<?php

                            echo htmlspecialchars(
                                $due_date ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>"

                    >

                </div>

            </div>


            <hr>


            <div class="form-group">

                <label>
                    ملاحظات
                </label>


                <textarea
                    name="notes"
                    placeholder="أدخل أي ملاحظات مرتبطة بالعهدة"
                ><?php

                    echo htmlspecialchars(
                        $notes,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                ?></textarea>

            </div>


            <div class="buttons">


                <button

                    type="submit"

                    class="save-btn"

                    <?php

                    if (
                        count($persons) === 0 ||
                        count($vehicles) === 0
                    ) {

                        echo 'disabled';

                    }

                    ?>

                >
                    ✓ حفظ العهدة
                </button>


                <button

                    type="button"

                    class="cancel-btn"

                    onclick="
                        window.location.href='assignments.php'
                    "

                >
                    ✕ إلغاء
                </button>


            </div>


        </form>


    </div>


<script>

/* show due date if temp custody */

function toggleDeliveryDate() {

    const assignmentType =
        document.getElementById(
            'assignment_type'
        ).value;


    const deliveryBox =
        document.getElementById(
            'delivery_date_box'
        );


    const dueDate =
        document.getElementById(
            'due_date'
        );


    if (
        assignmentType ===
        'temporary'
    ) {

        deliveryBox.style.display =
            'block';

        dueDate.required =
            true;

        updateDeliveryMinimum();

    }

    else {

        deliveryBox.style.display =
            'none';

        dueDate.required =
            false;

        dueDate.value =
            '';

    }

}


/* prevent due date from being before handover date */

function updateDeliveryMinimum() {

    const startDate =
        document.getElementById(
            'start_date'
        ).value;


    const dueDate =
        document.getElementById(
            'due_date'
        );


    if (
        startDate !== ''
    ) {

        dueDate.min =
            startDate;


        if (
            dueDate.value !== '' &&
            dueDate.value < startDate
        ) {

            dueDate.value =
                '';

        }

    }

}


/* run funtions when the page loads */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        toggleDeliveryDate();

        updateDeliveryMinimum();

    }
);

</script>



<?php
include __DIR__ . '/includes/footer.php';
?>