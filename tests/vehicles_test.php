<?php
/**
 * tests/vehicles_test.php
 * اختبارات وحدة المركبات: البحث، الفلاتر، التحقق من المدخلات، شارات الحالة، رقم العهدة، ومنع حذف مركبة لها سجل.
 *
 * التشغيل من سطر الأوامر داخل مجلد المشروع:
 *   php tests/vehicles_test.php
 */

require_once __DIR__ . '/../includes/vehicle_helpers.php';

$passed = 0;
$failed = 0;

function assert_true(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ✓ $message\n";
    } else {
        $failed++;
        echo "  ✗ FAILED: $message\n";
    }
}

function assert_equals($expected, $actual, string $message): void
{
    assert_true($expected === $actual, "$message (متوقع: " . var_export($expected, true) . " — فعلي: " . var_export($actual, true) . ")");
}

// ------------------------------------------------------------------
// 1) اختبارات build_vehicle_filters
// ------------------------------------------------------------------
echo "1) اختبارات بناء فلاتر البحث\n";

[$sql, $params] = build_vehicle_filters(null, null, null);
assert_equals('', $sql, 'بدون أي معايير يجب ألا تكون هناك جملة WHERE');

[$sql, $params] = build_vehicle_filters('كامري', '', '');
assert_true(str_contains($sql, 'v.plate_number LIKE :search'), 'يجب أن تبحث في رقم اللوحة');
assert_equals('%كامري%', $params[':search'], 'يجب تغليف نص البحث بعلامات %');

[$sql, $params] = build_vehicle_filters('', 'تويوتا', '');
assert_equals('تويوتا', $params[':type'], 'قيمة فلتر النوع صحيحة');

[$sql, $params] = build_vehicle_filters('', '', 'مسلمة');
assert_equals('مسلمة', $params[':status'], 'قيمة فلتر الحالة صحيحة');

// ------------------------------------------------------------------
// 2) اختبارات شارة الحالة
// ------------------------------------------------------------------
echo "\n2) اختبارات شارة الحالة\n";

assert_true(str_contains(vehicle_status_badge_class('مسلمة'), 'status-delivered'), 'حالة "مسلمة" تُعرض بكلاس status-delivered');
assert_true(str_contains(vehicle_status_badge_class('متاحة'), 'status-available'), 'حالة "متاحة" تُعرض بكلاس status-available');

// ------------------------------------------------------------------
// 3) اختبارات التحقق من مدخلات نموذج المركبة
// ------------------------------------------------------------------
echo "\n3) اختبارات التحقق من صحة المدخلات\n";

$errors = validate_vehicle_input([]);
assert_true(isset($errors['plate_number']), 'رقم اللوحة مطلوب عند تركه فارغًا');
assert_true(isset($errors['type']), 'نوع المركبة مطلوب عند تركه فارغًا');
assert_true(isset($errors['model']), 'الموديل مطلوب عند تركه فارغًا');
assert_true(isset($errors['department']), 'الإدارة/القسم مطلوب عند تركه فارغًا');

$errors = validate_vehicle_input([
    'plate_number' => 'ط ب د 4821',
    'type' => 'تويوتا',
    'model' => '2026',
    'department' => 'قسم الإمداد',
]);
assert_equals([], $errors, 'بيانات كاملة وصحيحة يجب ألا تُنتج أي أخطاء (الملاحظات ليست إلزامية)');

// ------------------------------------------------------------------
// 4) اختبارات تكامل مع قاعدة بيانات SQLite مؤقتة في الذاكرة
// ------------------------------------------------------------------
echo "\n4) اختبارات تكامل مع قاعدة بيانات مؤقتة (بحث، فلاتر، رقم العهدة، ومنع الحذف)\n";

