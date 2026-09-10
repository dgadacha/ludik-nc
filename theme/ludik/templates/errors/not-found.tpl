{**
 * Écran d'absence de résultat, partagé par la page introuvable et les listes
 * vides. Intitulés en français, icône du jeu Lucide comme partout ailleurs.
 *}
<section id="content" class="page-content page-content--not-found lud-empty">
  {block name='page_content'}
    {block name='error_content'}
      {if isset($errorContent)}
        {$errorContent nofilter}

        <a href="{$urls.pages.index}" class="lud-btn">
          <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-house"></use></svg>
          Retour à l'accueil
        </a>
      {else}
        <p class="h2">Cette page n'est plus disponible</p>
        <p>Elle a peut-être été renommée ou retirée du catalogue.</p>

        <a class="lud-btn" href="{$urls.pages.index}">
          Parcourir la boutique
          <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
        </a>
      {/if}
    {/block}

    {block name='hook_not_found'}
      {hook h='displayNotFound'}
    {/block}
  {/block}
</section>
