{**
 * En-tête Ludik, en deux rangées :
 *   1. l'identité, la recherche et les accès compte / panier ;
 *   2. la barre des rayons.
 *
 * Une troisième rangée très fine portait une accroche et des liens de service
 * (boutiques, contact, réseaux). Elle a été retirée : le pied de page donne
 * déjà les deux adresses, le formulaire de contact et Facebook, et l'en-tête
 * gagne la hauteur qu'elle prenait.
 *}
{include file='_partials/lucide-sprite.tpl'}

{capture name="header_banner"}{hook h='displayBanner'}{/capture}
{if !empty($smarty.capture.header_banner)}
  <div class="header-banner">{$smarty.capture.header_banner nofilter}</div>
{/if}

<div class="ludik-head">
  <div class="container-md ludik-head__row">
    <div class="ludik-head__brand">
      {if $page.page_name == 'index'}<h1 class="mb-0 ludik-head__h1">{/if}
        <a href="{$urls.base_url}" class="ludik-head__logo">
          <img src="{$shop.logo_details.src}" alt="{$shop.name}"
               width="{$shop.logo_details.width}" height="{$shop.logo_details.height}">
        </a>
      {if $page.page_name == 'index'}</h1>{/if}
    </div>

    <div class="ludik-head__search">{hook h='displaySearch'}</div>

    <div class="ludik-head__actions">{hook h='displayNav2'}</div>

    {* sous 768 px, le script du thème déplace compte et panier dans ces
       conteneurs : ils doivent rester présents et visibles *}
    <div class="ludik-head__mobile">
      <div id="_mobile_ps_customersignin" class="d-md-none d-flex"></div>
      {if !$configuration.is_catalog}
        <div id="_mobile_ps_shoppingcart" class="d-md-none d-flex"></div>
      {/if}
    </div>
  </div>
</div>

{capture name="nav_full_width"}{hook h='displayNavFullWidth'}{/capture}
{if !empty($smarty.capture.nav_full_width)}
  <div class="ludik-nav">{$smarty.capture.nav_full_width nofilter}</div>
{/if}
