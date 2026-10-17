<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare;

use PDO;
use SecretShare\Crypto\Envelope;
use SecretShare\Crypto\JwtCodec;
use SecretShare\Crypto\KeyRing;
use SecretShare\Crypto\PasswordRequiredException;
use SecretShare\Crypto\Semaphore;
use SecretShare\Http\ClientIp;
use SecretShare\Http\Csrf;
use SecretShare\Http\RateLimiter;
use SecretShare\Http\Request;
use SecretShare\Http\Response;
use SecretShare\Http\SecurityHeaders;
use SecretShare\Secret\Duration;
use SecretShare\Secret\InvalidPasswordException;
use SecretShare\Secret\SecretNotFoundException;
use SecretShare\Secret\SecretService;
use SecretShare\Secret\ShareRequest;
use SecretShare\Secret\QuotaExceededException;
use SecretShare\Secret\ValidationException;
use SecretShare\Storage\Database;
use SecretShare\Storage\Maintenance;
use SecretShare\Storage\RateLimitRepository;
use SecretShare\Storage\SecretRepository;
use SecretShare\View\QrCodeRenderer;
use SecretShare\View\Translator;
use SecretShare\View\View;

/**
 * Point d'entrée HTTP : routage et contrôleurs.
 *
 * Routes :
 *   GET  /        formulaire de création
 *   POST /        création, affiche le lien et le QR code
 *   GET  /s       page d'ouverture d'un secret (jeton dans le fragment #)
 *   POST /s       déchiffrement et affichage
 *   GET  /r       page de révocation (jeton dans le fragment #)
 *   POST /r       destruction du secret
 *   GET  /health  sonde de supervision
 *
 * Les jetons sont transmis dans le fragment de l'URL : le navigateur ne
 * l'envoie jamais au serveur, il n'apparaît donc dans aucun journal HTTP
 * (serveur, proxy…). Le JavaScript le recopie dans un formulaire POST,
 * ce qui protège aussi les secrets à usage unique des robots de
 * prévisualisation de liens (messageries, antispam) qui suivent les GET.
 */
