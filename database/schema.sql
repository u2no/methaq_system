-- database/schema.sql - هيكل قواعد البيانات والجداول المعتمد

-- 1. جدول المركبات (Vehicles)
CREATE TABLE IF NOT EXISTS vehicles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    color VARCHAR(30),
    department VARCHAR(100), -- الإدارة / القسم المالك أو المستخدم للمركبة
    notes TEXT, -- ملاحظات إضافية عن المركبة
    status VARCHAR(20) DEFAULT 'متاحة' -- (متاحة / مسلمة)
);

-- 2. جدول الموظفين/الأشخاص (Persons)
CREATE TABLE IF NOT EXISTS persons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'نشط' -- (نشط / غير نشط)
);

-- 3. جدول العهدة (Custody) - يربط المركبة بالموظف
CREATE TABLE IF NOT EXISTS custody (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER NOT NULL,
    person_id INTEGER NOT NULL,
    custody_type VARCHAR(20) NOT NULL, -- (دائمة / مؤقتة)
    start_date DATE NOT NULL,
    expected_return_date DATE,
    actual_return_date DATE,

    has_deduction INTEGER NOT NULL DEFAULT 0, -- 0 = لا ، 1 = نعم
    decision_reference VARCHAR(100), -- رقم القرار المرجعي - اختياري

    notes TEXT,
    status VARCHAR(20) DEFAULT 'نشطة', -- (نشطة / مكتملة / متأخرة)

    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (person_id) REFERENCES persons(id)
);

-- --------------------------------------------------------
-- إدراج بيانات تجريبية مبدئية لاختبار لوحة التحكم والأرقام
-- --------------------------------------------------------

-- بيانات مركبات تجريبية
INSERT INTO vehicles (plate_number, type, model, color, department, notes, status) VALUES 
('أ ب ج 1234', 'تويوتا', 'كامري 2022', 'أبيض', 'إدارة التشغيل', NULL, 'متاحة'),
('د هـ و 5678', 'هيونداي', 'سوناتا 2023', 'فضي', 'قسم الإمداد', NULL, 'مسلمة'),
('ر ز س 9012', 'فورد', 'تاورس 2021', 'أسود', 'الشؤون الإدارية', NULL, 'مسلمة');

-- بيانات موظفين تجريبيين
INSERT INTO persons (name, phone, status) VALUES 
('أحمد المحمد', '0501234567', 'نشط'),
('سارة العتيبي', '0559876543', 'نشط'),
('خالد الدوسري', '0541122334', 'نشط');

-- بيانات عهد تجريبية (واحدة نشطة، وواحدة متأخرة لاختبار التنبيهات والبطاقات)
INSERT INTO custody (vehicle_id, person_id, custody_type, start_date, expected_return_date, status, notes) VALUES 
(2, 2, 'مؤقتة', '2026-08-01', '2026-08-15', 'نشطة', 'عهدة مؤقتة لمشروع ميداني'),
(3, 3, 'مؤقتة', '2026-07-01', '2026-07-15', 'متأخرة', 'تأخر في إعادة المركبة');