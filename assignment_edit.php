<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Riyadh');

$error = '';

$assignmentId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$assignmentId || $assignmentId < 1) {
    header('Location: assignment_active.php');
    exit;
}


/* get the selected active custody */

$assignmentQuery = $pdo->prepare("
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
        v.plate_number
    FROM custody c
    INNER JOIN persons p
        ON p.id = c.person_id
    INNER JOIN vehicles v
        ON v.id = c.vehicle_id
    WHERE c.id = :id
      AND (
          c.actual_return_date IS NULL
          OR c.actual_return_date = ''
      )
    LIMIT 1
");

$assignmentQuery->execute([
    ':id' => $assignmentId
]);

$assignment = $assignmentQuery->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    header('Location: assignment_active.php');
    exit;
}


/* values shown in the edit form */

$assignmentType =
    $assignment['custody_type'] === 'دائمة'
    ? 'permanent'
    : 'temporary';

$dueDate = $assignment['expected_return_date'] ?? '';
$hasDeduction = $assignment['has_deduction'] == 1 ? '1' : '0';
$decisionReference = $assignment['decision_reference'] ?? '';
$notes = $assignment['notes'] ?? '';


/* validate a date written as YYYY-MM-DD */

function isValidCustodyDate(string $date): bool
{
    $dateObject = DateTime::createFromFormat('Y-m-d', $date);

    return
        $dateObject !== false &&
        $dateObject->format('Y-m-d') === $date;
}


/* save the editable custody information */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $assignmentType = $_POST['assignment_type'] ?? '';
    $dueDate = trim($_POST['due_date'] ?? '');
    $hasDeduction = $_POST['has_deduction'] ?? '0';
    $decisionReference = trim($_POST['decision_reference'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (
        $assignmentType !== 'permanent' &&
        $assignmentType !== 'temporary'
    ) {
        $error = 'يرجى اختيار نوع عهدة صحيح';

    } elseif (
        $assignmentType === 'temporary' &&
        $dueDate === ''
    ) {
        $error = 'يرجى تحديد تاريخ الاستلام للعهدة المؤقتة';

    } elseif (
        $assignmentType === 'temporary' &&
        !isValidCustodyDate($dueDate)
    ) {
        $error = 'تاريخ الاستلام غير صحيح';

    } elseif (
        $assignmentType === 'temporary' &&
        $dueDate < $assignment['start_date']
    ) {
        $error = 'تاريخ الاستلام لا يمكن أن يكون قبل تاريخ التسليم';

    } else {

        if ($assignmentType === 'permanent') {
            $custodyTypeValue = 'دائمة';
            $expectedReturnDate = null;
            $deductionValue = $hasDeduction === '1' ? 1 : 0;

            if (
                $deductionValue === 0 ||
                $decisionReference === ''
            ) {
                $decisionReferenceValue = null;
            } else {
                $decisionReferenceValue = $decisionReference;
            }

        } else {
            $custodyTypeValue = 'مؤقتة';
            $expectedReturnDate = $dueDate;
            $deductionValue = 0;
            $decisionReferenceValue = null;
        }

        try {
            $update = $pdo->prepare("
                UPDATE custody
                SET
                    custody_type = :custody_type,
                    expected_return_date = :expected_return_date,
                    has_deduction = :has_deduction,
                    decision_reference = :decision_reference,
                    notes = :notes
                WHERE id = :id
                  AND (
                      actual_return_date IS NULL
                      OR actual_return_date = ''
                  )
            ");

            $update->execute([
                ':custody_type' => $custodyTypeValue,
                ':expected_return_date' => $expectedReturnDate,
                ':has_deduction' => $deductionValue,
                ':decision_reference' => $decisionReferenceValue,
                ':notes' => $notes,
                ':id' => $assignmentId
            ]);

            header('Location: assignment_active.php?updated=1');
            exit;

        } catch (Exception $e) {
            $error = 'تعذر تعديل العهدة، يرجى المحاولة مرة أخرى';
        }
    }
}


function displayEditDate($date): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }

    return date('Y/m/d', strtotime($date));
}


function escapeEditValue($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>


<?php
include __DIR__ . '/includes/header.php';
?>


<style>

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
    padding: 30px;
    background-color: #f5f7fa;
}

.page-top {
    padding: 15px 18px;
    margin-bottom: 18px;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.page-title-wrap {
    display: flex;
    align-items: center;
    gap: 9px;
}

.page-title-wrap i {
    color: #1457c5;
    font-size: 21px;
}

.page-title-wrap h5 {
    margin: 0;
    font-size: 20px;
}

.form-card {
    padding: 25px;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.025);
}

.section-title {
    margin-bottom: 16px;
    color: #1e3a5f;
    font-size: 15px;
    font-weight: bold;
}

.fixed-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 25px;
}

.fixed-item {
    padding: 13px;
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
}