final readonly class App
{
    public const string VERSION = '1.1.0';
    public const string SHARE_PATH = '/s';
    public const string REVOKE_PATH = '/r';
    private const string ROOT_DIR = __DIR__ . '/..';

    public function __construct(
        private Config $config,
        private SecretService $secrets,
        private QrCodeRenderer $qrCode,
        private Maintenance $maintenance,
        private RateLimiter $rateLimiter,
        private ClientIp $clientIp,
        private Clock $clock,
        private string $templatesDir = self::ROOT_DIR . '/templates',
        private string $langDir = self::ROOT_DIR . '/lang',
    ) {
    }

    /**
     * Construit l'application et ses dépendances
     */
    public static function create(
        Config $config,
        ?PDO $pdo = null,
        ?Clock $clock = null,
        ?Envelope $envelope = null
    ): self {
        $clock ??= new SystemClock();
        $pdo ??= Database::connect($config->databasePath);
        $keyRing = new KeyRing($config->appSecret);
        $secrets = new SecretRepository($pdo);
        $rateLimits = new RateLimitRepository($pdo);
        $service = new SecretService(
            $config,
            $secrets,
            $keyRing,
            $envelope ?? new Envelope(
                pwhashSemaphore: new Semaphore(sys_get_temp_dir(), max(1, $config->argon2Concurrency))
            ),
            new JwtCodec($keyRing, $clock),
            $clock,
        );
        return new self(
            $config,
            $service,
            new QrCodeRenderer(),
            new Maintenance($pdo, $secrets, $rateLimits),
            new RateLimiter($rateLimits, $keyRing, $clock),
            new ClientIp($config->trustedProxies),
            $clock,
        );
    }

    public function handle(Request $request): Response
    {
        if ($request->path === '/health') {
            return new Response(
                'OK',
                200,
                ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']
            );
        }

        $view = new View(
            $this->templatesDir,
            Translator::forRequest($request->header('accept-language'), $this->config->defaultLocale, $this->langDir),
            ['sourceUrl' => $this->config->sourceUrl, 'version' => self::VERSION]
        );
        $csrf = new Csrf($this->config->isHttps());
        $csrfToken = $csrf->token($request);

        // ramasse-miettes : purge des secrets expirés, au plus une fois par minute
        $this->maintenance->runIfDue($this->clock->now());

        $route = $request->method . ' ' . $request->path;
        $retryAfter = $request->method === 'POST' ? $this->rateLimit($request) : null;
        if ($retryAfter !== null) {
            $response = $this->error($view, 'error.rate_limited', 429, $csrfToken)
                ->withHeader('Retry-After', (string)$retryAfter);
        } elseif ($request->method === 'POST' && !$csrf->isValid($request)) {
            $response = $this->error($view, 'error.csrf', 403, $csrfToken);
        } else {
            $response = match ($route) {
                'GET /' => $this->home($view, $csrfToken),
                'POST /' => $this->createSecret($view, $request, $csrfToken),
                'GET ' . self::SHARE_PATH => $this->revealForm($view, $csrfToken),
                'POST ' . self::SHARE_PATH => $this->reveal($view, $request, $csrfToken),
                'GET ' . self::REVOKE_PATH => $this->revokeForm($view, $csrfToken),
                'POST ' . self::REVOKE_PATH => $this->revoke($view, $request, $csrfToken),
                default => $this->error($view, 'error.not_found', 404, $csrfToken),
            };
        }

        $response = $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
        $response = $csrf->attach($request, $response, $csrfToken);
        return SecurityHeaders::apply($response, $this->config);
    }

    /**
     * Applique les limites de débit aux actions (POST) ; retourne le délai
     * d'attente en secondes si la limite est atteinte, null sinon
     */
    private function rateLimit(Request $request): ?int
    {
        $ip = $this->clientIp->resolve($request);
        $client = $ip === null ? 'unknown' : ClientIp::bucket($ip);
        if ($request->path === '/') {
            return $this->rateLimiter->consume(
                RateLimiter::SCOPE_CREATE,
                $client,
                $this->config->createLimitPerMinute,
                RateLimiter::WINDOWS[RateLimiter::SCOPE_CREATE][0]
            ) ?? $this->rateLimiter->consume(
                RateLimiter::SCOPE_CREATE,
                $client,
                $this->config->createLimitPerHour,
                RateLimiter::WINDOWS[RateLimiter::SCOPE_CREATE][1]
            );
        }
        return $this->rateLimiter->consume(
            RateLimiter::SCOPE_READ,
            $client,
            $this->config->readLimitPerMinute,
            RateLimiter::WINDOWS[RateLimiter::SCOPE_READ][0]
        );
    }

    /**
     * @param array<string, string> $old    valeurs saisies à réafficher
     * @param array<string, string> $errors erreurs de validation par champ
     */
    private function home(
        View $view,
        string $csrfToken,
        array $old = [],
        array $errors = [],
        int $status = 200
    ): Response {
        return new Response(
            $view->render(
                'home',
                [
                    'csrfToken' => $csrfToken,
                    'durations' => Duration::cases(),
                    'maxSecretLength' => $this->config->maxSecretLength,
                    'maxPasswordLength' => ShareRequest::MAX_PASSWORD_LENGTH,
                    'old' => $old + [
                        'secret' => '',
                        'duration' => Duration::DEFAULT->value,
                        'single_use' => '1',
                        'short_url' => '0',
                    ],
                    'errors' => $errors,
                ]
            ),
            $status
        );
    }

    private function createSecret(View $view, Request $request, string $csrfToken): Response
    {
        try {
            $shareRequest = ShareRequest::fromInput($request->post, $this->config->maxSecretLength);
        } catch (ValidationException $e) {
            $old = [
                'secret' => $request->postString('secret'),
                'duration' => $request->postString('duration'),
                'single_use' => $request->postString('single_use'),
                'short_url' => $request->postString('short_url'),
            ];
            return $this->home($view, $csrfToken, $old, $e->errors, 422);
        }

        try {
            $created = $this->secrets->create($shareRequest);
        } catch (QuotaExceededException $e) {
            return $this->error($view, $e->messageKey, 503, $csrfToken);
        }
        $shareUrl = $this->config->appUrl . self::SHARE_PATH . '#' . $created->token;
        return new Response(
            $view->render(
                'created',
                [
                    'shareUrl' => $shareUrl,
                    'revokeUrl' => $this->config->appUrl . self::REVOKE_PATH . '#' . $created->revokeToken,
                    'qrCode' => $this->qrCode->dataUri($shareUrl),
                    'created' => $created,
                ]
            ),
            201
        );
    }

    private function revealForm(View $view, string $csrfToken): Response
    {
        return new Response($view->render('reveal', ['csrfToken' => $csrfToken, 'token' => '']));
    }

    private function reveal(View $view, Request $request, string $csrfToken): Response
    {
        $token = $request->postString('token');
        $password = $request->postString('password');
        $vars = ['csrfToken' => $csrfToken, 'token' => $token];
        try {
            $revealed = $this->secrets->reveal($token, $password === '' ? null : $password);
        } catch (SecretNotFoundException) {
            return $this->error($view, 'error.secret_unavailable', 404, $csrfToken);
        } catch (PasswordRequiredException) {
            return new Response($view->render('reveal', $vars + ['passwordRequired' => true]));
        } catch (InvalidPasswordException $e) {
            if ($e->remainingAttempts === 0) {
                return $this->error($view, 'error.too_many_attempts', 410, $csrfToken);
            }
            return new Response(
                $view->render(
                    'reveal',
                    $vars + ['passwordRequired' => true, 'remainingAttempts' => $e->remainingAttempts]
                ),
                401
            );
        }
        return new Response($view->render('revealed', ['revealed' => $revealed]));
    }

    private function revokeForm(View $view, string $csrfToken): Response
    {
        return new Response($view->render('revoke', ['csrfToken' => $csrfToken]));
    }

    private function revoke(View $view, Request $request, string $csrfToken): Response
    {
        $revoked = $this->secrets->revoke($request->postString('token'));
        return new Response(
            $view->render('revoked', ['revoked' => $revoked, 'csrfToken' => $csrfToken]),
            $revoked ? 200 : 404
        );
    }

    private function error(View $view, string $messageKey, int $status, string $csrfToken): Response
    {
        return new Response(
            $view->render('error', ['messageKey' => $messageKey, 'status' => $status, 'csrfToken' => $csrfToken]),
            $status
        );
    }
}
