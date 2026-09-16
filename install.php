<?php
/**
 * HAvoice — نصب‌کننده‌ی خودکار (Auto Installer)
 * مناسب برای Shared Hosting (InfinityFree) و وب‌سرورهای آپاچی بدون SSH/Node.
 *
 * نشانی وب: https://hajivoice.kesug.com/install.php
 */

declare(strict_types=1);

define('HA_ROOT', __DIR__);
define('HA_INSTALLER_VERSION', '3.5.0');

// تنظیمات امنیتی نشست نصب
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    $sessDir = HA_ROOT . '/storage/sessions';
    if (!is_dir($sessDir)) {
        @mkdir($sessDir, 0700, true);
    }
    if (is_dir($sessDir) && is_writable($sessDir)) {
        ini_set('session.save_path', $sessDir);
    }
    ini_set('session.use_strict_mode', '1');
    session_name('HA_INSTALL');
    session_start();
}

// تولید و اعتبارسنجی توکن CSRF نصب
if (empty($_SESSION['ha_inst_csrf'])) {
    $_SESSION['ha_inst_csrf'] = bin2hex(random_bytes(16));
}
$installerCsrf = $_SESSION['ha_inst_csrf'];

function inst_csrf_field(): string
{
    global $installerCsrf;
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($installerCsrf, ENT_QUOTES, 'UTF-8') . '">';
}

function inst_csrf_verify(): bool
{
    global $installerCsrf;
    $sent = (string) ($_POST['csrf_token'] ?? '');
    return $sent !== '' && hash_equals($installerCsrf, $sent);
}

function inst_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// بررسی قفل نصب
$lockStorage = HA_ROOT . '/storage/installed.lock';
$lockConfig  = HA_ROOT . '/config/installed.lock';
$localConfig = HA_ROOT . '/config/config.local.php';

$isLocked = is_file($lockStorage) || is_file($lockConfig);

// تشخیص خودکار نشانی سایت
$detectedHost = $_SERVER['HTTP_HOST'] ?? 'hajivoice.kesug.com';
$detectedProto = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ||
                 ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443) ||
                 (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') ? 'https' : 'http';
$defaultSiteUrl = $detectedProto . '://' . $detectedHost;

// مراحل نصب
$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
if ($step < 1 || $step > 4) {
    $step = 1;
}

$errorMsg   = '';
$successMsg = '';
$diagInfo   = [];

// اگر قفل باشد، فقط صفحه قفل نمایش داده می‌شود
if ($isLocked) {
    $step = 99; // قفل
}

/* ------------------------------------------------------------------ */
/*  بررسی پیش‌نیازها (Step 1)                                          */
/* ------------------------------------------------------------------ */

$phpVersionOk = version_compare(PHP_VERSION, '7.4.0', '>=');
$extPdo       = extension_loaded('pdo');
$extPdoMysql  = extension_loaded('pdo_mysql');
$extMbstring  = extension_loaded('mbstring');
$extJson      = extension_loaded('json');
$extSession   = extension_loaded('session');
$extFilter    = extension_loaded('filter');
$extOpenssl   = extension_loaded('openssl');
$extCurl      = extension_loaded('curl');

// اطمینان از وجود و دسترسی نوشتن پوشه‌ها
$dirsToCheck = [
    'config'               => HA_ROOT . '/config',
    'storage'              => HA_ROOT . '/storage',
    'storage/sessions'     => HA_ROOT . '/storage/sessions',
    'storage/messages'     => HA_ROOT . '/storage/messages',
    'storage/rate-limit'   => HA_ROOT . '/storage/rate-limit',
    'storage/admin'        => HA_ROOT . '/storage/admin',
    'uploads'              => HA_ROOT . '/uploads',
];

$dirStatus = [];
$allDirsWritable = true;

foreach ($dirsToCheck as $name => $path) {
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
        if (is_dir($path)) {
            @file_put_contents($path . '/index.html', '<!doctype html><title>403</title>');
        }
    }
    $isWritable = is_dir($path) && is_writable($path);
    $dirStatus[$name] = $isWritable;
    if (!$isWritable) {
        $allDirsWritable = false;
    }
}

