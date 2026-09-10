{**
 * Barre d'outils de liste : tri et ouverture du tiroir de filtres.
 *
 * Le bouton « Filtrer » n'a de sens que sous 1200 px : au-delà, la colonne de
 * rayon est affichée en permanence.
 *}
<div id="js-product-list-top" class="products-top">
  <div class="products__selection">
    {* le nombre de produits est annoncé par l'en-tête de rayon : le répéter ici
       faisait deux fois la même information à trois centimètres d'écart *}
    <div class="products__sort">
      {block name='sort_by'}
        {include file='catalog/_partials/sort-orders.tpl' sort_orders=$listing.sort_orders}
      {/block}

      {if !empty($listing.rendered_facets) && !isset($page.body_classes['layout-full-width'])}
        <button id="search_filter_toggler" type="button"
                class="products__filter-button lud-btn lud-btn--ghost d-xl-none js-search-toggler"
                data-bs-toggle="offcanvas" data-bs-target="#left-column"
                aria-controls="left-column">
          <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-sliders-horizontal"></use></svg>
          Filtrer
        </button>
      {/if}
    </div>
  </div>
</div>
