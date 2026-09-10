{**
 * Rangée de produits d'un module (nouveautés, meilleures ventes, produits du
 * même rayon, déjà consultés).
 *
 * La rangée défile horizontalement et deux flèches la font avancer d'une page.
 * Une grille figée obligeait à choisir entre cinq cartes larges et dix cartes
 * serrées ; ici on montre cinq cartes à la fois et on en propose dix.
 *
 * Les flèches sont masquées quand tout tient à l'écran : c'est le script
 * assets/js/custom.js qui en décide, à la mesure du débordement réel.
 *}
{block name='module_products'}
  {block name='module_products_variables'}
    {assign var="need_container" value="true"}
  {/block}

  <section class="{block name='module_products_name'}{/block}">
    <div class="module-products {if isset($need_container) && $need_container}container{/if}">
      {block name='module_products_title'}{/block}

      {block name='module_products_list'}
        {if $products}
          <div class="module-products__list lud-carrousel js-carrousel">
            <button class="lud-carrousel__fleche lud-carrousel__fleche--prev js-carrousel-prev"
                    type="button" aria-label="Produits précédents" hidden>
              <svg class="lud-i" aria-hidden="true"><use href="#i-chevron-left"></use></svg>
            </button>

            {include file='catalog/_partials/productlist.tpl' products=$products}

            <button class="lud-carrousel__fleche lud-carrousel__fleche--next js-carrousel-next"
                    type="button" aria-label="Produits suivants" hidden>
              <svg class="lud-i" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
            </button>
          </div>
        {/if}
      {/block}

      {block name='module_products_footer' hide}
        <div class="module-products__buttons">
          {$smarty.block.child}
        </div>
      {/block}
    </div>
  </section>
{/block}
