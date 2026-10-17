<?php

/**
 * Traductions arabes
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

return [
    'lang.name' => 'العربية',
    'lang.dir' => 'rtl',

    'app.name' => 'Secret Share',
    'app.tagline' => 'شارك كلمات المرور والبيانات الحساسة عبر رابط مؤقت ومشفّر.',
    'app.footer' => 'أسرار مشفّرة، لا يُخزَّن أي شيء بنص واضح، ولا تُسجَّل الروابط.',
    'app.source_code' => 'الشيفرة المصدرية',
    'app.skip_to_content' => 'انتقل إلى المحتوى',
    'app.language' => 'اللغة',

    'common.yes' => 'نعم',
    'common.no' => 'لا',

    'action.copy' => 'نسخ',
    'action.copy_secret' => 'نسخ السر',
    'action.copied' => 'تم النسخ!',
    'action.show_password' => 'إظهار كلمة المرور',
    'action.hide_password' => 'إخفاء كلمة المرور',
    'action.new_secret' => 'مشاركة سر آخر',
    'action.share_own' => 'مشاركة سر خاص بي',
    'action.home' => 'العودة إلى الصفحة الرئيسية',

    'duration.10m' => '10 دقائق',
    'duration.1h' => 'ساعة واحدة',
    'duration.4h' => '4 ساعات',
    'duration.1d' => 'يوم واحد',
    'duration.1w' => 'أسبوع واحد',
    'duration.1mo' => 'شهر واحد',
    'duration.1y' => 'سنة واحدة',
    'duration.none' => 'بلا حدّ',

    'home.title' => 'مشاركة سر',
    'home.lead' => 'يُشفَّر السر ثم يُحوَّل إلى رابط ورمز QR لإرساله إلى المستلم.',
    'home.errors' => 'يحتوي النموذج على أخطاء، يُرجى تصحيحها.',
    'home.secret' => 'السر',
    'home.secret_placeholder' => 'كلمة مرور، مفتاح API، رمز دخول…',
    'home.duration' => 'مدة الصلاحية',
    'home.password' => 'كلمة المرور',
    'home.optional' => '(اختياري)',
    'home.password_hint' => 'تضيف طبقة تشفير ثانية. أرسلها عبر قناة أخرى.',
    'home.options' => 'الخيارات',
    'home.single_use' => 'استخدام لمرة واحدة',
    'home.single_use_hint' => 'يُتلَف السر فور الاطلاع عليه لأول مرة.',
    'home.short_url' => 'إنشاء رابط قصير',
    'home.short_url_hint' => 'يُخزَّن المحتوى المشفّر عندئذٍ مؤقتًا على الخادم (حتى قراءته أو انتهاء صلاحيته) بدلًا من '
        . 'تضمينه في الرابط.',
    'home.short_url_disabled' => 'غير متاح دون استخدام لمرة واحدة أو مدة صلاحية: سيُخزَّن المحتوى على الخادم إلى أجل '
        . 'غير مسمّى.',
    'home.submit' => 'إنشاء الرابط الآمن',
    'home.how_title' => 'آلية العمل',
    'home.feature_encrypted_title' => 'مشفّر',
    'home.feature_encrypted' => 'XChaCha20-Poly1305، وArgon2id لكلمة المرور الاختيارية.',
    'home.feature_split_title' => 'مفتاح مقسوم',
    'home.feature_split' => 'لا يستطيع الرابط وحده ولا الخادم وحده قراءة السر.',
    'home.feature_traceless_title' => 'بلا أثر',
    'home.feature_traceless' => 'يبقى الرمز في جزء الرابط بعد العلامة # ولا يصل أبدًا إلى سجلات الوصول.',

    'created.title' => 'رابطك جاهز',
    'created.lead' => 'أرسل هذا الرابط إلى المستلم أو دعه يمسح رمز QR.',
    'created.qr_alt' => 'رمز QR يحتوي على رابط المشاركة',
    'created.qr_too_long' => 'الرابط أطول من أن يتّسع له رمز QR. فعّل الرابط القصير للحصول على رمز.',
    'created.share_url' => 'رابط المشاركة',
    'created.expires' => 'انتهاء الصلاحية',
    'created.never' => 'أبدًا',
    'created.single_use' => 'استخدام لمرة واحدة',
    'created.password' => 'كلمة المرور',
    'created.storage' => 'المحتوى المشفّر',
    'created.storage_link' => 'داخل الرابط',
    'created.storage_server' => 'على الخادم',
    'created.warning_once' => 'لا يُعرض هذا الرابط إلا مرة واحدة: فهو غير محفوظ في أي مكان.',
    'created.warning_password' => 'أرسل كلمة المرور عبر قناة مختلفة عن قناة الرابط.',
    'created.revoke_title' => 'رابط الإتلاف',
    'created.revoke_hint' => 'احتفظ بهذا الرابط لنفسك: يتيح لك إتلاف السر قبل قراءته أو انتهاء صلاحيته.',

    'reveal.title' => 'شارك أحدهم سرًا معك',
    'reveal.lead' => 'لن يُفك تشفيره إلا عند طلبك. وإذا كان للاستخدام مرة واحدة فسيُتلَف فور عرضه: استعد لنسخه.',
    'reveal.noscript' => 'يلزم تفعيل JavaScript: يبقى رمز الرابط في متصفحك ولا يُرسَل إلا عند طلبك.',
    'reveal.missing_token' => 'هذا الرابط غير مكتمل. تأكد من نسخه كاملًا بما في ذلك الجزء الذي يلي «#».',
    'reveal.password' => 'كلمة المرور',
    'reveal.password_hint' => 'هذا السر محمي بكلمة مرور يُفترض أن المُرسِل قد أعطاك إياها.',
    'reveal.wrong_password' => 'كلمة مرور خاطئة. المحاولات المتبقية قبل إتلاف السر: {count}.',
    'reveal.submit' => 'إظهار السر',

    'revealed.title' => 'إليك السر',
    'revealed.label' => 'السر',
    'revealed.destroyed' => 'أُتلِف هذا السر للتو: انسخه الآن، فلن يمكن عرضه مرة أخرى.',
    'revealed.expires' => 'يبقى هذا السر متاحًا حتى',
    'revealed.no_expiry' => 'يبقى هذا السر متاحًا دون حد زمني.',

    'revoke.title' => 'إتلاف سر',
    'revoke.lead' => 'سيُتلَف السر المرتبط بهذا الرابط نهائيًا ولن يتمكن أحد من قراءته.',
    'revoke.submit' => 'إتلاف نهائي',

    'revoked.title' => 'تم إتلاف السر',
    'revoked.lead' => 'حُذف السر من الخادم. أصبح رابط المشاركة عديم الفائدة.',
    'revoked.not_found_title' => 'لا شيء لإتلافه',
    'revoked.not_found' => 'لم يعد هذا السر موجودًا: فقد سبق الاطلاع عليه أو إتلافه أو انتهت صلاحيته.',

    'error.title_gone' => 'السر غير متاح',
    'error.title_generic' => 'حدث خطأ',
    'error.not_found' => 'هذه الصفحة غير موجودة.',
    'error.csrf' => 'انتهت صلاحية جلسة النموذج. أعد تحميل الصفحة ثم حاول مرة أخرى.',
    'error.secret_unavailable' => 'هذا السر غير موجود أو انتهت صلاحيته أو سبق الاطلاع عليه أو أُتلِف.',
    'error.too_many_attempts' => 'عدد كبير جدًا من كلمات المرور الخاطئة: أُتلِف السر.',
    'error.secret_required' => 'يُرجى إدخال السر المراد مشاركته.',
    'error.secret_invalid' => 'يحتوي السر على أحرف غير صالحة.',
    'error.secret_too_long' => 'يجب ألا يتجاوز السر {max} حرف.',
    'error.duration_invalid' => 'يُرجى اختيار مدة الصلاحية.',
    'error.password_invalid' => 'تحتوي كلمة المرور على أحرف غير صالحة.',
    'error.password_too_long' => 'يجب ألا تتجاوز كلمة المرور 1024 حرفًا.',
    'error.storage_full' => 'بلغت الخدمة الحد الأقصى لسعة التخزين. يُرجى المحاولة لاحقًا.',
    'error.rate_limited' => 'طلبات كثيرة جدًا من اتصالك. يُرجى الانتظار بضع دقائق قبل المحاولة مجددًا.',
    'error.unlimited_quota' => 'يوجد بالفعل عدد كبير جدًا من الأسرار بلا حد زمني: يُرجى اختيار مدة صلاحية.',
    'error.short_url_forbidden' => 'يتطلب الرابط القصير استخدامًا لمرة واحدة أو مدة صلاحية.',
];
