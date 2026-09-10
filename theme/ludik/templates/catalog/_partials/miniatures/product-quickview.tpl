{**
 * Aperçu rapide : retiré du site.
 *
 * La vignette n'inclut plus ce gabarit, mais celui du thème parent l'inclut
 * encore depuis ses propres rendus de vignette. Le fichier reste donc en place
 * avec ses deux blocs vides : sans lui, c'est la version du parent qui
 * ressortirait, avec ses deux boutons et ses libellés anglais.
 *
 * Pour le remettre : rétablir l'include dans miniatures/product.tpl et le bloc
 * .product-miniature__quickview dans custom.css (voir l'historique git).
 *}
{block name='quick_view'}{/block}
{block name='quick_view_touch'}{/block}
