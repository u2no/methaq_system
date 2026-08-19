<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

/* بيانات الدخول المؤقتة */
$validUsername = 'admin';
$validPassword = '1234';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } elseif (
        hash_equals($validUsername, $username) &&
        hash_equals($validPassword, $password)
    ) {
        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;

        header('Location: index.php');
        exit;
    } else {
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | منصة ميثاق</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(135deg, #eef4fb 0%, #dfeaf7 100%);
            color: #243b53;
            font-family: Arial, Tahoma, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 410px;
            padding: 34px;
            background-color: #ffffff;
            border: 1px solid #dce6f0;
            border-radius: 14px;
            box-shadow: 0 14px 35px rgba(30, 41, 59, 0.13);
        }

        .brand {
            margin-bottom: 28px;
            text-align: center;
        }

        .brand img {
            width: auto;
            height: 62px;
            margin-bottom: 14px;
        }

        .brand h1 {
            margin: 0 0 7px;
            color: #1e3a5f;
            font-size: 24px;
        }

        .brand p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: bold;
        }

        input {
            width: 100%;
            height: 46px;
            padding: 0 13px;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            outline: none;
            color: #1e293b;
            font-family: Arial, Tahoma, sans-serif;
            font-size: 14px;
            text-align: left;
        }

        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .error-message {
            margin-bottom: 18px;
            padding: 11px 13px;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 7px;
            color: #b91c1c;
            font-size: 13px;
            text-align: center;
        }

        .login-button {
            width: 100%;
            height: 46px;
            margin-top: 5px;
            background-color: #1457c5;
            border: none;
            border-radius: 7px;
            color: #ffffff;
            font-family: Arial, Tahoma, sans-serif;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .login-button:hover {
            background-color: #0f46a2;
        }

        .footer-note {
            margin: 22px 0 0;
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 27px 22px;
            }
        }
    </style>
</head>

<body>

    <main class="login-card">

        <div class="brand">
            <img src="mod_logo.png" alt="شعار وزارة الدفاع">
            <h1>منصة ميثاق</h1>
            <p>نظام إدارة عهد المركبات العسكرية</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="error-message">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">

            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input
                    type="text"
                    name="username"
                    id="username"
                    value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                    dir="ltr"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    dir="ltr"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="login-button">
                تسجيل الدخول
            </button>

        </form>

        <p class="footer-note">قسم الإمداد</p>

    </main>

</body>
</html>