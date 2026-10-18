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
    var activeMessages = null;

    function translateMessage(metadata, fallback) {
        var descriptor = JSON.parse(metadata);
        var message = activeMessages && activeMessages[descriptor[0]];
        if (typeof message !== 'string') {
            return fallback;
        }
        Object.keys(descriptor[1]).forEach(function (name) {
            message = message.split('{' + name + '}').join(String(descriptor[1][name]));
        });
        return message;
    }

    function applyTranslations(messages, locale) {
        activeMessages = messages;
        document.documentElement.lang = locale;
        document.documentElement.dir = messages['lang.dir'] === 'rtl' ? 'rtl' : 'ltr';
        document.querySelectorAll('[data-i18n]').forEach(function (element) {
            element.textContent = translateMessage(element.dataset.i18n, element.textContent);
        });
        document.querySelectorAll('*').forEach(function (element) {
            Array.from(element.attributes).forEach(function (attribute) {
                if (attribute.name.indexOf('data-i18n-') === 0) {
                    var target = attribute.name.slice('data-i18n-'.length);
                    element.setAttribute(target, translateMessage(attribute.value, element.getAttribute(target)));
                }
            });
        });
        initLocalTimes();
        document.dispatchEvent(new Event('languagechange'));
    }

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
            var labelNode = button.querySelector('[data-i18n]');
            var timer = null;
            document.addEventListener('languagechange', function () {
                if (labelNode) {
                    label = translateMessage(labelNode.dataset.i18n, label);
                    labelNode.textContent = label;
                }
                if (button.classList.contains('is-copied')) {
                    button.textContent = button.dataset.labelCopied || label;
                }
            });
            button.addEventListener('click', function () {
                copyText(target.value, target).then(function () {
                    var copied = button.dataset.labelCopied || label;
                    button.textContent = copied;
                    button.classList.add('is-copied');
                    announce(copied);
                    window.clearTimeout(timer);
                    timer = window.setTimeout(function () {
                        if (labelNode) {
                            button.replaceChildren(labelNode);
                        } else {
                            button.textContent = label;
                        }
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
            function updateLabel() {
                var visible = input.type === 'text';
                var label = visible ? button.dataset.labelHide : button.dataset.labelShow;
                button.title = label;
                if (hiddenLabel) {
                    hiddenLabel.textContent = label;
                    hiddenLabel.dataset.i18n = JSON.stringify([
                        visible ? 'action.hide_password' : 'action.show_password', {}
                    ]);
                }
            }
            button.addEventListener('click', function () {
                var visible = input.type === 'password';
                input.type = visible ? 'text' : 'password';
                button.setAttribute('aria-pressed', visible ? 'true' : 'false');
                updateLabel();
            });
            document.addEventListener('languagechange', updateLabel);
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

        function setError(field, name, message, messageKey) {
            var error = form.querySelector('[data-error-for="' + name + '"]');
            if (message) {
                field.setAttribute('aria-invalid', 'true');
                field.setAttribute('aria-describedby', name + '-error');
            } else {
                field.removeAttribute('aria-invalid');
            }
            if (error) {
                error.textContent = message || '';
                if (messageKey) {
                    error.dataset.i18n = JSON.stringify([messageKey, {max: maxLength}]);
                } else {
                    delete error.dataset.i18n;
                }
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
            setError(secret, 'secret', secretError, secretError
                ? (secret.value.trim() === '' ? 'error.secret_required' : 'error.secret_too_long') : null);
            if (secretError) {
                errors.push(secret);
            }

            var passwordError = Array.from(password.value).length > 1024 ? form.dataset.msgPasswordTooLong : '';
            setError(password, 'password', passwordError, passwordError ? 'error.password_too_long' : null);
            if (passwordError) {
                errors.push(password);
            }

            var shortUrlError = shortUrl.checked && !shortUrlAllowed() ? form.dataset.msgShortUrlForbidden : '';
            setError(shortUrl, 'short_url', shortUrlError, shortUrlError ? 'error.short_url_forbidden' : null);
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
     * Traduit l'interface en place : aucun rechargement ni renvoi de secret.
     */
    function initLanguageSwitch() {
        document.querySelectorAll('[data-lang-switch]').forEach(function (form) {
            var select = form.querySelector('select');
            form.hidden = false;
            form.addEventListener('submit', function (event) {
                event.preventDefault();
            });
            select.addEventListener('change', async function () {
                var previousLocale = document.documentElement.lang;
                var locale = select.value;
                select.disabled = true;
                try {
                    var response = await fetch('/translations?lang=' + encodeURIComponent(locale), {
                        credentials: 'same-origin',
                        cache: 'no-store'
                    });
                    if (!response.ok) {
                        throw new Error('Translation request failed');
                    }
                    var messages = await response.json();
                    applyTranslations(messages, locale);
                    var url = new URL(window.location.href);
                    if (url.searchParams.has('lang')) {
                        url.searchParams.set('lang', locale);
                        window.history.replaceState(window.history.state, '', url);
                    }
                } catch (error) {
                    select.value = previousLocale;
                    announce(document.querySelector('label[for="lang-select"]').textContent);
                } finally {
                    select.disabled = false;
                }
            });
        });
    }

    function initThemeSwitch() {
        var control = document.querySelector('[data-theme-switch]');
        if (!control) {
            return;
        }
        var trigger = control.querySelector('.theme-trigger');
        var options = control.querySelector('.theme-options');
        var choices = control.querySelectorAll('[data-theme-value]');
        var icons = trigger.querySelectorAll('[data-theme-icon]');
        var theme = document.documentElement.dataset.theme || 'auto';

        function updateSelection() {
            choices.forEach(function (choice) {
                var selected = choice.dataset.themeValue === theme;
                choice.setAttribute('aria-pressed', String(selected));
                if (selected) {
                    trigger.title = choice.textContent.trim();
                }
            });
            icons.forEach(function (icon) {
                icon.toggleAttribute('hidden', icon.dataset.themeIcon !== theme);
            });
        }

        function positionOptions() {
            if (options.hidden) {
                return;
            }
            options.style.transform = '';
            var bounds = options.getBoundingClientRect();
            var viewportWidth = document.documentElement.clientWidth;
            var offset = Math.max(8 - bounds.left,
                Math.min(0, viewportWidth - 8 - bounds.right));
            options.style.transform = 'translateX(' + offset + 'px)';
        }

        window.addEventListener('resize', positionOptions);

        function closeOptions(restoreFocus) {
            options.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            if (restoreFocus) {
                trigger.focus();
            }
        }

        trigger.addEventListener('click', function () {
            var opening = options.hidden;
            options.hidden = !opening;
            trigger.setAttribute('aria-expanded', String(opening));
            if (opening) {
                positionOptions();
                options.querySelector('[aria-pressed="true"]').focus();
            }
        });
        control.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeOptions(true);
            }
        });
        document.addEventListener('click', function (event) {
            if (!control.contains(event.target)) {
                closeOptions(false);
            }
        });
        control.addEventListener('focusout', function (event) {
            if (!control.contains(event.relatedTarget)) {
                closeOptions(false);
            }
        });
        choices.forEach(function (choice, index) {
            choice.addEventListener('keydown', function (event) {
                var next = index;
                if (event.key === 'ArrowDown') {
                    next = (index + 1) % choices.length;
                } else if (event.key === 'ArrowUp') {
                    next = (index + choices.length - 1) % choices.length;
                } else if (event.key === 'Home') {
                    next = 0;
                } else if (event.key === 'End') {
                    next = choices.length - 1;
                } else {
                    return;
                }
                event.preventDefault();
                choices[next].focus();
            });
            choice.addEventListener('click', function () {
                theme = choice.dataset.themeValue;
                if (theme === 'auto') {
                    delete document.documentElement.dataset.theme;
                } else {
                    document.documentElement.dataset.theme = theme;
                }
                updateSelection();
                try {
                    if (theme === 'auto') {
                        window.localStorage.removeItem('secret-share-theme');
                    } else {
                        window.localStorage.setItem('secret-share-theme', theme);
                    }
                } catch (error) {
                    // Le choix reste actif même si le stockage est bloqué.
                }
                closeOptions(true);
            });
        });
        updateSelection();
        document.addEventListener('languagechange', updateSelection);
        control.hidden = false;
    }

    initThemeSwitch();
    initLanguageSwitch();
    initCopyButtons();
    initPasswordToggles();
    initLocalTimes();
    initShareForm();
    initFragmentForms();
}());
