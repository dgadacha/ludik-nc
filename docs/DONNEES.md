# Provenance des données

Ce document distingue ce qui vient de l'ancien site de ce qui a été ajouté pour faire
fonctionner la boutique. Tout ce qui figure dans la seconde partie est à valider.

## Repris tel quel de ludik.nc

| Donnée | Source |
|---|---|
| Noms, prix, descriptions courtes, EAN13, images des 32 939 produits | pages catégories de l'ancien site |
| Arbre des 3 965 rayons et leurs libellés | fil d'Ariane des pages catégories |
| Disponibilité (« Disponible » / « Rupture de stock ») | mention affichée sur chaque vignette |
| Pages Qui sommes-nous, Commande et Livraison, Paiement, Comment choisir un jeu, CGV, Mentions légales | pages CMS de l'ancien site, remises en forme |
| LUDIK SARL, 107 rue Auguste Bénébig, Vallée des Colons, 98800 Nouméa, +687 46.55.46 | mentions légales et pied de page |
| Adresse Michel-Ange : 1 avenue Michel-Ange | page « A propos » |
| Horaires 8h30-18h30 du lundi au vendredi, 9h-18h30 le samedi, dimanche fermé | confirmés par le client ; l'ancien site annonçait 9h30, la fiche Google Business et le client disent 8h30 |
| librairie@ludik.nc et contact@ludik.nc | bandeau d'en-tête de l'ancien site |
| Soirée chaque mardi au Fronton Etchekhan (Amicale Basque, Motor Pool), 18h45 à 22h, entrée offerte en cas de retrait de commande | page « Commande et Livraison » et bandeau défilant |
| Quatre modes de livraison, moyens de paiement acceptés | page « Commande et Livraison » |
| Société créée en 2012, réseau WPN depuis 2017, expédition sous 24 h | page « A propos » et bandeau |
| Logo | fourni par le client |

## Ajouté pour faire tourner la boutique - à valider

| Donnée | Valeur posée | Pourquoi |
|---|---|---|
| Tarifs OPT Nouvelle-Calédonie | 500 / 900 / 1 500 / 2 500 / 4 500 F selon 5 tranches de poids | l'ancien site indiquait seulement « tarifs en fonction du poids » |
| Tarifs OPT international | 2 200 / 4 200 / 6 800 / 11 500 / 21 000 F | idem |
| Poids par produit | 0,45 kg pour tous | aucun poids n'était publié, et les tranches OPT en ont besoin |
| Quantité en stock | 5 pour les produits « Disponible », 0 pour les autres | l'ancien site ne publiait pas les quantités |
| Coordonnées GPS de Michel-Ange | approximatives | non publiées |
| Horaires de Michel-Ange | mêmes que Vallée des Colons | seuls les horaires d'un magasin étaient publiés |
| Téléphone de Michel-Ange | non renseigné | illisible sur le bandeau de l'ancien site |
| Pays desservis | NC, Polynésie, Wallis-et-Futuna, Vanuatu, Australie, Nouvelle-Zélande, France, Belgique, Suisse, Canada, Luxembourg, Monaco, DOM | l'ancien site parle du « Pacifique et des pays francophones » sans liste |
| Baselines de l'en-tête et du pied de page | reprises des maquettes fournies | à arbitrer entre les formulations des maquettes et celles du site |

## Choix d'affichage assumés

- La pastille « nouveauté » est masquée : tout le catalogue a été créé le même jour lors de
  la reprise, la date d'ajout n'a donc pas de sens tant qu'elle ne vient pas des ventes réelles.
- La mention de taxe est masquée (pas de TVA en Nouvelle-Calédonie).
- Le module PayPal (`ps_checkout`) est retiré du tunnel : il demande un compte marchand.
  L'ancien site proposait le paiement par carte ou PayPal - à rebrancher avec les
  identifiants du client.
- 855 produits n'ont pas de visuel : l'ancien site n'en proposait pas.
- Les boutons de partage de la fiche produit (module `ps_sharebuttons`) sont retirés :
  Ludik n'a de présence que sur Facebook, et ces liens sortants se trouvaient à
  l'endroit où le visiteur décide d'acheter. L'aperçu rapide des vignettes est retiré
  aussi : il ouvrait une fenêtre qui redonne ce que la fiche donne déjà, un clic plus
  loin. `scripts/26-retirer-partage.php` décroche le module, sans le désinstaller.
- La page « Promotions » est retirée du site, avec sa pastille dans la barre de
  navigation et son lien dans le pied de page : le catalogue repris ne porte
  aucune remise (`ps_specific_price` est vide), la page n'affichait que
  « Aucun produit pour le moment ». `scripts/25-retirer-promotions.php` fait le
  retrait et explique comment la rétablir si des remises apparaissent.

## Écarts constatés sur l'ancien site

- Coquille « contaact@ludik.nc » dans les mentions légales, corrigée à l'import.
- Rayon « Jeux pédagoqiques » : la faute vient du site, le libellé a été conservé pour ne
  pas s'écarter des données d'origine.

## Bannières du diaporama (mise à jour)

