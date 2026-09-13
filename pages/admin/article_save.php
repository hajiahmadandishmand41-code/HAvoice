<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_articles'));
if (!csrf_verify()) { flash('error', 'نشست شما تمام شده.'); redirect(url('admin_articles')); }

$title = trim((string)($_POST['title'] ?? ''));
$slug = slugify((string)($_POST['slug'] ?? ''));
$category = trim((string)($_POST['category'] ?? 'عمومی'));
$excerpt = trim((string)($_POST['excerpt'] ?? ''));
$date = trim((string)($_POST['date'] ?? date('Y-m-d')));
$date_fa = trim((string)($_POST['date_fa'] ?? ''));
$minutes = max(1, (int)($_POST['minutes'] ?? 5));
$tags = array_map('trim', array_filter(explode(',', (string)($_POST['tags'] ?? ''))));
$blocksJson = (string)($_POST['blocks_json'] ?? '[]');
$blocks = json_decode($blocksJson, true);
if (!is_array($blocks)) $blocks = [];
$originalSlug = (string)($_POST['original_slug'] ?? '');

if ($title === '' || $slug === '') { flash('error', 'عنوان و نامک الزامی است.'); redirect(url('admin_article_edit')); }

$articles = admin_load('articles');
$article = ['slug'=>$slug,'title'=>$title,'category'=>$category,'excerpt'=>$excerpt,'date'=>$date,'date_fa'=>$date_fa,'minutes'=>$minutes,'tags'=>$tags,'blocks'=>$blocks];
$found = false;
foreach ($articles as $i => $a) { if (($a['slug'] ?? '') === $originalSlug || ($a['slug'] ?? '') === $slug) { $articles[$i] = $article; $found = true; break; } }
if (!$found) $articles[] = $article;
if (!admin_store('articles', $articles)) { flash('error', 'ذخیره‌سازی مقاله ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_articles')); }
flash('success', 'مقاله ذخیره شد.');
redirect(url('admin_articles'));