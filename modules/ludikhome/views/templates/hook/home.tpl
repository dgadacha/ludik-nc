<div class="container-md ludik-home">
{**
 * Les rayons du catalogue, en cartes illustrées.
 *
 * Il y avait deux présentations des mêmes rayons : une rangée de petites cases
 * sous la navigation, puis de grands blocs plus bas. Elles sont fusionnées en
 * une seule grille de six cartes : couvertures du rayon, pictogramme, nom,
 * premiers sous-rayons et nombre de produits.
 *
 * Les libellés reprennent l'organisation de la boutique, les nombres viennent
 * du catalogue importé.
 *}
{if $ludik_univers}
<section class="ludik-section">
  <div class="ludik-section-head">
    <div>
      <h2>Nos catégories</h2>
      <p>Tous nos univers, tels qu'ils sont organisés en boutique.</p>
    </div>
    <a href="{$urls.pages.sitemap}">
      Voir toutes les catégories
      <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
    </a>
  </div>

  <div class="ludik-univers">
    {foreach from=$ludik_univers item=u}
      <a href="{$u.link}" class="{$u.tone}">
        {if $u.covers}
          <span class="u-covers" aria-hidden="true">
            {foreach from=$u.covers item=cov}<img src="{$cov}" alt="" loading="lazy" width="452" height="452">{/foreach}
          </span>
        {/if}

        <span class="u-body">
          <span class="u-head">
            <svg class="lud-i" aria-hidden="true"><use href="#i-{$u.icon}"></use></svg>
            <span class="u-title">{$u.title}</span>
          </span>
          {if $u.children}
            <span class="u-desc">{', '|implode:$u.children}{if $u.more}, …{/if}</span>
          {/if}
          <span class="u-count">
            {$u.count|number_format:0:',':' '} produits
            <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
          </span>
        </span>
      </a>
    {/foreach}
  </div>
</section>
{/if}

</div>
