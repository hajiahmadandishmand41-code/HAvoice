<?php
/**
 * HAvoice Admin — اجزای مشترکِ صفحه‌های فهرست و فرم
 *
 * این فایل توسط _layout_start.php بارگذاری می‌شود و فقط در پنل مدیریت
 * در دسترس است: برچسبِ وضعیتِ انتشار، دکمه‌ی انتشار/مخفی، نشانِ منبع
 * (فایل/پنل) و رندرِ یکدستِ پیام‌های flash.
 */

if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');

/** رندرِ پیامِ flash پنل (اگر چیزی هست). */
function admin_flash(): string
{
    $flash = flash();
    if (empty($flash['message'])) {
        return '';
    }
    $ok = ($flash['type'] ?? '') === 'success';
    return '<div class="alert alert--' . ($ok ? 'success' : 'error') . '" role="' . ($ok ? 'status' : 'alert') . '">'
         . '<p>' . e((string) $flash['message']) . '</p></div>';
}

/**
 * نشانِ وضعیتِ انتشار: منتشرشده / پیش‌نویس.
 */
function admin_status_badge(array $item): string
{
    if (ha_is_published($item)) {
        return '<span class="admin-badge admin-badge--success">' . ha_icon('eye', 12) . ' منتشرشده</span>';
    }
    return '<span class="admin-badge admin-badge--warn">' . ha_icon('edit', 12) . ' پیش‌نویس (مخفی)</span>';
}

/**
 * نشانِ منبع: آیا این مورد از فایلِ data می‌آید یا نسخه‌ی پنل دارد؟
 * تشخیص بر پایه‌ی وجودِ کلید در فروشگاهِ پنل است، نه جایگاه در فهرست —
 * چون فهرست‌ها با ha_merge_overrides() ادغام می‌شوند و بازنویسیِ پنل
 * در همان جایگاهِ موردِ فایل می‌نشیند.
 */
function admin_source_badge(bool $hasPanelVersion): string
{
    return $hasPanelVersion
        ? '<span class="admin-badge admin-badge--success">پنل</span>'
        : '<span class="admin-badge admin-badge--info">فایل</span>';
}

/**
 * فرمِ کوچکِ تغییرِ وضعیت (انتشار ⇄ مخفی) برای یک مورد.
 * برای مواردِ «فایل» هم کار می‌کند: handler اول یک نسخه‌ی پنلی از مورد
 * می‌سازد و بعد وضعیت را روی آن می‌نویسد — فایلِ پایه دست‌نخورده می‌ماند.
 */
function admin_status_toggle(string $type, string $key, array $item): string
{
    $published = ha_is_published($item);
    $next      = $published ? 'draft' : 'published';
    $label     = $published ? 'مخفی کردن' : 'انتشار';
    $icon      = $published ? 'close' : 'check';
    ob_start(); ?>
    <form method="post" action="<?= e(url('admin_content_status')) ?>" class="inline-form"
          data-confirm="<?= $published ? 'این مورد از دیدِ کاربران مخفی شود؟ (حذف نمی‌شود)' : 'این مورد منتشر شود؟' ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="hidden" name="key" value="<?= e($key) ?>">
        <input type="hidden" name="status" value="<?= e($next) ?>">
        <button class="btn btn--ghost btn--sm" type="submit"><?= ha_icon($icon, 13) ?> <?= e($label) ?></button>
    </form>
    <?php return (string) ob_get_clean();
}

/**
 * فیلدِ انتخابِ وضعیت برای فرم‌های ویرایش.
 * موردِ تازه (بدون slug/id ذخیره‌شده): پیش‌فرض draft — Publish تصمیم مدیر است.
 */
function admin_status_field(array $item, bool $isNew = false): string
{
    if ($isNew && !isset($item['status'])) {
        $status = 'draft';
    } else {
        $status = ha_is_published($item) ? 'published' : 'draft';
    }
    ob_start(); ?>
    <div class="field">
        <label for="f-status">وضعیتِ انتشار</label>
        <select class="input" id="f-status" name="status">
            <option value="published"<?= $status === 'published' ? ' selected' : '' ?>>منتشرشده — برای کاربران نمایش داده شود</option>
            <option value="draft"<?= $status === 'draft' ? ' selected' : '' ?>>پیش‌نویس — مخفی از دیدِ کاربران (بدونِ حذف)</option>
        </select>
        <?php if ($isNew): ?>
        <p class="field__help">پیش‌فرض پیش‌نویس است؛ برای نمایش عمومی «منتشرشده» را انتخاب کنید.</p>
        <?php endif; ?>
    </div>
    <?php return (string) ob_get_clean();
}

