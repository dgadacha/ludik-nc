# Collecte du catalogue de l'ancien site

Trois étapes, dans cet ordre.

1. `crawl.py` - parcourt les catégories de www.ludik.nc à partir du menu, suit les pages
   d'index (La BD spécialisée, Le Manga, Le Roman Jeunesse…) et enregistre pour chaque
   catégorie ses produits. Sortie : `cats_full.json`, `products.json`.
2. `labels.py` - reprend le libellé exact de chaque rayon dans le fil d'Ariane, les
   balises `<title>` de l'ancien site étant remplies de mots-clés.
3. `images.py` - télécharge le visuel de chaque produit (format `large_default`, 458 px)
   et le recompresse à 600 px de côté dans `import/img/<id>.jpg`.

Puis `normalize.py` assemble `import/categories.json` et `import/products.json` pour les
scripts d'import PrestaShop : il reconstruit la hiérarchie des rayons (l'ancien site
rattachait près de 1 700 séries à plat sous « Librairie ») et écarte les catégories vides.

Le crawl respecte un parallélisme de 8 requêtes. Compter environ 25 minutes pour les
4 000 pages de catégories et 20 minutes pour les 32 000 visuels.
