<?php
/**
 * HAvoice — پردازش فرم تماس.
 * قبل از رندر قالب اجرا می‌شود تا بتواند Redirect کند (الگوی PRG).
 * امنیت: توکن CSRF + فیلد زنبوری (honeypot) + محدودیت نرخ + اعتبارسنجی سخت‌گیرانه.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$backUrl = HA_BASE_PATH . '/index.php?p=contact';
if (HA_PRETTY_URLS) {
    $backUrl = rtrim(HA_BASE_PATH, '/') . '/contact';
}

/* ۱) فقط POST */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect($backUrl);
}

/* ۲) بررسی CSRF */
if (!csrf_verify()) {
    flash('error', 'نشست شما تمام شده است. لطفاً فرم را دوباره پر کنید.');
    redirect($backUrl);
}

/* ۳) فیلد زنبوری: ربات‌ها آن را پر می‌کنند */
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    flash('error', 'پیام شما ثبت شد.'); // بی‌سروصدا دور انداخته می‌شود
    redirect($backUrl);
}

/* ۴) محدودیت نرخ بر پایه‌ی IP */
$ipKey = substr(sha1((string) ($_SERVER['REMOTE_ADDR'] ?? 'na') . '|' . date('YmdH')), 0, 16);
$rateDir = storage_dir('rate-limit');
if (!is_dir($rateDir)) {
    @mkdir($rateDir, 0755, true);
}
$bucket = $rateDir . '/' . $ipKey . '.json';

if (is_file($bucket)) {
    $stats = json_decode((string) @file_get_contents($bucket), true);
    $count = is_array($stats) ? (int) ($stats['n'] ?? 0) : 0;
    $time  = is_array($stats) ? (int) ($stats['t'] ?? 0) : 0;

    if (time() - $time < HA_RATE_LIMIT_WINDOW && $count >= HA_RATE_LIMIT_MAX) {
        flash('error', 'چند پیام پشت‌سرهم فرستادید. لطفاً ' . fa_num((int) ceil((HA_RATE_LIMIT_WINDOW - (time() - $time)) / 60)) . ' دقیقه‌ی دیگر دوباره تلاش کنید.');
        redirect($backUrl);
    }

    $count++;
} else {
    $count = 1;
    $time  = time();
}

@file_put_contents($bucket, json_encode(['n' => $count, 't' => $time]), LOCK_EX);

/* ۵) اعتبارسنجی ورودی‌ها */
$name    = trim((string) ($_POST['name'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$body    = trim((string) ($_POST['message'] ?? ''));
$consent = isset($_POST['consent']) && $_POST['consent'] === '1';

$errors  = [];
$allowed = array_keys((array) (data('site')['contact']['subjects'] ?? []));

if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 60) {
    $errors['name'] = 'نام را کامل بنویسید (بین ۲ تا ۶۰ نویسه).';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
    $errors['email'] = 'نشانی ایمیل معتبر نیست.';
}

if ($allowed !== [] && !in_array($subject, $allowed, true)) {
    $errors['subject'] = 'یک موضوع از فهرست انتخاب کنید.';
}

$bodyLen = mb_strlen($body, 'UTF-8');
if ($bodyLen < 20) {
    $errors['message'] = 'متن پیام باید حداقل ۲۰ نویسه باشد.';
} elseif ($bodyLen > 2000) {
    $errors['message'] = 'متن پیام کوتاه‌تر کنید (حداکثر ۲۰۰۰ نویسه).';
}

if (!$consent) {
    $errors['consent'] = 'برای نگهداری پیام جهت پاسخ‌گویی، تأیید را بزنید.';
}

if ($errors !== []) {
    old_set(['name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $body]);
    $_SESSION['ha_errors'] = $errors;
    flash('error', 'چند مورد در فرم نیاز به اصلاح دارد.');
    redirect($backUrl);
}

/* ۶) پاک‌سازی نهایی: حذف کاراکترهای کنترلی و تزریق سرصفحه */
$clean = static function (string $value): string {
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

    return trim((string) $value);
};

$record = [
    'time'    => date('Y-m-d H:i:s'),
    'ip'      => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    'name'    => $clean(str_replace("\n", ' ', $name)),
    'email'   => $clean(str_replace("\n", ' ', $email)),
    'subject' => $clean(str_replace("\n", ' ', $subject)),
    'message' => $clean($body),
];

/* ۷) ذخیره در فایل (CSV تا با اکسل/متن باز شود) */
$saved = false;

if (HA_STORE_MESSAGES) {
    $dir  = storage_dir('messages');
    $file = $dir . '/messages.csv';
    $new  = !is_file($file);

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    if (is_dir($dir) && is_writable($dir)) {
        $fp = fopen($file, 'a');
        if ($fp !== false) {
            if (flock($fp, LOCK_EX)) {
                if ($new) {
                    fputcsv($fp, ['زمان', 'موضوع', 'نام', 'ایمیل', 'متن', 'IP']);
                }
                fputcsv($fp, [
                    $record['time'],
                    $record['subject'],
                    $record['name'],
                    $record['email'],
                    str_replace("\n", ' / ', $record['message']),
                    $record['ip'],
                ]);
                flock($fp, LOCK_UN);
                $saved = true;
            }
            fclose($fp);
        }
    }
}

/* ۸) ایمیل (اختیاری — روی InfinityFree معمولاً مسدود است) */
if (HA_SEND_MAIL) {
    $safeName = str_replace(["\r", "\n"], '', $record['name']);
    $subjectLine = 'پیام جدید از سایت های‌ویس: ' . str_replace(["\r", "\n"], '', $record['subject']);
    $headers = [
        'From: ' . sprintf('%s <%s>', $safeName !== '' ? $safeName : HA_LEGAL, 'no-reply@localhost'),
        'Reply-To: ' . $record['email'],
        'Content-Type: text/plain; charset=UTF-8',
    ];
    @mail(HA_EMAIL, $subjectLine, $record['message'] . "\n\n— " . $record['name'] . ' <' . $record['email'] . '>', implode("\r\n", $headers));
}

old_clear();
unset($_SESSION['ha_errors']);

if ($saved) {
    flash('success', 'پیام شما ثبت شد. ممنون! معمولاً تا دو روز کاری با همان ایمیل پاسخ می‌دهیم.');
} else {
    flash('error', 'متأسفانه سامانه‌ی ثبت پیام در دسترس نیست. لطفاً با ایمیل ' . HA_EMAIL . ' در تماس باشید.');
}

redirect($backUrl);
