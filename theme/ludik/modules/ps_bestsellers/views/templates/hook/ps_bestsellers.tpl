{* Surcharge Ludik : intitulé français, bandeau de section et lien à droite. *}

{extends file="components/module-products.tpl"}

{block name='module_products_name'}ps-bestsellers{/block}

{block name='module_products_title'}
  <div class="ludik-section-head">
    <div>
      <h2>Les incontournables</h2>
      <p>Les valeurs sûres du rayon, celles qui ressortent chaque semaine.</p>
    </div>
    <a href="{$allBestSellers}">
      Voir tout le classement
      <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
    </a>
  </div>
{/block}

{block name='module_products_footer'}{/block}
