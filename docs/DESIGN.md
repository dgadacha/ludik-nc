# Ludik.nc - règles de design

Ce document décrit le système visuel du thème `ludik` (thème enfant de Hummingbird).
Il est la référence pour toute nouvelle page : on n'invente pas de style local, on
réutilise les jetons et les composants décrits ici.

Tout se trouve dans un seul fichier, `theme/ludik/assets/css/custom.css`, organisé en
sept sections numérotées : polices, jetons, socle, primitives, composants, pages,
utilitaires. Chaque nouvelle règle va dans la section correspondante - et nulle part
ailleurs. Les rustines ajoutées en fin de fichier finissent toujours par contredire
une section : quand une règle doit changer, on modifie la section qui la porte.

## 1. Jetons

Aucune couleur, taille ou espacement en dur dans le code : on passe toujours par une
variable CSS. Elles sont définies sur `:root`.

### Couleurs

| Rôle | Variable | Valeur |
|---|---|---|
| Vert pétrole, surfaces sombres | `--lud-green-950`, `--lud-green-900`, `--lud-green-800` | `#022825`, `#033330`, `#003d3a` |
| Vert pétrole, action et texte | `--lud-green-700`, `--lud-green-600` | `#005f57`, `#007a70` |
| Vert pétrole, fonds doux | `--lud-green-50`, `--lud-green-100` | `#e8f5f2`, `#d3ebe6` |
| Jaune Ludik, appel à l'action | `--lud-gold-500` (fond), `--lud-gold-600` (survol) | `#ffbf18`, `#f2a900` |
| Jaune Ludik, fonds doux | `--lud-gold-100`, `--lud-gold-200` | `#fff8e5`, `#ffedbe` |
| Papier | `--lud-paper` | `#faf9f5` |
| Surfaces | `--lud-surface`, `--lud-surface-alt` | `#ffffff`, `#f7f8f5` |
| Filets | `--lud-line`, `--lud-line-strong` | `#e9ede9`, `#d0d8d2` |
| Textes | `--lud-ink`, `--lud-ink-soft`, `--lud-ink-mute` | `#102f2e`, `#667876`, `#8a9997` |
| Textes sur fond sombre | `--lud-on-dark`, `--lud-on-dark-soft`, `--lud-on-dark-mute` | `#f2f8f6`, `#a8c8c3`, `#7e9c98` |
| États | `--lud-stock`, `--lud-warn`, `--lud-alert`, `--lud-promo` | `#19a85b`, `#b07d1a`, `#b3402c`, `#e2622a` |

Règles d'emploi :

- **le vert pétrole porte l'identité et l'action courante** : bouton de recherche,
  bouton « Tous les rayons », prix, ajout au panier des grilles, liens, pied de page ;
- **le jaune porte l'appel à l'action éditorial** : bouton des campagnes du hero,
  bouton d'ajout au panier de la fiche produit, badge de quantité du panier,
  soulignement de l'onglet actif. Il ne sert jamais de fond de bloc large ;
- **l'orange `--lud-promo`** ne sert qu'aux promotions ;
- les fonds de page sont toujours `--lud-paper`, les blocs toujours `--lud-surface` ;
- **les surfaces ne portent pas de filet.** Une carte se détache par son ombre,
  pas par un contour d'un pixel : le filet donnait un rendu de tableau.
  `--lud-line` ne sert plus qu'aux séparateurs internes (une ligne entre deux
  blocs d'une même carte) et aux champs de formulaire, où la limite du champ
  doit rester lisible ;
- **au survol, une carte se soulève** (`translateY(-4px)` et ombre `lg`) ; elle
  ne change ni de fond ni de couleur de bordure ;
- l'en-tête et la navigation sont **clairs** ; le vert sombre est réservé au hero,
  aux campagnes et au pied de page.

### Accents d'univers

