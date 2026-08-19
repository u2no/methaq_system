<?php
/**
 * includes/vehicle_helpers.php
 * دوال مساعدة مشتركة لوحدة المركبات (وحدة الشخص الثاني).
 * تم فصلها في ملف مستقل حتى يسهل اختبارها من ملف tests/vehicles_test.php
 * دون الحاجة لتشغيل خادم ويب كامل.
 */

/** القيم المسموح بها لحالة المركبة (محسوبة تلقائيًا من العهد، لا تُدخل يدويًا) */
function vehicle_status_options(): array
{
    return ['متاحة', 'مسلمة'];
}

/**
 * يبني جملة WHERE وقيم البحث/الفلترة لقائمة المركبات.
 * البحث يتم فقط برقم اللوحة (حسب طلب الفريق).
 * تُعاد كمصفوفة [$whereSql, $params] بحيث يسهل اختبارها دون الحاجة لقاعدة بيانات حقيقية.
 */
function build_vehicle_filters(?string $search, ?string $type, ?string $status): array
{
    $conditions = [];
    $params = [];

    $search = trim((string) $search);
    if ($search !== '') {
        $conditions[] = 'v.plate_number LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    $type = trim((string) $type);
    if ($type !== '' && $type !== 'الكل' && $type !== 'كل الأنواع') {
        $conditions[] = 'v.type = :type';
        $params[':type'] = $type;
    }

    $status = trim((string) $status);
    if ($status === 'متاحة') {
        $conditions[] = 'c.id IS NULL';
    } elseif ($status === 'مسلمة') {
        $conditions[] = 'c.id IS NOT NULL';
    }

    $whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

    return [$whereSql, $params];
}

/** يعيد كلاس شارة الحالة (نشط/غير نشط) */
function vehicle_status_badge_class(string $status): string
{
    return $status === 'مسلمة' ? 'status-badge status-delivered' : 'status-badge status-available';
}

/**
 * يجلب قائمة المركبات مع المستلم الحالي ونوع العهدة الحالية (إن وُجدت عهدة نشطة)
 * عبر LEFT JOIN مع جدولي العهد والأشخاص، مع تطبيق البحث والفلاتر.
 */
function fetch_vehicles(PDO $pdo, ?string $search, ?string $type, ?string $status): array
{
    [$whereSql, $params] = build_vehicle_filters($search, $type, $status);

    $sql = "
        SELECT
            v.id,
            v.plate_number,
            v.type,
            v.model,
            v.color,
            v.notes,
            CASE
                WHEN c.id IS NOT NULL THEN 'مسلمة'
                ELSE 'متاحة'
            END AS status,
            p.name AS current_holder,
            c.custody_type AS current_custody_type
        FROM vehicles v
        LEFT JOIN custody c
            ON c.vehicle_id = v.id
            AND (c.actual_return_date IS NULL OR c.actual_return_date = '')
        LEFT JOIN persons p ON p.id = c.person_id
        $whereSql
        ORDER BY v.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** يجلب قائمة الأنواع المختلفة الموجودة فعليًا في جدول المركبات (لتعبئة فلتر النوع) */
function fetch_vehicle_types(PDO $pdo): array
{
    return $pdo->query("SELECT DISTINCT type FROM vehicles ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);
}

/** يجلب بيانات مركبة واحدة مع المستلم الحالي ونوع العهدة الحالية */
function fetch_vehicle_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            v.id,
            v.plate_number,
            v.type,
            v.model,
            v.color,
            v.notes,
            CASE
                WHEN c.id IS NOT NULL THEN 'مسلمة'
                ELSE 'متاحة'
            END AS status,
            p.name AS current_holder,
            p.phone AS current_holder_phone,
            c.custody_type AS current_custody_type,
            c.start_date AS current_start_date,
            c.expected_return_date AS current_expected_return_date
        FROM vehicles v
        LEFT JOIN custody c
            ON c.vehicle_id = v.id
            AND (c.actual_return_date IS NULL OR c.actual_return_date = '')
        LEFT JOIN persons p ON p.id = c.person_id
        WHERE v.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** يجلب سجل عهد مركبة معينة (كل العهد، الحالية والمنتهية) مرتبة من الأحدث للأقدم */
function fetch_vehicle_custody_history(PDO $pdo, int $vehicleId): array
{
    $stmt = $pdo->prepare("
        SELECT
            c.*,
            p.name AS person_name,
            CASE
                WHEN
                    c.actual_return_date IS NOT NULL
                    AND c.actual_return_date <> ''
                THEN 'مكتملة'
                WHEN
                    c.custody_type = 'مؤقتة'
                    AND c.expected_return_date IS NOT NULL
                    AND c.expected_return_date <> ''
                    AND date(c.expected_return_date) < date(:today_status)
                THEN 'متأخرة'
                ELSE 'نشطة'
            END AS display_status
        FROM custody c
        JOIN persons p ON p.id = c.person_id
        WHERE c.vehicle_id = :vehicle_id
        ORDER BY c.start_date DESC, c.id DESC
    ");
    $stmt->execute([
        ':vehicle_id' => $vehicleId,
        ':today_status' => date('Y-m-d')
    ]);
    return $stmt->fetchAll();
}

/** يتحقق هل للمركبة أي سجل عهدة (نشطة أو سابقة) - يُستخدم لمنع حذف مركبة لها سجل */
function vehicle_has_custody_history(PDO $pdo, int $vehicleId): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM custody WHERE vehicle_id = :vehicle_id");
    $stmt->execute([':vehicle_id' => $vehicleId]);
    return (int) $stmt->fetchColumn() > 0;
}

/** تحقق أساسي من صحة بيانات نموذج إضافة/تعديل مركبة، يعيد مصفوفة أخطاء (فارغة = لا أخطاء) */
function validate_vehicle_input(array $data): array
{
    $errors = [];

    if (trim((string) ($data['plate_number'] ?? '')) === '') {
        $errors['plate_number'] = 'رقم اللوحة مطلوب.';
    }
    if (trim((string) ($data['type'] ?? '')) === '') {
        $errors['type'] = 'نوع المركبة مطلوب.';
    }
    if (trim((string) ($data['model'] ?? '')) === '') {
        $errors['model'] = 'الموديل مطلوب.';
    }

    return $errors;
}