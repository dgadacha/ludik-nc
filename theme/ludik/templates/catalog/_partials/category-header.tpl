{**
 * En-tête de rayon : nom, nombre de produits, description.
 *
 * Ce gabarit est la seule source de l'en-tête. PrestaShop le rend deux fois :
 * au chargement de la page, puis à chaque filtrage, quand le module de
 * navigation à facettes remplace #js-product-list-header. Avant, l'en-tête du
 * thème changeait d'aspect après le premier filtre, parce que le rendu initial
 * venait du gabarit de liste et le rendu filtré de celui-ci.
 *
 * Les sous-rayons ne sont plus repris en pastilles : la colonne de gauche les
 * présente déjà, et certains rayons en comptent quatre-vingt-dix.
 *}
<div id="js-product-list-header">
  <div class="category__header">
    <div class="category__header-main">
      {include file='components/page-title-section.tpl'
               title=$category.name|default:$listing.label}

      {if isset($listing.pagination.total_items)}
        <p class="category__count">
          {if $listing.pagination.total_items > 1}
            <strong>{$listing.pagination.total_items|number_format:0:',':' '}</strong> produits
          {elseif $listing.pagination.total_items == 1}
            <strong>1</strong> produit
          {else}
            Aucun produit pour le moment
          {/if}
          {if isset($subcategories) && $subcategories|@count > 0}
            <span class="category__count-sep">·</span>
            {if $subcategories|@count == 1}
              1 sous-rayon
            {else}
              {$subcategories|@count} sous-rayons
            {/if}
          {/if}
        </p>
      {/if}

      {if !empty($category.description)}
        <div class="category__description rich-text">{$category.description nofilter}</div>
      {/if}
    </div>
  </div>
</div>