Chaque rayon a une teinte propre, et **elle ne sert qu'à deux choses** : la
couleur de son pictogramme et celle de son nombre de produits. Jamais un fond de
carte, jamais une bordure, jamais un survol.

Ces teintes sont volontairement désaturées. À pleine saturation, six couleurs
vives sur la même rangée tiraient l'œil plus que les produits eux-mêmes.

Hiérarchie d'ensemble, du plus au moins présent : blanc cassé `#faf9f5` pour le
fond, blanc pour les cartes, vert foncé `#003d3a` pour l'identité (hero, pied de
page, gros blocs), vert principal `#007a70` pour les liens et les actions
secondaires, jaune `#ffbf18` pour les seules actions importantes, puis l'accent
du rayon.

| Rayon | Accent | Fond |
|---|---|---|
| Jeux de société | `--lud-uni-societe` `#a9682f` | `--lud-uni-societe-bg` |
| Cartes à collectionner | `--lud-uni-tcg` `#40718c` | `--lud-uni-tcg-bg` |
| Librairie, mangas | `--lud-uni-manga` `#9c6079` | `--lud-uni-manga-bg` |
| Jeux de rôle | `--lud-uni-jdr` `#6b5f8c` | `--lud-uni-jdr-bg` |
| BD | `--lud-uni-bd` `#4a6a8c` | `--lud-uni-bd-bg` |
| Loisirs créatifs, accessoires | `--lud-uni-creatif` `#4a8069` | `--lud-uni-creatif-bg` |

Les cartes de rayon et les raccourcis lisent l'accent dans deux variables locales,
`--u-accent` / `--u-fond` et `--t-accent` / `--t-fond`, posées par une classe
`.u-<rayon>` ou `.ludik-tile--<rayon>`. Pour ajouter un rayon : une paire de jetons,
une ligne de classe, rien d'autre.

### Typographie

| Usage | Variable | Police |
|---|---|---|
| Texte courant | `--lud-font` | Inter (fournie par Hummingbird) |
| Titres, libellés forts, prix | `--lud-font-title` | Outfit |
| Accroches manuscrites | `--lud-font-script` | Caveat |

Échelle : `--lud-text-xs` 13 px, `-sm` 14 px (plancher), `-base` 16 px,
`-md` 17 px, `-lg` 20 px, puis `--lud-title-sm` 22 px, `--lud-title-md` 34 px,
`--lud-title-lg` 38 px, `--lud-title-xl` 48 px (titres de campagne).

Les balises de titre portent leur taille par défaut : `h1` 28→38 px,
`h2` 28→34 px, `h3` 22 px. Pas besoin de la redéclarer page par page.

La police manuscrite ne sert qu'à deux endroits : l'accroche de l'en-tête et celle du
pied de page. Elle n'est jamais utilisée pour du texte à lire.

### Trame, rayons, ombres

Espacements en multiples de 4 px : `--lud-s1` (4 px) à `--lud-s16` (64 px).

Rayons : `--lud-r-sm` 8 px (champs, cases), `--lud-r-md` 14 px (boutons,
vignettes, filtres), `--lud-r-lg` 18 px (blocs de section), `--lud-r-xl` 24 px
(campagnes, fiche produit), `--lud-r-pill` (pastilles).

Ombres : `--lud-shadow-sm` au repos, `--lud-shadow-md` au survol, `--lud-shadow-lg`
pour les éléments flottants (menu déroulant, suggestions, carte survolée).

Espace entre deux grandes sections : `--lud-section`, de 40 px sur téléphone
à 68 px sur grand écran.

**Une seule source pour le rythme vertical de l'accueil.** Le thème parent
fait de `.page-content--home` une grille avec son propre `gap` ; c'est lui
qu'on règle sur `--lud-section`. Les blocs eux-mêmes n'ont pas de marge
verticale : quand ils en avaient, elle s'ajoutait au `gap` et l'écart réel
atteignait 100 px, ce qui donnait des sections flottant séparément. À
l'intérieur de `ludikhome`, les sections sont séparées par
`.ludik-home > * + *`.

