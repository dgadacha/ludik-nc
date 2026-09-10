# Audit des pages de la boutique

Relevé effectué le 10 septembre 2026 sur http://localhost:8801, PrestaShop 9.1.5,
thème enfant `ludik`. Rendu vérifié à 1440 px et à 390 px de large.

Le thème a été modifié pendant l'audit (custom.css et product.tpl à 16h48 / 16h49).
Tous les constats ci-dessous ont été revérifiés après cette modification, sur une
nouvelle récupération de chaque page.

Gravité utilisée : **bloquant** (à corriger avant de montrer au client),
**gênant** (visible, dégrade la qualité perçue), **détail** (finition).

---

## 1. Constats communs à toutes les pages

Ces éléments viennent de l'en-tête, du pied de page ou du thème : ils se répètent
sur les 24 pages parcourues.

### 1.1 Menu déroulant : tout l'arbre des rayons dans le HTML de chaque page

`div.ps-mainmenu.ps-mainmenu--desktop` (en-tête) sérialise les 3 965 catégories
dans la page, sur toutes les pages.

Mesures sur l'accueil : 10 285 nœuds DOM au total dont **9 195 dans le menu**
(89 %), **3 083 liens**, **917 Ko de HTML sur 1 054 Ko**.

Gravité : **bloquant** (poids de page, temps de rendu, dilution SEO).

### 1.2 Icône Facebook cassée dans le pied de page

`templates/_partials/footer.tpl:18` référence `<use href="#i-facebook">`, mais le
symbole `i-facebook` n'existe pas dans `templates/_partials/lucide-sprite.tpl`
(37 symboles définis, aucun Facebook). `svg.getBBox()` retourne vide.

Rendu : un cercle gris vide de 35x35 px (`.ludik-foot__social a`,
fond `rgba(255,255,255,.1)`) sous la signature du pied de page, sur toutes les pages.

Gravité : **bloquant** (élément visiblement cassé, partout).

### 1.3 Trou dans la grille du pied de page

`.ludik-foot__cols` est une grille de 4 colonnes de 222,88 px pour **5 enfants**.
Le cinquième bloc (`.ps-customeraccountlinks`, « Votre compte ») passe à la ligne
suivante : rangée 1 à y=1448 hauteur 294, rangée 2 à y=1806 hauteur 135.

Résultat : environ 64 px d'écart vertical plus trois cellules vides de 223x135 px,
soit un vide sombre d'environ 670x135 px à droite de « Votre compte ». Uniquement
en desktop, le mobile empile correctement les 5 blocs.

Gravité : **gênant**.

### 1.4 Ordre des colonnes du pied de page non déterministe

Le bloc `ps_contactinfo` (« Coordonnées ») apparaît tantôt en premier, tantôt en
dernier, **pour une même URL rechargée**. Reproduit sur `/panier?action=show`,
`/nous-contacter`, `/content/4-a-propos`, `/magasins` et la 404 en deux passes de
`curl` consécutives :

```
passe 1  /panier             La boutique | Commander | Informations | Coordonnées
passe 2  /panier             Coordonnées | La boutique | Commander | Informations
```

Gravité : **gênant** (le pied de page se réorganise d'une page à l'autre).

### 1.5 Textes restés en anglais (en-tête et pied de page)

| Chaîne exacte | Emplacement | Pages |
|---|---|---|
| `Skip to main content` | `a.visually-hidden-focusable.skip-link`, premier élément du body | 24 / 24 |
| `Toggle la boutique links` | `span.visually-hidden` dans `.footer-block__title button` | 24 / 24 |
| `Toggle commander links` | idem | 24 / 24 |
| `Toggle informations links` | idem | 24 / 24 |
| `Toggle your account links` | idem (bloc `ps_customeraccountlinks`) | 24 / 24 |
| `Main navigation` | `aria-label` de `nav.ps-mainmenu` | 24 / 24 |
| `Open mobile menu` | `aria-label` du bouton hamburger | 24 / 24 |
| `Go back to main menu` | `aria-label`, menu mobile | 24 / 24 |
| `Close search` | `aria-label` du bouton de fermeture de la recherche | 24 / 24 |
| `Open Jeux de société submenu` (et les 5 autres rayons) | `aria-label` de `button.menu__toggle-child` | 24 / 24 |
| `Subcategories for Magic The Gathering` (et 4 autres) | `aria-label`, arbre de catégories | 6 pages |

Les quatre `Toggle ... links` sont `display:none` en desktop (classe `d-md-none`) :
ils ne sont exposés qu'aux lecteurs d'écran et en mobile.

Gravité : **gênant** (accessibilité et cohérence linguistique).

### 1.6 Bandeau de pastilles `nav.ludik-tiles` : libellés collés

Les 14 pastilles utilisent un `<br>` codé en dur sans espace autour :
`<span class="ludik-tile__label">Jeux de<br>société</span>`.

Le texte accessible et le texte extrait valent donc `Jeux desociété`,
`Jeux de cartesà collectionner`, `Accessoiresjeux et JCC`, `Jeuxde rôle`,
`Librairiejeunesse`, `Premiersromans`, `Romansado`, `La BDfamille`,
`La BDspécialisée`, `Loisirscréatifs`.

Visuellement le rendu est correct, mais le nom accessible du lien et le texte
indexé sont faux.

Gravité : **gênant**.

### 1.7 Bandeau de pastilles : navigation incohérente

`nav.ludik-tiles` (14 pastilles) est placé juste sous la barre de navigation
(6 rayons) et **répète 5 de ces 6 rayons** : Jeux de société, Jeux de cartes à
collectionner, Accessoires jeux et JCC, Jeux de rôle, Loisirs créatifs.

Les 9 autres pastilles sont des sous-rayons de Librairie (Librairie jeunesse,
Premiers romans, Romans ado, Romans, La BD famille, La BD spécialisée, Comics,
Mangas, Webtoon), alors que « Librairie » lui-même n'y figure pas. Deux niveaux de
hiérarchie sont mélangés dans la même rangée.

Gravité : **gênant**.

### 1.8 Libellés de rayons non alignés entre les trois navigations

| Rayon | Barre de navigation | Pastilles | Carte d'accueil |
|---|---|---|---|
| Accessoires | `Accessoires Jeux & JCC` | `Accessoires jeux et JCC` | `Accessoires Jeux & JCC` |
| Jeux de rôle | `Jeux de rôle` | `Jeux de rôle` | `Jeux de Rôle` |

Gravité : **détail**.

### 1.9 Couleurs hors palette

`nav.ludik-tiles` fait tourner sept couleurs d'accent sur les icônes :
`#2f7fb5` (bleu), `#cf4a3f` (rouge), `#35915f` (vert), `#cc4f86` (rose),
`#7a5bb8` (violet), `#dd9f05` (or), `#1d8079` (teal). La couleur n'est pas liée au
rayon : elle est attribuée dans l'ordre des pastilles.

La charte annoncée est « palette verte et or » (README). Bleu, rouge, rose et
violet sont hors palette.

Gravité : **détail** (décision de direction artistique à trancher).

### 1.10 Mention PrestaShop dans le pied de page

`© 2026 - Logiciel e-commerce par PrestaShop™`, barre basse du pied de page,
sur toutes les pages.

Gravité : **détail** (élément par défaut à retirer).

### 1.11 Balises `<title>` hétérogènes

