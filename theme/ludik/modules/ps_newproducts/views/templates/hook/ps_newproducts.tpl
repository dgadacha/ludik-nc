{* Surcharge Ludik : intitulé français, bandeau de section et lien à droite. *}

{extends file="components/module-products.tpl"}

{block name='module_products_name'}ps-newproducts{/block}

{block name='module_products_title'}
  <div class="ludik-section-head">
    <div>
      <h2>Nos nouveautés</h2>
      <p>Les dernières sorties et les arrivages de la semaine, jeux comme librairie.</p>
    </div>
    <a href="{$allNewProductsLink}">
      Voir toutes les nouveautés
      <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
    </a>
  </div>
{/block}

{block name='module_products_footer'}{/block}