Gabarit : `--lud-page` = 1360 px et `--lud-gutter` = `clamp(20px, 3vw, 48px)`,
appliqués à `.container`, `.container-md` et à tous les blocs pleine largeur. Un bloc
qui se cale lui-même doit reprendre ces deux jetons, sinon il se désaligne du reste.

Transitions : `--lud-ease` (160 ms). Il est ramené à 1 ms sous
`prefers-reduced-motion`, donc une transition qui l'utilise n'a besoin d'aucune
précaution supplémentaire.

## 2. Composants

Chaque composant a un préfixe et un seul endroit où il est stylé.

| Composant | Classe racine | Où |
|---|---|---|
| En-tête (identité, recherche, compte) | `.ludik-head` | `templates/_partials/header.tpl` |
| Suggestions de recherche | `.ps-searchbar__dropdown` | `modules/ps_searchbar/ps_searchbar.tpl` |
| Navigation des rayons | `.ludik-nav` | `modules/ps_mainmenu/ps_mainmenu.tpl` |
| Fil d'Ariane | `.breadcrumb` | `_partials/breadcrumb.tpl` (parent) |
| Hero de campagnes | `.ludik-hero` | `modules/ps_imageslider/.../slider.tpl` |
| Cartes de rayon | `.ludik-univers` | `ludikhome/.../home.tpl` |
| Rangée de produits défilante | `.lud-carrousel` | `templates/components/module-products.tpl` |
| Vignette produit | `.product-miniature` | `catalog/_partials/miniatures/product.tpl` |
| Bouton favori | `.product-miniature__favori` | `.../miniatures/product.tpl` (module `blockwishlist`) |
| Gabarit de liste | `.ludik-listing`, `.ludik-aside` | `templates/layouts/layout-left-column.tpl` |
| Sections de filtre | `.lud-facet` | `ps_categorytree/...`, `ps_facetedsearch/...` |
| Barre d'outils de liste | `.products-top` | `catalog/_partials/products-top.tpl` |
| En-tête de rayon | `.category__header` | `catalog/_partials/category-header.tpl` |
| Fiche produit | `.product__container`, `.product__heading` | `catalog/product.tpl` |
| Fiche technique | `.lud-specs` | `catalog/_partials/product-details.tpl` |
| Réassurance de fiche | `.ludik-trust-product` | `_partials/ludik-trust-product.tpl` |
| Barre de services | `.ludik-trust` | `ludikhome/.../footer_before.tpl` |
| Bannière soirée du mardi | `.ludik-event` | `ludikhome/.../footer_before.tpl` |
| Cartes boutique | `.ludik-shops`, `.ludik-shop` | `ludikhome/.../footer_before.tpl` |
| Pied de page | `.footer__main`, `.ludik-foot` | `_partials/footer.tpl` |
| Écran vide | `.lud-empty` | `templates/errors/not-found.tpl` |

### Primitives réutilisables

- `.lud-btn` : bouton. Jaune par défaut (action principale), `--ghost` pour l'action
  secondaire (fond blanc, filet vert, texte vert), `--dark` pour le vert plein.
  Tailles `--sm`, `--lg`, `--block`, `--icon`. Zone tactile de 44 px sur mobile.
- `.lud-btn-square` : bouton carré d'ajout au panier des grilles.
- `.lud-card` : surface blanche, filet, rayon `lg`, ombre `sm`. Variantes `--pad`,
  `--flat`, `--soft`.
- `.lud-title` : titre de bloc avec barre jaune verticale. C'est **le** marqueur de
  section : description de produit, fiche technique, produits similaires, avis.
- `.lud-section-head` : titre + sous-titre à gauche, lien à droite. Sert aux sections
  de l'accueil et aux blocs de produits.
