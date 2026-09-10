{**
 * Navigation des rayons Ludik.
 *
 * Le catalogue compte 3 965 catégories sur cinq niveaux. Le gabarit du thème parent
 * sérialise tout l'arbre dans chaque page : 2 962 liens et 1,1 Mo de HTML rien que
 * pour le menu, avec un panneau à onglets illisible dès qu'un rayon a des dizaines
 * de sous-rayons.
 *
 * Ici, le menu ne descend qu'à un niveau : au survol d'un rayon, ses sous-rayons
 * s'affichent en colonnes, plafonnés, suivis d'un lien vers le rayon complet.
 * L'exploration fine se fait dans la colonne de gauche des pages de rayon.
 *}
{$ludik_max_sous_rayons = 24}

{* Icône Lucide par rayon, repérée sur l'identifiant de la catégorie. *}
{$ludik_icones = ['category-4' => 'dices', 'category-5' => 'layers', 'category-6' => 'book-open',
                  'category-3' => 'swords', 'category-7' => 'package', 'category-8' => 'palette']}

{* Libellés courts pour la barre. Les noms complets du catalogue
   - « Jeux de cartes à collectionner », « Accessoires Jeux & JCC » -
   remplissaient la barre à eux seuls. Les pages de rayon gardent leur nom
   complet. *}
{$ludik_libelles = ['category-5' => 'Cartes à collectionner',
                    'category-6' => 'BD, mangas, romans',
                    'category-7' => 'Accessoires',
                    'category-8' => 'Loisirs créatifs']}

{* Teinte du pictogramme, la même que sur les cartes de rayon : la barre et
   les cartes désignent les mêmes univers, elles partagent leurs couleurs. *}
{$ludik_tons = ['category-4' => 'societe', 'category-5' => 'tcg',
                'category-6' => 'manga',   'category-3' => 'jdr',
                'category-7' => 'creatif', 'category-8' => 'bd']}

<div class="ps-mainmenu ps-mainmenu--desktop col-xl col-auto">
  <nav class="ps-mainmenu__desktop d-none d-xl-block position-static js-menu-desktop"
       aria-label="{l s='Main navigation' d='Shop.Theme.Menu'}">
    <div class="ludik-nav__inner">
      <button class="ludik-nav__all" type="button"
              data-bs-toggle="offcanvas" data-bs-target="#mobileMenu"
              aria-controls="mobileMenu">
        <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-list"></use></svg>
        Toutes les catégories
        <svg class="lud-i lud-i--sm ludik-nav__all-chevron" aria-hidden="true"><use href="#i-chevron-down"></use></svg>
      </button>

      <ul class="ps-mainmenu__tree" id="top-menu">
        {foreach from=$menu.children item=rayon}
          {$ludik_ton = $ludik_tons[$rayon.page_identifier]|default:''}
          <li class="ps-mainmenu__tree-item type-{$rayon.type}{if $ludik_ton} t-{$ludik_ton}{/if}{if $rayon.current} current{/if}"
              data-id="{$rayon.page_identifier}">
            <div class="ps-mainmenu__tree-item-wrapper">
              <a class="ps-mainmenu__tree-link" href="{$rayon.url}"
                 {if $rayon.current}aria-current="page"{/if}
                 {if $rayon.open_in_new_window}target="_blank" rel="noopener noreferrer"{/if}>
                {$ludik_ico = $ludik_icones[$rayon.page_identifier]|default:'tag'}
                <svg class="lud-i" aria-hidden="true"><use href="#i-{$ludik_ico}"></use></svg>
                <span>{$ludik_libelles[$rayon.page_identifier]|default:$rayon.label}</span>
              </a>
            </div>

            {if $rayon.children|count}
              <div class="ludik-submenu" role="menu" aria-label="Sous-rayons de {$rayon.label}">
                <div class="ludik-submenu__inner">
                  <ul class="ludik-submenu__list">
                    {foreach from=$rayon.children item=sous name=sousRayons}
                      {if $smarty.foreach.sousRayons.index >= $ludik_max_sous_rayons}{break}{/if}
                      <li>
                        <a class="ludik-submenu__link" href="{$sous.url}"
                           {if $sous.open_in_new_window}target="_blank" rel="noopener noreferrer"{/if}>
                          {$sous.label}
                        </a>
                      </li>
                    {/foreach}
                  </ul>
                  <a class="ludik-submenu__all" href="{$rayon.url}">
                    Tout le rayon {$rayon.label}
                    {if $rayon.children|count > $ludik_max_sous_rayons}
                      <span class="ludik-submenu__count">{$rayon.children|count} sous-rayons</span>
                    {/if}
                  </a>
                </div>
              </div>
            {/if}
          </li>
        {/foreach}
      </ul>
    </div>
  </nav>

  {* --- Bouton du menu mobile : libellé explicite, une icône seule ne disait
         pas ce qu'elle ouvrait --- *}
  <div class="ps-mainmenu__mobile-toggle">
    <button class="menu-toggle ludik-nav__all" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#mobileMenu" aria-controls="mobileMenu">
      <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-list"></use></svg>
      Toutes les catégories
    </button>
  </div>
</div>

{* --- Mobile : les rayons et leurs sous-rayons, sans descendre plus bas --- *}
<div class="ps-mainmenu ps-mainmenu--tiroir offcanvas offcanvas-start js-menu-canvas"
     tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
  <div class="offcanvas-header">
    <span class="ludik-menu-mobile__titre" id="mobileMenuLabel">Toutes les catégories</span>
    <button type="button" class="btn-close btn text-reset" data-bs-dismiss="offcanvas"
            aria-label="{l s='Close' d='Shop.Theme.Global'}"></button>
  </div>

  <div class="ps-mainmenu__mobile offcanvas-body">
    <ul class="ludik-menu-mobile">
      {foreach from=$menu.children item=rayon}
        <li class="ludik-menu-mobile__rayon">
          <a class="ludik-menu-mobile__lien" href="{$rayon.url}">
            {$ludik_ico = $ludik_icones[$rayon.page_identifier]|default:'tag'}
            <svg class="lud-i" aria-hidden="true"><use href="#i-{$ludik_ico}"></use></svg>
            <span>{$rayon.label}</span>
          </a>
          {if $rayon.children|count}
            <ul class="ludik-menu-mobile__sous">
              {foreach from=$rayon.children item=sous name=sousMobile}
                {if $smarty.foreach.sousMobile.index >= 12}{break}{/if}
                <li><a href="{$sous.url}">{$sous.label}</a></li>
              {/foreach}
              {if $rayon.children|count > 12}
                <li><a class="ludik-menu-mobile__tout" href="{$rayon.url}">Tout voir ({$rayon.children|count})</a></li>
              {/if}
            </ul>
          {/if}
        </li>
      {/foreach}
    </ul>
  </div>

  <div class="ps-mainmenu__additionnals offcanvas-body d-flex flex-wrap align-items-center gap-3">
    <div class="ps-mainmenu__selects d-flex gap-2 me-auto">
      <div id="_mobile_ps_currencyselector" class="col-auto"></div>
      <div id="_mobile_ps_languageselector" class="col-auto"></div>
    </div>
    <div id="_mobile_ps_contactinfo"></div>
  </div>
</div>
