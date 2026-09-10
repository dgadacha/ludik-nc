#!/bin/sh
# Recopie les assets compilés de Hummingbird dans le thème enfant Ludik.
# Le thème enfant n'embarque que custom.css, les polices Outfit et ses templates ;
# le reste (theme.css, JS, images, icônes) vient du parent et n'est pas versionné.
set -e
SRC=/var/www/html/themes/hummingbird/assets
DST=/var/www/html/themes/ludik/assets
mkdir -p "$DST/css" "$DST/js" "$DST/fonts" "$DST/img-dist"
for f in theme.css theme_rtl.css rtl.css error.css error_rtl.css; do
  [ -f "$SRC/css/$f" ] && cp "$SRC/css/$f" "$DST/css/$f"
done
cp -r "$SRC/js/." "$DST/js/" 2>/dev/null || true
cp -r "$SRC/img-dist/." "$DST/img-dist/" 2>/dev/null || true
# polices du parent (Inter, icônes Material) sans écraser les nôtres
for f in "$SRC"/fonts/*; do
  b=$(basename "$f")
  [ -f "$DST/fonts/$b" ] || cp "$f" "$DST/fonts/$b"
done
[ -f /var/www/html/themes/hummingbird/preview.png ] && cp /var/www/html/themes/hummingbird/preview.png /var/www/html/themes/ludik/preview.png
echo "assets du thème Ludik synchronisés"
