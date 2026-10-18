/*
 * Secret Share - thème appliqué avant le premier affichage.
 * @license AGPL-3.0-or-later
 */
(function () {
    'use strict';
    try {
        var theme = window.localStorage.getItem('secret-share-theme');
        if (theme === 'light' || theme === 'dark') {
            document.documentElement.dataset.theme = theme;
        }
    } catch (error) {
        // Repli sur les préférences système si le stockage est bloqué.
    }
}());
