<?php

/**
 * Traductions portugaises
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

return [
    'lang.name' => 'Português',
    'lang.dir' => 'ltr',

    'app.name' => 'Secret Share',
    'app.tagline' => 'Partilhe palavras-passe e dados sensíveis através de um link efémero e cifrado.',
    'app.footer' => 'Segredos cifrados, nada guardado em claro, nenhum registo dos links.',
    'app.source_code' => 'Código-fonte',
    'app.skip_to_content' => 'Ir para o conteúdo',
    'app.language' => 'Idioma',

    'common.yes' => 'Sim',
    'common.no' => 'Não',

    'action.copy' => 'Copiar',
    'action.copy_secret' => 'Copiar o segredo',
    'action.copied' => 'Copiado!',
    'action.show_password' => 'Mostrar a palavra-passe',
    'action.hide_password' => 'Ocultar a palavra-passe',
    'action.new_secret' => 'Partilhar outro segredo',
    'action.share_own' => 'Partilhar um segredo meu',
    'action.home' => 'Voltar ao início',

    'duration.10m' => '10 minutos',
    'duration.1h' => '1 hora',
    'duration.4h' => '4 horas',
    'duration.1d' => '1 dia',
    'duration.1w' => '1 semana',
    'duration.1mo' => '1 mês',
    'duration.1y' => '1 ano',
    'duration.none' => 'Sem limite',

    'home.title' => 'Partilhar um segredo',
    'home.lead' => 'O segredo é cifrado e transformado num link e num código QR para o destinatário.',
    'home.errors' => 'O formulário contém erros, corrija-os por favor.',
    'home.secret' => 'Segredo',
    'home.secret_placeholder' => 'Palavra-passe, chave de API, código de acesso…',
    'home.duration' => 'Validade',
    'home.password' => 'Palavra-passe',
    'home.optional' => '(opcional)',
    'home.password_hint' => 'Acrescenta uma segunda camada de cifra. Envie-a por outro canal.',
    'home.options' => 'Opções',
    'home.single_use' => 'Utilização única',
    'home.single_use_hint' => 'O segredo é destruído assim que for consultado pela primeira vez.',
    'home.short_url' => 'Gerar um URL curto',
    'home.short_url_hint' => 'O conteúdo cifrado fica então guardado temporariamente no servidor (até ser lido ou '
        . 'expirar) em vez de seguir dentro do link.',
    'home.short_url_disabled' => 'Indisponível sem utilização única nem validade: o conteúdo ficaria guardado '
        . 'indefinidamente no servidor.',
    'home.submit' => 'Criar o link seguro',
    'home.how_title' => 'Como funciona',
    'home.feature_encrypted_title' => 'Cifrado',
    'home.feature_encrypted' => 'XChaCha20-Poly1305, e Argon2id para a palavra-passe opcional.',
    'home.feature_split_title' => 'Chave dividida',
    'home.feature_split' => 'Nem o link sozinho nem o servidor sozinho conseguem ler o segredo.',
    'home.feature_traceless_title' => 'Sem rasto',
    'home.feature_traceless' => 'O token fica no fragmento do URL e nunca chega aos registos.',

    'created.title' => 'O seu link está pronto',
    'created.lead' => 'Envie este link ao destinatário ou deixe-o ler o código QR.',
    'created.qr_alt' => 'Código QR com o link de partilha',
    'created.qr_too_long' => 'O link é demasiado longo para um código QR. Ative o URL curto para obter um.',
    'created.share_url' => 'Link de partilha',
    'created.expires' => 'Validade',
    'created.never' => 'Nunca',
    'created.single_use' => 'Utilização única',
    'created.password' => 'Palavra-passe',
    'created.storage' => 'Conteúdo cifrado',
    'created.storage_link' => 'No link',
    'created.storage_server' => 'No servidor',
    'created.warning_once' => 'Este link só é mostrado uma vez: não fica guardado em lado nenhum.',
    'created.warning_password' => 'Envie a palavra-passe por um canal diferente do link.',
    'created.revoke_title' => 'Link de destruição',
    'created.revoke_hint' => 'Guarde este link para si: permite destruir o segredo antes de ser lido ou expirar.',

    'reveal.title' => 'Partilharam um segredo consigo',
    'reveal.lead' => 'Só será decifrado quando o pedir. Se for de utilização única, será destruído assim que for '
        . 'mostrado: prepare-se para o copiar.',
    'reveal.noscript' => 'É necessário JavaScript: o token do link fica no seu navegador e só é enviado quando o '
        . 'pedir.',
    'reveal.missing_token' => 'Este link está incompleto. Verifique se foi copiado por inteiro, incluindo a parte '
        . 'depois de «#».',
    'reveal.password' => 'Palavra-passe',
    'reveal.password_hint' => 'Este segredo está protegido por uma palavra-passe que o remetente lhe deve ter dado.',
    'reveal.wrong_password' => 'Palavra-passe incorreta. Tentativas restantes antes de o segredo ser destruído: '
        . '{count}.',
    'reveal.submit' => 'Mostrar o segredo',

    'revealed.title' => 'Aqui está o segredo',
    'revealed.label' => 'Segredo',
    'revealed.destroyed' => 'Este segredo acabou de ser destruído: copie-o agora, não poderá voltar a ser mostrado.',
    'revealed.expires' => 'Este segredo continua disponível até',
    'revealed.no_expiry' => 'Este segredo continua disponível sem limite de tempo.',

    'revoke.title' => 'Destruir um segredo',
    'revoke.lead' => 'O segredo deste link será destruído definitivamente e ninguém o poderá ler.',
    'revoke.submit' => 'Destruir definitivamente',

    'revoked.title' => 'Segredo destruído',
    'revoked.lead' => 'O segredo foi apagado do servidor. O link de partilha deixou de funcionar.',
    'revoked.not_found_title' => 'Nada a destruir',
    'revoked.not_found' => 'Este segredo já não existe: já foi consultado, destruído ou expirou.',

    'error.title_gone' => 'Segredo indisponível',
    'error.title_generic' => 'Ocorreu um erro',
    'error.not_found' => 'Esta página não existe.',
    'error.csrf' => 'A sessão do formulário expirou. Recarregue a página e tente novamente.',
    'error.secret_unavailable' => 'Este segredo não existe, expirou, já foi consultado ou foi destruído.',
    'error.too_many_attempts' => 'Demasiadas palavras-passe incorretas: o segredo foi destruído.',
    'error.secret_required' => 'Introduza o segredo a partilhar.',
    'error.secret_invalid' => 'O segredo contém caracteres inválidos.',
    'error.secret_too_long' => 'O segredo não pode exceder {max} caracteres.',
    'error.duration_invalid' => 'Escolha uma validade.',
    'error.password_invalid' => 'A palavra-passe contém caracteres inválidos.',
    'error.password_too_long' => 'A palavra-passe não pode exceder 1024 caracteres.',
    'error.storage_full' => 'O serviço atingiu a capacidade máxima de armazenamento. Tente novamente mais tarde.',
    'error.rate_limited' => 'Demasiados pedidos a partir da sua ligação. Aguarde alguns minutos antes de tentar '
        . 'novamente.',
    'error.unlimited_quota' => 'Já existem demasiados segredos sem limite de tempo: escolha uma validade.',
    'error.short_url_forbidden' => 'O URL curto requer utilização única ou uma validade.',
];
