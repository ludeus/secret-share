/*
 * Secret Share - comportements côté navigateur
 *
 * Amélioration progressive : la validation est doublée côté serveur, seul
 * l'ouverture d'un lien nécessite JavaScript (le jeton est dans le fragment
 * de l'URL, que le navigateur n'envoie jamais au serveur).
 *
 * @license AGPL-3.0-or-later
 */
(function () {
    'use strict';

    var liveRegion = document.getElementById('live-region');

    /**
     * Annonce un message aux lecteurs d'écran
     */
    function announce(message) {
        if (!liveRegion) {
            return;
        }
        liveRegion.textContent = '';
        window.setTimeout(function () {
            liveRegion.textContent = message;
        }, 50);
    }

    /**
     * Copie un texte dans le presse-papiers, avec repli pour les contextes non sécurisés
     */
    function copyText(text, fallbackElement) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            fallbackElement.focus();
            fallbackElement.select();
            try {
                if (document.execCommand('copy')) {
                    resolve();
                } else {
                    reject(new Error('copy failed'));
                }
            } catch (error) {
                reject(error);
            }
        });
    }

    /**
     * Boutons [data-copy="id-du-champ"]
     */
    function initCopyButtons() {
        document.querySelectorAll('[data-copy]').forEach(function (button) {
            var target = document.getElementById(button.dataset.copy);
            if (!target) {
                return;
            }
            var label = button.textContent.trim();
            var timer = null;
            button.addEventListener('click', function () {
                copyText(target.value, target).then(function () {
                    var copied = button.dataset.labelCopied || label;
                    button.textContent = copied;
                    button.classList.add('is-copied');
                    announce(copied);
                    window.clearTimeout(timer);
                    timer = window.setTimeout(function () {
                        button.textContent = label;
                        button.classList.remove('is-copied');
                    }, 2000);
                }).catch(function () {
                    // échec silencieux : le texte reste sélectionné pour une copie manuelle
                    target.focus();
                    target.select();
                });
            });
        });

        document.querySelectorAll('[data-select-on-focus]').forEach(function (field) {
            field.addEventListener('focus', function () {
                field.select();
            });
        });
    }

    /**
     * Boutons [data-toggle-password="id-du-champ"] : affiche/masque un mot de passe
     */
    function initPasswordToggles() {
        document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
            var input = document.getElementById(button.dataset.togglePassword);
            if (!input) {
                return;
            }
            var hiddenLabel = button.querySelector('.visually-hidden');
            button.addEventListener('click', function () {
                var visible = input.type === 'password';
                input.type = visible ? 'text' : 'password';
                button.setAttribute('aria-pressed', visible ? 'true' : 'false');
                var label = visible ? button.dataset.labelHide : button.dataset.labelShow;
                button.title = label;
                if (hiddenLabel) {
                    hiddenLabel.textContent = label;
                }
            });
        });
    }

    /**
     * Dates ISO <time data-local-time> affichées dans le fuseau du visiteur
     */
    function initLocalTimes() {
        var lang = document.documentElement.lang || undefined;
        document.querySelectorAll('time[data-local-time]').forEach(function (element) {
            var date = new Date(element.getAttribute('datetime'));
            if (isNaN(date.getTime())) {
                return;
            }
            try {
                element.textContent = date.toLocaleString(lang, {dateStyle: 'full', timeStyle: 'short'});
            } catch (error) {
                element.textContent = date.toLocaleString();
            }
        });
    }

    /**
     * Formulaire de création : validation (miroir de ShareRequest.php),
     * compteur de caractères et règle de l'URL courte
     */
    function initShareForm() {
        var form = document.getElementById('share-form');
        if (!form) {
            return;
        }
        var secret = form.elements.secret;
        var duration = form.elements.duration;
        var password = form.elements.password;
        var singleUse = form.elements.single_use;
        var shortUrl = form.elements.short_url;
        var counter = document.getElementById('secret-counter');
        var shortUrlDisabled = document.getElementById('short-url-disabled');
        var maxLength = parseInt(form.dataset.maxLength, 10);
        var submitted = false;

        // longueur en caractères Unicode, comme mb_strlen() côté serveur
        function length(value) {
            return Array.from(value.replace(/\r\n/g, '\n')).length;
        }

        function shortUrlAllowed() {
            return singleUse.checked || duration.value !== 'none';
        }

        function updateCounter() {
            var current = length(secret.value);
            counter.textContent = current + ' / ' + maxLength;
            counter.classList.toggle('is-over', current > maxLength);
        }

        function updateShortUrl() {
            var allowed = shortUrlAllowed();
            shortUrl.disabled = !allowed;
            if (!allowed) {
                shortUrl.checked = false;
            }
            shortUrlDisabled.hidden = allowed;
        }

        function setError(field, name, message) {
            var error = form.querySelector('[data-error-for="' + name + '"]');
            if (message) {
                field.setAttribute('aria-invalid', 'true');
                field.setAttribute('aria-describedby', name + '-error');
            } else {
                field.removeAttribute('aria-invalid');
            }
            if (error) {
                error.textContent = message || '';
                error.hidden = !message;
            }
        }

        function validate() {
            var errors = [];
            var secretError = '';
            if (secret.value.trim() === '') {
                secretError = form.dataset.msgSecretRequired;
            } else if (length(secret.value) > maxLength) {
                secretError = form.dataset.msgSecretTooLong;
            }
            setError(secret, 'secret', secretError);
            if (secretError) {
                errors.push(secret);
            }

            var passwordError = Array.from(password.value).length > 1024 ? form.dataset.msgPasswordTooLong : '';
            setError(password, 'password', passwordError);
            if (passwordError) {
                errors.push(password);
            }

            var shortUrlError = shortUrl.checked && !shortUrlAllowed() ? form.dataset.msgShortUrlForbidden : '';
            setError(shortUrl, 'short_url', shortUrlError);
            if (shortUrlError) {
                errors.push(shortUrl);
            }
            return errors;
        }

        secret.addEventListener('input', function () {
            updateCounter();
            if (secret.hasAttribute('aria-invalid')) {
                validate();
            }
        });
        singleUse.addEventListener('change', updateShortUrl);
        duration.addEventListener('change', updateShortUrl);

        form.addEventListener('submit', function (event) {
            var errors = validate();
            if (errors.length > 0 || submitted) {
                event.preventDefault();
                if (errors.length > 0) {
                    errors[0].focus();
                }
                return;
            }
            // évite la création de doublons en cas de double clic
            submitted = true;
            form.querySelector('[type="submit"]').setAttribute('aria-busy', 'true');
        });

        updateCounter();
        updateShortUrl();
    }

    /**
     * Pages d'ouverture et de révocation : le jeton est lu dans le fragment
     * de l'URL (#...) puis envoyé en POST uniquement à la demande du visiteur
     */
    function initFragmentForms() {
        document.querySelectorAll('[data-fragment-form]').forEach(function (form) {
            var input = form.querySelector('[data-fragment-token]');
            var submit = form.querySelector('[type="submit"]');
            if (input.value === '') {
                input.value = window.location.hash.slice(1).trim();
            }
            // retire immédiatement le jeton de la barre d'adresse et de l'historique
            if (window.location.hash && window.history.replaceState) {
                window.history.replaceState(null, '', window.location.pathname + window.location.search);
            }
            if (input.value === '') {
                document.querySelectorAll('[data-missing-token]').forEach(function (alert) {
                    alert.hidden = false;
                });
                submit.disabled = true;
                return;
            }
            form.addEventListener('submit', function () {
                submit.disabled = true;
            });
        });
    }

    /**
     * Sélecteur de langue : recharge la page dans la langue choisie, en conservant
     * le jeton du lien (fragment #) sur les pages d'ouverture et de destruction
     */
    function initLanguageSwitch() {
        document.querySelectorAll('[data-lang-switch]').forEach(function (form) {
            var select = form.querySelector('select');
            select.addEventListener('change', function () {
                var token = document.querySelector('[data-fragment-token]');
                var url = form.action + '?lang=' + encodeURIComponent(select.value);
                if (token && token.value !== '') {
                    url += '#' + token.value;
                }
                window.location.assign(url);
            });
        });
    }

    initLanguageSwitch();
    initCopyButtons();
    initPasswordToggles();
    initLocalTimes();
    initShareForm();
    initFragmentForms();
}());
