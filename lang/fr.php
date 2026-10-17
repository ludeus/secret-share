<?php

/**
 * Traductions françaises
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

return [
    'app.name' => 'Secret Share',
    'app.tagline' => 'Partagez mots de passe et informations sensibles via un lien éphémère et chiffré.',
    'app.footer' => 'Secrets chiffrés, aucune donnée conservée en clair, aucun journal des liens.',
    'app.source_code' => 'Code source',
    'app.skip_to_content' => 'Aller au contenu',

    'common.yes' => 'Oui',
    'common.no' => 'Non',

    'action.copy' => 'Copier',
    'action.copy_secret' => 'Copier le secret',
    'action.copied' => 'Copié !',
    'action.show_password' => 'Afficher le mot de passe',
    'action.hide_password' => 'Masquer le mot de passe',
    'action.new_secret' => 'Partager un autre secret',
    'action.share_own' => 'Partager un secret à mon tour',
    'action.home' => 'Retour à l\'accueil',

    'duration.10m' => '10 minutes',
    'duration.1h' => '1 heure',
    'duration.4h' => '4 heures',
    'duration.1d' => '1 jour',
    'duration.1w' => '1 semaine',
    'duration.1mo' => '1 mois',
    'duration.1y' => '1 an',
    'duration.none' => 'Sans limite',

    'home.title' => 'Partager un secret',
    'home.lead' => 'Le secret est chiffré puis transformé en lien et QR code à transmettre à votre destinataire.',
    'home.errors' => 'Le formulaire contient des erreurs, merci de les corriger.',
    'home.secret' => 'Secret',
    'home.secret_placeholder' => 'Mot de passe, clé d\'API, code d\'accès…',
    'home.duration' => 'Durée de validité',
    'home.password' => 'Mot de passe',
    'home.optional' => '(facultatif)',
    'home.password_hint' => 'Ajoute une seconde couche de chiffrement. À communiquer par un autre canal.',
    'home.options' => 'Options',
    'home.single_use' => 'Usage unique',
    'home.single_use_hint' => 'Le secret est détruit dès sa première consultation.',
    'home.short_url' => 'Générer une URL courte',
    'home.short_url_hint' => 'Le contenu chiffré est alors conservé temporairement sur le serveur '
        . '(jusqu\'à sa lecture ou son expiration) au lieu d\'être contenu dans le lien.',
    'home.short_url_disabled' => 'Indisponible sans usage unique ni durée limitée : '
        . 'le contenu serait conservé indéfiniment sur le serveur.',
    'home.submit' => 'Créer le lien sécurisé',
    'home.how_title' => 'Fonctionnement',
    'home.feature_encrypted_title' => 'Chiffré',
    'home.feature_encrypted' => 'XChaCha20-Poly1305, et Argon2id pour le mot de passe optionnel.',
    'home.feature_split_title' => 'Clé partagée',
    'home.feature_split' => 'Ni le lien seul ni le serveur seul ne permettent de lire le secret.',
    'home.feature_traceless_title' => 'Sans trace',
    'home.feature_traceless' => 'Le jeton reste dans le fragment de l\'URL, jamais transmis dans les journaux.',

    'created.title' => 'Votre lien est prêt',
    'created.lead' => 'Transmettez ce lien ou faites scanner le QR code à votre destinataire.',
    'created.qr_alt' => 'QR code contenant le lien de partage',
    'created.qr_too_long' => 'Le lien est trop long pour être représenté par un QR code. '
        . 'Activez l\'URL courte pour en obtenir un.',
    'created.share_url' => 'Lien de partage',
    'created.expires' => 'Expiration',
    'created.never' => 'Jamais',
    'created.single_use' => 'Usage unique',
    'created.password' => 'Mot de passe',
    'created.storage' => 'Contenu chiffré',
    'created.storage_link' => 'Dans le lien',
    'created.storage_server' => 'Sur le serveur',
    'created.warning_once' => 'Ce lien n\'est affiché qu\'une seule fois : il n\'est conservé nulle part.',
    'created.warning_password' => 'Communiquez le mot de passe par un autre canal que le lien.',
    'created.revoke_title' => 'Lien de destruction',
    'created.revoke_hint' => 'Conservez ce lien pour vous : il permet de détruire le secret '
        . 'avant sa lecture ou son expiration.',

    'reveal.title' => 'Un secret a été partagé avec vous',
    'reveal.lead' => 'Il ne sera déchiffré qu\'à votre demande. S\'il est à usage unique, '
        . 'il sera détruit dès son affichage : préparez-vous à le copier.',
    'reveal.noscript' => 'JavaScript est nécessaire : le jeton du lien reste dans votre navigateur '
        . 'et n\'est transmis qu\'à votre demande.',
    'reveal.missing_token' => 'Ce lien est incomplet. Vérifiez qu\'il a été copié en entier, '
        . 'partie après « # » comprise.',
    'reveal.password' => 'Mot de passe',
    'reveal.password_hint' => 'Ce secret est protégé par un mot de passe que l\'expéditeur a dû vous transmettre.',
    'reveal.wrong_password' => 'Mot de passe incorrect. Tentatives restantes avant destruction du secret : {count}.',
    'reveal.submit' => 'Afficher le secret',

    'revealed.title' => 'Voici le secret',
    'revealed.label' => 'Secret',
    'revealed.destroyed' => 'Ce secret vient d\'être détruit : copiez-le maintenant, il ne pourra plus être affiché.',
    'revealed.expires' => 'Ce secret reste consultable jusqu\'au',
    'revealed.no_expiry' => 'Ce secret reste consultable sans limite de durée.',

    'revoke.title' => 'Détruire un secret',
    'revoke.lead' => 'Le secret associé à ce lien sera définitivement détruit et ne pourra plus être lu par personne.',
    'revoke.submit' => 'Détruire définitivement',

    'revoked.title' => 'Secret détruit',
    'revoked.lead' => 'Le secret a été effacé du serveur. Le lien de partage est désormais inutilisable.',
    'revoked.not_found_title' => 'Rien à détruire',
    'revoked.not_found' => 'Ce secret n\'existe plus : il a déjà été consulté, détruit ou a expiré.',

    'error.title_gone' => 'Secret indisponible',
    'error.title_generic' => 'Une erreur est survenue',
    'error.not_found' => 'Cette page n\'existe pas.',
    'error.csrf' => 'Votre session de formulaire a expiré. Rechargez la page puis réessayez.',
    'error.secret_unavailable' => 'Ce secret n\'existe pas, a expiré, a déjà été consulté ou a été détruit.',
    'error.too_many_attempts' => 'Trop de mots de passe incorrects : le secret a été détruit.',
    'error.secret_required' => 'Veuillez saisir le secret à partager.',
    'error.secret_invalid' => 'Le secret contient des caractères invalides.',
    'error.secret_too_long' => 'Le secret ne doit pas dépasser {max} caractères.',
    'error.duration_invalid' => 'Veuillez choisir une durée de validité.',
    'error.password_invalid' => 'Le mot de passe contient des caractères invalides.',
    'error.password_too_long' => 'Le mot de passe ne doit pas dépasser 1024 caractères.',
    'error.storage_full' => 'Le service a atteint sa capacité maximale de stockage. Réessayez plus tard.',
    'error.rate_limited' => 'Trop de requêtes depuis votre connexion. Patientez quelques minutes avant de réessayer.',
    'error.unlimited_quota' => 'Trop de secrets sans limite de durée sont déjà stockés : '
        . 'choisissez une durée de validité.',
    'error.short_url_forbidden' => 'L\'URL courte nécessite l\'usage unique ou une durée limitée.',
];