/**
 * فیلدِ انتخابِ «حوزه» برای فرم‌های ویرایش — اتصالِ محتوا به یکی از
 * حوزه‌های آموزشی. برای مقاله‌ها برچسبِ فارسیِ category هم جداگانه
 * نگه داشته می‌شود (نمایش در کارت)، ولی فیلترها بر اساسِ همین اتصال
 * کار می‌کنند.
 */
function admin_field_select(array $item, string $inputName = 'field'): string
{
    $current = slugify((string) ($item['field'] ?? ''));
    if ($current === '' || find_category($current) === null) {
        $current = ha_item_field_slug($item);
    }
    ob_start(); ?>
    <div class="field">
        <label for="f-field">حوزه‌ی آموزشی</label>
        <select class="input" id="f-field" name="<?= e($inputName) ?>">
            <option value="">— بدونِ اتصال —</option>
            <?php foreach (categories_all() as $c): $cs = (string) ($c['slug'] ?? ''); ?>
            <option value="<?= e($cs) ?>"<?= $cs === $current ? ' selected' : '' ?>><?= e($c['title'] ?? $cs) ?></option>
            <?php endforeach; ?>
        </select>
        <p class="field__help">برای رنگِ کارت، صفحه‌ی حوزه و فیلترها استفاده می‌شود.</p>
    </div>
    <?php return (string) ob_get_clean();
}

/** مقدارِ ورودیِ status از POST — فقط دو مقدارِ مجاز. */
function admin_post_status(): string
{
    return ((string) ($_POST['status'] ?? '')) === 'draft' ? 'draft' : 'published';
}

/** خطوطِ غیرخالیِ یک textarea به‌صورتِ آرایه (برای refs، how_to و…). */
function admin_lines(string $key): array
{
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', (string) ($_POST[$key] ?? '')) as $line) {
        $line = trim($line);
        if ($line !== '') $out[] = $line;
    }
    return $out;
}

/**
 * نامک از POST + ساختِ خودکار از رویِ عنوان.
 *
 * باگِ پیشین: fallback فقط slugify($title) بود و slugify() همه‌ی نویسه‌های
 * غیرِ ASCII را دور می‌ریزد ⇒ عنوانِ کاملاً فارسی نامکِ خالی می‌داد و فرم
 * با پیامِ «عنوان الزامی است» بسته می‌شد (مدیر فارسی‌زبان عملاً نمی‌توانست
 * صوت/ویدیو/کتاب/مقاله ثبت کند). ترتیبِ جدید:
 *   ۱) نامکِ تایپ‌شده‌ی مدیر — دقیقاً همان رفتارِ قبلی (ویرایشِ همان مورد)
 *   ۲) بخشِ لاتینِ عنوان
 *   ۳) نویسه‌گردانیِ فارسی («فن بیان» ⇒ fan-bayan)
 *   ۴) پیشوند + زمان (audio-20260916-114500) — هرگز خالی نمی‌ماند
 * اگر $taken داده شود، نامکِ «خودکار» تا یکتا شدن شماره می‌گیرد تا موردِ
 * دیگری بی‌صدا بازنویسی نشود.
 *
 * @param string        $title  عنوانِ نوشته‌شده در فرم
 * @param string        $prefix نوعِ محتوا برای نامکِ پشتیبان (audio، book، …)
 * @param callable|null $taken  closure(string $slug): bool — آیا اشغال است؟
 */
function admin_post_slug(string $title = '', string $prefix = '', ?callable $taken = null): string
{
    $typed = slugify((string) ($_POST['slug'] ?? ''));
    if ($typed !== '') {
        return $typed;
    }

    $slug = ha_slug_from_title($title);
    if ($slug === '') {
        $prefix = slugify($prefix);
        $slug   = ($prefix !== '' ? $prefix : 'item') . '-' . date('Ymd-His');
    }

    return ha_unique_slug($slug, $taken);
}

/**
 * وضعیت انتشار از POST.
 * پیش‌فرض برای موردِ «تازه» = draft (Publish تصمیمِ صریحِ مدیر است).
 * برای ویرایش، اگر status ارسال نشود published می‌ماند (سازگاری فرم‌های قدیم).
 */
function admin_post_status_default_draft(bool $isNew = false): string
{
    $raw = (string) ($_POST['status'] ?? '');
    if ($raw === 'draft' || $raw === 'published') {
        return $raw;
    }
    return $isNew ? 'draft' : 'published';
}

/**
 * JSONِ بلوک‌ها از POST → آرایه یا null (null یعنی خطای پارس).
 */
