<?php

/**
 * Traductions anglaises
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

return [
    'app.name' => 'Secret Share',
    'app.tagline' => 'Share passwords and sensitive data through an ephemeral, encrypted link.',
    'app.footer' => 'Encrypted secrets, no plaintext ever stored, no link logging.',
    'app.source_code' => 'Source code',
    'app.skip_to_content' => 'Skip to content',

    'common.yes' => 'Yes',
    'common.no' => 'No',

    'action.copy' => 'Copy',
    'action.copy_secret' => 'Copy secret',
    'action.copied' => 'Copied!',
    'action.show_password' => 'Show password',
    'action.hide_password' => 'Hide password',
    'action.new_secret' => 'Share another secret',
    'action.share_own' => 'Share a secret of my own',
    'action.home' => 'Back to home',

    'duration.10m' => '10 minutes',
    'duration.1h' => '1 hour',
    'duration.4h' => '4 hours',
    'duration.1d' => '1 day',
    'duration.1w' => '1 week',
    'duration.1mo' => '1 month',
    'duration.1y' => '1 year',
    'duration.none' => 'No limit',

    'home.title' => 'Share a secret',
    'home.lead' => 'Your secret is encrypted, then turned into a link and a QR code for your recipient.',
    'home.errors' => 'The form contains errors, please fix them.',
    'home.secret' => 'Secret',
    'home.secret_placeholder' => 'Password, API key, access code…',
    'home.duration' => 'Expires after',
    'home.password' => 'Password',
    'home.optional' => '(optional)',
    'home.password_hint' => 'Adds a second encryption layer. Send it through another channel.',
    'home.options' => 'Options',
    'home.single_use' => 'Single use',
    'home.single_use_hint' => 'The secret is destroyed as soon as it is first viewed.',
    'home.short_url' => 'Generate a short URL',
    'home.short_url_hint' => 'The encrypted content is then temporarily stored on the server '
        . '(until it is read or expires) instead of being embedded in the link.',
    'home.short_url_disabled' => 'Unavailable without single use or an expiration: '
        . 'the content would be stored on the server indefinitely.',
    'home.submit' => 'Create secure link',
    'home.how_title' => 'How it works',
    'home.feature_encrypted_title' => 'Encrypted',
    'home.feature_encrypted' => 'XChaCha20-Poly1305, plus Argon2id for the optional password.',
    'home.feature_split_title' => 'Split key',
    'home.feature_split' => 'Neither the link alone nor the server alone can read the secret.',
    'home.feature_traceless_title' => 'Traceless',
    'home.feature_traceless' => 'The token stays in the URL fragment and never ends up in access logs.',

    'created.title' => 'Your link is ready',
    'created.lead' => 'Send this link to your recipient or let them scan the QR code.',
    'created.qr_alt' => 'QR code containing the share link',
    'created.qr_too_long' => 'The link is too long to fit in a QR code. Enable the short URL to get one.',
    'created.share_url' => 'Share link',
    'created.expires' => 'Expires',
    'created.never' => 'Never',
    'created.single_use' => 'Single use',
    'created.password' => 'Password',
    'created.storage' => 'Encrypted content',
    'created.storage_link' => 'In the link',
    'created.storage_server' => 'On the server',
    'created.warning_once' => 'This link is displayed only once: it is not stored anywhere.',
    'created.warning_password' => 'Send the password through a different channel than the link.',
    'created.revoke_title' => 'Destruction link',
    'created.revoke_hint' => 'Keep this link to yourself: it lets you destroy the secret before it is read or expires.',

    'reveal.title' => 'A secret has been shared with you',
    'reveal.lead' => 'It will only be decrypted when you ask for it. If it is single use, '
        . 'it will be destroyed as soon as it is displayed: get ready to copy it.',
    'reveal.noscript' => 'JavaScript is required: the link token stays in your browser '
        . 'and is only sent when you ask for it.',
    'reveal.missing_token' => 'This link is incomplete. Make sure it was copied entirely, '
        . 'including the part after "#".',
    'reveal.password' => 'Password',
    'reveal.password_hint' => 'This secret is protected by a password the sender should have given you.',
    'reveal.wrong_password' => 'Wrong password. Attempts left before the secret is destroyed: {count}.',
    'reveal.submit' => 'Reveal secret',

    'revealed.title' => 'Here is the secret',
    'revealed.label' => 'Secret',
    'revealed.destroyed' => 'This secret has just been destroyed: copy it now, it cannot be displayed again.',
    'revealed.expires' => 'This secret remains available until',
    'revealed.no_expiry' => 'This secret remains available with no time limit.',

    'revoke.title' => 'Destroy a secret',
    'revoke.lead' => 'The secret behind this link will be permanently destroyed and nobody will be able to read it.',
    'revoke.submit' => 'Destroy permanently',

    'revoked.title' => 'Secret destroyed',
    'revoked.lead' => 'The secret has been erased from the server. The share link is now useless.',
    'revoked.not_found_title' => 'Nothing to destroy',
    'revoked.not_found' => 'This secret no longer exists: it has already been viewed, destroyed or has expired.',

    'error.title_gone' => 'Secret unavailable',
    'error.title_generic' => 'Something went wrong',
    'error.not_found' => 'This page does not exist.',
    'error.csrf' => 'Your form session has expired. Reload the page and try again.',
    'error.secret_unavailable' => 'This secret does not exist, has expired, '
        . 'has already been viewed or was destroyed.',
    'error.too_many_attempts' => 'Too many wrong passwords: the secret has been destroyed.',
    'error.secret_required' => 'Please enter the secret to share.',
    'error.secret_invalid' => 'The secret contains invalid characters.',
    'error.secret_too_long' => 'The secret must not exceed {max} characters.',
    'error.duration_invalid' => 'Please choose an expiration.',
    'error.password_invalid' => 'The password contains invalid characters.',
    'error.password_too_long' => 'The password must not exceed 1024 characters.',
    'error.storage_full' => 'The service has reached its maximum storage capacity. Please try again later.',
    'error.rate_limited' => 'Too many requests from your connection. Please wait a few minutes before trying again.',
    'error.unlimited_quota' => 'Too many secrets without expiration are already stored: please choose an expiration.',
    'error.short_url_forbidden' => 'A short URL requires single use or an expiration.',
];
