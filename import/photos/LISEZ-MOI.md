# Photos fournies par le client

Déposez ici les photos que le thème sait utiliser, puis relancez le script
indiqué. Les fichiers de ce dossier ne sont pas versionnés.

| Fichier attendu | Où elle apparaît | Script à relancer |
|---|---|---|
| `soiree-jeux.jpg` | fond de la bannière « Les soirées jeux de société », sur la page d'accueil | `bash scripts/12-banners.sh` |

Format conseillé pour `soiree-jeux.jpg` : paysage, au moins 2400 x 700 px, une
table de jeu en situation. Le script la recadre, l'assombrit légèrement et la
place derrière le dégradé vert de la bannière.

Sans cette photo, le script compose un fond de repli à partir des couvertures
du catalogue, flouté et assombri.