.fixed-label {
    display: block;
    margin-bottom: 7px;
    color: #64748b;
    font-size: 11px;
    font-weight: bold;
}

.fixed-value {
    color: #334155;
    font-size: 13px;
    font-weight: bold;
}

.divider {
    margin: 26px 0;
    border: none;
    border-top: 1px solid #e7edf3;
    opacity: 1;
}

.error-message {
    padding: 13px 16px;
    margin-bottom: 20px;
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 7px;
    color: #b91c1c;
    font-size: 13px;
}

.form-row {
    display: flex;
    gap: 22px;
    margin-bottom: 22px;
}

.form-group {
    flex: 1;
}

.form-group label,
.reference-group label {
    display: block;
    margin-bottom: 8px;
    color: #243b53;
    font-size: 14px;
    font-weight: bold;
}

.required {
    color: #dc2626;
}

.form-group select,
.form-group input,
.form-group textarea,
.reference-group input {
    width: 100%;
    padding: 12px;
    background-color: #ffffff;
    border: 1px solid #d9e2ec;
    border-radius: 6px;
    outline: none;
    color: #243b53;
    font-family: Arial, Tahoma, sans-serif;
    font-size: 14px;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus,
.reference-group input:focus {
    border-color: #2563eb;
}

.form-group textarea {
    height: 105px;
    resize: vertical;
}

.conditional-box {
    display: none;
    padding: 20px;
    margin-bottom: 22px;
    background-color: #f2f7fd;
    border: 1px solid #d5e5f5;
    border-radius: 7px;
}

.deduction-title {
    margin-bottom: 17px;
    color: #1457c5;
    font-size: 15px;
    font-weight: bold;
}

.deduction-content {
    display: grid;
    grid-template-columns: 1fr 1.3fr;
    gap: 28px;
    align-items: end;
}

.deduction-question {
    color: #243b53;
    font-size: 14px;
    font-weight: bold;
}

.radio-options {
    display: flex;
    align-items: center;
    gap: 26px;
    margin-top: 13px;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #243b53;
    font-size: 14px;
}

.radio-option input {
    width: 18px;
    height: 18px;
    accent-color: #1457c5;
}

.buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

.save-btn,
.cancel-btn {
    padding: 12px 25px;
    border-radius: 6px;
    font-family: Arial, Tahoma, sans-serif;
    font-size: 14px;
    cursor: pointer;
}

.save-btn {
    background-color: #1457c5;
    border: 1px solid #1457c5;
    color: #ffffff;
}

.save-btn:hover {
    background-color: #0f46a2;
}

.cancel-btn {
    background-color: #ffffff;
    border: 1px solid #d9e2ec;
    color: #334155;
}

.cancel-btn:hover {
    background-color: #f8fafc;
}

@media (max-width: 1000px) {
    .fixed-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .content {
        padding: 18px;
    }

    .form-card {
        padding: 18px;
    }

    .fixed-grid,
    .deduction-content {
        grid-template-columns: 1fr;
    }

    .form-row {
        flex-direction: column;
    }
}

</style>


<?php
include __DIR__ . '/includes/sidebar.php';
?>


<div class="assignment-area">

<main class="content">

    <div class="page-top d-flex justify-content-between align-items-center">

        <div class="page-title-wrap">
            <i class="fa-solid fa-pen-to-square"></i>
            <h5 class="fw-bold text-dark">تعديل العهدة النشطة</h5>
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


    <?php if ($error !== ''): ?>
        <div class="error-message">
            <?php echo escapeEditValue($error); ?>
        </div>
    <?php endif; ?>


    <div class="form-card">

        <div class="section-title">
            بيانات ثابتة غير قابلة للتعديل
        </div>

        <div class="fixed-grid">

            <div class="fixed-item">
                <span class="fixed-label">اسم المستلم</span>
                <span class="fixed-value">
                    <?php echo escapeEditValue($assignment['name']); ?>
                </span>
            </div>

            <div class="fixed-item">
                <span class="fixed-label">رقم الجوال</span>
                <span class="fixed-value">
                    <?php echo escapeEditValue($assignment['phone']); ?>
                </span>
            </div>

            <div class="fixed-item">
                <span class="fixed-label">المركبة</span>
                <span class="fixed-value">
                    <?php echo escapeEditValue($assignment['type']); ?>
                </span>
            </div>

            <div class="fixed-item">
                <span class="fixed-label">رقم اللوحة</span>
                <span class="fixed-value">
                    <?php echo escapeEditValue($assignment['plate_number']); ?>
                </span>
            </div>

            <div class="fixed-item">
                <span class="fixed-label">تاريخ التسليم</span>
                <span class="fixed-value">
                    <?php echo displayEditDate($assignment['start_date']); ?>
                </span>
            </div>

        </div>

        <hr class="divider">

        <div class="section-title">
            البيانات القابلة للتعديل
        </div>

        <form
            method="POST"
            action="assignment_edit.php?id=<?php echo urlencode($assignmentId); ?>"
        >

            <div class="form-row">

                <div class="form-group">
                    <label for="assignment_type">
                        نوع العهدة
                        <span class="required">*</span>
                    </label>

                    <select
                        name="assignment_type"
                        id="assignment_type"
                        required
                        onchange="toggleEditFields()"
                    >
                        <option value="permanent" <?php echo $assignmentType === 'permanent' ? 'selected' : ''; ?>>
                            دائمة
                        </option>
                        <option value="temporary" <?php echo $assignmentType === 'temporary' ? 'selected' : ''; ?>>
                            مؤقتة
                        </option>
                    </select>
                </div>

            </div>


            <div
                class="conditional-box"
                id="delivery_date_box"
            >
                <div class="form-group">
                    <label for="due_date">
                        تاريخ الاستلام
                        <span class="required">*</span>
                    </label>
                    <input
                        type="date"
                        name="due_date"
                        id="due_date"
                        min="<?php echo escapeEditValue($assignment['start_date']); ?>"
                        value="<?php echo escapeEditValue($dueDate); ?>"
                    >
                </div>
            </div>


            <div
                class="conditional-box"
                id="deduction_box"
            >

                <div class="deduction-title">
                    معلومات الحسم (للعهدة الدائمة فقط)
                </div>

                <div class="deduction-content">

                    <div>
                        <div class="deduction-question">
                            هل يتم الحسم؟
                            <span class="required">*</span>
                        </div>

                        <div class="radio-options">
                            <label class="radio-option">
                                <input
                                    type="radio"
                                    name="has_deduction"
                                    value="1"
                                    <?php echo $hasDeduction === '1' ? 'checked' : ''; ?>
                                    onchange="toggleDecisionReference()"
                                >
                                نعم
                            </label>

                            <label class="radio-option">
                                <input
                                    type="radio"
                                    name="has_deduction"
                                    value="0"
                                    <?php echo $hasDeduction !== '1' ? 'checked' : ''; ?>
                                    onchange="toggleDecisionReference()"
                                >
                                لا
                            </label>
                        </div>
                    </div>

                    <div
                        class="reference-group"
                        id="reference_group"
                    >
                        <label for="decision_reference">
                            رقم القرار المرجعي
                        </label>
                        <input
                            type="text"
                            name="decision_reference"
                            id="decision_reference"
                            placeholder="أدخل رقم القرار المرجعي"
                            value="<?php echo escapeEditValue($decisionReference); ?>"
                        >
                    </div>

                </div>

            </div>


            <div class="form-group">
                <label for="notes">ملاحظات</label>
                <textarea
                    name="notes"
                    id="notes"
                    placeholder="أدخل أي ملاحظات مرتبطة بالعهدة"
                ><?php echo escapeEditValue($notes); ?></textarea>
            </div>


            <div class="buttons">
                <button type="submit" class="save-btn">
                    ✓ حفظ التعديلات
                </button>

                <button
                    type="button"
                    class="cancel-btn"
                    onclick="window.location.href='assignment_active.php'"
                >
                    ✕ إلغاء
                </button>
            </div>

        </form>

    </div>


<script>

function toggleEditFields() {
    const assignmentType =
        document.getElementById('assignment_type').value;

    const deliveryBox =
        document.getElementById('delivery_date_box');

    const dueDate =
        document.getElementById('due_date');

    const deductionBox =
        document.getElementById('deduction_box');

    const deductionOptions =
        document.querySelectorAll('input[name="has_deduction"]');

    const referenceInput =
        document.getElementById('decision_reference');

    if (assignmentType === 'temporary') {
        deliveryBox.style.display = 'block';
        dueDate.required = true;

        deductionBox.style.display = 'none';
        deductionOptions.forEach(function (option) {
            option.disabled = true;
        });
        referenceInput.disabled = true;

    } else {
        deliveryBox.style.display = 'none';
        dueDate.required = false;
        dueDate.value = '';

        deductionBox.style.display = 'block';
        deductionOptions.forEach(function (option) {
            option.disabled = false;
        });
        referenceInput.disabled = false;
        toggleDecisionReference();
    }
}


function toggleDecisionReference() {
    const selected =
        document.querySelector('input[name="has_deduction"]:checked');

    const referenceGroup =
        document.getElementById('reference_group');

    const referenceInput =
        document.getElementById('decision_reference');

    if (
        selected &&
        !selected.disabled &&
        selected.value === '1'
    ) {
        referenceGroup.style.display = 'block';
        referenceInput.disabled = false;
    } else {
        referenceGroup.style.display = 'none';
        referenceInput.value = '';
        referenceInput.disabled = true;
    }
}


document.addEventListener(
    'DOMContentLoaded',
    function () {
        toggleEditFields();
    }
);

</script>


<?php
include __DIR__ . '/includes/footer.php';
?>