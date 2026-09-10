{**
 * Colonne de rayon : les sous-rayons du rayon consulté, en section dépliante.
 *
 * Certains rayons en comptent quatre-vingt-dix. Une liste complète allongeait la
 * page bien au-delà de la grille produits : on en montre douze, le reste se
 * déplie sur demande. Même forme que les facettes : un element details, sans
 * script.
 *}
{$componentName = 'category-tree'}
{$ludik_max_visibles = 12}
{$ludik_total = $categories.children|count}

{if $ludik_total > 0}
  <section class="lud-facet" data-name="Sous-catégories">
    <details class="lud-facet__box" open>
      <summary class="lud-facet__head">
        <span class="lud-facet__label">Sous-catégories</span>
        <span class="lud-facet__count">{$ludik_total}</span>
        <svg class="lud-i lud-i--sm lud-facet__chevron" aria-hidden="true"><use href="#i-chevron-down"></use></svg>
      </summary>

      <div class="lud-facet__body">
        <ul class="lud-facet__list">
          <li>
            <a class="lud-facet__link lud-facet__link--all is-current" href="{$categories.link nofilter}">
              Tout {$categories.name|escape:'htmlall':'UTF-8'}
            </a>
          </li>
          {foreach from=$categories.children item=node name=sousRayons}
            {if $smarty.foreach.sousRayons.index >= $ludik_max_visibles}{break}{/if}
            <li>
              <a class="lud-facet__link" href="{$node.link|escape:'htmlall':'UTF-8'}">
                {$node.name|escape:'htmlall':'UTF-8'}
              </a>
            </li>
          {/foreach}
        </ul>

        {if $ludik_total > $ludik_max_visibles}
          <details class="lud-facet__more">
            <summary>Voir les {$ludik_total - $ludik_max_visibles} autres</summary>
            <ul class="lud-facet__list">
              {foreach from=$categories.children item=node name=suite}
                {if $smarty.foreach.suite.index < $ludik_max_visibles}{continue}{/if}
                <li>
                  <a class="lud-facet__link" href="{$node.link|escape:'htmlall':'UTF-8'}">
                    {$node.name|escape:'htmlall':'UTF-8'}
                  </a>
                </li>
              {/foreach}
            </ul>
          </details>
        {/if}
      </div>
    </details>
  </section>
{/if}
