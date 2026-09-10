{**
 * Produits du même rayon, sous la fiche produit.
 *
 * Le titre d'origine annonçait un décompte - « 8 autres produits dans la même
 * catégorie » - qui décrit le mécanisme plutôt que l'intérêt du lecteur.
 *}
{extends file="components/module-products.tpl"}

{block name='module_products_variables'}
  {assign var="need_container" value=false}
{/block}

{block name='module_products_name'}ps-categoryproducts{/block}

{block name='module_products_title'}
  <div class="ludik-section-head">
    <div>
      <h2>Vous aimerez aussi</h2>
      <p>D'autres articles du même rayon.</p>
    </div>
  </div>
{/block}