function admin_post_blocks(string $key = 'blocks_json'): ?array
{
    $raw = trim((string) ($_POST[$key] ?? ''));
    if ($raw === '' || $raw === '[]') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

/** فیلدِ رأی/نشانِ «ویژه» برای فرم‌های ویرایش (نمایش در صفحه‌ی نخست). */
function admin_featured_field(array $item): string
{
    $checked = !empty($item['featured']) ? ' checked' : '';
    return '<div class="field"><label class="check"><input type="checkbox" name="featured" value="1"' . $checked . '>'
         . '<span>منتخب — در صفحه‌ی نخست نمایش داده شود</span></label></div>';
}

/* ------------------------------------------------------------------ */
/*  سازنده‌ی دوره: متنِ ساده ↔ بلوک‌های محتوا                            */
/* ------------------------------------------------------------------ */

/**
 * بلوک‌های محتوای یک درس → متنِ خط‌به‌خطِ قابلِ ویرایش.
 *
 * قالب (ساده و قابلِ حفظ کردن برای مدیرِ غیرِ فنی):
 *   «## عنوان»  → h2          «### عنوان» → h3
 *   «- مورد»    → لیستِ نقطه‌ای   «1. مورد»  → لیستِ شماره‌دار
 *   «> نکته»    → جعبه‌ی نکته    خطِ ساده  → پاراگراف
 *
 * بلوک‌های پیشرفته (drill، table، audio، video، quote) با این قالبِ متنی
 * بیان‌شدنی نیستند؛ در آن حالت lossy=true برمی‌گردد و فرم، مدیر را به
 * کادرِ JSON همان درس می‌فرستد تا چیزی بی‌صدا پاک نشود.
 *
 * @param list<array<string,mixed>> $blocks
 * @return array{0:string,1:bool}
 */
function admin_lesson_blocks_to_text(array $blocks): array
{
    $lines = [];
    $lossy = false;

    foreach ($blocks as $block) {
        if (!is_array($block)) {
            $lossy = true;
            continue;
        }
        $type = (string) ($block['type'] ?? 'p');
        switch ($type) {
            case 'h2':
                $lines[] = '## ' . trim((string) ($block['text'] ?? ''));
                break;
            case 'h3':
                $lines[] = '### ' . trim((string) ($block['text'] ?? ''));
                break;
            case 'p':
            case 'lead':
                $text = trim((string) ($block['text'] ?? ''));
                if ($type === 'lead') {
                    $lossy = true;   // lead با پاراگراف یکی نیست؛ بی‌صدا تبدیل نشود
                }
                if ($text !== '') {
                    $lines[] = $text;
                }
                break;
            case 'ul':
            case 'ol':
                $items = (array) ($block['items'] ?? []);
                $n = 1;
                foreach ($items as $item) {
                    $text = trim((string) $item);
                    if ($text === '') {
                        continue;
                    }
                    $lines[] = ($type === 'ol' ? ($n . '. ') : '- ') . $text;
                    $n++;
                }
                break;
            case 'tip':
                $lines[] = '> ' . trim((string) ($block['text'] ?? ''));
                break;
            default:
                $lossy = true;       // drill / table / audio / video / quote
                break;
        }
    }

    return [implode("\n", $lines), $lossy];
}

/**
 * متنِ خط‌به‌خطِ مدیر → بلوک‌های محتوا (همان قالبِ بالا).
 *
 * @return list<array<string,mixed>>
 */
function admin_lesson_text_to_blocks(string $text): array
{
    $blocks = [];
    $ul     = [];
    $ol     = [];

    $flush = static function () use (&$ul, &$ol, &$blocks): void {
        if ($ul !== []) {
            $blocks[] = ['type' => 'ul', 'items' => $ul];
            $ul = [];
        }
        if ($ol !== []) {
            $blocks[] = ['type' => 'ol', 'items' => $ol];
            $ol = [];
        }
    };

    foreach ((array) preg_split('/\r\n|\r|\n/', $text) as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            $flush();
            continue;
        }
        if (str_starts_with($line, '### ')) {
            $flush();
            $blocks[] = ['type' => 'h3', 'text' => trim(mb_substr($line, 4))];
            continue;
        }
        if (str_starts_with($line, '## ')) {
            $flush();
            $blocks[] = ['type' => 'h2', 'text' => trim(mb_substr($line, 3))];
            continue;
        }
        if (str_starts_with($line, '> ')) {
            $flush();
            $blocks[] = ['type' => 'tip', 'tone' => 'info', 'title' => 'نکته', 'text' => trim(mb_substr($line, 2))];
            continue;
        }
        if (preg_match('/^\d+[.)]\s+(.+)$/u', $line, $m)) {
            $ol[] = trim($m[1]);
            continue;
        }
        if (preg_match('/^[-*•]\s+(.+)$/u', $line, $m)) {
            $ul[] = trim($m[1]);
            continue;
        }
        $flush();
        $blocks[] = ['type' => 'p', 'text' => $line];
    }
    $flush();

    return $blocks;
}

