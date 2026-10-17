# Politique de sécurité

## Versions maintenues

Seule la dernière version publiée reçoit des correctifs de sécurité.

## Signaler une vulnérabilité

Merci de **ne pas ouvrir d'issue publique**. Utilisez le signalement privé de GitHub
(onglet *Security* → *Report a vulnerability*) en précisant :

- la version ou le commit concerné ;
- la description du problème et son impact ;
- les étapes de reproduction ou une preuve de concept.

Un accusé de réception est envoyé sous 7 jours. Le correctif est publié dès que possible,
puis la vulnérabilité est rendue publique avec mention de son auteur, sauf demande contraire.

## Périmètre

Le modèle de sécurité est décrit dans le [README](README.md#fonctionnement-et-sécurité).
Sont notamment dans le périmètre : la lecture d'un secret sans le lien complet, le contournement
de l'usage unique, de l'expiration ou de la limite d'essais de mot de passe, toute fuite d'un
secret ou d'un jeton (journaux, en-têtes, cache), les failles XSS, CSRF ou d'injection.

Hors périmètre : un serveur ou un `APP_SECRET` compromis, une instance servie sans HTTPS,
les attaques par déni de service volumétrique.
