{* Surcharge Ludik : intitulés français et bandeau de section maison. *}

{extends file="components/module-products.tpl"}

{block name='module_products_name'}ps-featuredproducts{/block}

{block name='module_products_title'}
  <div class="ludik-section-head">
    <div>
      <h2>Notre sélection</h2>
      <p>Ce que l'équipe met en avant en boutique en ce moment.</p>
    </div>
  </div>
{/block}

{block name='module_products_footer'}
  <a class="btn btn-outline-primary ludik-more" href="{$allProductsLink}">
    Voir le catalogue
  </a>
{/block}