Seuls l'accueil, les catégories et les pages CMS portent le suffixe `- Ludik.nc`.

| URL | `<title>` |
|---|---|
| `/` | `Ludik.nc` |
| `/4-jeux-de-societe` | `Jeux de société - Ludik.nc` |
| `/content/4-a-propos` | `Qui sommes-nous ? - Ludik.nc` |
| `/connexion` | `Identifiant` (libellé PrestaShop par défaut) |
| `/inscription` | `Inscription` |
| `/recuperation-mot-de-passe` | `Mot de passe oublié` |
| `/panier?action=show` | `Panier` |
| `/nous-contacter` | `Contactez-nous` |
| `/magasins` | `Magasins` |
| `/marques` | `Marques` |
| `/promotions` | `Promotions` |
| `/meilleures%20ventes` | `Meilleures ventes` |
| `/nouveaux-produits` | `Nouveaux produits` |
| `/recherche?s=pokemon` | `Rechercher` |
| page inexistante | `Erreur 404` |

Gravité : **gênant** (SEO et cohérence).

### 1.12 Polices embarquées non utilisées

`theme/ludik/assets/fonts/` contient `material-symbols-outlined.woff2` (322 Ko) et
`Vazirmatn-Regular` / `Vazirmatn-Bold` (101 Ko) qui ne sont déclarés par aucune
règle `@font-face` du CSS compilé. Les `@font-face` réellement émis sont Inter,
Material Icons, Outfit et Caveat.

Gravité : **détail**.

### 1.13 jQuery Migrate en production

`JQMIGRATE: Migrate is installed, version 3.5.2` est écrit dans la console sur
chaque page (une fois par chargement).

Gravité : **détail**.

### 1.14 Placement du titre H1 variable

| Page | H1 |
|---|---|
| `/content/*`, `/magasins`, `/plan-site` | `h1.page-title-section` **au-dessus** de la carte blanche (H1 à y=359, `.page-content` commence à y=418) |
| `/nous-contacter` | H1 **dans** la carte, en tête du formulaire |
| `/4-jeux-de-societe` | H1 **dans** la carte des sous-rayons |
| `/1389-...html` | `h1.product__name.h2`, en majuscules, dans la carte produit |
| page 404 | `h1.h2` (pas `page-title-section`), sous un gros « 404 » qui est un `<p>` |
| `/` | H1 sans classe (bandeau) |

Gravité : **gênant**.

---

## 2. Accueil `/`

### 2.1 Bandeau du carrousel : titre en double et illisible

`#ps_imageslider .carousel-item.active` ne contient qu'une `<img>` et une
`<figcaption class="ps-imageslider__figcaption carousel-caption d-none d-lg-block fs-5">`
contenant `<h2 class="h1 text-uppercase">Jeux de cartes à collectionner</h2>`.

Deux problèmes cumulés :

1. Le titre, la description et le bouton « Découvrir le rayon » visibles à gauche
   sont **incrustés dans le JPG** (`modules/ps_imageslider/images/ludik-cartes-a-collectionner.jpg`).
   Le `<h2>` HTML affiche donc **une deuxième fois** le même titre.
2. Le `<h2>` hérite du fond Bootstrap par défaut `rgba(33, 37, 41, 0.5)` mais sa
   couleur de texte est `rgb(18, 48, 51)` : **contraste calculé 1,10** pour 32 px
   (3,0 requis).

Rendu : un rectangle gris translucide au milieu du bandeau, avec un texte
majuscule à peine perceptible qui chevauche la photo.

Sélecteurs : `#ps_imageslider .ps-imageslider__figcaption`, `#ps_imageslider .carousel-caption h2`.

Gravité : **bloquant** (c'est le premier élément vu sur le site).

### 2.2 Carrousel : chaînes anglaises et légendes masquées

- `aria-label="Go to slide 1"`, `"Go to slide 2"`, `"Go to slide 3"` sur
  `.carousel-indicators button`. Anglais.
- `aria-label="Conteneur carrousel"` sur `.carousel-inner` : formulation calquée.
- `figcaption` porte `d-none d-lg-block` : les légendes disparaissent sous 992 px.
  En mobile le carrousel n'est plus qu'une suite de trois images.

Gravité : **gênant** pour l'anglais, **détail** pour les légendes.

### 2.3 « Nos rayons » : compteurs illisibles et incohérents

Les 6 cartes de `.ludik-home` ont deux variantes de couleur pour la ligne
`.u-count` :

| Carte | Couleur | Contraste sur blanc (13 px, gras) |
|---|---|---|
| Jeux de société, Librairie, Accessoires Jeux & JCC | `rgb(22, 106, 103)` | 6,38 (conforme) |
| Jeux de cartes à collectionner, Jeux de Rôle, Loisirs Créatifs (`a.u-lagoon`) | `rgb(221, 159, 5)` | **2,32** (4,5 requis) |

Trois compteurs sur six sont donc en or peu lisible, et la grille alterne deux
couleurs sans logique apparente.

Sélecteur : `.ludik-home a.u-lagoon .u-count`.

Gravité : **gênant**.

### 2.4 « Nouveautés » : dernière rangée bancale

Grille à 5 colonnes pour 8 produits : rangée 1 pleine, rangée 2 avec 3 vignettes
et deux emplacements vides à droite.

Gravité : **détail**.

### 2.5 Pages Promotions et Marques non atteignables depuis l'accueil

Liens de navigation présents sur l'accueil : `/nouveaux-produits`,
`/meilleures ventes`, `/plan-site`, `/magasins`, `/nous-contacter`, les 7 pages CMS.

Absents de l'en-tête **et** du pied de page : `/promotions` et `/marques`. Ces deux
pages ne sont accessibles que par `/plan-site`.

Gravité : **gênant**.

### 2.6 Images de démonstration du diaporama encore sur le serveur

`modules/ps_imageslider/images/sample-1.jpg` et `sample-2.jpg` répondent en 200.
Elles ne sont plus référencées par la page, mais restent servies.

Gravité : **détail**.

### 2.7 Mobile (390 px)

- Les vignettes des cartes « Nos rayons » passent à 4 colonnes d'environ 55 px de
  large dans des boîtes blanches très hautes : les visuels produits deviennent des
  timbres perdus dans du blanc. **Gênant**.
- L'en-tête plus la navigation occupent 272 px (`.ludik-head` 119 px +
  `.ludik-nav` 153 px) avant le premier contenu. **Détail**.
- Le bouton hamburger est seul sur une bande pleine largeur d'environ 43 px, aligné
  à gauche, tout le reste de la bande est vide. **Détail**.
- `nav.ludik-tiles` devient un défilement horizontal : la 4e pastille est coupée en
  plein milieu, sans autre indication que la barre de défilement du navigateur.
  **Détail**.
- Le placeholder du champ de recherche est tronqué : « Rechercher un produit, une
  licence, une ré ». **Détail**.
- Pas de débordement horizontal de la page (`document.scrollWidth` = 390).

---

## 3. Recherche `/recherche?s=...`

### 3.1 La recherche ne renvoie jamais de résultat

Testé avec `pokemon`, `asterix`, `astérix`, `splendor`, `jeu`, `manga`, `bd`,
`puzzle`, `magic`, `naruto` : les dix requêtes rendent

