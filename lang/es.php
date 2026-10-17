<?php

/**
 * Traductions espagnole
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

return [
    'lang.name' => 'Español',
    'lang.dir' => 'ltr',

    'app.name' => 'Secret Share',
    'app.tagline' => 'Comparte contraseñas y datos sensibles mediante un enlace efímero y cifrado.',
    'app.footer' => 'Secretos cifrados, nada se guarda en claro, ningún registro de los enlaces.',
    'app.source_code' => 'Código fuente',
    'app.skip_to_content' => 'Ir al contenido',
    'app.language' => 'Idioma',

    'common.yes' => 'Sí',
    'common.no' => 'No',

    'action.copy' => 'Copiar',
    'action.copy_secret' => 'Copiar el secreto',
    'action.copied' => '¡Copiado!',
    'action.show_password' => 'Mostrar la contraseña',
    'action.hide_password' => 'Ocultar la contraseña',
    'action.new_secret' => 'Compartir otro secreto',
    'action.share_own' => 'Compartir un secreto propio',
    'action.home' => 'Volver al inicio',

    'duration.10m' => '10 minutos',
    'duration.1h' => '1 hora',
    'duration.4h' => '4 horas',
    'duration.1d' => '1 día',
    'duration.1w' => '1 semana',
    'duration.1mo' => '1 mes',
    'duration.1y' => '1 año',
    'duration.none' => 'Sin límite',

    'home.title' => 'Compartir un secreto',
    'home.lead' => 'El secreto se cifra y se convierte en un enlace y un código QR para tu destinatario.',
    'home.errors' => 'El formulario contiene errores, corrígelos por favor.',
    'home.secret' => 'Secreto',
    'home.secret_placeholder' => 'Contraseña, clave de API, código de acceso…',
    'home.duration' => 'Validez',
    'home.password' => 'Contraseña',
    'home.optional' => '(opcional)',
    'home.password_hint' => 'Añade una segunda capa de cifrado. Envíala por otro canal.',
    'home.options' => 'Opciones',
    'home.single_use' => 'Uso único',
    'home.single_use_hint' => 'El secreto se destruye en cuanto se consulta por primera vez.',
    'home.short_url' => 'Generar una URL corta',
    'home.short_url_hint' => 'El contenido cifrado se guarda entonces temporalmente en el servidor (hasta que se lea o '
        . 'caduque) en lugar de ir dentro del enlace.',
    'home.short_url_disabled' => 'No disponible sin uso único ni caducidad: el contenido se guardaría indefinidamente '
        . 'en el servidor.',
    'home.submit' => 'Crear el enlace seguro',
    'home.how_title' => 'Cómo funciona',
    'home.feature_encrypted_title' => 'Cifrado',
    'home.feature_encrypted' => 'XChaCha20-Poly1305, y Argon2id para la contraseña opcional.',
    'home.feature_split_title' => 'Clave dividida',
    'home.feature_split' => 'Ni el enlace solo ni el servidor solo pueden leer el secreto.',
    'home.feature_traceless_title' => 'Sin rastro',
    'home.feature_traceless' => 'El token se queda en el fragmento de la URL y nunca llega a los registros.',

    'created.title' => 'Tu enlace está listo',
    'created.lead' => 'Envía este enlace a tu destinatario o deja que escanee el código QR.',
    'created.qr_alt' => 'Código QR con el enlace para compartir',
    'created.qr_too_long' => 'El enlace es demasiado largo para un código QR. Activa la URL corta para obtener uno.',
    'created.share_url' => 'Enlace para compartir',
    'created.expires' => 'Caducidad',
    'created.never' => 'Nunca',
    'created.single_use' => 'Uso único',
    'created.password' => 'Contraseña',
    'created.storage' => 'Contenido cifrado',
    'created.storage_link' => 'En el enlace',
    'created.storage_server' => 'En el servidor',
    'created.warning_once' => 'Este enlace solo se muestra una vez: no se guarda en ningún sitio.',
    'created.warning_password' => 'Envía la contraseña por un canal distinto al del enlace.',
    'created.revoke_title' => 'Enlace de destrucción',
    'created.revoke_hint' => 'Guarda este enlace para ti: permite destruir el secreto antes de que se lea o caduque.',

    'reveal.title' => 'Alguien ha compartido un secreto contigo',
    'reveal.lead' => 'Solo se descifrará cuando lo pidas. Si es de uso único, se destruirá en cuanto se muestre: '
        . 'prepárate para copiarlo.',
    'reveal.noscript' => 'Se necesita JavaScript: el token del enlace se queda en tu navegador y solo se envía cuando '
        . 'lo pides.',
    'reveal.missing_token' => 'Este enlace está incompleto. Comprueba que se ha copiado entero, incluida la parte '
        . 'después de «#».',
    'reveal.password' => 'Contraseña',
    'reveal.password_hint' => 'Este secreto está protegido por una contraseña que el remitente debe haberte dado.',
    'reveal.wrong_password' => 'Contraseña incorrecta. Intentos restantes antes de destruir el secreto: {count}.',
    'reveal.submit' => 'Mostrar el secreto',

    'revealed.title' => 'Este es el secreto',
    'revealed.label' => 'Secreto',
    'revealed.destroyed' => 'Este secreto acaba de destruirse: cópialo ahora, no podrá volver a mostrarse.',
    'revealed.expires' => 'Este secreto seguirá disponible hasta el',
    'revealed.no_expiry' => 'Este secreto seguirá disponible sin límite de tiempo.',

    'revoke.title' => 'Destruir un secreto',
    'revoke.lead' => 'El secreto de este enlace se destruirá definitivamente y nadie podrá leerlo.',
    'revoke.submit' => 'Destruir definitivamente',

    'revoked.title' => 'Secreto destruido',
    'revoked.lead' => 'El secreto se ha borrado del servidor. El enlace para compartir ya no sirve.',
    'revoked.not_found_title' => 'Nada que destruir',
    'revoked.not_found' => 'Este secreto ya no existe: ya se ha consultado, destruido o ha caducado.',

    'error.title_gone' => 'Secreto no disponible',
    'error.title_generic' => 'Se ha producido un error',
    'error.not_found' => 'Esta página no existe.',
    'error.csrf' => 'Tu sesión de formulario ha caducado. Recarga la página e inténtalo de nuevo.',
    'error.secret_unavailable' => 'Este secreto no existe, ha caducado, ya se ha consultado o ha sido destruido.',
    'error.too_many_attempts' => 'Demasiadas contraseñas incorrectas: el secreto se ha destruido.',
    'error.secret_required' => 'Introduce el secreto que quieres compartir.',
    'error.secret_invalid' => 'El secreto contiene caracteres no válidos.',
    'error.secret_too_long' => 'El secreto no debe superar los {max} caracteres.',
    'error.duration_invalid' => 'Elige una validez.',
    'error.password_invalid' => 'La contraseña contiene caracteres no válidos.',
    'error.password_too_long' => 'La contraseña no debe superar los 1024 caracteres.',
    'error.storage_full' => 'El servicio ha alcanzado su capacidad máxima de almacenamiento. Vuelve a intentarlo más '
        . 'tarde.',
    'error.rate_limited' => 'Demasiadas solicitudes desde tu conexión. Espera unos minutos antes de volver a '
        . 'intentarlo.',
    'error.unlimited_quota' => 'Ya hay demasiados secretos sin límite de tiempo: elige una validez.',
    'error.short_url_forbidden' => 'La URL corta requiere uso único o una caducidad.',
];