/* ------------------------------------------------------------------ */
/*  سازنده‌ی دوره: ساختارِ مراحل/درس‌ها از فرمِ ساختاریافته              */
/* ------------------------------------------------------------------ */

/** خطاهایِ پارسِ فرمِ سازنده (JSONِ نامعتبرِ یک درس و مانند آن). */
function admin_builder_errors(): array
{
    return isset($GLOBALS['HA_ADMIN_BUILDER_ERRORS']) && is_array($GLOBALS['HA_ADMIN_BUILDER_ERRORS'])
        ? $GLOBALS['HA_ADMIN_BUILDER_ERRORS']
        : [];
}

/** ثبتِ یک خطا در حینِ پارسِ فرمِ سازنده. */
function admin_builder_error(string $message): void
{
    if (!isset($GLOBALS['HA_ADMIN_BUILDER_ERRORS']) || !is_array($GLOBALS['HA_ADMIN_BUILDER_ERRORS'])) {
        $GLOBALS['HA_ADMIN_BUILDER_ERRORS'] = [];
    }
    $GLOBALS['HA_ADMIN_BUILDER_ERRORS'][] = $message;
}

/**
 * مرتب‌سازیِ ردیف‌های یک فهرستِ ساختاریافته بر پایه‌ی فیلدِ «ترتیب».
 *
 * کلیدهای عددیِ آرایه‌ی POST همیشه صعودی بازچینش می‌شوند، پس ملاکِ واقعیِ
 * ترتیب همان عددی است که مدیر (یا دکمه‌های بالا/پایین) نوشته است؛ در
 * تساوی، ترتیبِ ارسال حفظ می‌شود.
 *
 * @param array<int|string,mixed> $rows
 * @return list<array<string,mixed>>
 */
function admin_sort_rows(array $rows): array
{
    $items = [];
    $i = 0;
    foreach ($rows as $key => $row) {
        if (!is_array($row)) {
            continue;
        }
        $order = isset($row['order']) && trim((string) $row['order']) !== '' ? (int) $row['order'] : ($i + 1);
        $items[] = ['order' => $order, 'i' => $i, 'row' => $row];
        $i++;
    }
    usort($items, static fn(array $a, array $b): int => [$a['order'], $a['i']] <=> [$b['order'], $b['i']]);
    return array_map(static fn(array $x): array => $x['row'], $items);
}

/**
 * فرمِ ساختاریافته‌ی «مراحل و درس‌ها» → همان ساختاری که JSONِ قدیمی
 * داشت؛ بقیه‌ی نرمال‌سازی در course_save.php انجام می‌شود.
 *
 * دو اصلِ مهم:
 *  ۱. چیزی بی‌صدا پاک نشود: محتوای پیشرفته‌ی درس (drill, prerequisite, refs)
 *     و آزمونِ مرحله از نسخه‌ی موجود حفظ می‌شود.
 *  ۲. اولویتِ محتوا: JSONِ همان درس ← متنِ ساده ← نسخه‌ی موجود.
 *
 * @param array<string,mixed> $existingCourse دوره‌ی فعلی (برای حفظِ داده)
 * @return list<array<string,mixed>>
 */