- `.lud-tag` : étiquette de rayon (petites capitales, barre jaune).
- `.lud-stock` : point coloré + libellé d'état.
- `.lud-badge` : pastille (`--new`, `--promo`, `--out`).
- `.lud-chip` / `.lud-chips` : raccourci de filtre, sous un titre de recherche.
- `.lud-specs` : table à deux colonnes intitulé / valeur.
- `.lud-facet` : section de filtre dépliante.
- `.lud-i` : icône Lucide, via `<svg class="lud-i"><use href="#i-nom"></use></svg>`.
  Tailles `--sm`, `--lg`, `--xl`, `--2xl`.

### Icônes

Jeu **Lucide**, embarqué en sprite dans `theme/ludik/assets/img/lucide.svg` et inséré
dans chaque page par `_partials/lucide-sprite.tpl` (les références externes dans `<use>`
ne sont pas fiables selon les navigateurs). Les trois logos de réseaux sociaux sont
tracés à la main : Lucide les a retirés de son jeu.

Pour en ajouter une : récupérer le SVG Lucide, l'ajouter comme `<symbol id="i-nom">`
dans `lucide.svg`, puis reporter le fichier dans `_partials/lucide-sprite.tpl`. Les
deux fichiers doivent rester identiques.

## 3. Règles de composition

1. **Une page = des blocs sur fond papier.** Le contenu structurant vit dans une
   surface blanche à filet et rayon. Les grilles produits, elles, respirent
   directement sur le fond : un cadre autour d'une grille de cartes fait un cadre
   de trop.
2. **Un seul niveau de titre par bloc.** Le titre de page est en `h1` 800, les titres
   de section portent la barre jaune, les intertitres éditoriaux sont en vert 700.
3. **Les cartes réagissent au survol de la même façon** : remontée de 4 px, ombre qui
   prend l'accent, ombre `lg`. Jamais de changement de fond, jamais de décalage de la
   mise en page.
4. **Un seul appel à l'action par bloc.** Jaune pour l'action éditoriale, vert pour
   l'action courante, `.lud-btn--ghost` pour le secondaire.
5. **Les grilles produits** passent par `.products`, jamais par une grille locale.
   Sur une page de rayon, c'est une grille : quatre colonnes au plus en présence
   de la colonne de filtres. Dans une section d'accueil, c'est une **bande
   défilante** : cinq cartes visibles, dix proposées, deux flèches qui avancent
   d'une page et se masquent en bout de course.
6. **Les états de stock** utilisent trois mots et trois seulement : « En stock »,
   « Sur commande », « Épuisé », toujours doublés d'un point coloré - jamais la
   couleur seule.
7. **Les prix** sont en Outfit 800, sans décimale, suivis de `F` (franc pacifique).
8. **Pas d'animation** au-delà de `--lud-ease`, et rien du tout si le visiteur a
   demandé à réduire les animations.
9. **La colonne de rayon ne dépasse jamais la grille.** Douze sous-rayons visibles,
   le reste replié. Sous 1200 px, elle devient un tiroir.

## 4. Points de rupture

| Palier | Largeur | Effet |
|---|---|---|
| Mobile | < 768 px | grille produits sur 2 colonnes, raccourcis en défilement, en-tête empilé, flèches du hero masquées, filtres en tiroir |
| Tablette | 768–1199 px | grille sur 3 colonnes, recherche sur sa propre ligne, libellés de compte masqués, filtres en tiroir |
| Portable | 1200–1399 px | colonne de rayon visible, grille sur 4 colonnes |
| Bureau | ≥ 1400 px | gabarit de référence, grille sur 5 colonnes en pleine largeur |

## 5. Ce qui reste au thème parent

Hummingbird fournit la structure de page, les formulaires, le tunnel et les scripts.
On ne remplace un gabarit parent que lorsque le rendu s'écarte des maquettes, et la
surcharge garde alors **les noms de blocs Smarty, les classes `js-*` et les attributs
`data-*` d'origine**, sans quoi le comportement JavaScript disparaît sans erreur
visible. Deux pièges rencontrés :

