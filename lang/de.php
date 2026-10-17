<?php

/**
 * Traductions allemandes
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

return [
    'lang.name' => 'Deutsch',
    'lang.dir' => 'ltr',

    'app.name' => 'Secret Share',
    'app.tagline' => 'Teilen Sie Passwörter und vertrauliche Daten über einen kurzlebigen, verschlüsselten Link.',
    'app.footer' => 'Verschlüsselte Geheimnisse, nichts im Klartext gespeichert, keine Protokollierung der Links.',
    'app.source_code' => 'Quellcode',
    'app.skip_to_content' => 'Zum Inhalt springen',
    'app.language' => 'Sprache',

    'common.yes' => 'Ja',
    'common.no' => 'Nein',

    'action.copy' => 'Kopieren',
    'action.copy_secret' => 'Geheimnis kopieren',
    'action.copied' => 'Kopiert!',
    'action.show_password' => 'Passwort anzeigen',
    'action.hide_password' => 'Passwort verbergen',
    'action.new_secret' => 'Weiteres Geheimnis teilen',
    'action.share_own' => 'Selbst ein Geheimnis teilen',
    'action.home' => 'Zurück zur Startseite',

    'duration.10m' => '10 Minuten',
    'duration.1h' => '1 Stunde',
    'duration.4h' => '4 Stunden',
    'duration.1d' => '1 Tag',
    'duration.1w' => '1 Woche',
    'duration.1mo' => '1 Monat',
    'duration.1y' => '1 Jahr',
    'duration.none' => 'Unbegrenzt',

    'home.title' => 'Ein Geheimnis teilen',
    'home.lead' => 'Das Geheimnis wird verschlüsselt und in einen Link und einen QR-Code für den Empfänger '
        . 'umgewandelt.',
    'home.errors' => 'Das Formular enthält Fehler, bitte korrigieren Sie sie.',
    'home.secret' => 'Geheimnis',
    'home.secret_placeholder' => 'Passwort, API-Schlüssel, Zugangscode…',
    'home.duration' => 'Gültigkeit',
    'home.password' => 'Passwort',
    'home.optional' => '(optional)',
    'home.password_hint' => 'Fügt eine zweite Verschlüsselungsebene hinzu. Über einen anderen Kanal übermitteln.',
    'home.options' => 'Optionen',
    'home.single_use' => 'Einmalige Verwendung',
    'home.single_use_hint' => 'Das Geheimnis wird beim ersten Abruf vernichtet.',
    'home.short_url' => 'Kurze URL erzeugen',
    'home.short_url_hint' => 'Der verschlüsselte Inhalt wird dann vorübergehend auf dem Server gespeichert (bis er '
        . 'gelesen wird oder abläuft), statt im Link enthalten zu sein.',
    'home.short_url_disabled' => 'Nicht verfügbar ohne einmalige Verwendung oder Ablaufdatum: der Inhalt würde '
        . 'unbegrenzt auf dem Server gespeichert.',
    'home.submit' => 'Sicheren Link erstellen',
    'home.how_title' => 'So funktioniert es',
    'home.feature_encrypted_title' => 'Verschlüsselt',
    'home.feature_encrypted' => 'XChaCha20-Poly1305, dazu Argon2id für das optionale Passwort.',
    'home.feature_split_title' => 'Geteilter Schlüssel',
    'home.feature_split' => 'Weder der Link allein noch der Server allein kann das Geheimnis lesen.',
    'home.feature_traceless_title' => 'Spurlos',
    'home.feature_traceless' => 'Das Token bleibt im Fragment der URL und landet nie in Protokollen.',

    'created.title' => 'Ihr Link ist bereit',
    'created.lead' => 'Senden Sie diesen Link an den Empfänger oder lassen Sie ihn den QR-Code scannen.',
    'created.qr_alt' => 'QR-Code mit dem Freigabelink',
    'created.qr_too_long' => 'Der Link ist zu lang für einen QR-Code. Aktivieren Sie die kurze URL, um einen zu '
        . 'erhalten.',
    'created.share_url' => 'Freigabelink',
    'created.expires' => 'Ablauf',
    'created.never' => 'Nie',
    'created.single_use' => 'Einmalige Verwendung',
    'created.password' => 'Passwort',
    'created.storage' => 'Verschlüsselter Inhalt',
    'created.storage_link' => 'Im Link',
    'created.storage_server' => 'Auf dem Server',
    'created.warning_once' => 'Dieser Link wird nur einmal angezeigt: er wird nirgends gespeichert.',
    'created.warning_password' => 'Übermitteln Sie das Passwort über einen anderen Kanal als den Link.',
    'created.revoke_title' => 'Vernichtungslink',
    'created.revoke_hint' => 'Behalten Sie diesen Link für sich: damit können Sie das Geheimnis vernichten, bevor es '
        . 'gelesen wird oder abläuft.',

    'reveal.title' => 'Jemand hat ein Geheimnis mit Ihnen geteilt',
    'reveal.lead' => 'Es wird erst auf Ihre Anforderung entschlüsselt. Ist es zur einmaligen Verwendung bestimmt, wird '
        . 'es nach der Anzeige vernichtet: halten Sie sich bereit, es zu kopieren.',
    'reveal.noscript' => 'JavaScript ist erforderlich: das Token des Links bleibt in Ihrem Browser und wird nur auf '
        . 'Ihre Anforderung gesendet.',
    'reveal.missing_token' => 'Dieser Link ist unvollständig. Prüfen Sie, ob er vollständig kopiert wurde, '
        . 'einschließlich des Teils nach „#“.',
    'reveal.password' => 'Passwort',
    'reveal.password_hint' => 'Dieses Geheimnis ist durch ein Passwort geschützt, das Ihnen der Absender mitgeteilt '
        . 'haben sollte.',
    'reveal.wrong_password' => 'Falsches Passwort. Verbleibende Versuche, bevor das Geheimnis vernichtet wird: '
        . '{count}.',
    'reveal.submit' => 'Geheimnis anzeigen',

    'revealed.title' => 'Hier ist das Geheimnis',
    'revealed.label' => 'Geheimnis',
    'revealed.destroyed' => 'Dieses Geheimnis wurde soeben vernichtet: kopieren Sie es jetzt, es kann nicht erneut '
        . 'angezeigt werden.',
    'revealed.expires' => 'Dieses Geheimnis bleibt verfügbar bis',
    'revealed.no_expiry' => 'Dieses Geheimnis bleibt unbegrenzt verfügbar.',

    'revoke.title' => 'Ein Geheimnis vernichten',
    'revoke.lead' => 'Das Geheimnis hinter diesem Link wird endgültig vernichtet und kann von niemandem mehr gelesen '
        . 'werden.',
    'revoke.submit' => 'Endgültig vernichten',

    'revoked.title' => 'Geheimnis vernichtet',
    'revoked.lead' => 'Das Geheimnis wurde vom Server gelöscht. Der Freigabelink ist nun nutzlos.',
    'revoked.not_found_title' => 'Nichts zu vernichten',
    'revoked.not_found' => 'Dieses Geheimnis existiert nicht mehr: es wurde bereits abgerufen, vernichtet oder ist '
        . 'abgelaufen.',

    'error.title_gone' => 'Geheimnis nicht verfügbar',
    'error.title_generic' => 'Ein Fehler ist aufgetreten',
    'error.not_found' => 'Diese Seite existiert nicht.',
    'error.csrf' => 'Ihre Formularsitzung ist abgelaufen. Laden Sie die Seite neu und versuchen Sie es erneut.',
    'error.secret_unavailable' => 'Dieses Geheimnis existiert nicht, ist abgelaufen, wurde bereits abgerufen oder '
        . 'wurde vernichtet.',
    'error.too_many_attempts' => 'Zu viele falsche Passwörter: das Geheimnis wurde vernichtet.',
    'error.secret_required' => 'Bitte geben Sie das zu teilende Geheimnis ein.',
    'error.secret_invalid' => 'Das Geheimnis enthält ungültige Zeichen.',
    'error.secret_too_long' => 'Das Geheimnis darf höchstens {max} Zeichen lang sein.',
    'error.duration_invalid' => 'Bitte wählen Sie eine Gültigkeit.',
    'error.password_invalid' => 'Das Passwort enthält ungültige Zeichen.',
    'error.password_too_long' => 'Das Passwort darf höchstens 1024 Zeichen lang sein.',
    'error.storage_full' => 'Der Dienst hat seine maximale Speicherkapazität erreicht. Bitte versuchen Sie es später '
        . 'erneut.',
    'error.rate_limited' => 'Zu viele Anfragen von Ihrer Verbindung. Bitte warten Sie einige Minuten, bevor Sie es '
        . 'erneut versuchen.',
    'error.unlimited_quota' => 'Es sind bereits zu viele unbegrenzt gültige Geheimnisse gespeichert: bitte wählen Sie '
        . 'eine Gültigkeit.',
    'error.short_url_forbidden' => 'Die kurze URL erfordert einmalige Verwendung oder ein Ablaufdatum.',
];
