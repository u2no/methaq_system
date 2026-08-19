-- database/schema.sql - هيكل قواعد البيانات والجداول المعتمد

-- 1. جدول المركبات (Vehicles)
CREATE TABLE IF NOT EXISTS vehicles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    color VARCHAR(30),
    notes TEXT -- ملاحظات إضافية عن المركبة
);

-- 2. جدول الموظفين/الأشخاص (Persons)
CREATE TABLE IF NOT EXISTS persons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'نشط'
    CHECK (status IN ('نشط', 'غير نشط'))
);

-- 3. جدول العهدة (Custody) - يربط المركبة بالموظف
CREATE TABLE IF NOT EXISTS custody (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER NOT NULL,
    person_id INTEGER NOT NULL,
    custody_type VARCHAR(20) NOT NULL
    CHECK (custody_type IN ('دائمة', 'مؤقتة')),
    start_date DATE NOT NULL,
    expected_return_date DATE,
    actual_return_date DATE,

    has_deduction INTEGER NOT NULL DEFAULT 0
    CHECK (has_deduction IN (0, 1)),
    decision_reference VARCHAR(100), -- رقم القرار المرجعي - اختياري

    notes TEXT,
    status VARCHAR(20) DEFAULT 'نشطة', -- (نشطة / مكتملة / متأخرة)

    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (person_id) REFERENCES persons(id),

    CHECK (date(start_date) IS NOT NULL),

    CHECK (
        (
            custody_type = 'دائمة'
            AND expected_return_date IS NULL
        )
        OR
        (
            custody_type = 'مؤقتة'
            AND expected_return_date IS NOT NULL
            AND date(expected_return_date) IS NOT NULL
            AND date(expected_return_date) >= date(start_date)
        )
    ),

    CHECK (
        actual_return_date IS NULL
        OR (
            date(actual_return_date) IS NOT NULL
            AND date(actual_return_date) >= date(start_date)
        )
    )
);

-- منع وجود أكثر من عهدة نشطة للشخص نفسه
CREATE UNIQUE INDEX IF NOT EXISTS unique_active_person
ON custody(person_id)
WHERE actual_return_date IS NULL OR actual_return_date = '';

-- منع وجود أكثر من عهدة نشطة للمركبة نفسها
CREATE UNIQUE INDEX IF NOT EXISTS unique_active_vehicle
ON custody(vehicle_id)
WHERE actual_return_date IS NULL OR actual_return_date = '';