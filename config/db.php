<?php
// config/db.php - الاتصال بقاعدة البيانات SQLite باستخدام PDO

try {
    // إنشاء أو فتح قاعدة البيانات SQLite داخل مجلد database
    $db_path = __DIR__ . '/../database/database.sqlite';
    $pdo = new PDO("sqlite:" . $db_path);
    
    // تفعيل وضع الأخطاء للاستثناءات
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // تفعيل المفاتيح الأجنبية
    $pdo->exec("PRAGMA foreign_keys = ON;");

    // ------------------------------------------------------------
    // ترقية بسيطة وآمنة لقاعدة البيانات (Migration):
    // تضيف عمودي "الإدارة / القسم" و"ملاحظات" لجدول المركبات إن لم يكونا
    // موجودين، حتى لا تنكسر قاعدة البيانات الموجودة لدى بقية الفريق.
    // ------------------------------------------------------------
    $vehicleColumns = $pdo->query("PRAGMA table_info(vehicles)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('department', $vehicleColumns, true)) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN department VARCHAR(100)");
    }
    if (!in_array('notes', $vehicleColumns, true)) {
        $pdo->exec("ALTER TABLE vehicles ADD COLUMN notes TEXT");
    }

} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}
?>