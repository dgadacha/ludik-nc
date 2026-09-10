#!/usr/bin/env bash
#
# Compose les visuels d'attente du thème : fond crème, logo Ludik en filigrane
# et mention « Visuel à venir ».
#
# PrestaShop livre un « Aucune image disponible » en gris qui jure avec le
# reste du site. Ce script produit le même visuel pour les deux emplacements
# qui en ont besoin :
#   - les produits sans photo   -> img/p/fr-default-<format>.jpg
#   - les boutiques sans photo  -> img/st/<id>.jpg et <id>-stores_default.jpg
#
# Le script est rejouable : il écrase les fichiers existants.
#
# Usage : bash scripts/17-placeholders.sh
#
set -euo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOGO="$RACINE/import/logo-ludik.png"
SORTIE="$RACINE/import/placeholders"
CONTENEUR="ludik-ps"

CREME="#fdfbf4"
ENCRE="#b8ad8e"
POLICE="/System/Library/Fonts/Avenir.ttc"

mkdir -p "$SORTIE"

# composer <fichier> <largeur> <hauteur>
composer() {
  local fichier="$1" l="$2" h="$3"
  local cote=$(( l < h ? l : h ))
  # le logo occupe 55 % du plus petit côté, le texte est proportionnel
  local largeur_logo=$(( cote * 55 / 100 ))
  local corps=$(( cote / 18 ))
  [ "$corps" -lt 8 ] && corps=8
  local decalage_texte=$(( cote * 22 / 100 ))

  local travail
  travail="$(mktemp -d)"

  magick "$LOGO" -resize "${largeur_logo}x" -alpha set \
    -channel A -evaluate multiply 0.35 +channel "$travail/logo.png"

  # sous 120 px de côté, la mention devient illisible : on ne garde que le logo
  if [ "$cote" -ge 120 ]; then
    magick -background none -fill "$ENCRE" -font "$POLICE" -pointsize "$corps" \
      "label:Visuel à venir" -trim +repage "$travail/texte.png"
    magick -size "${l}x${h}" "xc:${CREME}" \
      "$travail/logo.png" -gravity center -geometry "+0-$(( cote / 22 ))" -composite \
      "$travail/texte.png" -gravity center -geometry "+0+${decalage_texte}" -composite \
      -strip -quality 88 "$SORTIE/$fichier"
  else
    magick -size "${l}x${h}" "xc:${CREME}" \
      "$travail/logo.png" -gravity center -composite \
      -strip -quality 88 "$SORTIE/$fichier"
  fi

  rm -rf "$travail"
  echo "  $fichier (${l}x${h})"
}

echo "Visuels d'attente des produits :"
composer "fr-default-small_default.jpg"  98  98
composer "fr-default-cart_default.jpg"  125 125
composer "fr-default-home_default.jpg"  236 236
composer "fr-default-medium_default.jpg" 452 452
composer "fr-default-large_default.jpg" 800 800
composer "fr-default-product_main.jpg"  800 800
composer "fr-default-default_xs.jpg"     98  98
composer "fr-default-default_sm.jpg"    161 161
composer "fr-default-default_md.jpg"    261 261
composer "fr-default-default_lg.jpg"    336 336
composer "fr-default-default_xl.jpg"    500 500
composer "fr.jpg"                       800 800

echo "Visuels d'attente des boutiques :"
composer "st-stores_default.jpg" 287 160
composer "st.jpg"                574 320

echo "Copie dans le conteneur $CONTENEUR :"
for f in fr-default-small_default fr-default-cart_default fr-default-home_default \
         fr-default-medium_default fr-default-large_default fr-default-product_main \
         fr-default-default_xs fr-default-default_sm fr-default-default_md \
         fr-default-default_lg fr-default-default_xl; do
  docker cp "$SORTIE/$f.jpg" "$CONTENEUR:/var/www/html/img/p/$f.jpg"
done
docker cp "$SORTIE/fr.jpg" "$CONTENEUR:/var/www/html/img/p/fr.jpg"
echo "  img/p/ : formats produits"

# Les boutiques n'ont pas de visuel générique : PrestaShop lit img/st/<id>.jpg.
# On pose donc le même visuel d'attente sur chaque boutique enregistrée.
IDS=$(docker exec "$CONTENEUR" php -r "
require_once '/var/www/html/config/config.inc.php';
foreach (Db::getInstance()->executeS('SELECT id_store FROM ' . _DB_PREFIX_ . 'store') as \$l) {
    echo (int) \$l['id_store'], ' ';
}")
for id in $IDS; do
  docker cp "$SORTIE/st.jpg" "$CONTENEUR:/var/www/html/img/st/$id.jpg"
  docker cp "$SORTIE/st-stores_default.jpg" "$CONTENEUR:/var/www/html/img/st/$id-stores_default.jpg"
  echo "  img/st/$id.jpg"
done

docker exec "$CONTENEUR" chown -R www-data:www-data /var/www/html/img/p /var/www/html/img/st
echo "Terminé."