Les visuels du hero sont composés par `scripts/12-banners.sh` à partir des
**couvertures réelles du catalogue** (`import/img/<id>.jpg`, identifiants du site
d'origine), réduites en mosaïque, désaturées et fondues dans un dégradé. Aucun visuel
n'est inventé ni repris d'une banque d'images.

Ils ne portent **plus de texte incrusté**. Le titre, l'accroche et le libellé du bouton
de chaque campagne viennent des champs de la diapositive et sont donc modifiables dans
« Modules > Diaporama page d'accueil » :

| Champ du back-office | Rôle à l'écran |
|---|---|
| Titre | grand titre de la campagne |
| Légende | accroche sous le titre |
| Description | libellé du bouton |
| Lien | destination du bouton et de la campagne |

Les six campagnes créées par `scripts/12-slider.php` sont des exemples : leurs textes
reprennent les intitulés de rayon et les informations du site d'origine (licences
citées pour les cartes à collectionner, horaires de la soirée du mardi). Elles sont
destinées à être ajustées par le client.

## Textes de la maquette du client

La maquette fournie par le client apporte quelques formulations qui ne
figuraient pas sur le site d'origine. Elles sont de sa main, donc validées :

| Emplacement | Texte |
|---|---|
| Barre de service | « Votre boutique jeux et loisirs en Nouvelle-Calédonie » |
| Hero, accroche manuscrite | « Plus qu'une passion, un univers ! » |
| Hero, bande de repères | « Des milliers de références », « Conseils de passionnés », « En boutique et en ligne », « Livraison en Nouvelle-Calédonie » |
| Bas de page | « Fièrement calédonien » |

Les surtitres et titres des six campagnes du diaporama reprennent les intitulés
de rayon du catalogue et les licences réellement présentes en stock. Ils sont
modifiables en back-office.

## Photos des boutiques

Les deux photos de façade viennent du client (`import/boutiques/`). Elles sont
installées par `scripts/19-photos-boutiques.sh` dans `img/st/`, aux formats
attendus par PrestaShop, et sont donc remplaçables depuis
« Préférences > Magasins ».

## Visuels d'attente

Les produits et les magasins sans photo affichent un visuel composé par
`scripts/17-placeholders.sh` : fond crème, logo Ludik en filigrane et mention
« Visuel à venir ». Il remplace le « Aucune image disponible » gris livré par
PrestaShop.

## Typographie

Les tirets cadratins ont été remplacés par des traits d'union dans tout le
contenu, base comprise (`scripts/20-tirets.php`). C'est une normalisation
typographique : aucun texte n'a changé de sens.

## Ce que les données ne permettent pas

| Élément de la maquette | Pourquoi il n'y est pas |
|---|---|
| Pastille « Nouveau » sur les vignettes | le site d'origine ne donnait pas de date de mise en rayon ; les 32 939 produits portent la date d'import, la pastille serait sur tout le catalogue |
| Composition produit mise en scène dans le hero | pas de visuel de ce genre dans les sources ; les bannières sont des mosaïques de couvertures réelles |
| Section « Les incontournables » | aucune vente enregistrée : le bloc se remplira aux premières commandes |

## Coordonnées des boutiques

Le site d'origine ne donnait le téléphone que pour la Vallée des Colons. Celui
de Michel-Ange vient de la fiche Google Business de la boutique, qui confirme
au passage le premier :

| Boutique | Téléphone | Source |
|---|---|---|
| Ludik Vallée des Colons | 46 55 46 | site d'origine, confirmé par Google Business |
| Ludik Michel-Ange | 30 31 01 | fiche Google Business |

Elles sont posées par `scripts/23-coordonnees-boutiques.php` et restent
modifiables dans « Préférences > Magasins » : le thème lit la fiche magasin.

## Visuels de vitrine des rayons

La carte de chaque rayon montre un produit du rayon, choisi par le module
parmi une liste ordonnée de références emblématiques (constante `UNIVERS` de
`ludikhome`) : le premier candidat en stock gagne, et à défaut le module tire
un produit du rayon au hasard.

L'ordre de cette liste n'est pas arbitraire. Deux mesures le décident, prises
sur la déclinaison réellement affichée (`large_default`, 600 x 600) :

- le **remplissage**, part de l'image occupée par le produit : les packshots
  du catalogue portent leur propre marge blanche, très variable, et un produit
  photographié de loin paraît minuscule à côté d'un autre cadré serré ;
- la **clarté**, luminosité moyenne de l'image : les cartes sont blanches, et
  un produit sombre y creuse un trou. C'est ce qui a fait écarter le coffret
  dresseur d'élite, un boîtier noir, du rayon des cartes à collectionner.

| Rayon | Visuel retenu | Remplissage | Clarté | Replis, dans l'ordre |
|---|---|---|---|---|
| Jeux de société | 7 Wonders Duel | 51 % | 70 % | Carcassonne 38 / 79, Dixit 15 / 92, Catan 10 / 92 |
| Cartes à collectionner | Coffret Académie de Combat | 39 % | 82 % | Pack 3 boosters 53 / 71, Starter 58 / 43 |
| Librairie | Naruto tome 1 | 42 % | 79 % | One Piece 37 / 80, Astérix 43 / 72 |
| Jeux de rôle | Pathfinder Unchained | 43 % | 71 % | Appel de Cthulhu 39 / 69 |
| Accessoires | Protèges cartes | 41 % | 74 % | Tapis de jeu, aucun en stock |
| Loisirs créatifs | Art Origami | 56 % | 86 % | Speedpaint 47 / 91, Book Nook aucun en stock |

Le rayon des cartes à collectionner est le seul où le visuel retenu n'a pas le
meilleur remplissage : le coffret Académie de Combat est nettement plus clair
que le pack de boosters, et une boîte jaune se lit mieux sur une carte blanche
qu'un blister gris.

Si le catalogue change, remesurer les deux critères :

```bash
magick <image> -fuzz 6% -format '%@' info:          # boîte du produit
magick <image> -resize 1x1! -format '%[fx:mean]' info:   # clarté
```

