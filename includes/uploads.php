<?php
/**
 * HAvoice — آپلودِ امنِ فایل توسط مدیر
 *
 * تصمیم‌های امنیتی:
 *  • پسوند فقط از فهرستِ سفید می‌آید و از MIMEِ واقعیِ فایل (finfo)
 *    تأیید می‌شود — نه از نامِ فایلِ کاربر. نامِ نهایی تصادفی است، پس
 *    path traversal و overwriteِ عمدی منتفی‌اند.
 *  • فایل‌ها در uploads/ می‌روند که .htaccess خودش اجرای هر اسکریپتی
 *    را در آن مسدود می‌کند (دفاعِ لایه‌ی دوم در برابر web-shell).
 *  • سقفِ حجم از config (HA_UPLOAD_MAX_BYTES) می‌آید.
 *  • روی میزبانیِ read-only (Vercel) به‌جای خطای خاموش، پیامِ شفاف
 *    برمی‌گردد تا مدیر از فیلدِ «نشانیِ خارجی» استفاده کند.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/** پوشه‌ی آپلود (ریشه‌ی وب تا فایل مستقیم قابلِ سرو شدن باشد). */
function ha_uploads_dir(): string
{
    return HA_ROOT . '/uploads';
}

/**
 * فایلِ ارسالیِ یک فیلد را (در صورت وجود) با اعتبارسنجی ذخیره می‌کند.
 *
 * @param string $field    نامِ فیلدِ فایل در $_FILES
 * @param array  $mimeMap  نگاشتِ MIME ⇒ پسوند، مثل ['application/pdf' => 'pdf']
 * @return array{ok:bool, path:string, name:string, error:?string}
 *             ok=true و path خالی یعنی «فایلی ارسال نشده» (اختیاری).
 */
function ha_upload_store(string $field, array $mimeMap): array
{
    $none = ['ok' => true, 'path' => '', 'name' => '', 'error' => null];
    $fail = static function (string $error): array {
        return ['ok' => false, 'path' => '', 'name' => '', 'error' => $error];
    };

    $info = $_FILES[$field] ?? null;
    if (!is_array($info) || !isset($info['error'])) {
        return $none; // اصلاً فایلی ارسال نشده
    }
    $err = (int) $info['error'];
    if ($err === UPLOAD_ERR_NO_FILE) {
        return $none;
    }
    if ($err !== UPLOAD_ERR_OK) {
        return $fail($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE
            ? 'حجمِ فایل از سقفِ مجازِ سرور بیشتر است.'
            : 'آپلود کامل نشد (کدِ خطا: ' . $err . ').');
    }

    $tmp  = (string) ($info['tmp_name'] ?? '');
    $size = (int) ($info['size'] ?? 0);
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return $fail('فایلِ موقتِ آپلود معتبر نیست.');
    }
    $max = (int) (defined('HA_UPLOAD_MAX_BYTES') ? HA_UPLOAD_MAX_BYTES : 8388608);
    if ($size <= 0 || $size > $max) {
        return $fail('حجمِ فایل باید بین ۱ بایت تا ' . fa_num((int) round($max / 1048576)) . ' مگابایت باشد.');
    }

    /* MIMEِ واقعی با finfo — به content-type ارسالیِ کلاینت اعتماد نمی‌کنیم. */
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = (string) @finfo_file($finfo, $tmp);
            @finfo_close($finfo);
        }
    }
    if ($mime === '' || !isset($mimeMap[$mime])) {
        return $fail('نوعِ فایل مجاز نیست؛ فقط: ' . implode('، ', array_values(array_unique($mimeMap))) . '.');
    }
    $ext = strtolower((string) $mimeMap[$mime]);

    /* پسوندِ نامِ اصلی فقط برای ردِ double-extension خطرناک (file.php.jpg) */
    $origName = (string) ($info['name'] ?? '');
    $origName = str_replace(["\0", '\\', '/', '..'], '', $origName);
    if (preg_match('/\.(php|phtml|phar|cgi|pl|py|sh|exe|htaccess|js|html|htm)(\.|$)/i', $origName)) {
        return $fail('نام یا پسوندِ فایل مجاز نیست.');
    }
    /* فهرست سفید پسوندِ نهایی */
    $allowedExt = array_unique(array_map('strtolower', array_values($mimeMap)));
    if (!in_array($ext, $allowedExt, true)) {
        return $fail('پسوند فایل مجاز نیست.');
    }

    $dir = ha_uploads_dir();
    $realBase = realpath(HA_ROOT);
    if ($realBase === false) {
        return $fail('مسیر ریشه نامعتبر است.');
    }
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return $fail('پوشه‌ی uploads در دسترس نیست.');
    }
    $realDir = realpath($dir);
    if ($realDir === false || !str_starts_with($realDir, $realBase)) {
        return $fail('مسیر uploads خارج از ریشه است.');
    }
    if (!is_writable($dir)) {
        return $fail('پوشه‌ی uploads قابلِ نوشتن نیست؛ از فیلدِ «نشانیِ خارجی» استفاده کنید.');
    }

    /* نامِ تصادفیِ غیرقابلِ حدس — بدونِ هیچ بخشی از نامِ اصلی. */
    $name = date('Ym') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\')) {
        return $fail('نام فایل نامعتبر ساخته شد.');
    }
    $dest = $dir . '/' . $name;
    if (!@move_uploaded_file($tmp, $dest)) {
        return $fail('ذخیره‌ی فایل روی سرور ناموفق بود.');
    }
    @chmod($dest, 0644);

    /* دفاع لایه‌ای: اگر به‌اشتباه PHP در uploads اجرا شود، محتوای polyglot را سخت‌تر می‌کند */
    if (is_file($dest) && in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
        $head = (string) @file_get_contents($dest, false, null, 0, 256);
        if ($head !== '' && preg_match('/<\\?php|\\beval\\s*\\(|\\bbase64_decode\\s*\\(/i', $head)) {
            @unlink($dest);
            return $fail('محتوای فایل مشکوک است و رد شد.');
        }
    }

    return ['ok' => true, 'path' => 'uploads/' . $name, 'name' => $name, 'error' => null];
}

/**
 * پیکربندی‌های آماده‌ی آپلود برای انواع محتوا.
 */
function ha_upload_kinds(): array
{
    return [
        'document' => ['application/pdf' => 'pdf'],
        'image'    => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
    ];
}

/** آیا نشانی برای استفاده در فیلدهای فایل/تصویر معتبر است؟ (http/https یا مسیرِ نسبیِ داخلی) */
function ha_safe_file_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    $url = (string) preg_replace('/[\\x00-\\x20\\x7F]/u', '', $url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $url)) {
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
    if (preg_match('#^[a-z][a-z0-9+.\\-]*:#i', $url)) {
        return ''; // javascript:، data: و… رد می‌شوند
    }
    if (str_starts_with($url, '//')) {
        return '';
    }
    /* مسیرِ نسبی فقط به پوشه‌های امنِ شناخته‌شده. */
    if (str_starts_with($url, 'uploads/') || str_starts_with($url, '/uploads/') || str_starts_with($url, 'assets/')) {
        return ltrim($url, '/');
    }
    return '';
}
