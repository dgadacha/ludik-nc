{**
 * Gabarit à colonne de rayon.
 *
 * La colonne a une largeur fixe : les colonnes Bootstrap du thème parent lui
 * donnaient un quart de la page, soit 360 px sur un écran large, alors que la
 * grille produits a besoin de la place. Sous 1200 px la colonne devient un
 * tiroir, ouvert par le bouton « Filtrer » de l'en-tête de liste.
 *}
{extends file='layouts/layout-both-columns.tpl'}

{block name='content_columns'}
  <div class="{block name='container_class'}columns-container container{/block}">
    <div class="ludik-listing">
      {block name='left_column'}
        <aside id="left-column"
               class="left-column ludik-aside offcanvas-xl offcanvas-start"
               tabindex="-1"
               aria-labelledby="ludik-aside-titre">
          <div class="offcanvas-header ludik-aside__header d-xl-none">
            <p class="offcanvas-title ludik-aside__titre" id="ludik-aside-titre">Filtrer</p>
            <button type="button" class="btn-close"
                    data-bs-dismiss="offcanvas" data-bs-target="#left-column"
                    aria-label="Fermer les filtres"></button>
          </div>

          <div class="offcanvas-body ludik-aside__corps">
            {if $page.page_name == 'product'}
              {hook h='displayLeftColumnProduct'}
            {else}
              {hook h='displayLeftColumn'}
            {/if}
          </div>
        </aside>
      {/block}

      {block name='content_wrapper'}
        <div id="center-column" class="center-column page">
          {hook h='displayContentWrapperTop'}
          {block name='content'}{/block}
          {hook h='displayContentWrapperBottom'}
        </div>
      {/block}

      {block name='right_column'}{/block}
    </div>
  </div>
{/block}
