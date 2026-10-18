<?php

/**
 * Traductions hindies
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

return [
    'lang.name' => 'हिन्दी',
    'lang.dir' => 'ltr',
    'lang.flag' => '🇮🇳',

    'app.name' => 'Secret Share',
    'app.tagline' => 'पासवर्ड और संवेदनशील जानकारी को एक अस्थायी, एन्क्रिप्टेड लिंक के ज़रिए साझा करें।',
    'app.footer' => 'रहस्य एन्क्रिप्टेड रहते हैं, कुछ भी सादे रूप में संग्रहीत नहीं होता, लिंक का कोई लॉग नहीं।',
    'app.source_code' => 'सोर्स कोड',
    'app.skip_to_content' => 'सामग्री पर जाएँ',
    'app.language' => 'भाषा',
    'app.theme' => 'थीम',
    'app.theme_auto' => 'स्वचालित',
    'app.theme_light' => 'हल्की',
    'app.theme_dark' => 'गहरी',

    'common.yes' => 'हाँ',
    'common.no' => 'नहीं',

    'action.copy' => 'कॉपी करें',
    'action.copy_secret' => 'रहस्य कॉपी करें',
    'action.copied' => 'कॉपी हो गया!',
    'action.show_password' => 'पासवर्ड दिखाएँ',
    'action.hide_password' => 'पासवर्ड छिपाएँ',
    'action.new_secret' => 'एक और रहस्य साझा करें',
    'action.share_own' => 'मैं भी एक रहस्य साझा करूँ',
    'action.home' => 'मुखपृष्ठ पर लौटें',

    'duration.10m' => '10 मिनट',
    'duration.1h' => '1 घंटा',
    'duration.4h' => '4 घंटे',
    'duration.1d' => '1 दिन',
    'duration.1w' => '1 सप्ताह',
    'duration.1mo' => '1 महीना',
    'duration.1y' => '1 वर्ष',
    'duration.none' => 'कोई सीमा नहीं',

    'home.title' => 'एक रहस्य साझा करें',
    'home.lead' => 'रहस्य को एन्क्रिप्ट करके प्राप्तकर्ता के लिए एक लिंक और QR कोड बनाया जाता है।',
    'home.errors' => 'फ़ॉर्म में त्रुटियाँ हैं, कृपया उन्हें ठीक करें।',
    'home.secret' => 'रहस्य',
    'home.secret_placeholder' => 'पासवर्ड, API कुंजी, एक्सेस कोड…',
    'home.duration' => 'वैधता',
    'home.password' => 'पासवर्ड',
    'home.optional' => '(वैकल्पिक)',
    'home.password_hint' => 'एन्क्रिप्शन की दूसरी परत जोड़ता है। इसे किसी दूसरे माध्यम से भेजें।',
    'home.options' => 'विकल्प',
    'home.single_use' => 'एक बार उपयोग',
    'home.single_use_hint' => 'पहली बार देखे जाते ही रहस्य नष्ट हो जाता है।',
    'home.short_url' => 'छोटा URL बनाएँ',
    'home.short_url_hint' => 'तब एन्क्रिप्टेड सामग्री लिंक में रहने के बजाय अस्थायी रूप से सर्वर पर संग्रहीत होती है '
        . '(पढ़े जाने या समाप्त होने तक)।',
    'home.short_url_disabled' => 'एक बार उपयोग या समय-सीमा के बिना उपलब्ध नहीं: सामग्री सर्वर पर अनिश्चित काल तक '
        . 'संग्रहीत रहती।',
    'home.submit' => 'सुरक्षित लिंक बनाएँ',
    'home.how_title' => 'यह कैसे काम करता है',
    'home.feature_encrypted_title' => 'एन्क्रिप्टेड',
    'home.feature_encrypted' => 'XChaCha20-Poly1305, और वैकल्पिक पासवर्ड के लिए Argon2id।',
    'home.feature_split_title' => 'विभाजित कुंजी',
    'home.feature_split' => 'न अकेला लिंक और न अकेला सर्वर रहस्य पढ़ सकता है।',
    'home.feature_traceless_title' => 'कोई निशान नहीं',
    'home.feature_traceless' => 'टोकन URL के फ़्रैगमेंट में रहता है और कभी एक्सेस लॉग में नहीं पहुँचता।',

    'created.title' => 'आपका लिंक तैयार है',
    'created.lead' => 'यह लिंक प्राप्तकर्ता को भेजें या उन्हें QR कोड स्कैन करने दें।',
    'created.qr_alt' => 'साझा करने वाले लिंक का QR कोड',
    'created.qr_too_long' => 'लिंक QR कोड के लिए बहुत लंबा है। QR कोड पाने के लिए छोटा URL चालू करें।',
    'created.share_url' => 'साझा करने का लिंक',
    'created.expires' => 'समाप्ति',
    'created.never' => 'कभी नहीं',
    'created.single_use' => 'एक बार उपयोग',
    'created.password' => 'पासवर्ड',
    'created.storage' => 'एन्क्रिप्टेड सामग्री',
    'created.storage_link' => 'लिंक में',
    'created.storage_server' => 'सर्वर पर',
    'created.warning_once' => 'यह लिंक केवल एक बार दिखाया जाता है: इसे कहीं संग्रहीत नहीं किया जाता।',
    'created.warning_password' => 'पासवर्ड को लिंक से अलग किसी माध्यम से भेजें।',
    'created.revoke_title' => 'नष्ट करने का लिंक',
    'created.revoke_hint' => 'यह लिंक अपने पास रखें: इससे आप रहस्य को पढ़े जाने या समाप्त होने से पहले नष्ट कर सकते '
        . 'हैं।',

    'reveal.title' => 'किसी ने आपके साथ एक रहस्य साझा किया है',
    'reveal.lead' => 'यह केवल आपके अनुरोध पर डिक्रिप्ट होगा। यदि यह एक बार उपयोग वाला है, तो दिखाते ही नष्ट हो जाएगा: '
        . 'इसे कॉपी करने के लिए तैयार रहें।',
    'reveal.noscript' => 'JavaScript आवश्यक है: लिंक का टोकन आपके ब्राउज़र में रहता है और केवल आपके अनुरोध पर भेजा '
        . 'जाता है।',
    'reveal.missing_token' => 'यह लिंक अधूरा है। जाँचें कि इसे पूरा कॉपी किया गया है, "#" के बाद वाला भाग भी।',
    'reveal.password' => 'पासवर्ड',
    'reveal.password_hint' => 'यह रहस्य एक पासवर्ड से सुरक्षित है, जो भेजने वाले ने आपको दिया होगा।',
    'reveal.wrong_password' => 'गलत पासवर्ड। रहस्य नष्ट होने से पहले बचे प्रयास: {count}।',
    'reveal.submit' => 'रहस्य दिखाएँ',

    'revealed.title' => 'यह रहा रहस्य',
    'revealed.label' => 'रहस्य',
    'revealed.destroyed' => 'यह रहस्य अभी नष्ट कर दिया गया है: इसे अभी कॉपी करें, यह दोबारा नहीं दिखाया जा सकेगा।',
    'revealed.expires' => 'यह रहस्य इस समय तक उपलब्ध रहेगा:',
    'revealed.no_expiry' => 'यह रहस्य बिना किसी समय-सीमा के उपलब्ध रहेगा।',

    'revoke.title' => 'रहस्य नष्ट करें',
    'revoke.lead' => 'इस लिंक वाला रहस्य स्थायी रूप से नष्ट हो जाएगा और कोई भी उसे पढ़ नहीं पाएगा।',
    'revoke.submit' => 'स्थायी रूप से नष्ट करें',

    'revoked.title' => 'रहस्य नष्ट हो गया',
    'revoked.lead' => 'रहस्य सर्वर से मिटा दिया गया है। साझा करने का लिंक अब बेकार है।',
    'revoked.not_found_title' => 'नष्ट करने को कुछ नहीं',
    'revoked.not_found' => 'यह रहस्य अब मौजूद नहीं है: इसे पहले ही देखा, नष्ट किया जा चुका है या यह समाप्त हो गया है।',

    'error.title_gone' => 'रहस्य उपलब्ध नहीं',
    'error.title_generic' => 'कोई त्रुटि हुई',
    'error.not_found' => 'यह पृष्ठ मौजूद नहीं है।',
    'error.csrf' => 'आपके फ़ॉर्म का सत्र समाप्त हो गया है। पृष्ठ फिर से लोड करें और दोबारा प्रयास करें।',
    'error.secret_unavailable' => 'यह रहस्य मौजूद नहीं है, समाप्त हो गया है, पहले ही देखा जा चुका है या नष्ट कर दिया '
        . 'गया है।',
    'error.too_many_attempts' => 'बहुत अधिक गलत पासवर्ड: रहस्य नष्ट कर दिया गया है।',
    'error.secret_required' => 'कृपया साझा करने के लिए रहस्य दर्ज करें।',
    'error.secret_invalid' => 'रहस्य में अमान्य वर्ण हैं।',
    'error.secret_too_long' => 'रहस्य {max} वर्णों से अधिक नहीं होना चाहिए।',
    'error.duration_invalid' => 'कृपया वैधता चुनें।',
    'error.password_invalid' => 'पासवर्ड में अमान्य वर्ण हैं।',
    'error.password_too_long' => 'पासवर्ड 1024 वर्णों से अधिक नहीं होना चाहिए।',
    'error.storage_full' => 'सेवा की अधिकतम भंडारण क्षमता पूरी हो गई है। कृपया बाद में प्रयास करें।',
    'error.rate_limited' => 'आपके कनेक्शन से बहुत अधिक अनुरोध आए हैं। दोबारा प्रयास करने से पहले कुछ मिनट प्रतीक्षा '
        . 'करें।',
    'error.unlimited_quota' => 'बिना समय-सीमा वाले बहुत अधिक रहस्य पहले से संग्रहीत हैं: कृपया वैधता चुनें।',
    'error.short_url_forbidden' => 'छोटे URL के लिए एक बार उपयोग या समय-सीमा आवश्यक है।',
];