- surcharger le gabarit du **module** au lieu de celui du **thème parent** : le
  parent réécrit souvent le balisage et son JavaScript ne reconnaît plus le nôtre.
  C'est ce qui avait désactivé le curseur de prix des facettes ;
- ne pas donner de `z-index` à `#wrapper` : cela en ferait un contexte d'empilement,
  et les tiroirs qui vivent à l'intérieur ne pourraient plus passer devant l'en-tête.

Un comportement du parent se corrige au besoin depuis `custom.js`, sans toucher
à son script compilé. Les **suggestions de recherche** en sont l'exemple : le
nombre demandé (dix) est figé dans le script du parent, et les dix lignes
débordaient du panneau. `custom.js` en garde cinq et démasque le lien
« Voir tous les résultats » du gabarit, qui renvoie vers la page de résultats.
Le lien vit à côté de la liste, pas dedans : le parent vide la liste à chaque
frappe. Et l'observateur se met en sourdine le temps du retrait, sinon la passe
suivante ne voit plus que cinq lignes et croit qu'il n'y a plus rien à montrer.

## 6. Travailler sur le thème

L'URL de `custom.css` ne porte pas de numéro de version, et PrestaShop combine les
feuilles en un fichier dont le nom ne change pas quand le contenu change. Deux
conséquences pendant le développement :

```bash
docker compose exec prestashop php /scripts/dev-ccc.php off
```

coupe la combinaison CSS/JS, et

```bash
docker compose exec prestashop sh /scripts/cc.sh
```

vide le cache Smarty **et** le cache des feuilles combinées. Le navigateur peut encore
servir son propre cache : `scripts/dev-recharge.js` recharge la feuille avec un
paramètre d'horodatage. Avant la mise en production, remettre la combinaison :

```bash
docker compose exec prestashop php /scripts/dev-ccc.php on
```

## 7. Ordre d'empilement

Un seul contexte d'empilement dans la page : `#header`. Tout ce qui doit
flotter au-dessus du contenu vit dedans, ou reçoit un z-index positif sans que
son parent en crée un.

| Calque | Valeur | Quoi |
|---|---|---|
| Fond | `0` | motif en filigrane du corps |
| Contenu | `auto` | `#wrapper`, `#footer` |
| Voile des tiroirs | `1030` | passe **sous** l'en-tête : la page s'assombrit, l'en-tête reste net |
| En-tête | `1050` | et à l'intérieur : panneau de sous-rayons `10`, suggestions de recherche `40`, tiroir des catégories `1045` |
| Tiroir de filtres | `1060` | dans la page, sous 1200 px seulement |
| Voile des boîtes de dialogue | `1140` | |
| Boîtes de dialogue | `1150` | y compris celles du module favoris, qui les pose à `1051`-`1053` (piège 5) |
| Messages temporaires | `1200` | confirmation d'ajout au panier |

Cinq pièges rencontrés sur ce thème, à ne pas réintroduire :

1. **Un z-index sur `#wrapper`** en fait un contexte d'empilement : le tiroir
   de filtres, qui vit à l'intérieur, ne peut plus passer devant l'en-tête.
2. **Un z-index sur les rangées de l'en-tête** (`.ludik-head`,
   `.ludik-nav`) : chacune devient un contexte, et le tiroir des
   catégories, qui vit dans la barre de navigation, se retrouve coincé sous les
   rangées du dessus. Elles ne se recouvrent pas, elles n'en ont pas besoin.
3. **Un `overflow` sur un conteneur dont un descendant est en position
   absolue** : le descendant est rogné. C'est ce qui faisait disparaître le
   panneau de sous-rayons, et cela ressemble à un problème d'empilement sans en
   être un.
4. **Un z-index appliqué hors de son palier responsive** : la colonne de rayon
   garde la classe `offcanvas-xl` au-delà de 1200 px alors qu'elle n'est plus
   un tiroir ; lui laisser le z-index du tiroir la faisait passer par-dessus
   l'en-tête.
