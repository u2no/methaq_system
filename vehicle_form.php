<?php

session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/vehicle_helpers.php';

date_default_timezone_set('Asia/Riyadh');

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $id !== null;

$vehicle = [
    'plate_number' => '',
    'type' => '',
    'model' => '',
    'color' => '',
    'notes' => '',
    'status' => 'متاحة',
];

$notFound = false;

if ($isEdit) {
    $existing = fetch_vehicle_by_id($pdo, $id);
    if (!$existing) {
        $notFound = true;
    } else {
        $vehicle = $existing;
    }
}

if ($notFound) {
    header('Location: vehicles.php');
    exit;
}

$errors = [];
$success = '';

if (isset($_GET['saved']) && $_GET['saved'] == '1') {
    $success = 'تم حفظ بيانات المركبة بنجاح';
}

/* عند إرسال النموذج */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $vehicle['plate_number'] = trim($_POST['plate_number'] ?? '');
    $vehicle['type'] = trim($_POST['type'] ?? '');
    $vehicle['model'] = trim($_POST['model'] ?? '');
    $vehicle['color'] = trim($_POST['color'] ?? '');
    $vehicle['notes'] = trim($_POST['notes'] ?? '');

    $errors = validate_vehicle_input($vehicle);

    /* التحقق من عدم تكرار رقم اللوحة (فريد لكل مركبة) */

    if (empty($errors['plate_number'])) {

        $stmt = $pdo->prepare(
            $isEdit
                ? "SELECT COUNT(*) FROM vehicles WHERE plate_number = :plate AND id != :id"
                : "SELECT COUNT(*) FROM vehicles WHERE plate_number = :plate"
        );

        $params = [':plate' => $vehicle['plate_number']];

        if ($isEdit) {
            $params[':id'] = $id;
        }

        $stmt->execute($params);

        if ((int) $stmt->fetchColumn() > 0) {
            $errors['plate_number'] = 'رقم اللوحة مستخدم بالفعل لمركبة أخرى.';
        }

    }

    if (empty($errors)) {

        if ($isEdit) {

            $stmt = $pdo->prepare("
                UPDATE vehicles
                SET plate_number = :plate_number,
                    type = :type,
                    model = :model,
                    color = :color,
                    notes = :notes
                WHERE id = :id
            ");

            $stmt->execute([
                ':plate_number' => $vehicle['plate_number'],
                ':type' => $vehicle['type'],
                ':model' => $vehicle['model'],
                ':color' => $vehicle['color'],
                ':notes' => $vehicle['notes'],
                ':id' => $id,
            ]);

        } else {

            $stmt = $pdo->prepare("
                INSERT INTO vehicles (plate_number, type, model, color, notes, status)
                VALUES (:plate_number, :type, :model, :color, :notes, 'متاحة')
            ");

            $stmt->execute([
                ':plate_number' => $vehicle['plate_number'],
                ':type' => $vehicle['type'],
                ':model' => $vehicle['model'],
                ':color' => $vehicle['color'],
                ':notes' => $vehicle['notes'],
            ]);

        }

        $redirectUrl = $isEdit ? "vehicle_form.php?id={$id}&saved=1" : 'vehicles.php?saved=1';
        header('Location: ' . $redirectUrl);
        exit;

    }

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
    padding: 30px;
    background-color: #f5f7fa;
}


.white-box {
    width: 100%;
    max-width: 900px;
    padding: 30px;
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
}


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


.success-message {
    padding: 14px 18px;
    margin-bottom: 25px;
    background-color: #ecfdf3;
    border: 1px solid #bbf7d0;
    border-radius: 7px;
    color: #166534;
    font-size: 14px;
}


.error-message {
    padding: 14px 18px;
    margin-bottom: 25px;
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 7px;
    color: #b91c1c;
    font-size: 14px;
}


.form-row {
    display: flex;
    gap: 25px;
    margin-bottom: 25px;
}


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
    font-family: Arial, Tahoma, sans-serif;
    font-size: 14px;
}


.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #2563eb;
}


.form-group input.has-error,
.form-group select.has-error {
    border-color: #dc2626;
}


.field-error {
    margin-top: 6px;
    color: #dc2626;
    font-size: 12px;
}