try {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("
        CREATE TABLE vehicles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plate_number VARCHAR(20) UNIQUE NOT NULL,
            type VARCHAR(50) NOT NULL,
            model VARCHAR(50) NOT NULL,
            color VARCHAR(30),
            department VARCHAR(100),
            notes TEXT,
            status VARCHAR(20) DEFAULT 'متاحة'
        );
    ");
    $pdo->exec("
        CREATE TABLE persons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) UNIQUE NOT NULL,
            status VARCHAR(20) DEFAULT 'نشط'
        );
    ");
    $pdo->exec("
        CREATE TABLE custody (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vehicle_id INTEGER NOT NULL,
            person_id INTEGER NOT NULL,
            custody_type VARCHAR(20) NOT NULL,
            start_date DATE NOT NULL,
            expected_return_date DATE,
            actual_return_date DATE,
            has_deduction INTEGER NOT NULL DEFAULT 0,
            decision_reference VARCHAR(100),
            notes TEXT,
            status VARCHAR(20) DEFAULT 'نشطة'
        );
    ");

    $pdo->exec("INSERT INTO vehicles (plate_number, type, model, color, department, status) VALUES
        ('أ ب ج 1234', 'تويوتا', 'كامري 2022', 'أبيض', 'إدارة التشغيل', 'متاحة'),
        ('د هـ و 5678', 'هيونداي', 'سوناتا 2023', 'فضي', 'قسم الإمداد', 'مسلمة'),
        ('ر ز س 9012', 'فورد', 'تاورس 2021', 'أسود', 'الشؤون الإدارية', 'مسلمة')
    ");
    $pdo->exec("INSERT INTO persons (name, phone) VALUES ('سارة العتيبي', '0559876543')");
    $pdo->exec("INSERT INTO custody (vehicle_id, person_id, custody_type, start_date, status) VALUES
        (2, 1, 'مؤقتة', '2026-08-01', 'نشطة')
    ");
    // عهدة سابقة منتهية على المركبة الثالثة (يجب أن تمنع حذفها رغم أنها بلا عهدة نشطة الآن)
    $pdo->exec("INSERT INTO custody (vehicle_id, person_id, custody_type, start_date, actual_return_date, status) VALUES
        (3, 1, 'مؤقتة', '2026-05-01', '2026-05-10', 'مكتملة')
    ");

    $all = fetch_vehicles($pdo, null, null, null);
    assert_equals(3, count($all), 'بدون فلاتر يجب أن تُعاد كل المركبات الثلاث');

    $bySearch = fetch_vehicles($pdo, 'كامري', null, null);
    assert_equals(1, count($bySearch), 'البحث بـ "كامري" يجب أن يعيد مركبة واحدة فقط');

    $byType = fetch_vehicles($pdo, null, 'فورد', null);
    assert_equals(1, count($byType), 'فلتر النوع "فورد" يجب أن يعيد مركبة واحدة');

    $byStatus = fetch_vehicles($pdo, null, null, 'مسلمة');
    assert_equals(2, count($byStatus), 'فلتر الحالة "مسلمة" يجب أن يعيد مركبتين');

    $vehicleWithHolder = array_values(array_filter($byStatus, fn($v) => $v['plate_number'] === 'د هـ و 5678'))[0];
    assert_equals('سارة العتيبي', $vehicleWithHolder['current_holder'], 'المستلم الحالي يجب أن يظهر بشكل صحيح عبر الربط');
    assert_equals(1, (int) $vehicleWithHolder['current_custody_id'], 'رقم العهدة الحالية يجب أن يظهر بشكل صحيح');

    $vehicleReturned = array_values(array_filter($byStatus, fn($v) => $v['plate_number'] === 'ر ز س 9012'))[0];
    assert_true($vehicleReturned['current_custody_id'] === null, 'مركبة بلا عهدة نشطة (رغم وجود عهدة سابقة مكتملة) يجب ألا يكون لها رقم عهدة حالية');

    // اختبار منع الحذف
    assert_true(vehicle_has_custody_history($pdo, 2) === true, 'مركبة لها عهدة نشطة يجب أن تُمنع من الحذف');
    assert_true(vehicle_has_custody_history($pdo, 3) === true, 'مركبة لها عهدة سابقة منتهية فقط يجب أن تُمنع من الحذف أيضًا (حفظ السجل التاريخي)');
    assert_true(vehicle_has_custody_history($pdo, 1) === false, 'مركبة بدون أي سجل عهد يجب أن يُسمح بحذفها');

} catch (Throwable $e) {
    $failed++;
    echo "  ✗ FAILED: خطأ غير متوقع أثناء اختبارات التكامل: " . $e->getMessage() . "\n";
}

// ------------------------------------------------------------------
echo "\n----------------------------------------\n";
echo "النتيجة: نجح $passed | فشل $failed\n";
exit($failed > 0 ? 1 : 0);
