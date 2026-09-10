#!/bin/sh
# Vide les caches du thème et force le navigateur à recharger les assets.
#
# Trois choses à faire, et pas deux :
#   1. var/cache            : Smarty et le conteneur Symfony ;
#   2. themes/*/assets/cache : les fichiers CSS et JS combinés ;
#   3. PS_CCCCSS_VERSION    : le numéro qui entre dans le nom du fichier
#      combiné. Sans lui, le nom ne change pas et le navigateur sert
#      indéfiniment l'ancienne feuille - la modification reste invisible.
#
# Les droits sont rendus à www-data : le cache supprimé depuis l'hôte est
# recréé par le conteneur, qui tourne sous un autre utilisateur.
set -e
rm -rf /var/www/html/var/cache/* 2>/dev/null || true
rm -rf /var/www/html/themes/ludik/assets/cache/* 2>/dev/null || true
php /scripts/cc.php
chown -R www-data:www-data /var/www/html/var /var/www/html/themes/ludik/assets
echo "caches vidés"