function admin_course_stages_from_post(array $existingCourse): array
{
    $posted = $_POST['stages'] ?? [];
    if (!is_array($posted)) {
        return [];
    }

    /* نمایه‌ی درس‌ها و مراحلِ موجود */
    $existingLessons = [];
    $existingStages  = [];
    foreach ((array) ($existingCourse['stages'] ?? []) as $est) {
        if (!is_array($est)) {
            continue;
        }
        $byTitle = trim((string) ($est['title'] ?? ''));
        if ($byTitle !== '') {
            $existingStages[$byTitle] = $est;
        }
        foreach ((array) ($est['lessons'] ?? []) as $els) {
            if (!is_array($els)) {
                continue;
            }
            $key = slugify((string) ($els['slug'] ?? ''));
            if ($key !== '') {
                $existingLessons[$key] = $els;
            }
        }
    }

    $stages = [];
    foreach (admin_sort_rows($posted) as $si => $stage) {
        if (!empty($stage['remove'])) {
            continue;
        }
        $stageTitle = trim((string) ($stage['title'] ?? ''));
        $prev       = $existingStages[$stageTitle] ?? [];

        $lessons = [];
        $rows    = is_array($stage['lessons'] ?? null) ? admin_sort_rows((array) $stage['lessons']) : [];
        /* درس‌های همین مرحله در نسخه‌ی موجود — برایِ پیدا کردنِ نامکِ قبلی
           وقتی مدیر فیلدِ نامک را خالی گذاشته است (عنوانِ فارسی با slugify
           به «-» می‌شود و نباید نشانیِ درس را عوض کند). */
        $prevLessons = [];
        foreach ((array) ($prev['lessons'] ?? []) as $pls) {
            if (!is_array($pls)) {
                continue;
            }
            $prevLessons[] = $pls;
        }
        foreach ($rows as $row) {
            if (!empty($row['remove'])) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            $slug  = slugify((string) ($row['slug'] ?? ''));
            $text  = trim((string) ($row['text'] ?? ''));
            $json  = trim((string) ($row['blocks_json'] ?? ''));

            /* ردیفِ خالی (برایِ «درسِ جدید» گذاشته شده) → نادیده */
            if ($title === '' && $slug === '' && $text === '' && ($json === '' || $json === '[]')) {
                continue;
            }
            if ($title === '' && $slug === '') {
                admin_builder_error('یک ردیفِ درس عنوان ندارد؛ عنوان را پر کنید یا ردیفِ خالی را پاک بگذارید.');
                continue;
            }

            $current = $slug !== '' ? ($existingLessons[$slug] ?? []) : [];

            /* نامکِ خالی + عنوانِ یکسان با یک درسِ موجود ⇒ همان نامکِ قبلی */
            if ($slug === '' && $current === []) {
                foreach ($prevLessons as $pls) {
                    $plsSlug = slugify((string) ($pls['slug'] ?? ''));
                    if ($plsSlug !== '' && $plsSlug !== '-' && trim((string) ($pls['title'] ?? '')) === $title) {
                        $slug    = $plsSlug;
                        $current = $pls;
                        break;
                    }
                }
            }

            /* محتوا: JSONِ صریح ← متنِ ساده ← نسخه‌ی موجود */
            $blocks = [];
            if ($json !== '' && $json !== '[]') {
                $decoded = json_decode($json, true);
                if (!is_array($decoded)) {
                    admin_builder_error('JSONِ بلوک‌های درسِ «' . ($title !== '' ? $title : $slug) . '» معتبر نیست: ' . json_last_error_msg());
                    $decoded = null;
                }
                if (is_array($decoded)) {
                    $blocks = $decoded;
                } elseif (is_array($current['blocks'] ?? null)) {
                    $blocks = $current['blocks'];   // خطا گزارش شد؛ داده‌ی قبلی پاک نشود
                }
            } elseif ($text !== '') {
                $blocks = admin_lesson_text_to_blocks($text);
            } elseif (is_array($current['blocks'] ?? null)) {
                $blocks = $current['blocks'];
            }

            $minutesRaw = trim((string) ($row['minutes'] ?? ''));
            $minutes    = $minutesRaw === '' ? (int) ($current['minutes'] ?? 10) : max(0, (int) $minutesRaw);
            if ($minutes === 0) {
                $minutes = 10;
            }

            $lessons[] = [
                'slug'         => $slug,
                'title'        => $title,
                'minutes'      => $minutes,
                'goal'         => trim((string) ($row['goal'] ?? '')),
                'blocks'       => $blocks,
                /* فیلدهای پیشرفته از نسخه‌ی موجود حفظ می‌شوند (فرمِ ساده آن‌ها را ندارد) */
                'drill'        => is_array($current['drill'] ?? null) ? $current['drill'] : [],
                'prerequisite' => slugify((string) ($current['prerequisite'] ?? '')),
                'refs'         => is_array($current['refs'] ?? null) ? $current['refs'] : [],
            ];
        }

        $stages[] = [
            'id'         => (string) ($prev['id'] ?? ('stage-' . ($si + 1))),
            'label'      => trim((string) ($stage['label'] ?? '')),
            'title'      => $stageTitle,
            'summary'    => trim((string) ($stage['summary'] ?? '')),
            'outcome'    => trim((string) ($stage['outcome'] ?? '')),
            'duration'   => trim((string) ($stage['duration'] ?? '')),
            'lessons'    => $lessons,
            'assessment' => is_array($prev['assessment'] ?? null) ? $prev['assessment'] : [],
        ];
    }

    return $stages;
}
