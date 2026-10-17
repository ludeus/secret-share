# Contribuer

Merci de votre intérêt pour Secret Share ! Les rapports de bogue et propositions passent par
les issues et pull requests GitHub. Pour une faille de sécurité, suivez [SECURITY.md](SECURITY.md).

## Préparer l'environnement

```bash
composer install
composer check    # standard de codage, traductions, analyse statique, tests
```

Le code suit PSR-12 ; les commentaires sont rédigés en français, les identifiants en anglais.

## Ajouter ou corriger une langue

Les textes de l'interface sont dans `lang/`, un fichier par langue, nommé d'après le code
ISO 639-1 de la langue (`es.php`, `zh.php`…). L'anglais (`lang/en.php`) est la référence :
tout texte absent d'une traduction s'y affiche en anglais.

1. Copiez `lang/en.php` vers `lang/<code>.php`.
2. Renseignez les trois clés d'identification :
   - `lang.name` : le nom de la langue dans cette langue (« Español », « 中文 »), affiché dans
     le sélecteur ;
   - `lang.flag` : l'émoji du drapeau affiché devant ce nom (« 🇪🇸 ») ;
   - `lang.dir` : `ltr`, ou `rtl` pour les langues écrites de droite à gauche (arabe, hébreu…).
3. Traduisez les autres textes en conservant les paramètres entre accolades (`{count}`,
   `{max}`), qui sont remplacés par des valeurs à l'affichage.
4. Vérifiez la traduction :

   ```bash
   composer translations    # ou : php bin/check-translations <code>
   ```

   L'outil signale les textes manquants, les clés inconnues et les paramètres différents de
   l'anglais. Une langue incomplète reste utilisable mais fait échouer les tests.

La nouvelle langue apparaît automatiquement dans le sélecteur : aucun code à modifier.

Les traductions autres que le français et l'anglais ont été produites sans relecture par des
locuteurs natifs : les corrections sont les bienvenues.

## Ajouter un texte

Ajoutez la clé dans `lang/en.php` et `lang/fr.php` au minimum, utilisez-la dans les gabarits
avec `$this->t('ma.cle')` (échappement inclus), puis lancez `composer translations` pour voir
les langues à compléter.