```
Aucun résultat de la recherche pour "<terme>"
Effectuez une nouvelle recherche.
```

« splendor » ne trouve pas la fiche `/1389-splendor-le-soleil-ne-se-couche-jamais-56804.html`
qui existe. L'index de recherche PrestaShop n'est pas construit.

Gravité : **bloquant** (fonction principale d'un catalogue de 32 939 références).

### 3.2 Page de résultats vide sans porte de sortie

`h1.page-title-section` = « Aucun résultat de la recherche pour "pokemon" ».
Aucune suggestion, aucun lien vers les rayons, pas de champ de recherche dans le
corps de page. Le contenu n'est pas dans une carte blanche contrairement aux pages
CMS.

Gravité : **gênant**.

---

## 4. Pages de rayon

### 4.1 `/6-librairie` : facette « Catégories » de 2 816 cases à cocher

La section `<section class="accordion-item" data-type="category" data-name="Catégories">`
de `ps_facetedsearch` génère **2 816 éléments `.search-filters__item`**, soit
**5,2 Mo de HTML** sur les 6,4 Mo de la page. Le DOM rendu compte
**33 376 nœuds**.

Comparaison : `/4-jeux-de-societe` en génère 6, `/nouveaux-produits` 88.

Gravité : **bloquant** (page de 6,4 Mo, filtre inutilisable).

### 4.2 Navigation des sous-rayons en double

Sur `/4-jeux-de-societe` la même liste de 9 sous-rayons est rendue deux fois :

1. `#left-column` : carte blanche « JEUX DE SOCIÉTÉ / Toutes les sous-catégories /
   Jeux Enfants / ... / Carroms et Jeux géants ».
2. `#center-column` : carte des puces sous le H1, avec exactement les mêmes 9
   entrées.

En desktop les deux blocs sont côte à côte. **En mobile ils sont empilés** : il faut
faire défiler environ 1 100 px de listes de sous-rayons identiques avant d'atteindre
la première vignette produit.

Gravité : **gênant** en desktop, **bloquant** en mobile.

### 4.3 Textes anglais des listes et des filtres

Pages concernées : `/4-jeux-de-societe`, `/6-librairie`, `/nouveaux-produits`.

| Chaîne | Emplacement |
|---|---|
| `results` / `result` | `span.visually-hidden` dans `.search-filters__magnitude`, ex. `(1962<span class="visually-hidden">results</span>)` |
| `Product filters` | `aria-label` de `#search-filters` |
| `Sort products by: Pertinence` (et Nom A à Z, Nom Z à A, Prix croissant, Prix décroissant) | attribut `title` des options de tri |
| `Go to previous page`, `Go to next page`, `Go to page 1` ... `Go to page 1172` | `aria-label` de la pagination |
| `Quick view <nom du produit>` | `aria-label` du bouton d'aperçu rapide de chaque vignette |
| `Close` | `aria-label` du bouton de fermeture de la modale d'aperçu |

Gravité : **gênant**.

### 4.4 Séparateur de milliers invisible sur les prix des vignettes

`.product-miniature__prices` affiche « 2 983 F » avec un U+202F (espace fine
insécable) comme séparateur de milliers et un U+00A0 avant le F.

À `font-size: 14px` en Inter, l'espace fine mesure **1,97 px** : à l'écran le prix
se lit « 2983F ». Vérifié sur `/4-jeux-de-societe` (2983 F, 6480 F, 5760 F, 2960 F,
4000 F, 4500 F) et sur l'accueil (2487 F, 1313 F, 1181 F, 1044 F).

Sur la fiche produit (`.product__price`, 33,6 px) l'espace est lisible.

Gravité : **gênant** (risque de mauvaise lecture sur un prix à cinq chiffres).

### 4.5 Placeholder « Visuel à venir » trop pâle

Les produits sans visuel utilisent `/img/p/fr-default-default_md.jpg`, une image de
marque Ludik avec la mention « Visuel à venir ». Le logo et le texte sont dans un
crème très clair sur fond crème : la vignette se lit comme une case vide.

Fréquence relevée : 3 vignettes sur 24 sur `/4-jeux-de-societe`
(« (1000 pièces) - Puzzle Attack on Titans », « (300 Pièces) - François Ruyer:
Coccinelle Pirate », « (100 pièces) - Gambit de la reine »), 1 sur 24 sur
`/6-librairie` et `/nouveaux-produits` (« 404 Démons - Tome 3 - Tome 3 »).

Gravité : **gênant**.

### 4.6 Titres de produits avec le tome répété

Sur 56 titres relevés dans les listings, **28 répètent le numéro de tome** :

```
Goblin Slayer - Tome 6 - Tome 6
Spy x Family - Tome 3 - Volume 3
Livre des démons (Le) - Tome 4 - Tome 4
Land - Tome 9 - Tome 9
Frieren - Tome 12 - Volume 12
Fées le Roi-Dragon et moi (en chat) (Les) - Tome 2 - Tome 2
Sommet des dieux (Le) - Tome 1 - Volume 1
Kemono incidents - Tome 24 - Tome 24
```

Défaut de reprise de données, visible partout où le produit est affiché.

Gravité : **gênant**.

### 4.7 Noms de catégories fautifs

Visibles dans le menu, la colonne de gauche, les puces, les facettes et le plan du
site :

| Nom actuel | Attendu |
|---|---|
| `Jeux pédagoqiques` | Jeux pédagogiques |
| `Jeux asbtraits` | Jeux abstraits |
| `_Ecole des Loisirs` | École des Loisirs (souligné parasite en tête) |
| `Enigme - Enquête - Déduction` | présent deux fois, sous « Ambiance & Rigolade » et sous « Les grandes séries » |

Gravité : **gênant**.

### 4.8 Facette « Sélections » inutile et non homogène

`/4-jeux-de-societe` propose « Sélections > Nouveau produit (3536 results) », soit
la totalité des produits du rayon : tous les produits importés portent le drapeau
« nouveau ». La facette ne filtre rien.

`/nouveaux-produits` n'a pas la facette « Sélections » alors que la page de rayon
l'a. Les deux pages n'exposent donc pas le même jeu de filtres.

Gravité : **détail**.

### 4.9 La catégorie racine `/2-accueil` est navigable

`http://localhost:8801/2-accueil` répond 200, avec `h1` = « Accueil » et
« Il y a 32939 produits ». Elle est liée depuis `/plan-site` et depuis la colonne
de gauche de `/promotions`, `/marques` et `/nouveaux-produits`.

Gravité : **gênant**.

---

## 5. Fiches produit

Pages examinées : `/1389-splendor-le-soleil-ne-se-couche-jamais-56804.html`,
`/2177-1000-pieces-puzzle-attack-on-titans-1000.html` (en stock),
`/1758-magnetique-echecs-55042.html` (épuisé).

### 5.1 Vignette de miniature écrasée à 0 px

`.product__thumbnails-list > button.product__thumbnail` a une largeur calculée de
**0 x 22 px** (enfant de grille effondré). L'image à l'intérieur se rend en
**4 x 17 px**.

Rendu : un petit point sombre isolé à gauche de l'image principale, à environ
33 px sous le haut du bloc image. Présent sur toutes les fiches (tous les produits
n'ont qu'un visuel). Le mobile rend la miniature correctement.

Sélecteur : `.product__images .product__thumbnails-list > button.product__thumbnail`.

Gravité : **gênant**.

### 5.2 Requête 404 sur chaque fiche produit

Le `srcset` de l'image de la modale de zoom se termine par un descripteur de
largeur sans URL :

```html
<img class="img-fluid" srcset=" .../1368-default_md/....jpg 320w,
                                .../1368-product_main/....jpg 720w,
                                1440w"
     sizes="(min-width: 1200px) 1440px, (min-width: 768px) 720px, 100vw" ...>
```

Le navigateur résout `1440w` comme une URL relative :
`GET http://localhost:8801/1440w` répond **404**. Visible dans la console
(« Failed to load resource: 404 ») et dans les requêtes réseau, sur toutes les
fiches.

Emplacement : `.product-images-modal__carousel img.img-fluid`, troisième entrée du
`srcset`.

Gravité : **gênant**.

### 5.3 Balise `<meta>` incomplète dans le `<head>`

`theme/ludik/templates/catalog/product.tpl:8` :

```smarty
<meta content="{$product.url}">
```

Il manque `property="og:url"`. La balise est émise telle quelle dans le `<head>`
de chaque fiche :

```html
<meta content="http://localhost:8801/1389-splendor-le-soleil-ne-se-couche-jamais-56804.html">
```

L'`og:url` correct est par ailleurs déjà produit par le thème parent : la balise du
thème enfant est donc à supprimer, pas à compléter.

Gravité : **détail**.

### 5.4 Textes anglais des fiches produit

| Chaîne | Emplacement |
|---|---|
| `Price: ` | `span.visually-hidden` dans `.product__price` |
| `Product availability:` | `span.visually-hidden` dans `.product__availability-messages` |
| `Rating for ` | `span.visually-hidden` dans la `legend` de `fieldset.star-rating-group` |
| `Title of your review ` | `label[for="comment_title"]` (module productcomments) |
| `Send review` | `aria-label` du bouton d'envoi d'avis |
| `1 star out of 5` ... `5 stars out of 5` | `aria-label` des étoiles de notation |
| `Slide to product image 1` | `aria-label` de `button.product__thumbnail` |
| `Open zoomed product image gallery` | `aria-label` du bouton de zoom |
| `Decrease quantity of <produit>`, `Increase quantity of <produit>`, `Change quantity of <produit>` | `aria-label` du sélecteur de quantité |
| `Add to cart <produit>` | `aria-label` du bouton d'ajout au panier |

Gravité : **gênant**.

### 5.5 Mention « Aucune taxe » sous chaque prix

`.product__tax-label` affiche « Aucune taxe » sur toutes les fiches. La formulation
n'a pas de sens commercial en Nouvelle-Calédonie et elle contredit les CGV qui
mentionnent la TGC (voir 8.6).

Gravité : **gênant**.

### 5.6 L'étiquette de rayon reprend la catégorie la plus profonde

`span.lud-tag` affiche `$product.category_name`, c'est-à-dire la sous-catégorie
terminale. Résultats observés :

| Fiche | Étiquette affichée |
|---|---|
| Splendor : Le soleil ne se couche jamais | `COMMERCE ET GESTION DE RESSOURCES` |
| (1000 pièces) - Puzzle Attack on Titans | `1000 - 1999 PIÈCES` |
| Magnetique : Echecs | `GAMME MAGNÉTIQUE ALBI` |

L'étiquette du puzzle ne dit rien du produit.

Gravité : **gênant**.

### 5.7 Titre produit en capitales

`h1.product__name` est en `text-transform: uppercase` à 30 px :
« SPLENDOR : LE SOLEIL NE SE COUCHE JAMAIS ». Le même titre est en casse normale
dans le fil d'Ariane, dans les vignettes et dans le `<title>`. Les titres de manga
longs deviennent des blocs de capitales sur trois lignes.

Gravité : **détail** (choix à confirmer).

### 5.8 Boutons de partage X et Pinterest

Le bloc « Partager » propose Facebook, X et Pinterest. Ludik n'a qu'une page
Facebook (seul réseau du pied de page). Les deux autres sont les valeurs par défaut
de PrestaShop.

Gravité : **détail**.

### 5.9 Module d'avis actif sans aucun avis

`productcomments` est actif sur toutes les fiches : bloc « Commentaires (0) /
Aucun avis n'a été publié pour le moment. », bouton « Donnez votre avis », plus
toutes les boîtes de dialogue de signalement (« Signaler le commentaire »,
« Êtes-vous certain de vouloir signaler ce commentaire ? », « Signalement
envoyé »...) présentes dans le HTML.

À trancher avant la mise en ligne : 32 939 fiches avec un bloc d'avis vide.

Gravité : **détail** (décision produit).

### 5.10 Description courte d'un autre produit sur la fiche Splendor

`/1389-splendor-le-soleil-ne-se-couche-jamais-56804.html`, `.product__description-short` :

```
Splendor : La Route de la Soie est une extension pour Splendor, ajoutant deux
modules : les Cites et les Comptoirs.
```

« La Route de la Soie » est une autre extension que « Le soleil ne se couche
jamais ». Le texte alimente aussi `<meta name="description">` et `og:description`.
Accessoirement « Cites » au lieu de « Cités ».

Gravité : **bloquant** pour cette fiche, à vérifier sur l'ensemble de la reprise.

### 5.11 Mot « Translator » collé en tête de description

`/2177-1000-pieces-puzzle-attack-on-titans-1000.html` :

```
TranslatorReconstituez la bataille épique du district de Trost avec ce puzzle...
```

Également sur `/2170-50-pieces-puzzle-lettre-43767.html` (« TranslatorPuzzle vendu
à l'unité... »).

Artefact du script de reprise. Taux relevé : 2 fiches sur 24 testées dans « Jeux de
société » (environ 8 %), 0 sur 12 testées dans « Librairie ». Le texte apparaît
aussi dans la meta description.

Gravité : **gênant**.

### 5.12 Produit épuisé : formulation du rappel de stock

`/1758-magnetique-echecs-55042.html`, bloc « Prévenez-moi lorsque le produit est
disponible » :

```
Intéressé par ce produit ? Communiquez-nous votre adresse email et nous
informerons dès qu'il est disponible pour passer commande
```

Il manque « vous » (« nous vous informerons ») et le point final.

Gravité : **détail**.

### 5.13 Bonne nouvelle sur les états de stock

L'étiquetage est désormais homogène : la vignette porte le badge « Épuisé », la
fiche affiche « Épuisé » et le bouton d'ajout au panier est remplacé par une flèche
vers la fiche. Le drapeau « Neuf » (`ul.product-flags li.badge.new`) est masqué sur
la fiche (`display: none`) et visible sur les vignettes.

---

## 6. Nouveautés, meilleures ventes, promotions

### 6.1 `/promotions` et `/meilleures ventes` sont vides, avec un message en anglais

Les deux pages rendent :

```html
<section id="content" class="page-content page-content--not-found">
  <p class="h3">No products available at the moment</p>
  <p>Restez à l'écoute ! D'autres produits seront affichés ici au fur et à mesure qu'ils seront ajoutés.</p>
```

Titre en anglais, paragraphe en français, dans le même bloc.

Gravité : **bloquant**.

### 6.2 L'URL des meilleures ventes contient une espace

```
/meilleures-ventes    -> 404
/meilleures%20ventes  -> 200
```

L'espace apparaît telle quelle dans les liens émis :

- accueil, `.ludik-section-head a` : `href="http://localhost:8801/meilleures ventes"`
- `/plan-site`, `a#best-sales-page` : `href="http://localhost:8801/meilleures ventes"`

Gravité : **bloquant** (réécriture d'URL à corriger en base).

### 6.3 `/promotions` : carte blanche vide dans la colonne de gauche

```html
<div id="search_filters_wrapper" class="ps-facetedsearch d-none d-md-block left-block">
  <div id="_desktop_faceted"></div>
</div>
```

Le conteneur est vide mais garde son fond blanc et son cadre : une petite carte
blanche vide s'affiche sous l'arbre de catégories, à environ 163 x 30 px.

Gravité : **gênant**.

### 6.4 Colonne de gauche hors sujet sur ces trois pages

`/promotions`, `/meilleures ventes`, `/marques` et `/nouveaux-produits` affichent
la colonne de gauche des pages de rayon, avec l'arbre de la catégorie racine :
« ACCUEIL / Toutes les sous-catégories / Jeux de société / Jeux de cartes à
collectionner / Accessoires Jeux & JCC / Jeux de rôle / Librairie / Loisirs
Créatifs ».

Gravité : **gênant**.

### 6.5 `/nouveaux-produits` liste tout le catalogue

La page affiche « Il y a 32939 produits. » sur 1 373 pages de pagination : tous les
produits importés sont marqués comme nouveaux. La page ne remplit pas sa fonction.

À noter aussi : « 32939 » sans séparateur de milliers ici, alors que l'accueil
affiche « 32 939 ».

Gravité : **bloquant**.

---

## 7. `/marques`

### 7.1 Les deux fabricants de démonstration PrestaShop sont toujours là

```html
<img class="brand__img" src="http://localhost:8801/img/m/2-small_default.jpg" alt="Graphic Corner">
<a class="brand__title" href="http://localhost:8801/brand/2-graphic-corner">Graphic Corner</a>
<p class="brand__products">Aucun produit</p>

<img class="brand__img" src="http://localhost:8801/img/m/1-small_default.jpg" alt="Studio Design">
<a class="brand__title" href="http://localhost:8801/brand/1-studio-design">Studio Design</a>
<p class="brand__products">Aucun produit</p>
```

Les deux logos de démonstration s'affichent bien (200, 3,3 Ko et 2,2 Ko). Les deux
marques sont aussi listées dans `/plan-site`, sous « Nos offres > Marques ».

Gravité : **bloquant** (données de démonstration visibles).

### 7.2 Aucun produit n'est rattaché à une marque

Les deux seuls fabricants existants affichent « Aucun produit ». La fonction marque
est donc inexploitée, et la page n'est liée ni depuis l'en-tête ni depuis le pied de
page.

Gravité : **gênant** (soit peupler les marques, soit désactiver la page et retirer
son entrée du plan du site).

---

## 8. Pages éditoriales

### 8.1 `/content/4-a-propos` : adresse e-mail obsolète et non cliquable

Section « Contact » :

```
Ecrivez nous sur ludik.nc@gmail.com
```

Trois problèmes : l'adresse Gmail contredit `contact@ludik.nc` et
`librairie@ludik.nc` affichés dans le pied de page ; ce n'est pas un lien
`mailto:` ; « Ecrivez nous » manque l'accent et le trait d'union
(« Écrivez-nous »).

Gravité : **gênant**.

### 8.2 `/content/4-a-propos` : renvoi interne non cliquable

```
Pour découvrir ces jeux, Ludik propose chaque Mardi une soirée ouverte à tous.
Consultez notre page Soirées Jeux à l'Amicale Basque pour plus d'informations !
```

« notre page Soirées Jeux à l'Amicale Basque » est du texte brut, alors que
`/content/6-soirees-jeux` existe. La page ne contient qu'un seul lien, vers
Facebook. « chaque Mardi » avec une majuscule.

Gravité : **gênant**.

### 8.3 `/content/9-conditions-generales-de-vente` : titre en double

```html
<h1 class="page-title-section">Conditions générales de vente</h1>
...
<h2>Conditions générales de ventes</h2>
```

Le H2 répète le H1 avec une orthographe différente (« ventes » au pluriel), juste
au-dessus du premier paragraphe.

Gravité : **gênant**.

### 8.4 CGV : adresse postale contradictoire

Section « Moyens de paiement et sécurité », paiement par chèque :

```
à l'adresse LUDIK, 30 Rue André Capiez, PK 4, Nouméa
```

Le préambule de la même page et le pied de page indiquent
« 107 rue Auguste Bénébig, Vallée des Colons, 98800 Nouméa ». Le paragraphe
« Garantie » utilise encore une autre formulation de la même adresse Bénébig.

Gravité : **bloquant** (mention légale contradictoire).

### 8.5 Moyens de paiement annoncés mais non disponibles

Trois endroits annoncent la carte bancaire et PayPal :

| Emplacement | Texte |
|---|---|
| `/content/1-livraison` | « Paiement en ligne par Carte bancaire ou compte Paypal » et « Cartes bancaires et AMEX » |
| CGV, « Moyens de paiement et sécurité » | 4 moyens listés dont « Paiement par compte PayPal » et « Paiement par carte bancaire via Paypal », 5 occurrences de « PayPal » et 4 de « carte bancaire » |
| Bloc de réassurance `.ludik-trust` (panier, fiche produit, 404, rayons) | « Plusieurs moyens de paiement / Carte, PayPal, chèque, espèces, virement » |

Les moyens réellement configurés sont chèque, virement bancaire et paiement au
retrait (voir README).

Gravité : **bloquant** (engagement commercial non tenu).

### 8.6 CGV : mention de la TGC alors que les taxes sont désactivées

Section « Tarifs » : « Les prix figurant dans le catalogue sont des prix TTC en XPF
tenant compte de la TGC applicable au jour de la commande ». Les fiches produit
affichent « Aucune taxe » (voir 5.5).

Gravité : **gênant**.

### 8.7 Ce qui va bien sur les pages CMS

Contenu entièrement en français, carte blanche `.page-content` présente,
conteneur cohérent avec les autres pages, aucun débordement à 390 px, listes à
puces correctement rendues. `/content/6-soirees-jeux` et `/content/1-livraison`
n'ont aucun défaut de rendu relevé.

---

## 9. `/magasins`

### 9.1 Placeholder PrestaShop par défaut sur les deux magasins

```html
<img class="store__img img-fluid" srcset="http://localhost:8801/img/st/fr.jpg"
     loading="lazy" width="287" height="160" alt="Ludik Michel-Ange" ...>
```

`/img/st/fr.jpg` est l'image « Aucune image disponible » livrée avec PrestaShop.
Elle occupe environ la moitié de chaque carte, en gris, avec le texte en gros.

Gravité : **bloquant** (données de démonstration très visibles).

### 9.2 `<img>` sans attribut `src`

La même balise porte un `srcset` mais **pas de `src`**. Un seul candidat, sans
descripteur de largeur.

Gravité : **détail** (technique).

### 9.3 Ligne « Dimanche » vide dans les horaires

```html
<tr><th> Dimanche </th><td> </td></tr>
```

Le libellé reste, la valeur est vide, sur les deux magasins. « Fermé » attendu.

Gravité : **détail**.

### 9.4 Téléphone manquant pour Michel-Ange

La carte « Ludik Vallée des Colons » affiche « 46 55 46 » et
« librairie@ludik.nc ». La carte « Ludik Michel-Ange » n'affiche que
« contact@ludik.nc », sans téléphone. Point déjà listé dans `docs/DONNEES.md`.

Gravité : **gênant**.

### 9.5 Accordéons fermés par défaut

« À propos et Contact » et « Voir les horaires » sont replié(e)s au chargement.
Chaque carte se réduit donc à l'adresse plus le grand placeholder gris : aucune
information utile n'est visible sans clic.

Gravité : **gênant**.

---

## 10. `/nous-contacter`

### 10.1 Bouton d'envoi en anglais

```html
<button class="btn btn-primary" type="submit" name="submitMessage"
        data-ps-action="form-validation-submit"> Send your message </button>
```

C'est l'unique action de la page, en bas à droite du formulaire.

Gravité : **bloquant** (texte anglais visible).

### 10.2 Destinataire « Webmaster » par défaut

```html
<select id="contact-us-subject-select" name="id_contact">
  <option value="2">Service client</option>
  <option value="1">Webmaster</option>
</select>
```

« Webmaster » est le contact par défaut de PrestaShop, sans pertinence pour Ludik.

Gravité : **gênant**.

### 10.3 Mention « optionnel » non harmonisée

`span.form-text` sous le champ pièce jointe : « optionnel » en minuscule et sans
ponctuation, alors que `/inscription` écrit « Optionnel » avec une majuscule.

Gravité : **détail**.

### 10.4 Colonne de gauche vide sur une version antérieure

Sur la version servie en début d'audit, `#left-column.wrapper__left-column.col-md-4.col-lg-3`
était présent et complètement vide, ce qui décalait le formulaire de 3 colonnes.
Le point est **corrigé** dans la version actuelle : le formulaire est centré dans
une carte blanche. Mentionné pour mémoire.

---

## 11. Connexion, création de compte, mot de passe oublié

### 11.1 Cause commune : les conteneurs limités ne limitent plus rien

`theme/ludik/assets/css/custom.css:200` :

```css
.container, .container-md, .container-fluid {
  max-width: var(--lud-page);   /* 1340px */
  margin-inline: auto;
  padding-inline: var(--lud-s4);
}
```

Cette règle est émise **après** `.container--limited-sm{max-width:540px}` dans le
CSS compilé (position 393204 contre 300707 dans `theme-3605c9.css`), à spécificité
égale : c'est donc 1340 px qui gagne.

Vérification à 1440 px sur `/connexion` :

```
.container.container--limited-sm  ->  max-width calculé : 1340px, largeur : 1340
formulaire de connexion          ->  1300 px de large
```

Conséquence directe sur `/connexion`, `/inscription`,
`/recuperation-mot-de-passe` (`--limited-sm` et `--limited-md`) et la page 404
(`--limited-md`) : les champs e-mail, mot de passe et le bouton
« Créez votre compte » s'étirent sur 1300 px.

Gravité : **bloquant** (formulaires visuellement cassés sur 4 pages).

### 11.2 Aucune carte blanche sur ces pages

Les formulaires de connexion, d'inscription et de mot de passe oublié sont posés
directement sur le fond crème, sans `.page-content` blanche, contrairement aux
pages CMS, à la fiche produit, à la page de rayon et à `/nous-contacter`.

Gravité : **gênant**.

### 11.3 `/inscription` : texte RGPD en anglais

```html
<label class="form-check-label required" for="field-customer_privacy">
  Message concernant la confidentialité des données clients<br>
  <em>The personal data you provide is used to answer queries, process orders or
  allow access to specific information. You have the right to modify and delete
  all the personal information found in the "My Account" page.</em>
</label>
```

Trois lignes d'anglais dans le tunnel de création de compte, dans un libellé
présenté comme une case à cocher obligatoire alors qu'il s'agit d'une mention
d'information.

Gravité : **bloquant**.

### 11.4 `/inscription` : astérisque obligatoire placée avant le libellé

Les champs classiques écrivent « Prénom * », « Nom * », « E-mail * »,
« Mot de passe * ». Les deux cases à cocher écrivent
« * J'accepte les conditions générales et la politique de confidentialité » et
« * Message concernant la confidentialité des données clients », astérisque en tête.

Gravité : **détail**.

### 11.5 `/inscription` : consentement sans lien vers les documents

« J'accepte les conditions générales et la politique de confidentialité » est du
texte brut. Aucun lien vers `/content/9-conditions-generales-de-vente` ni vers la
politique de confidentialité.

Gravité : **gênant** (conformité).

### 11.6 `/inscription` : trois indications redondantes sur la date de naissance

Le champ porte le placeholder `JJ/MM/AAAA`, la mention « (Ex. : 31/05/1970) » et la
mention « Optionnel », les trois empilées.

Gravité : **détail**.

### 11.7 Anglais sur l'affichage du mot de passe

```html
<button type="button" data-ps-action="toggle-password"
        data-text-show="Show password" data-text-hide="Hide password"
        aria-label="Show password" ...>
```

Sur `/connexion` et `/inscription`.

Gravité : **gênant**.

### 11.8 `/connexion` : trois libellés différents pour la même page

- `<title>` : « Identifiant » (libellé PrestaShop par défaut)
- fil d'Ariane : « Connectez-vous à votre compte »
- `h1` : « Connexion »

Gravité : **gênant**.

### 11.9 `/connexion` : boutons dépareillés

Conséquence de 11.1 : « Mot de passe oublié ? » est collé au bord gauche et le
bouton « Connexion » au bord droit, séparés d'environ 1200 px sur la même ligne.
« Créez votre compte » est un bouton contour de 1300 px de large, alors que
« Connexion » est un bouton plein d'environ 80 px.

Gravité : **gênant**.

---

## 12. `/panier?action=show`

### 12.1 Panier vide : formulation trompeuse

`« Il n'y a plus d'articles dans votre panier »` s'affiche aussi lorsqu'aucun
article n'y a jamais été mis. Libellé PrestaShop par défaut.

Gravité : **détail**.

### 12.2 Panier vide : récapitulatif inutile conservé

Le panier vide affiche quand même « Récapitulatif de commande / 0 articles / 0 F /
Total 0 F » avec le bouton « Commander ». Le bouton est correctement désactivé
(`<button type="button" class="btn btn-primary disabled" disabled>`), mais le bloc
n'apporte rien.

Gravité : **détail**.

### 12.3 Panier rempli : accord de « gratuit »

Ligne de récapitulatif : « Livraison  gratuit ». Attendu « gratuite » ou
« Gratuite ».

Gravité : **détail**.

### 12.4 Aucune carte blanche autour du panier

La liste des articles et le récapitulatif sont posés à même le fond crème, séparés
par un simple filet, contrairement aux autres pages de contenu. La page paraît
inachevée à côté d'une fiche produit ou d'une page CMS.

Gravité : **gênant**.

---

## 13. `/plan-site`

### 13.1 Mise en page déséquilibrée par l'arbre complet des catégories

La grille est un `row` de 4 colonnes `col-md-6 col-lg-3` : « Nos offres » (4 liens),
« Catégories », « Votre compte » (2 liens), « Pages » (10 liens).

La colonne « Catégories » déroule les **3 965 catégories** dans une seule colonne
de 25 % de large, ce qui donne une page de **2,2 Mo** et trois colonnes courtes à
côté d'une colonne de plusieurs milliers de lignes.

Gravité : **gênant**.

### 13.2 Lien « Meilleures ventes » avec une espace dans l'URL

`a#best-sales-page href="http://localhost:8801/meilleures ventes"` (voir 6.2).

Gravité : **bloquant**.

### 13.3 Marques de démonstration et catégorie racine listées

Sous « Nos offres > Marques » : `Graphic Corner` (`/brand/2-graphic-corner`) et
`Studio Design` (`/brand/1-studio-design`). En tête de « Catégories » :
`Accueil` (`/2-accueil`). Voir 7.1 et 4.9.

Gravité : **bloquant** pour les marques, **gênant** pour la catégorie racine.

### 13.4 Deux URL possibles, une seule fonctionne

`/plan-site` répond 200, `/plan-du-site` répond 404. Le pied de page utilise
`/plan-site`. À arbitrer si l'URL lisible est souhaitée.

Gravité : **détail**.

---

## 14. Page inexistante (404)

Testé avec `/page-qui-nexiste-pas-du-tout` et `/page-inexistante-xyz` : code HTTP
404, `<title>` « Erreur 404 ».

### 14.1 Phrase en anglais

```html
<section id="content" class="page-content page-content--not-found">
  <h1 class="h2">La page que vous cherchez n'a pas été trouvée.</h1>
  <p> If this is a recurring problem, please
      <a href="http://localhost:8801/nous-contacter">contact us</a>. </p>
```

Gravité : **bloquant** (anglais au milieu d'une page française).

### 14.2 Hiérarchie de titres inversée

Le gros « 404 » est un `<p>`, le vrai titre est un `h1` avec la classe `h2` :
c'est la seule page du site dont le titre n'utilise pas `page-title-section`, et il
s'affiche donc plus petit qu'ailleurs.

Gravité : **gênant**.

### 14.3 Pas de carte blanche, conteneur étiré

Contenu centré directement sur le fond crème, dans un `.container--limited-md`
inopérant (voir 11.1) donc étalé sur 1300 px.

Gravité : **gênant**.

### 14.4 Aucune aide au rebond

Un seul lien utile, « Retour à la page d'accueil ». Pas de champ de recherche
(qui ne fonctionnerait de toute façon pas, voir 3.1), pas de liens vers les rayons.

Gravité : **détail**.

---

## 15. Console et réseau

Relevé sur l'accueil, une page de rayon, une fiche produit, le panier, une page CMS
et la 404.

| Constat | Détail | Gravité |
|---|---|---|
| `GET /1440w` -> 404 | Une requête par fiche produit, `srcset` malformé (voir 5.2) | gênant |
| `JQMIGRATE: Migrate is installed, version 3.5.2` | Log console à chaque chargement, sur toutes les pages | détail |
| Aucune erreur JavaScript | Aucune exception relevée | - |
| Aucun avertissement PHP visible | Rien dans le HTML servi | - |
| Toutes les feuilles de style, scripts, polices et images produit testés répondent 200 | `theme-3605c9.css`, `bottom-b2732a.js`, Inter, Material Icons, Outfit, Caveat, logo, favicon | - |
| Aucun débordement horizontal | `document.scrollWidth == innerWidth` à 1440 px et à 390 px sur l'accueil, `/4-jeux-de-societe`, `/6-librairie`, la fiche produit, `/panier`, `/magasins`, les CGV et `/plan-site` | - |

---

## 16. Synthèse par thème

### 16.1 Textes restés en anglais

**14 chaînes sur toutes les pages, 26 chaînes au total, 24 pages concernées.**

| Chaîne | Pages | Gravité |
|---|---|---|
| `Skip to main content` | 24 | gênant |
| `Toggle la boutique links` / `Toggle commander links` / `Toggle informations links` / `Toggle your account links` | 24 | gênant |
| `Main navigation`, `Open mobile menu`, `Go back to main menu`, `Close search`, `Open <rayon> submenu` | 24 | gênant |
| `Subcategories for <catégorie>` | 6 | gênant |
| `No products available at the moment` | 2 (`/promotions`, `/meilleures ventes`) | **bloquant** |
| `The personal data you provide is used...` (RGPD, 3 lignes) | 1 (`/inscription`) | **bloquant** |
| `Send your message` | 1 (`/nous-contacter`) | **bloquant** |
| `If this is a recurring problem, please contact us.` | 1 (404) | **bloquant** |
| `results` / `result` | 3 (rayons, nouveautés) | gênant |
| `Product filters`, `Sort products by: ...`, `Go to page N`, `Go to previous/next page`, `Quick view ...`, `Close` | 3 | gênant |
| `Price: `, `Product availability:` | 3 fiches produit | gênant |
| `Rating for`, `Title of your review`, `Send review`, `N star(s) out of 5` | 3 fiches produit | gênant |
| `Slide to product image 1`, `Open zoomed product image gallery`, `Decrease/Increase/Change quantity of ...`, `Add to cart ...` | 3 fiches produit | gênant |
| `Show password` / `Hide password` | 2 (`/connexion`, `/inscription`) | gênant |
| `Go to slide 1/2/3` | 1 (accueil) | gênant |

Quatre chaînes sont **visibles à l'écran** et bloquantes :
`No products available at the moment`, le paragraphe RGPD,
`Send your message` et `If this is a recurring problem...`. Les autres sont
`visually-hidden` ou des attributs `aria-label` / `title`.

### 16.2 Incohérences de gabarit

| Constat | Pages concernées | Gravité |
|---|---|---|
| Conteneurs `--limited-sm` / `--limited-md` inopérants, formulaires étirés à 1300 px | 4 (`/connexion`, `/inscription`, `/recuperation-mot-de-passe`, 404) | **bloquant** |
| Absence de carte blanche là où les autres pages en ont une | 6 (`/connexion`, `/inscription`, `/recuperation-mot-de-passe`, `/panier`, 404, résultats de recherche) | gênant |
| Position du H1 : dedans ou dehors de la carte | 6 gabarits différents | gênant |
| Ordre des colonnes du pied de page non déterministe | toutes | gênant |
| Trou de 3 cellules dans la grille du pied de page (5 blocs pour 4 colonnes) | toutes, desktop | gênant |
| Colonne de gauche de rayon sur des pages qui n'en sont pas | 4 (`/promotions`, `/meilleures ventes`, `/marques`, `/nouveaux-produits`) | gênant |
| Sous-rayons affichés deux fois (colonne de gauche + puces) | toutes les pages de rayon | gênant |
| `<title>` sans suffixe boutique, dont un libellé PrestaShop (`Identifiant`) | 11 | gênant |
| Bandeau de pastilles qui répète la barre de navigation et mélange deux niveaux | toutes | gênant |
| Titre produit en capitales, casse normale ailleurs | toutes les fiches | détail |
| Compteurs « Nos rayons » en deux couleurs sans logique | accueil | gênant |
| Palette de 7 accents pour une charte « verte et or » | toutes | détail |
| Libellés de rayons divergents entre les trois navigations | toutes | détail |
| Boutons dépareillés sur `/connexion` (plein 80 px face à contour 1300 px) | 1 | gênant |
| Facettes différentes entre `/4-jeux-de-societe` et `/nouveaux-produits` | 2 | détail |

### 16.3 Éléments cassés

| Constat | Pages concernées | Gravité |
|---|---|---|
| Recherche : 0 résultat pour toute requête | tout le site | **bloquant** |
| Titre du bandeau d'accueil en double, contraste 1,10, sur un rectangle gris | accueil | **bloquant** |
| `/meilleures-ventes` en 404, URL réelle avec une espace | 3 (accueil, plan du site, la page elle-même) | **bloquant** |
| Symbole `#i-facebook` absent du sprite, cercle gris vide | toutes | **bloquant** |
| Marques de démonstration PrestaShop (Graphic Corner, Studio Design) | 2 (`/marques`, `/plan-site`) | **bloquant** |
| Placeholder PrestaShop « Aucune image disponible » sur les deux magasins | 1 (`/magasins`) | **bloquant** |
| `/promotions` et `/meilleures ventes` vides | 2 | **bloquant** |
| `/nouveaux-produits` = tout le catalogue (32 939 produits) | 1 | **bloquant** |
| Facette « Catégories » de 2 816 cases, 5,2 Mo, 33 376 nœuds DOM | 1 (`/6-librairie`) | **bloquant** |
| Menu déroulant : 9 195 nœuds et 917 Ko dans chaque page | toutes | **bloquant** |
| Description d'un autre produit sur la fiche Splendor | 1 fiche, à contrôler sur toute la reprise | **bloquant** |
| Adresse postale contradictoire dans les CGV | 1 | **bloquant** |
| Carte bancaire et PayPal annoncés mais non disponibles | 3 emplacements | **bloquant** |
| Miniature produit écrasée à 0 px, point sombre parasite | toutes les fiches, desktop | gênant |
| `GET /1440w` en 404 (srcset malformé) | toutes les fiches | gênant |
| Carte blanche vide dans la colonne de gauche | 1 (`/promotions`) | gênant |
| Placeholder « Visuel à venir » illisible (crème sur crème), 4 à 12 % des vignettes | 3 pages de liste | gênant |
| Séparateur de milliers de 1,97 px sur les prix des vignettes | toutes les listes | gênant |
| Compteurs or sur blanc, contraste 2,32 | accueil | gênant |
| Titres produits avec le tome répété, 28 sur 56 relevés | toutes les listes | gênant |
| Mot « Translator » en tête de description, environ 8 % des jeux | fiches et metas | gênant |
| Noms de catégories fautifs (`Jeux pédagoqiques`, `Jeux asbtraits`, `_Ecole des Loisirs`) | menu, colonnes, facettes, plan du site | gênant |
| Catégorie racine `/2-accueil` navigable | 4 emplacements | gênant |
| Contact « Webmaster » par défaut | 1 (`/nous-contacter`) | gênant |
| Ligne « Dimanche » sans valeur | 1 (`/magasins`) | détail |
| `<img>` sans `src` (srcset seul) | 1 (`/magasins`) | détail |
| `<meta content="...">` sans `property` | toutes les fiches | détail |
| Mention PrestaShop dans le pied de page | toutes | détail |
| Images de démonstration `sample-1.jpg` / `sample-2.jpg` encore servies | serveur | détail |
| Polices embarquées non utilisées (423 Ko) | thème | détail |
| jQuery Migrate chargé | toutes | détail |

Aucune liste à puces parasite, aucun chevauchement d'éléments et aucun débordement
horizontal n'ont été relevés, à 1440 px comme à 390 px.

### 16.4 Mobile (390 px)

| Constat | Pages concernées | Gravité |
|---|---|---|
| Sous-rayons empilés deux fois : environ 1 100 px de listes identiques avant la première vignette | toutes les pages de rayon | **bloquant** |
| Vignettes des cartes « Nos rayons » réduites à 4 colonnes d'environ 55 px dans des boîtes très hautes | accueil | gênant |
| En-tête plus navigation : 272 px avant tout contenu | toutes | détail |
| Bouton hamburger seul sur une bande pleine largeur vide | toutes | détail |
| Bandeau de pastilles en défilement horizontal, 4e pastille coupée, pas d'indice visuel | toutes | détail |
| Placeholder du champ de recherche tronqué | toutes | détail |
| Légendes du carrousel masquées sous 992 px (`d-none d-lg-block`) | accueil | détail |

Points sains en mobile : aucun débordement horizontal sur les 8 pages testées, le
pied de page s'empile correctement sur une colonne (le trou de grille est propre au
desktop), la miniature de la fiche produit se rend normalement (le défaut est propre
au desktop), la barre de recherche est bien visible et pleine largeur, les grilles de
produits passent proprement à 2 colonnes.

---

## 17. Ordre de traitement suggéré

**Lot 1, corrections de fond (bloquants fonctionnels)**

1. Reconstruire l'index de recherche (3.1).
2. Limiter la facette « Catégories » de `ps_facetedsearch` en profondeur (4.1).
3. Limiter le menu déroulant aux deux ou trois premiers niveaux (1.1).
4. Corriger la réécriture d'URL des meilleures ventes (6.2).
5. Traiter le drapeau « nouveau » pour que `/nouveaux-produits` ait un sens (6.5),
   puis décider du sort de `/promotions` et `/meilleures ventes` (6.1).

**Lot 2, données de démonstration et données de reprise**

6. Supprimer les fabricants Graphic Corner et Studio Design (7.1).
7. Charger les photos des deux magasins (9.1) et compléter le téléphone
   Michel-Ange (9.4).
8. Nettoyer les descriptions : préfixe « Translator » (5.11) et fiche Splendor
   (5.10).
9. Nettoyer les titres à tome répété (4.6) et les noms de catégories fautifs (4.7).
10. Retirer la mention PrestaShop du pied de page (1.10).

**Lot 3, traductions**

11. Les 4 chaînes anglaises visibles à l'écran (16.1).
12. Les chaînes `visually-hidden` et les `aria-label` du thème et des modules
    ps_facetedsearch, ps_imageslider, productcomments, ps_linklist.

**Lot 4, gabarits et CSS**

13. Retirer `.container` de la règle `custom.css:200` ou passer les limiteurs en
    `!important` / augmenter leur spécificité (11.1).
14. Corriger la grille du pied de page (1.3) et fixer l'ordre des blocs (1.4).
15. Ajouter le symbole `i-facebook` au sprite (1.2).
16. Corriger le bandeau d'accueil : retirer le `<h2>` ou le sortir de
    `.carousel-caption` (2.1).
17. Corriger la miniature produit à 0 px (5.1) et le `srcset` à `1440w` (5.2).
18. Supprimer un des deux blocs de sous-rayons, en priorité en mobile (4.2).
19. Harmoniser la carte blanche et la position du H1 (11.2, 1.14), et les `<title>`
    (1.11).
20. Rendre lisibles les compteurs or (2.3) et les prix des vignettes (4.4).

**Lot 5, contenu éditorial à valider avec le client**

21. Adresse postale des CGV (8.4).
22. Moyens de paiement annoncés (8.5) et mention TGC (8.6).
23. Adresse e-mail de la page « Qui sommes-nous ? » (8.1) et lien vers la page
    Soirées (8.2).
24. Module d'avis produit : le garder ou le désactiver (5.9).
25. Boutons de partage X et Pinterest (5.8).
