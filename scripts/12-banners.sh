#!/usr/bin/env bash
#
# Compose les bannières du diaporama d'accueil : des visuels, et rien d'autre.
#
# La version précédente incrustait le titre, l'accroche et le bouton dans le
# JPEG. Ce texte n'était ni traduisible, ni sélectionnable, ni lisible par un
# lecteur d'écran, et le client ne pouvait pas le modifier depuis le back-office.
# Désormais l'image ne porte que la matière visuelle ; le titre, l'accroche et
# le bouton sont produits par le gabarit à partir des champs de la diapositive,
# donc administrables.
#
# Composition : mosaïque de couvertures réelles du rayon, désaturée et fondue
# dans un dégradé vert pétrole, avec un voile plus dense à gauche pour que le
# texte du gabarit garde son contraste.
#
# Les identifiants de visuels sont ceux du catalogue d'origine
# (import/img/<id>.jpg), choisis parmi les produits en stock de chaque rayon.
#
# Usage : bash scripts/12-banners.sh
#
set -euo pipefail

RACINE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOSSIER_VISUELS="$RACINE/import/img"
DOSSIER_SORTIE="$RACINE/import/banners"
CONTENEUR="ludik-ps"
DOSSIER_MODULE="/var/www/html/modules/ps_imageslider/images"

# 1440 x 480 en densité double : le hero mesure 420 à 480 px de haut sur écran large
LARGEUR=2880
HAUTEUR=960
VERT_CLAIR="#0a4f4a"
VERT_FONCE="#043330"
OPACITE_MOSAIQUE=0.60

mkdir -p "$DOSSIER_SORTIE"

# composer_banniere <fichier> <teinte claire> <teinte foncee> <id...>
composer_banniere() {
  local fichier="$1" clair="$2" fonce="$3"
  shift 3
  local ids=("$@")
  local travail
  travail="$(mktemp -d)"

  # 1. Fond : dégradé diagonal, teinte du rayon.
  magick -size "${HAUTEUR}x${LARGEUR}" "gradient:${clair}-${fonce}" \
    -rotate -90 "$travail/fond.png"

  # 2. Mosaïque : chaque couverture recadrée en carré de 360 px, grille 8 x 3.
  #    Une grille dense vaut mieux qu'une grille de grandes cases : à l'écran,
  #    des visuels de 240 px de côté se lisent comme une matière, pas comme
  #    quatre boîtes posées derrière le texte.
  local n=0 tuiles=()
  for id in "${ids[@]}"; do
    n=$((n + 1))
    local tuile="$travail/tuile_$(printf '%02d' "$n").png"
    magick "$DOSSIER_VISUELS/$id.jpg" \
      -resize "360x360^" -gravity center -extent 360x360 "$tuile"
    tuiles+=("$tuile")
  done
  # vingt-quatre cases pour douze visuels : la seconde moitié de la grille
  # reprend les mêmes tuiles dans un ordre décalé, ce qui évite les répétitions
  # côte à côte.
  local grille=("${tuiles[@]}" "${tuiles[@]:5}" "${tuiles[@]:0:5}")
  magick "${grille[@]:0:8}"  +append "$travail/rangee_1.png"
  magick "${grille[@]:8:8}"  +append "$travail/rangee_2.png"
  magick "${grille[@]:16:8}" +append "$travail/rangee_3.png"
  magick "$travail/rangee_1.png" "$travail/rangee_2.png" "$travail/rangee_3.png" \
    -append "$travail/mosaique.png"
  magick "$travail/mosaique.png" -resize "${LARGEUR}x${HAUTEUR}^" \
    -gravity center -extent "${LARGEUR}x${HAUTEUR}" "$travail/mosaique_pleine.png"

  # 3. Masque de fondu horizontal : la moitié gauche s'efface pour laisser
  #    respirer le titre, la droite reste visible.
  local zone_texte=$((LARGEUR * 38 / 100))
  local zone_fondu=$((LARGEUR * 22 / 100))
  local zone_pleine=$((LARGEUR - zone_texte - zone_fondu))
  magick \
    \( -size "${zone_texte}x${HAUTEUR}" xc:black \) \
    \( -size "${HAUTEUR}x${zone_fondu}" gradient:white-black -rotate 90 \) \
    \( -size "${zone_pleine}x${HAUTEUR}" xc:white \) \
    +append "$travail/masque.png"

  # 4. Filigrane : le masque devient la couche alpha, la mosaïque est désaturée
  #    puis fondue en incrustation pour se teinter du vert du fond.
  magick "$travail/mosaique_pleine.png" -alpha set -modulate 100,70 \
    \( "$travail/masque.png" -colorspace gray \) \
    -compose CopyOpacity -composite \
    -channel A -evaluate multiply "$OPACITE_MOSAIQUE" +channel \
    "$travail/filigrane.png"

  magick "$travail/fond.png" "$travail/filigrane.png" -compose Overlay -composite \
    -strip -quality 86 "$DOSSIER_SORTIE/$fichier"

  rm -rf "$travail"
  echo "  $DOSSIER_SORTIE/$fichier"
}

echo "Composition des bannières (${LARGEUR} x ${HAUTEUR} px, sans texte incrusté) :"

