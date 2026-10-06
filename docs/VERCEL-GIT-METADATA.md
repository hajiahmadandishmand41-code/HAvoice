# Vercel Git Deployment — Fix for `git_info_fail`

## یافتهٔ قطعی

Production Deployment سالم پروژه:

- Deployment: `dpl_BufPmZpofaca7exmH1zRNFXQMZsz`
- Commit: `97ca3acc3a495b7230d9a82014ffe20ab94fb677`
- نتیجه: `READY`
- پاسخ صفحهٔ اصلی: HTTP 200

Deploymentهای جدید روی همین پروژه با خطای `git_info_fail` متوقف می‌شوند؛ این خطا قبل از اجرای PHP/Runtime رخ می‌دهد. در ۷ روز اخیر نیز Runtime Error برای پروژه ثبت نشده است.

## علت

حساب Vercel که تیم `Eshop` را مالک است با ایمیل `nekes31389@seakol.com` و حساب GitHub متصل به مخزن `hajiahmadandishmand41-code/HAvoice` با ایمیل `hajiahmadandishmand41@gmail.com` شناسایی شده است. برای Git Deployment روی Vercel باید Git author/metadata با کاربر Vercel قابل تطبیق باشد؛ mismatch می‌تواند باعث `git_info_fail` شود.

## اقدام لازم خارج از مخزن

یکی از این مسیرها باید انجام شود:

1. ایمیل حساب Vercel را با ایمیل تأییدشدهٔ GitHub یکسان کنید؛ یا
2. یک ایمیل تأییدشدهٔ همسان در GitHub/Vercel تنظیم کنید و commitهای بعدی را با همان ایمیل بسازید.

بعد از اصلاح حساب، یک Push/Deployment جدید از `main` ایجاد کنید.

## وضعیت کد

در بررسی Production، کد فعلی روی Deployment سالم HTTP 200 می‌دهد و Runtime Error ندارد؛ بنابراین برای این مشکل نباید کد PHP را بی‌دلیل تغییر داد.

## هشدار

Redeploy کردن Deployment سالم نیز بدون اصلاح Git identity به `git_info_fail` برمی‌گردد، چون Vercel هنگام ساخت Deployment جدید دوباره Git metadata را اعتبارسنجی می‌کند.
