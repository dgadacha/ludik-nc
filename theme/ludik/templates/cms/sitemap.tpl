{**
 * Plan du site.
 *
 * Le contrôleur de PrestaShop construit la liste des pages en dur, page
 * « Prix réduits » comprise (SitemapController, entrée « prices-drop-page ») :
 * supprimer sa fiche SEO retire l'URL lisible mais pas cette entrée. Comme la
 * page a été retirée du site - le catalogue ne porte aucune remise -, on la
 * retire aussi du plan.
 *
 * Le reste reprend le gabarit du thème parent à l'identique.
 *}
{extends file='page.tpl'}

{block name='page_title'}
  {l s='Sitemap' d='Shop.Theme.Global'}
{/block}

{block name='page_content'}
  <div class="row sitemap">
    {foreach $sitemapUrls as $group}
      <div class="sitemap__block col-md-6 col-lg-3">
        <h2>
          {$group.name}
        </h2>

        {$ludik_liens = []}
        {foreach $group.links as $ludik_lien}
          {if isset($ludik_lien.id) && $ludik_lien.id == 'prices-drop-page'}
            {continue}
          {/if}
          {append var='ludik_liens' value=$ludik_lien}
        {/foreach}

        {include file='cms/_partials/sitemap-nested-list.tpl' links=$ludik_liens}

        <hr class="d-block d-md-none">
      </div>
    {/foreach}
  </div>
{/block}
