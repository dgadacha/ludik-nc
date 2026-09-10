#!/usr/bin/env bash
#
# Installe les photos des deux boutiques.
#
# PrestaShop attend, pour chaque magasin, img/st/<id>.jpg et une déclinaison
# <id>-stores_default.jpg au format déclaré dans « Images ». Les sources
# fournies par le client sont dans import/boutiques/, nommées <id>-<nom>.webp.
#
# Le recadrage est centré sur la façade : les sources sont en portrait ou en
# paysage, la vignette du site est en 287 x 160.
#
# Le script est rejouable.
#
# Usage : bash scripts/19-photos-boutiques.sh
#
set -euo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SOURCES="$RACINE/import/boutiques"
CONTENEUR="ludik-ps"

# Deux tailles, deux usages :
#   - img/st/<id>.jpg : la photo affichée en carte sur l'accueil, dans une
#     zone en portrait d'environ 192 x 280 px. On la produit à 800 x 1160
#     pour rester nette en densité double ; à 574 x 320 elle était étirée
#     verticalement, donc floue.
#   - img/st/<id>-stores_default.jpg : la vignette du format « stores_default »
#     de PrestaShop, utilisée par la page « Nos magasins ».
LARGEUR_CARTE=800
HAUTEUR_CARTE=1160
LARGEUR=287
HAUTEUR=160

echo "Photos des boutiques :"
for source in "$SOURCES"/*.webp; do
  [ -f "$source" ] || continue
  base="$(basename "$source")"
  id="${base%%-*}"
  travail="$(mktemp -d)"

  magick "$source" -resize "${LARGEUR_CARTE}x${HAUTEUR_CARTE}^" -gravity center \
    -extent "${LARGEUR_CARTE}x${HAUTEUR_CARTE}" -unsharp 0x0.75+0.6+0.02 \
    -strip -quality 88 "$travail/$id.jpg"
  magick "$source" -resize "${LARGEUR}x${HAUTEUR}^" -gravity center \
    -extent "${LARGEUR}x${HAUTEUR}" -strip -quality 88 "$travail/$id-stores_default.jpg"

  docker cp "$travail/$id.jpg" "$CONTENEUR:/var/www/html/img/st/$id.jpg"
  docker cp "$travail/$id-stores_default.jpg" "$CONTENEUR:/var/www/html/img/st/$id-stores_default.jpg"
  echo "  boutique $id : $base"
  rm -rf "$travail"
done

docker exec "$CONTENEUR" chown -R www-data:www-data /var/www/html/img/st
echo "Terminé."
