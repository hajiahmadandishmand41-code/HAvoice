<?php
/**
 * HAvoice Admin — فهرستِ پیام‌های تماس
 *
 * قابلیت‌ها:
 * - مرتب‌سازی بر اساس تازه‌ترین پیام‌ها در بالای لیست
 * - تفکیک پیام‌های خوانده‌شده و خوانده‌نشده
 * - جستجو در نام، ایمیل، موضوع و متن پیام
 * - فیلتر بر اساس وضعیت (همه / خوانده‌نشده / خوانده‌شده)
 * - تغییر وضعیت سریع (خوانده‌شده/خوانده‌نشده) و حذف با CSRF
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$filterStatus = isset($_GET['status']) && in_array($_GET['status'], ['unread', 'read'], true) ? $_GET['status'] : null;
$search       = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

$allMessages    = admin_messages_all(null, '');
$totalCount     = count($allMessages);
$unreadCount    = admin_message_unread_count();
$readCount      = max(0, $totalCount - $unreadCount);

$filteredMessages = admin_messages_all($filterStatus, $search);
$flash            = flash();
?>
<?php if (!empty($flash['message'])): ?>
    <div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="admin-toolbar admin-toolbar--wrap">
    <div class="filter-tabs">
        <a class="filter-tab<?= $filterStatus === null ? ' is-active' : '' ?>" href="<?= e(url('admin_messages', ($search !== '' ? ['q' => $search] : []))) ?>">
            همه <span class="filter-tab__count"><?= fa_num($totalCount) ?></span>
        </a>
        <a class="filter-tab<?= $filterStatus === 'unread' ? ' is-active' : '' ?>" href="<?= e(url('admin_messages', ['status' => 'unread'] + ($search !== '' ? ['q' => $search] : []))) ?>">
            جدید / خوانده‌نشده <span class="filter-tab__count"><?= fa_num($unreadCount) ?></span>
        </a>
        <a class="filter-tab<?= $filterStatus === 'read' ? ' is-active' : '' ?>" href="<?= e(url('admin_messages', ['status' => 'read'] + ($search !== '' ? ['q' => $search] : []))) ?>">
            خوانده‌شده <span class="filter-tab__count"><?= fa_num($readCount) ?></span>
        </a>
    </div>

    <form method="get" action="index.php" class="admin-search-form">
        <input type="hidden" name="p" value="admin_messages">
        <?php if ($filterStatus !== null): ?>
            <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
        <?php endif; ?>
        <input class="input input--sm" type="text" name="q" value="<?= e($search) ?>" placeholder="جستجو در نام، ایمیل، موضوع، متن…" aria-label="جستجو در پیام‌ها">
        <button class="btn btn--primary btn--sm" type="submit"><?= ha_icon('search', 14) ?> جستجو</button>
        <?php if ($search !== ''): ?>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('admin_messages', $filterStatus !== null ? ['status' => $filterStatus] : [])) ?>">پاک کردن جستجو</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>وضعیت</th>
                <th>تاریخ</th>
                <th>نام فرستنده</th>
                <th>ایمیل</th>
                <th>موضوع</th>
                <th>منبع</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($filteredMessages as $msg):
            $ref = (string) ($msg['ref'] ?? '');
            if ($ref === '') { continue; }
            $isUnread = ($msg['status'] ?? 'unread') === 'unread';
        ?>
            <tr class="<?= $isUnread ? 'is-highlight' : '' ?>">
                <td>
                    <?php if ($isUnread): ?>
                        <span class="admin-badge admin-badge--warn">جدید</span>
                    <?php else: ?>
                        <span class="admin-badge admin-badge--ghost">خوانده‌شده</span>
                    <?php endif; ?>
                </td>
                <td class="muted-sm"><?= e($msg['time'] ?? '') ?></td>
                <td><strong><?= e($msg['name'] ?? '—') ?></strong></td>
                <td dir="ltr"><a href="mailto:<?= e($msg['email'] ?? '') ?>"><?= e($msg['email'] ?? '—') ?></a></td>
                <td><?= e($msg['subject'] ?: 'بدون موضوع') ?></td>
                <td>
                    <?php
                    $msgSource = (string) ($msg['source'] ?? 'csv');
                    $msgBadge  = $msgSource === 'panel' ? ['admin-badge--success', 'پنل']
                        : ($msgSource === 'db' ? ['admin-badge--info', 'دیتابیس'] : ['admin-badge--info', 'فایل CSV']);
                    ?>
                    <span class="admin-badge <?= e($msgBadge[0]) ?>"><?= e($msgBadge[1]) ?></span>
                </td>
                <td class="actions">
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin_message_view', ['slug' => $ref])) ?>" title="مشاهده کامل پیام">
                        <?= ha_icon('eye', 14) ?> مشاهده
                    </a>

                    <form method="post" action="<?= e(url('admin_message_status')) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ref" value="<?= e($ref) ?>">
                        <input type="hidden" name="status" value="<?= $isUnread ? 'read' : 'unread' ?>">
                        <button class="btn btn--ghost btn--sm" type="submit" title="<?= $isUnread ? 'علامت‌گذاری به عنوان خوانده‌شده' : 'علامت‌گذاری به عنوان خوانده‌نشده' ?>">
                            <?= $isUnread ? ha_icon('check', 14) . ' خوانده شد' : ha_icon('sparkle', 14) . ' بازنشانی به جدید' ?>
                        </button>
                    </form>

                    <form method="post" action="<?= e(url('admin_message_delete')) ?>" class="inline-form"
                          data-confirm="پیامِ «<?= e($msg['subject'] ?: ($msg['name'] ?? '')) ?>» برای همیشه حذف شود؟">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ref" value="<?= e($ref) ?>">
                        <button class="btn btn--ghost btn--sm btn--danger-text" type="submit" title="حذف پیام">
                            <?= ha_icon('trash', 14) ?> حذف
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($filteredMessages === []): ?>
            <tr>
                <td colspan="7" class="admin-empty">
                    <?php if ($search !== ''): ?>
                        هیچ پیامی با عبارت «<?= e($search) ?>» پیدا نشد.
                    <?php elseif ($filterStatus === 'unread'): ?>
                        هیچ پیامِ خوانده‌نشده‌ای وجود ندارد.
                    <?php elseif ($filterStatus === 'read'): ?>
                        هیچ پیامِ خوانده‌شده‌ای وجود ندارد.
                    <?php else: ?>
                        هنوز پیامی دریافت نشده است.
                    <?php endif; ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
