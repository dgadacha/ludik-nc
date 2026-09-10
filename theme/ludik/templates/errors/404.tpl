{* Page introuvable : textes en français. *}
{extends file='page.tpl'}

{block name='breadcrumb'}{/block}

{block name='container_class'}container container--limited-md text-center{/block}

{block name='page_header_container'}
  {block name='page_title'}
    <div class="page-header mb-2">
      <p class="display-1 fw-bold">{l s='404' d='Shop.Theme.Catalog'}</p>
    </div>
  {/block}
{/block}

{capture assign="errorContent"}
  <h1 class="h2">{$page.title}</h1>

  <p>
    La page demandée n'existe pas ou n'existe plus. Utilisez la recherche pour
    retrouver un article, ou revenez à l'accueil.
  </p>

  <p>
    Si le problème se répète, <a href="{$urls.pages.contact}">écrivez-nous</a>.
  </p>
{/capture}

{block name='page_content_container'}
  {include file='errors/not-found.tpl' errorContent=$errorContent}
{/block}