.status-note {
    padding: 14px 16px;
    margin-top: 10px;
    background-color: #f1f5f9;
    border: 1px dashed #cbd5e1;
    border-radius: 7px;
    color: #64748b;
    font-size: 13px;
}


textarea {
    height: 100px;
    resize: none;
}


.white-box hr {
    margin: 30px 0;
    border: none;
    border-top: 1px solid #e7edf3;
    opacity: 1;
}


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


<?php
include __DIR__ . '/includes/sidebar.php';
?>


<div class="vehicles-area">


<main class="content">


    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">

        <h5 class="fw-bold text-dark mb-0">
            <i class="fa-solid <?php echo $isEdit ? 'fa-pen' : 'fa-plus'; ?> me-2 text-primary"></i>
            <?php echo $isEdit ? 'تعديل بيانات مركبة' : 'إضافة مركبة'; ?>
        </h5>

        <div>
            <img
                src="mod_logo.png"
                alt="شعار وزارة الدفاع"
                style="height: 35px; width: auto;"
                class="img-fluid"
            >
        </div>

    </div>


    <div class="white-box">

        <?php if ($success !== ''): ?>
            <div class="success-message">✓ <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message">يرجى تصحيح الأخطاء التالية قبل الحفظ.</div>
        <?php endif; ?>


        <form method="POST" action="">

            <div class="form-row">

                <div class="form-group">
                    <label>
                        نوع المركبة <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        name="type"
                        class="<?php echo isset($errors['type']) ? 'has-error' : ''; ?>"
                        placeholder="مثال: تويوتا هايلكس"
                        value="<?php echo htmlspecialchars($vehicle['type'], ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <?php if (isset($errors['type'])): ?>
                        <div class="field-error"><?php echo $errors['type']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>
                        رقم اللوحة <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        name="plate_number"
                        autocomplete="off"
                        class="<?php echo isset($errors['plate_number']) ? 'has-error' : ''; ?>"
                        placeholder="مثال: ط ب د 4821"
                        value="<?php echo htmlspecialchars($vehicle['plate_number'], ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <?php if (isset($errors['plate_number'])): ?>
                        <div class="field-error"><?php echo $errors['plate_number']; ?></div>
                    <?php endif; ?>
                </div>

            </div>


            <div class="form-row">

                <div class="form-group">
                    <label>اللون</label>
                    <input
                        type="text"
                        name="color"
                        placeholder="اختر اللون"
                        value="<?php echo htmlspecialchars($vehicle['color'], ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>
                        الموديل <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        name="model"
                        class="<?php echo isset($errors['model']) ? 'has-error' : ''; ?>"
                        placeholder="مثال: 2026"
                        value="<?php echo htmlspecialchars($vehicle['model'], ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <?php if (isset($errors['model'])): ?>
                        <div class="field-error"><?php echo $errors['model']; ?></div>
                    <?php endif; ?>
                </div>

            </div>


            <div class="form-group" style="max-width: 100%;">
                <label>الحالة</label>
                <input type="text" value="<?php echo htmlspecialchars($vehicle['status'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                <div class="status-note">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    حالة المركبة تُحدَّد تلقائيًا عند التسليم والاسترجاع، ولا يمكن تعديلها هنا يدويًا.
                </div>
            </div>



            <?php if ($isEdit && !empty($vehicle['current_holder'])): ?>
                <div class="form-group" style="max-width: 100%; margin-top: 20px;">
                    <label>المستلم الحالي</label>
                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($vehicle['current_holder'] . ' (' . $vehicle['current_custody_type'] . ')', ENT_QUOTES, 'UTF-8'); ?>"
                        disabled
                    >
                </div>
            <?php endif; ?>


            <hr>


            <div class="form-group" style="max-width: 100%;">
                <label>ملاحظات</label>
                <textarea
                    name="notes"
                    placeholder="أدخل أي ملاحظات مرتبطة بالمركبة (اختياري)"
                ><?php echo htmlspecialchars($vehicle['notes'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>


            <div class="buttons">

                <button type="submit" class="save-btn">
                    ✓ حفظ المركبة
                </button>

                <button
                    type="button"
                    class="cancel-btn"
                    onclick="window.location.href='vehicles.php'"
                >
                    ✕ إلغاء
                </button>

            </div>

        </form>

    </div>

</main>

</div>


<?php
include __DIR__ . '/includes/footer.php';
?>