$allReqsOk = $phpVersionOk && $extPdo && $extPdoMysql && $extMbstring && $extJson && $extSession && $extFilter && $allDirsWritable;

/* ------------------------------------------------------------------ */
/*  پردازش فرم‌های نصب (POST)                                         */
/* ------------------------------------------------------------------ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    if (!inst_csrf_verify()) {
        $errorMsg = 'توکن امنیتی فرم نامعتبر است یا نشست منقضی شده. لطفاً دوباره تلاش کنید.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'install_all') {
            $dbHost     = trim((string) ($_POST['db_host'] ?? 'sql201.infinityfree.com'));
            $dbPort     = (int) ($_POST['db_port'] ?? 3306);
            $dbName     = trim((string) ($_POST['db_name'] ?? 'if0_42876129_hajiahmad'));
            $dbUser     = trim((string) ($_POST['db_user'] ?? 'if0_42876129'));
            $dbPass     = (string) ($_POST['db_pass'] ?? '');
            $siteUrl    = rtrim(trim((string) ($_POST['site_url'] ?? $defaultSiteUrl)), '/');
            $adminName  = trim((string) ($_POST['admin_name'] ?? 'حاجی احمد صالحی'));
            $adminEmail = strtolower(trim((string) ($_POST['admin_email'] ?? 'hajiahmads299@gmail.com')));
            $adminPass  = (string) ($_POST['admin_pass'] ?? 'HAvoice@2026#Admin');

            if ($dbHost === '' || $dbName === '' || $dbUser === '') {
                $errorMsg = 'لطفاً تمام اطلاعات اتصال به دیتابیس (هاست، نام دیتابیس و نام کاربری) را وارد کنید.';
            } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $errorMsg = 'نشانی ایمیل مدیر معتبر نیست.';
            } elseif (strlen($adminPass) < 8) {
                $errorMsg = 'رمز عبور مدیر باید حداقل ۸ کاراکتر باشد.';
            } else {
                // تست اتصال PDO
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
                $pdo = null;
                try {
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    ]);
                } catch (Throwable $e) {
                    $errorMsg = 'خطا در اتصال به پایگاه داده MySQL: ' . $e->getMessage() . '<br><small>اطلاعات هاست، نام کاربری، رمز عبور و دیتابیس را در پنل هاست خود بررسی کنید.</small>';
                }

                if ($pdo !== null) {
                    // اجرای ساخت جداول Schema
                    try {
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

                        $sqlFile = HA_ROOT . '/sql/002-schema.sql';
                        if (!is_file($sqlFile)) {
                            throw new RuntimeException('فایل ساختار دیتابیس (sql/002-schema.sql) یافت نشد.');
                        }
                        $sqlContent = (string) file_get_contents($sqlFile);
                        $sqlContent = preg_replace('/^\s*--.*$/m', '', $sqlContent) ?? $sqlContent;
                        $statements = array_filter(array_map('trim', explode(';', $sqlContent)));

                        foreach ($statements as $stmt) {
                            if ($stmt === '') continue;
                            if (stripos($stmt, 'SET NAMES') === 0 || stripos($stmt, 'SET FOREIGN_KEY') === 0) {
                                $pdo->exec($stmt);
                                continue;
                            }
                            $pdo->exec($stmt);
                        }

                        // تضمین ساخت جدول roles و اضافه شدن نقش‌های پایه
                        $pdo->exec("CREATE TABLE IF NOT EXISTS ha_roles (
                            role_key VARCHAR(20) NOT NULL,
                            label VARCHAR(80) NOT NULL,
                            created_at DATETIME NOT NULL,
                            PRIMARY KEY (role_key)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

                        $pdo->exec("INSERT IGNORE INTO ha_roles (role_key, label, created_at) VALUES
                            ('user', 'کاربر', NOW()),
                            ('admin', 'مدیر', NOW());");

                        // تضمین ستون‌های جدول پیام‌های تماس
                        $pdo->exec("CREATE TABLE IF NOT EXISTS ha_contact_messages (
                            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                            name          VARCHAR(120) NOT NULL DEFAULT '',
                            email         VARCHAR(190) NOT NULL DEFAULT '',
                            subject       VARCHAR(200) NOT NULL DEFAULT '',
                            message       TEXT NOT NULL,
                            status        ENUM('unread','read') NOT NULL DEFAULT 'unread',
                            read_at       DATETIME NULL DEFAULT NULL,
                            ip            VARCHAR(45)  NOT NULL DEFAULT '',
                            created_at    DATETIME NOT NULL,
                            PRIMARY KEY (id),
                            KEY idx_messages_created (created_at),
                            KEY idx_messages_status (status)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

                        // تضمین ثبت Schema Version 4
                        $pdo->prepare("INSERT INTO ha_schema_meta (meta_key, meta_value) VALUES ('version', '4')
                                       ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)")
                            ->execute();

                        // ساخت یا به‌روزرسانی حساب Admin
                        $adminHash = password_hash($adminPass, PASSWORD_DEFAULT);
                        $adminId   = bin2hex(random_bytes(16));
                        $now       = date('Y-m-d H:i:s');

                        $checkUser = $pdo->prepare("SELECT id FROM ha_users WHERE email = ? LIMIT 1");
                        $checkUser->execute([$adminEmail]);
                        $existingUser = $checkUser->fetch();

                        if ($existingUser) {
                            $updateUser = $pdo->prepare("UPDATE ha_users SET name = ?, pass_hash = ?, role = 'admin', updated_at = ? WHERE email = ?");
                            $updateUser->execute([$adminName, $adminHash, $now, $adminEmail]);
                            $adminId = (string) $existingUser['id'];
                        } else {
                            $insertUser = $pdo->prepare("INSERT INTO ha_users (id, name, email, pass_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, 'admin', ?, ?)");
                            $insertUser->execute([$adminId, $adminName, $adminEmail, $adminHash, $now, $now]);
                        }

                        // همگام‌سازی کاربر با storage/users.json (جهت fallback)
                        $jsonUsers = [
                            [
                                'id'         => $adminId,
                                'name'       => $adminName,
                                'email'      => $adminEmail,
                                'pass_hash'  => $adminHash,
                                'role'       => 'admin',
                                'created_at' => date('c'),
                            ]
                        ];
                        @file_put_contents(HA_ROOT . '/storage/users.json', json_encode($jsonUsers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

                        // Seed داده‌های اولیه از data/*.php در صورت خالی بودن
                        require_once HA_ROOT . '/config/config.php';
                        require_once HA_ROOT . '/includes/helpers.php';
                        require_once HA_ROOT . '/includes/db.php';
                        require_once HA_ROOT . '/includes/repository.php';

                        if (function_exists('repo_seed')) {
                            repo_seed(false);
                        }

                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

                        // ایجاد فایل config/config.local.php
                        $configContent = "<?php\n"
                            . "/**\n"
                            . " * HAvoice — پیکربندی محلی (Production)\n"
                            . " * تولیدشده توسط HAvoice Auto Installer در تاریخ " . date('Y-m-d H:i:s') . "\n"
                            . " */\n\n"
                            . "if (!defined('HA_ROOT')) {\n"
                            . "    exit('دسترسی مستقیم ممنوع است.');\n"
                            . "}\n\n"
                            . "/* ---- تنظیمات دیتابیس ---- */\n"
                            . "define('HA_DB_HOST', " . var_export($dbHost, true) . ");\n"
                            . "define('HA_DB_PORT', " . (int)$dbPort . ");\n"
                            . "define('HA_DB_NAME', " . var_export($dbName, true) . ");\n"
                            . "define('HA_DB_USER', " . var_export($dbUser, true) . ");\n"
                            . "define('HA_DB_PASS', " . var_export($dbPass, true) . ");\n\n"
                            . "/* ---- آدرس‌دهی و مسیرها ---- */\n"
                            . "define('HA_SITE_URL', " . var_export($siteUrl, true) . ");\n"
                            . "define('HA_BASE_PATH', '');\n"
                            . "define('HA_PRETTY_URLS', false);\n"
                            . "define('HA_FORCE_HTTPS', false);\n"
                            . "define('HA_DEBUG', false);\n\n"
                            . "/* ---- تنظیمات آپلود ---- */\n"
                            . "define('HA_UPLOAD_MAX_BYTES', 8388608);\n"
                            . "define('HA_UPLOAD_MAX_MEDIA_BYTES', 26214400);\n";

                        @file_put_contents($localConfig, $configContent);
                        @chmod($localConfig, 0644);

                        // ایجاد قفل نصب
                        $lockData = [
                            'installed_at'   => date('c'),
                            'schema_version' => '4',
                            'app_version'    => HA_INSTALLER_VERSION,
                            'db_host'        => $dbHost,
                            'db_name'        => $dbName,
                            'admin_email'    => $adminEmail,
                        ];
                        $lockJson = json_encode($lockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        @file_put_contents($lockStorage, $lockJson);
                        @file_put_contents($lockConfig, $lockJson);
                        @chmod($lockStorage, 0644);
                        @chmod($lockConfig, 0644);

                        $step = 4; // صفحه موفقیت نهایی
                        $successMsg = 'نصب سامانه با موفقیت به پایان رسید!';

                    } catch (Throwable $e) {
                        $errorMsg = 'خطا در حین ساخت جداول و راه‌اندازی دیتابیس: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب و راه‌اندازی سامانه HAvoice</title>
    <style>
        :root {
            --brand: #1A3A7C;
            --brand-soft: #eef2ff;
            --brand-accent: #4F46E5;
            --accent-green: #10B981;
            --accent-green-soft: #ecfdf5;
            --accent-red: #EF4444;
            --accent-red-soft: #fef2f2;
            --accent-amber: #F59E0B;
            --bg: #F8FAFC;
            --surface: #FFFFFF;
            --text: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --r-md: 14px;
            --r-lg: 20px;
            --sh: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            --sh-lg: 0 20px 35px -10px rgba(15, 23, 42, 0.12);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Vazirmatn", Tahoma, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1.5rem 1rem;
        }
        .inst-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            box-shadow: var(--sh-lg);
            width: 100%;
            max-width: 680px;
            overflow: hidden;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .inst-header {
            background: linear-gradient(135deg, #1A3A7C 0%, #312E81 100%);
            color: #ffffff;
            padding: 2rem 2rem 1.75rem;
            text-align: center;
            position: relative;
        }
        .inst-header__brand {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .inst-header__title {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .inst-header__desc {
            color: #C7D2FE;
            font-size: 0.95rem;
            margin-top: 0.25rem;
        }
        .inst-steps {
            display: flex;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 1.5rem;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .inst-step {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .inst-step.is-active {
            color: var(--brand-accent);
            font-weight: 700;
        }
        .inst-step.is-done {
            color: var(--accent-green);
        }
        .inst-step__num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--border);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .inst-step.is-active .inst-step__num {
            background: var(--brand-accent);
            color: #ffffff;
        }
        .inst-step.is-done .inst-step__num {
            background: var(--accent-green);
            color: #ffffff;
        }
        .inst-body {
            padding: 2rem;
        }
        .inst-section-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--brand);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .check-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .check-table th, .check-table td {
            padding: 0.65rem 0.85rem;
            border-bottom: 1px solid var(--border);
            text-align: right;
        }
        .check-table th {
            background: var(--bg);
            font-weight: 600;
            color: var(--text-muted);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .badge--ok { background: var(--accent-green-soft); color: #065f46; }
        .badge--fail { background: var(--accent-red-soft); color: #991b1b; }
        .badge--warn { background: #fffbeb; color: #92400e; }
        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--r-md);
            margin-bottom: 1.5rem;
            font-size: 0.92rem;
            line-height: 1.6;
        }
        .alert--error {
            background: var(--accent-red-soft);
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert--success {
            background: var(--accent-green-soft);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert--warn {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-grid--full {
            grid-template-columns: 1fr;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .form-group label {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text);
        }
        .form-group .hint {
            font-size: 0.78rem;
            color: var(--text-muted);
        }
        .input {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            font-family: inherit;
        }
        .input:focus {
            border-color: var(--brand-accent);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 0.98rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        .btn--primary {
            background: var(--brand-accent);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .btn--primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }
        .btn--green {
            background: var(--accent-green);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .btn--green:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        .btn--ghost {
            background: var(--bg);
            color: var(--text);
            border: 1.5px solid var(--border);
        }
        .btn--ghost:hover {
            background: var(--border);
        }
        .btn--block {
            width: 100%;
        }
        .inst-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        .inst-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.82rem;
            color: var(--text-muted);
        }
        .success-box {
            text-align: center;
            padding: 1.5rem 1rem;
        }
        .success-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--accent-green-soft);
            color: var(--accent-green);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .inst-steps { flex-direction: column; gap: 0.5rem; }
            .inst-body { padding: 1.25rem; }
        }
    </style>
</head>
<body>

<div class="inst-card">
    <header class="inst-header">
        <div class="inst-header__brand">
            <svg viewBox="0 0 32 32" width="36" height="36" fill="none">
                <rect width="32" height="32" rx="9" fill="#ffffff" fill-opacity="0.2"/>
                <rect x="7" y="13" width="2.6" height="6" rx="1.3" fill="#ffffff"/>
                <rect x="11.8" y="9" width="2.6" height="14" rx="1.3" fill="#ffffff"/>
                <rect x="16.6" y="5.5" width="2.6" height="21" rx="1.3" fill="#ffffff"/>
                <rect x="21.4" y="11" width="2.6" height="10" rx="1.3" fill="#ffffff"/>
            </svg>
            <h1 class="inst-header__title">نصب‌کننده‌ی خودکار HAvoice</h1>
        </div>
        <p class="inst-header__desc">آماده‌سازی و پیکربندی پایگاه داده برای میزبانی InfinityFree و وب‌سرور آپاچی</p>
    </header>

    <?php if ($step !== 99): ?>
        <nav class="inst-steps" aria-label="مراحل نصب">
            <div class="inst-step <?= $step >= 1 ? ($step > 1 ? 'is-done' : 'is-active') : '' ?>">
                <span class="inst-step__num">۱</span>
                <span>بررسی پیش‌نیازها</span>
            </div>
            <div class="inst-step <?= $step >= 2 ? ($step > 2 ? 'is-done' : 'is-active') : '' ?>">
                <span class="inst-step__num">۲</span>
                <span>پیکربندی دیتابیس و مدیر</span>
            </div>
            <div class="inst-step <?= $step >= 4 ? 'is-done is-active' : '' ?>">
                <span class="inst-step__num">۳</span>
                <span>تکمیل و قفل امنیتی</span>
            </div>
        </nav>
    <?php endif; ?>

    <main class="inst-body">
        <?php if ($errorMsg !== ''): ?>
            <div class="alert alert--error">
                <strong>خطا در فرآیند نصب:</strong><br>
                <?= $errorMsg ?>
            </div>
        <?php endif; ?>

        <?php if ($successMsg !== '' && $step !== 4): ?>
            <div class="alert alert--success">
                <?= $successMsg ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 99): ?>
            <!-- صفحه قفل امنیتی -->
            <div class="success-box">
                <div class="success-icon" style="background: #eff6ff; color: #1d4ed8;">🔒</div>
                <h2 style="font-size: 1.35rem; margin-bottom: 0.5rem; color: var(--brand);">سامانه قبلاً نصب شده است</h2>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.95rem;">
                    به دلایل امنیتی و جلوگیری از دستکاری پایگاه داده، فایل Installer قفل گردیده است.
                </p>

                <div class="alert alert--warn" style="text-align: right;">
                    <strong>نکته فنی:</strong> چنانچه قصد راه‌اندازی مجدد دارید، فایل <code>storage/installed.lock</code> و <code>config/config.local.php</code> را از طریق پنل File Manager هاست به صورت دستی حذف نمایید.
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; margin-top: 1.5rem;">
                    <a class="btn btn--primary" href="index.php?p=login">ورود به حساب کاربری</a>
                    <a class="btn btn--ghost" href="index.php?p=admin">پیشخوان مدیریت</a>
                    <a class="btn btn--ghost" href="index.php">مشاهده صفحه اصلی سایت</a>
                </div>
            </div>

        <?php elseif ($step === 1): ?>
            <!-- مرحله ۱: بررسی پیش‌نیازها -->
            <h2 class="inst-section-title">بررسی محیط سرور و مجوزها</h2>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;">
                تمام افزونه‌های PHP و پوشه‌های سیستم برای عملکرد کامل بر روی هاست اشتراکی بررسی می‌شوند:
            </p>

            <table class="check-table">
                <thead>
                    <tr>
                        <th>آیتم مورد بررسی</th>
                        <th>نیازمندی</th>
                        <th>وضعیت سرور</th>
                        <th>نتیجه</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>نسخه PHP</td>
                        <td>7.4+ (پیشنهاد 8.0+)</td>
                        <td><?= PHP_VERSION ?></td>
                        <td><?= $phpVersionOk ? '<span class="badge badge--ok">مناسب</span>' : '<span class="badge badge--fail">نامناسب</span>' ?></td>
                    </tr>
                    <tr>
                        <td>افزونه PDO</td>
                        <td>فعال</td>
                        <td><?= $extPdo ? 'نصب شده' : 'یافت نشد' ?></td>
                        <td><?= $extPdo ? '<span class="badge badge--ok">مناسب</span>' : '<span class="badge badge--fail">خطا</span>' ?></td>
                    </tr>
                    <tr>
                        <td>درایور PDO MySQL</td>
                        <td>فعال</td>
                        <td><?= $extPdoMysql ? 'نصب شده' : 'یافت نشد' ?></td>
                        <td><?= $extPdoMysql ? '<span class="badge badge--ok">مناسب</span>' : '<span class="badge badge--fail">خطا</span>' ?></td>
                    </tr>
                    <tr>
                        <td>افزونه mbstring</td>
                        <td>فعال (پشتیبانی فارسی)</td>
                        <td><?= $extMbstring ? 'نصب شده' : 'یافت نشد' ?></td>
                        <td><?= $extMbstring ? '<span class="badge badge--ok">مناسب</span>' : '<span class="badge badge--fail">خطا</span>' ?></td>
                    </tr>
                    <tr>
                        <td>افزونه json & session</td>
                        <td>فعال</td>
                        <td><?= ($extJson && $extSession) ? 'نصب شده' : 'یافت نشد' ?></td>
                        <td><?= ($extJson && $extSession) ? '<span class="badge badge--ok">مناسب</span>' : '<span class="badge badge--fail">خطا</span>' ?></td>
                    </tr>
                    <?php foreach ($dirStatus as $dirName => $isOk): ?>
                        <tr>
                            <td>مجوز نوشتن <code><?= inst_e($dirName) ?>/</code></td>
                            <td>قابل نوشتن (Writable)</td>
                            <td><?= $isOk ? 'مجاز (0755/0777)' : 'غیرقابل نوشتن' ?></td>
                            <td><?= $isOk ? '<span class="badge badge--ok">مناسب</span>' : '<span class="badge badge--fail">نیاز به مجوز</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!$allReqsOk): ?>
                <div class="alert alert--error">
                    برخی از پیش‌نیازها یا مجوزهای پوشه‌ها تأمین نشده است. لطفاً از طریق File Manager سطح دسترسی پوشه‌های <code>storage/</code>، <code>config/</code> و <code>uploads/</code> را به 755 یا 777 تغییر دهید.
                </div>
            <?php endif; ?>

            <div class="inst-actions">
                <a class="btn <?= $allReqsOk ? 'btn--primary' : 'btn--ghost' ?>" href="<?= $allReqsOk ? 'install.php?step=2' : 'install.php?step=1' ?>">
                    <?= $allReqsOk ? 'ادامه به مرحله بعد ←' : 'بررسی مجدد پیش‌نیازها ⟳' ?>
                </a>
            </div>

        <?php elseif ($step === 2): ?>
            <!-- مرحله ۲: دریافت اطلاعات دیتابیس و اطلاعات مدیر -->
            <form method="post" action="install.php?step=2">
                <?= inst_csrf_field() ?>
                <input type="hidden" name="action" value="install_all">

                <h2 class="inst-section-title">اطلاعات اتصال به دیتابیس MySQL (InfinityFree)</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="db_host">نام هاست دیتابیس (MySQL Host)</label>
                        <input class="input" type="text" id="db_host" name="db_host" value="sql201.infinityfree.com" required dir="ltr">
                        <span class="hint">معمولاً <code>sqlXXX.infinityfree.com</code> در پنل MySQL هاست</span>
                    </div>

                    <div class="form-group">
                        <label for="db_port">پورت دیتابیس (Port)</label>
                        <input class="input" type="number" id="db_port" name="db_port" value="3306" required dir="ltr">
                        <span class="hint">پیش‌فرض: 3306</span>
                    </div>

                    <div class="form-group">
                        <label for="db_name">نام پایگاه داده (Database Name)</label>
                        <input class="input" type="text" id="db_name" name="db_name" value="if0_42876129_hajiahmad" required dir="ltr">
                        <span class="hint">نام دیتابیس ساخته‌شده در پنل InfinityFree</span>
                    </div>

                    <div class="form-group">
                        <label for="db_user">نام کاربری دیتابیس (Username)</label>
                        <input class="input" type="text" id="db_user" name="db_user" value="if0_42876129" required dir="ltr">
                        <span class="hint">نام کاربری اکانت هاست</span>
                    </div>

                    <div class="form-group form-grid--full" style="grid-column: span 2;">
                        <label for="db_pass">رمز عبور دیتابیس (Password)</label>
                        <input class="input" type="password" id="db_pass" name="db_pass" placeholder="رمز عبور دیتابیس را وارد کنید" required dir="ltr">
                        <span class="hint">رمز عبور دیتابیس یا همان vPanel Password در InfinityFree</span>
                    </div>

                    <div class="form-group form-grid--full" style="grid-column: span 2;">
                        <label for="site_url">نشانی وب‌سایت (Site URL)</label>
                        <input class="input" type="url" id="site_url" name="site_url" value="<?= inst_e($defaultSiteUrl) ?>" required dir="ltr">
                        <span class="hint">مثال: <code>https://hajivoice.kesug.com</code> (بدون اسلش پایانی)</span>
                    </div>
                </div>

                <h2 class="inst-section-title" style="margin-top: 1.5rem;">اطلاعات حساب کاربری مدیر ارشد (Admin)</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="admin_name">نام و نام خانوادگی مدیر</label>
                        <input class="input" type="text" id="admin_name" name="admin_name" value="حاجی احمد صالحی" required>
                    </div>

                    <div class="form-group">
                        <label for="admin_email">ایمیل مدیر (جهت ورود)</label>
                        <input class="input" type="email" id="admin_email" name="admin_email" value="hajiahmads299@gmail.com" required dir="ltr">
                    </div>

                    <div class="form-group form-grid--full" style="grid-column: span 2;">
                        <label for="admin_pass">رمز عبور اولیه مدیر</label>
                        <input class="input" type="text" id="admin_pass" name="admin_pass" value="HAvoice@2026#Admin" required dir="ltr">
                        <span class="hint">رمز به صورت هش‌شده با <code>password_hash()</code> ذخیره می‌گردد. پس از اولین ورود آن را تغییر دهید.</span>
                    </div>
                </div>

                <div class="inst-actions">
                    <a class="btn btn--ghost" href="install.php?step=1">بازگشت</a>
                    <button class="btn btn--green" type="submit">اتصال به MySQL و راه‌اندازی خودکار ⚡</button>
                </div>
            </form>

        <?php elseif ($step === 4): ?>
            <!-- مرحله نهایی: موفقیت و اتمام نصب -->
            <div class="success-box">
                <div class="success-icon">✓</div>
                <h2 style="font-size: 1.4rem; color: var(--brand); margin-bottom: 0.5rem;">نصب با موفقیت انجام شد!</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
                    تمام جداول پایگاه داده ایجاد شدند، محتوای پایه وارد شد و حساب مدیریت ساخته شد.
                </p>

                <table class="check-table" style="text-align: right; margin-bottom: 1.5rem;">
                    <tbody>
                        <tr>
                            <td>وضعیت اتصال دیتابیس</td>
                            <td><span class="badge badge--ok">متصل شد (utf8mb4)</span></td>
                        </tr>
                        <tr>
                            <td>ساختار جداول (Schema v4)</td>
                            <td><span class="badge badge--ok">۱۷ جدول با قیدهای کلید خارجی ساخته شد</span></td>
                        </tr>
                        <tr>
                            <td>حوزه‌های آموزشی و محتوای پایه</td>
                            <td><span class="badge badge--ok">۱۲ حوزه + دوره‌ها، درس‌ها و مقالات وارد شدند</span></td>
                        </tr>
                        <tr>
                            <td>ایمیل حساب مدیریت</td>
                            <td><code>hajiahmads299@gmail.com</code> (نقش: admin)</td>
                        </tr>
                        <tr>
                            <td>رمز عبور موقت مدیر</td>
                            <td><code>HAvoice@2026#Admin</code></td>
                        </tr>
                        <tr>
                            <td>فایل پیکربندی محلی</td>
                            <td><code>config/config.local.php</code> ایجاد گردید</td>
                        </tr>
                        <tr>
                            <td>قفل امنیتی Installer</td>
                            <td><span class="badge badge--ok">فعال شد (امکان سوءاستفاده مسدود است)</span></td>
                        </tr>
                    </tbody>
                </table>

                <div class="alert alert--warn" style="text-align: right;">
                    <strong>توصیه امنیتی:</strong> لطفاً بلافاصله وارد پنل مدیریت شوید و از بخش <strong>حساب کاربری</strong>، رمز عبور موقت خود را به یک رمز شخصی و قدرتمند تغییر دهید.
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; margin-top: 1.5rem;">
                    <a class="btn btn--primary" href="index.php?p=login">ورود به پنل مدیریت</a>
                    <a class="btn btn--ghost" href="index.php?p=admin">پیشخوان مدیریت</a>
                    <a class="btn btn--ghost" href="index.php">مشاهده صفحه اصلی سایت</a>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<footer class="inst-footer">
    سامانه آموزش مهارت‌های کاربردی HAvoice · نسخه <?= HA_INSTALLER_VERSION ?>
</footer>

</body>
</html>
