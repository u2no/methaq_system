<?php

session_start();

require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Riyadh');


/* التأكد من وجود عمودي created_at و updated_at (بدون كسر بيانات الأشخاص الحالية) */

$personsColumns = $pdo->query("PRAGMA table_info(persons)")->fetchAll();
$personsColumnNames = array_column($personsColumns, 'name');

if (!in_array('created_at', $personsColumnNames, true)) {
    $pdo->exec("ALTER TABLE persons ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
}

if (!in_array('updated_at', $personsColumnNames, true)) {
    $pdo->exec("ALTER TABLE persons ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP");
}


$error = '';
$success = '';

$full_name = '';
$mobile_number = '';


/* رسالة النجاح بعد الحفظ */

if (isset($_GET['saved']) && $_GET['saved'] == '1') {
    $success = 'تم إضافة الشخص بنجاح';
}


/* عند الضغط على زر الحفظ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $mobile_number = trim($_POST['mobile_number'] ?? '');


    if (empty($full_name) || empty($mobile_number)) {

        $error = 'يرجى تعبئة جميع الحقول المطلوبة';

    } elseif (mb_strlen($full_name) < 3) {

        $error = 'الاسم الكامل يجب ألا يقل عن 3 أحرف';

    } elseif (!preg_match('/^05[0-9]{8}$/', $mobile_number)) {

        $error = 'رقم الجوال غير صحيح، يجب أن يبدأ بـ 05 ويتكون من 10 أرقام';

    } else {

        try {

            /* التحقق من عدم تكرار رقم الجوال */

            $checkPhone = $pdo->prepare("
                SELECT id
                FROM persons
                WHERE phone = ?
            ");

            $checkPhone->execute([$mobile_number]);

            if ($checkPhone->fetch()) {

                $error = 'رقم الجوال مسجل مسبقًا لشخص آخر';

            } else {

                $insert = $pdo->prepare("
                    INSERT INTO persons (name, phone, status, created_at, updated_at)
                    VALUES (:name, :phone, 'نشط', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");

                $insert->execute([
                    ':name'  => $full_name,
                    ':phone' => $mobile_number,
                ]);

                header('Location: person_add.php?saved=1');
                exit;

            }

        } catch (PDOException $e) {

            $error = 'رقم الجوال مسجل مسبقًا لشخص آخر';

        }

    }

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
    padding: 30px;
    background-color: #f5f7fa;
}

.white-box {
    width: 100%;
    max-width: 720px;
    padding: 30px;
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
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

.form-group {
    margin-bottom: 25px;
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

.form-group input {
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

.form-group input:focus {
    border-color: #2563eb;
}

.form-group input.field-invalid {
    border-color: #dc2626;
}

.field-error {
    display: none;
    margin-top: 6px;
    color: #dc2626;
    font-size: 12px;
}

.field-hint {
    margin-top: 6px;
    color: #94a3b8;
    font-size: 12px;
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
    .content { padding: 20px; }
    .white-box { padding: 20px; }
}

</style>


<?php include __DIR__ . '/includes/sidebar.php'; ?>


<div class="persons-area">

<main class="content">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">

        <h5 class="fw-bold text-dark mb-0">
            <i class="fa-solid fa-user-plus me-2 text-primary"></i>
            إضافة شخص جديد
        </h5>

        <div>
            <img src="mod_logo.png" alt="شعار وزارة الدفاع" style="height: 35px; width: auto;" class="img-fluid">
        </div>

    </div>

    <div class="white-box">

        <?php if (!empty($success)): ?>
            <div class="success-message">
                ✓ <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="personAddForm" novalidate>

            <div class="form-group">

                <label>
                    الاسم الكامل <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="full_name"
                    id="full_name"
                    maxlength="100"
                    placeholder="مثال: أحمد بن محمد المحمد"
                    value="<?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >

                <div class="field-error" id="full_name_error">
                    يرجى إدخال اسم صحيح لا يقل عن 3 أحرف
                </div>

            </div>

            <div class="form-group">

                <label>
                    رقم الجوال <span class="required">*</span>
                </label>

                <input
                    type="tel"
                    name="mobile_number"
                    id="mobile_number"
                    maxlength="10"
                    inputmode="numeric"
                    placeholder="05xxxxxxxx"
                    value="<?php echo htmlspecialchars($mobile_number, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >

                <div class="field-hint">
                    يبدأ بـ 05 ويتكون من 10 أرقام
                </div>

                <div class="field-error" id="mobile_number_error">
                    رقم الجوال غير صحيح
                </div>

            </div>

            <div class="buttons">

                <button type="submit" class="save-btn">
                    ✓ حفظ الشخص
                </button>

                <button type="button" class="cancel-btn" onclick="window.location.href='person_list.php'">
                    ✕ إلغاء
                </button>

            </div>

        </form>

    </div>

</main>

</div>


<script>

/* التحقق من صحة البيانات في المتصفح قبل الإرسال */

function validatePersonForm(event) {

    let isValid = true;

    const fullName = document.getElementById('full_name');
    const mobileNumber = document.getElementById('mobile_number');

    const fullNameError = document.getElementById('full_name_error');
    const mobileNumberError = document.getElementById('mobile_number_error');

    fullName.classList.remove('field-invalid');
    mobileNumber.classList.remove('field-invalid');
    fullNameError.style.display = 'none';
    mobileNumberError.style.display = 'none';

    if (fullName.value.trim().length < 3) {
        fullName.classList.add('field-invalid');
        fullNameError.style.display = 'block';
        isValid = false;
    }

    const phonePattern = /^05[0-9]{8}$/;

    if (!phonePattern.test(mobileNumber.value.trim())) {
        mobileNumber.classList.add('field-invalid');
        mobileNumberError.style.display = 'block';
        isValid = false;
    }

    if (!isValid) {
        event.preventDefault();
    }

}

document.getElementById('personAddForm').addEventListener('submit', validatePersonForm);

/* السماح بإدخال أرقام فقط في حقل الجوال */

document.getElementById('mobile_number').addEventListener('input', function () {
    this.value = this.value.replace(/[^0-9]/g, '');
});

</script>


<?php include __DIR__ . '/includes/footer.php'; ?>