# Les teintes restent dans la famille du vert Ludik : l'accent d'univers passe
# par les badges et les boutons du gabarit, pas par un fond de couleur franche.
composer_banniere "ludik-cartes-a-collectionner.jpg" "#0d4a63" "#062f3f" \
  50852 60009 46778 51981 46899 46905 54735 57103 46889 54732 61376 47669

composer_banniere "ludik-jeux-de-societe.jpg" "#0a4f4a" "#043330" \
  7842 22654 26198 20410 26309 23175 58189 54963 57573 11027 44120 19308

composer_banniere "ludik-librairie.jpg" "#5c2a45" "#33162a" \
  1052 58209 19573 68522 58635 61840 62135 51751 66988 62393 67270 56630

composer_banniere "ludik-jeux-de-role.jpg" "#3b2a5c" "#211736" \
  10032 50612 36447 30031 43782 36499 36508 39933 43735 55284 11518 43734

composer_banniere "ludik-loisirs-creatifs.jpg" "#0f5340" "#053324" \
  58168 54826 54853 54836 60614 62774 60576 60581 60721 60875 60800 60708

# Nouveautés et soirée jeux : visuels puisés dans l'ensemble du catalogue.
composer_banniere "ludik-nouveautes.jpg" "#0a4f4a" "#043330" \
  2349 15838 30241 50517 14511 56048 5099 46054 58209 22654 51981 36447

composer_banniere "ludik-soirees-jeux.jpg" "#6b4413" "#3a2408" \
  46792 13658 37419 44078 14275 7764 790 51147 26198 23175 11027 19308

# Fond de la bannière événementielle du bas de page.
#
# Si une photo de soirée est fournie dans import/photos/soiree-jeux.jpg, c'est
# elle qui est utilisée : rien ne vaut une vraie photo de table de jeux.
# Sinon, on compose un fond de repli à partir des couvertures du catalogue,
# fortement flouté et assombri : une mosaïque nette derrière un texte se lit
# comme un damier, pas comme une ambiance.
composer_fond_editorial() {
  local fichier="$1"
  shift
  local ids=("$@")
  local travail
  travail="$(mktemp -d)"

  # une vraie photo prend le pas sur le repli
  local photo="$RACINE/import/photos/soiree-jeux.jpg"
  if [ -f "$photo" ]; then
    magick "$photo" -resize "2400x700^" -gravity center -extent 2400x700 \
      -modulate 78,110 -strip -quality 88 "$DOSSIER_SORTIE/$fichier"
    rm -rf "$travail"
    echo "  $DOSSIER_SORTIE/$fichier (photo fournie)"
    return
  fi

  local n=0 tuiles=()
  for id in "${ids[@]}"; do
    n=$((n + 1))
    local tuile="$travail/t_$(printf '%02d' "$n").png"
    magick "$DOSSIER_VISUELS/$id.jpg" \
      -resize "300x300^" -gravity center -extent 300x300 "$tuile"
    tuiles+=("$tuile")
  done

  local grille=("${tuiles[@]}" "${tuiles[@]:4}" "${tuiles[@]:0:4}")
  magick "${grille[@]:0:8}"  +append "$travail/r1.png"
  magick "${grille[@]:8:8}"  +append "$travail/r2.png"
  magick "${grille[@]:16:8}" +append "$travail/r3.png"
  magick "$travail/r1.png" "$travail/r2.png" "$travail/r3.png" -append "$travail/mosaique.png"

  # Flou marqué et forte baisse de luminosité : on ne cherche pas à montrer
  # des boîtes mais à donner une matière colorée derrière le texte.
  magick "$travail/mosaique.png" -resize "2400x700^" -gravity center \
    -extent 2400x700 -blur 0x28 -modulate 58,130 \
    -strip -quality 88 "$DOSSIER_SORTIE/$fichier"

  rm -rf "$travail"
  echo "  $DOSSIER_SORTIE/$fichier (2400 x 700, repli flouté)"
}

echo "Fond de la bannière soirée :"
composer_fond_editorial "ludik-fond-soiree.jpg" \
  7842 22654 26198 20410 26309 23175 58189 54963 57573 11027 44120 19308

echo "Copie dans le conteneur $CONTENEUR :"
for f in "$DOSSIER_SORTIE"/ludik-*.jpg; do
  case "$(basename "$f")" in
    ludik-fond-*) continue ;;
  esac
  docker cp "$f" "$CONTENEUR:$DOSSIER_MODULE/$(basename "$f")"
  echo "  $DOSSIER_MODULE/$(basename "$f")"
done
docker exec "$CONTENEUR" chown -R www-data:www-data "$DOSSIER_MODULE"
docker exec "$CONTENEUR" sh -c "chmod 644 $DOSSIER_MODULE/ludik-*.jpg"

# Le fond de la bannière soirée appartient au thème : c'est une image de
# décor, pas une diapositive administrable.
cp "$DOSSIER_SORTIE/ludik-fond-soiree.jpg" "$RACINE/theme/ludik/assets/img/soiree-jeux.jpg"
echo "  theme/ludik/assets/img/soiree-jeux.jpg"

echo "Terminé."
