# Ludik.nc - refonte de la boutique

Reprise de [ludik.nc](http://www.ludik.nc/), boutique de jeux de société et librairie à
Nouméa, aujourd'hui sur PrestaShop 1.6. Le catalogue a été recollecté sur le site en
ligne puis réimporté dans **PrestaShop 9.1.5**, avec un thème enfant écrit pour
l'occasion.

Le dépôt contient la recette complète : la pile Docker, les scripts de collecte et
d'import, le thème et sa documentation. Les données de l'ancien site (catalogue et
visuels) ne sont pas versionnées, elles se reconstituent avec les scripts.

Ce qui est vérifiable sur le site en ligne au 10 septembre 2026 : il sert le thème
`default-bootstrap`, celui livré par défaut avec PrestaShop 1.6, dont la branche n'est
plus maintenue. La refonte tourne sur PrestaShop 9.1.5 en PHP 8.3, avec un thème écrit
pour cette boutique.

## Prérequis

- Docker et Docker Compose
- ImageMagick sur l'hôte, pour les trois scripts qui composent des visuels
- Python 3 avec `lxml`, uniquement pour recollecter le catalogue

## Démarrage

```bash
docker compose up -d
```

| Service | Adresse | Identifiants |
|---|---|---|
| Boutique | http://localhost:8801 | - |
| Back-office | http://localhost:8801/admin-ludik | `admin@ludik.nc` / `LudikAdmin2026!` |
| phpMyAdmin | http://localhost:8802 | `ludik` / `ludik` |
| MariaDB | `localhost:3307` | base `ludik`, utilisateur `ludik` / `ludik` |

Ces identifiants sont ceux d'une pile de développement, liée à `localhost` et sans
données réelles. Avant de déployer cette boutique ailleurs que sur un poste, les sortir
du `docker-compose.yml` et changer le mot de passe du back-office.

Arrêt : `docker compose stop`. Suppression complète, base et images comprises :
`docker compose down -v`.

Le back-office refuse la connexion tant que le dossier d'installation de PrestaShop est
en place. Après une installation neuve, le mettre de côté, en le renommant plutôt qu'en
le supprimant :

```bash
docker compose exec prestashop mv /var/www/html/install /var/www/html/install-hors-service
```

## Installation depuis une base vide

Les scripts sont montés sur `/scripts` dans le conteneur. Ils s'exécutent dans cet
ordre ; trois d'entre eux tournent sur l'hôte parce qu'ils appellent ImageMagick.

```bash
docker exec ludik-ps php /scripts/00-shop-config.php            # devise, pays, magasins
docker exec ludik-ps php /scripts/03-reset-catalog.php --yes     # vide le catalogue de démo
docker exec ludik-ps php /scripts/01-import-categories.php       # arbre des rayons
docker exec ludik-ps php /scripts/02-import-products.php         # produits et visuels
docker exec ludik-ps sh  /scripts/06-theme-assets.sh             # assets du thème parent
docker exec ludik-ps php /scripts/04-carriers-payments.php       # livraisons et paiements
docker exec ludik-ps php /scripts/05-cms-pages.php               # pages éditoriales
docker exec ludik-ps php /scripts/07-home-blocks.php             # page d'accueil
docker exec ludik-ps php /scripts/09-menu.php                    # menu et pied de page
docker exec ludik-ps php /scripts/10-textes-fr.php               # textes et réglages
docker exec ludik-ps php /scripts/11-header-hooks.php            # emplacements de l'en-tête
bash scripts/12-banners.sh                                       # bannières du diaporama
docker exec ludik-ps php /scripts/12-slider.php                  # diaporama page d'accueil
docker exec ludik-ps php /scripts/13-correctifs-fonctionnels.php # panier, transporteurs, paiements
docker exec ludik-ps php /scripts/14-index-recherche.php         # index de recherche
docker exec ludik-ps php /scripts/15-index-facettes.php          # index des facettes
docker exec ludik-ps php /scripts/16-textes-pages.php            # intitulés de pages
bash scripts/17-placeholders.sh                                  # visuels d'attente
docker exec ludik-ps php /scripts/18-accueil-sections.php        # sections de l'accueil
bash scripts/19-photos-boutiques.sh                              # photos des boutiques
docker exec ludik-ps php /scripts/20-tirets.php                  # typographie des tirets
docker exec ludik-ps php /scripts/21-pied-de-page.php            # colonnes du pied de page
docker exec ludik-ps php /scripts/22-purge-demo.php --yes        # commandes de démonstration
docker exec ludik-ps php /scripts/23-coordonnees-boutiques.php   # téléphones des boutiques
docker exec ludik-ps php /scripts/24-horaires-boutiques.php      # horaires des boutiques
docker exec ludik-ps php /scripts/25-retirer-promotions.php      # retrait de la page Promotions
docker exec ludik-ps php /scripts/26-retirer-partage.php         # retrait des boutons de partage
```

Les vignettes se génèrent ensuite en parallèle, environ trois minutes :

```bash
docker exec ludik-ps sh -c 'for i in 0 1 2 3 4 5 6 7; do php /scripts/08-thumbnails.php $i 8 & done; wait'
```

`02-import-products.php` reprend là où il s'est arrêté : il relit `import/map-products.json`.

## Démo statique déployable

`tools/demo/build-demo.py` aspire la boutique locale et produit un dossier
`dist/` de fichiers statiques, à déposer sur n'importe quel hébergeur de
fichiers. C'est ce qui permet de montrer la refonte par un lien, sans PHP ni
base de données.

```bash
python3 tools/demo/build-demo.py            # dist/, environ 68 Mo
python3 tools/demo/serve-demo.py            # contrôle sur http://localhost:8803
npx vercel deploy --prod dist
```

Contenu : l'accueil, les six rayons avec leur première page de produits, 246
fiches produit dont une sélection de licences (Pokémon, Naruto, One Piece,
Magic, Lego, Dixit, Catan, Astérix, Cthulhu, Origami), les sept pages
éditoriales, les magasins, le plan du site, la connexion et le panier.

Ce qui reste cliquable, grâce à `tools/demo/demo.js` : la navigation, le tiroir
des rayons, le diaporama de campagnes, les rangées défilantes, les suggestions
de recherche, la page de résultats et l'ajout au panier avec son compteur et
son message. La recherche et le panier travaillent sur `demo-index.json`, écrit
à la fabrication, et le panier vit dans le navigateur du visiteur.

Ce qui ne peut pas l'être, faute de serveur : les filtres à facettes, le tri,
la pagination, le tunnel de commande, le compte client et le formulaire de
contact. Ces commandes affichent un message au clic plutôt que de ne rien
faire. Le module favoris interroge son API en GraphQL et se plaint dans la
console du navigateur, sans conséquence à l'écran.

Deux réglages tiennent la démo debout :

- `vercel.json` active `cleanUrls`, qui sert `/4-jeux-de-societe` depuis
  `4-jeux-de-societe.html`. Sans lui, les adresses sans extension seraient
  téléchargées au lieu d'être affichées.
- les liens vers les 3 965 catégories du catalogue, dont la démo ne reprend que
  six, sont marqués à la fabrication et interceptés au clic. Le visiteur reste
  dans la démo au lieu de tomber sur une page d'erreur.

Pour une démo complète, filtres et commande comprises, il faut un serveur :
tunnel Cloudflare depuis la machine de développement, ou un petit VPS avec le
`docker-compose.yml` de ce dépôt.

## Recollecter le catalogue

`import/` n'est pas versionné : 32 000 visuels et 16 Mo de JSON. Les scripts de collecte
sont dans `tools/scrape/` et sont documentés là-bas. Trois étapes : parcourir les
catégories du site en ligne, relever le libellé exact de chaque rayon dans le fil
d'Ariane, télécharger les visuels. `normalize.py` assemble ensuite les fichiers attendus
par les scripts d'import, en reconstruisant la hiérarchie des rayons.

Compter environ 25 minutes pour les pages de catégories et 20 minutes pour les visuels.

## Ce qui est en place

- **32 939 produits** et **3 965 catégories** sur sept niveaux, avec prix, description
  courte, code EAN13 et visuel de couverture. 19 491 références en stock, 855 sans
  visuel parce que le site en ligne n'en proposait pas.
  La collecte s'appuie sur les pages de rayon, qui n'exposent pas la description longue :
  27 623 produits ont une description courte, 267 une description longue. Récupérer les
  fiches complètes demande de parcourir les 32 939 pages produit.
- **Devise XPF** sans décimale, pays par défaut Nouvelle-Calédonie, taxes désactivées.
- **Deux magasins** avec adresses, téléphones et horaires groupés, sur `/magasins`.
- **Cinq modes de livraison** : retrait dans chacune des deux boutiques, retrait à la
  soirée jeux du mardi, colis OPT Nouvelle-Calédonie, colis OPT international.
- **Trois moyens de paiement** : chèque, virement bancaire, paiement au retrait. Le
  paiement par carte reste à brancher avec le prestataire du client.
- **Sept pages éditoriales** reprises de l'ancien site : qui sommes-nous, soirées Ludik,
  commande et livraison, paiement, comment choisir un jeu, CGV, mentions légales.
- **Recherche et filtres** : index de recherche reconstruit, filtres à facettes avec
  curseur de prix au format XPF.
- **Thème enfant `ludik`** sur Hummingbird, bâti sur un système de jetons documenté :
  un seul fichier CSS en sept sections, icônes Lucide en sprite local, polices
  embarquées, aucune dépendance externe à l'affichage.
- **Diaporama de campagnes administrable** depuis le back-office, texte hors de l'image
  pour rester lisible et modifiable.

## Structure du dépôt

```
docker-compose.yml     PrestaShop 9.1.5, MariaDB 11.4, phpMyAdmin
theme/ludik/           thème enfant, monté dans le conteneur
modules/ludikhome/     blocs maison de la page d'accueil
scripts/               installation, import et maintenance, montés sur /scripts
tools/scrape/          collecte du catalogue de l'ancien site
tools/demo/            fabrication de la démo statique déployable
import/                données de reprise, hors dépôt
docs/                  système de design, provenance des données, audit de la refonte
```

## Documentation

- `docs/DESIGN.md` : jetons, composants, règles de composition, paliers responsive,
  ordre d'empilement et les pièges du thème parent.
- `docs/DONNEES.md` : ce qui vient du site d'origine, ce qui a été ajouté pour faire
  tourner la boutique, et ce qui reste à valider avec le client.
- `docs/AUDIT-PAGES.md` : relevé page par page de cette refonte, 24 pages parcourues à
  1440 px et à 390 px, constats classés par gravité.

## Travailler sur le thème

Deux règles pour ne pas défaire le système de design : toute couleur, taille ou
espacement passe par un jeton CSS, et un composant existant se réutilise plutôt qu'on ne
le redéfinit sur place. Le CSS tient dans un seul fichier,
`theme/ludik/assets/css/custom.css`, découpé en sept sections numérotées.

Vider les caches après une modification. Il y en a trois, et pas un seul : Smarty, les
fichiers CSS et JS combinés, et le numéro de version qui entre dans leur nom. Sans ce
dernier, le navigateur sert indéfiniment l'ancienne feuille et la modification reste
invisible. Le script fait les trois et rend la main à `www-data` :

```bash
docker exec ludik-ps sh /scripts/cc.sh
```

Pendant le travail sur le thème, on peut couper la combinaison CSS et JS pour voir
`custom.css` servi tel quel, puis la remettre avant une mise en production :

```bash
docker exec ludik-ps php /scripts/dev-ccc.php off
```

## Points à valider avec le client

Tarifs OPT et poids des colis, quantités en stock, prestataire de paiement par carte,
photos des deux boutiques et d'une soirée jeux. Le détail est dans `docs/DONNEES.md`.