5. **Une échelle relevée sans les modules qui portent la leur.** Le voile des
   boîtes de dialogue est monté de `1050` à `1140` pour passer devant
   l'en-tête ; `blockwishlist` cale les siennes sur l'échelle d'origine de
   Bootstrap (`1051`-`1053`). Résultat : la fenêtre s'ouvrait **sous son
   propre voile**, l'écran s'assombrissait et plus rien n'était cliquable.
   Ses feuilles sont compilées dans ses paquets JavaScript et injectées dans
   `<head>` à l'exécution, donc après la nôtre, avec un sélecteur par fenêtre
   au moins aussi spécifique que le nôtre
   (`.wishlist-login .wishlist-modal.show`, et pour le renommage
   `.wishlist-rename .wishlist-modal.show[data-v-02ac532b]`) : à spécificité
   égale, la dernière déclarée gagne, jamais la nôtre. D'où le seul autre
   `!important` du thème, sur `.wishlist-modal.show`. Quand l'échelle bouge,
   vérifier toutes les fenêtres des modules, pas seulement celles du thème.

Pour contrôler : ouvrir chaque élément flottant et vérifier avec
`document.elementFromPoint` au centre de sa surface que c'est bien lui qui
reçoit le pointeur. Un z-index correct sur le papier ne prouve rien.

## 8. Traductions

Le catalogue français de PrestaShop ne couvre pas quelques chaînes récentes de
Hummingbird : le lien d'évitement, les étiquettes de prix pour lecteurs
d'écran, les contrôles de diaporama. Les compléments sont dans
`theme/ludik/translations/fr-FR/*.xlf`, un fichier par domaine.

C'est la bonne place : un fichier de thème survit aux mises à jour du cœur, là
où une ligne ajoutée dans `ps_translation` n'est pas prise en compte et où une
retouche des fichiers de `translations/` du cœur serait écrasée.

L'identifiant d'une unité est le `md5` de la chaîne source. Pour en ajouter
une, reprendre la chaîne exactement comme elle apparaît dans le gabarit,
espaces compris : `Price: ` porte une espace finale.

## 9. Pastille « Nouveau »

Elle est masquée volontairement. Les 32 939 produits ont été importés le même
jour et le site d'origine ne fournissait pas de date de mise en rayon : la
pastille se serait affichée sur tout le catalogue. Elle redeviendra utile dès
que la boutique ajoutera des produits avec de vraies dates ; il suffira alors
de retirer la règle qui la masque, section 5.6 de `custom.css`.

## 10. Prix

Le franc pacifique se compose sans décimale, avec une espace fine insécable
entre les milliers : `34 000 F`. La devise est configurée ainsi, le formateur
de PrestaShop s'en charge.

Deux corrections restent à la charge du thème, parce que les données de locale
de PrestaShop sont en lecture seule : ni la devise ni la langue ne permettent
de les changer en back-office.

**Le séparateur de milliers.** Le CLDR français emploie l'espace fine
insécable `U+202F`, qui mesure deux pixels à la taille du corps de texte : le
prix se lisait `4300 F`. Le module `ludikhome` la remplace par l'espace
insécable ordinaire `U+00A0` dans le hook `actionOutputHTMLBefore`, seul point
par lequel passe tout le HTML d'une page. Les fragments rechargés en Ajax (la
grille filtrée, le panier) ne passent pas par ce hook : `custom.js` fait le
même remplacement sur les événements `updateProductList` et `updatedCart`.

Un `word-spacing` en CSS ne suffisait pas : les navigateurs ne traitent pas
`U+202F` comme une espace de séparation de mots.

**Le curseur de prix des facettes** est formaté par le navigateur à partir du
motif CLDR du XPF, qui porte deux décimales : il affichait
`419.00F - 15513.00F`. `custom.js` reformate le libellé et surveille ses
réécritures.
