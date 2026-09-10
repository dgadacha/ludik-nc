{**
 * Pied de page Ludik : bloc identité à gauche, colonnes de liens, coordonnées.
 * Les informations proviennent des mentions légales et du bandeau du site actuel.
 *}
{capture name="footer_before"}{hook h='displayFooterBefore'}{/capture}
{if $smarty.capture.footer_before}
  <div class="footer footer__before">{$smarty.capture.footer_before nofilter}</div>
{/if}

<div class="footer footer__main">
  <div class="container-md">
    <div class="ludik-foot">
      <div class="ludik-foot__brand">
        <img src="{$shop.logo_details.src}" alt="{$shop.name}" width="200" height="97" loading="lazy">
        <p class="ludik-foot__claim">Conseil, animation et vente de jeux de société et BD</p>
        <div class="ludik-foot__social">
          <a href="https://www.facebook.com/ludik.ncal" target="_blank" rel="noopener" aria-label="Facebook">
            <svg class="lud-i" aria-hidden="true"><use href="#i-facebook"></use></svg>
          </a>
        </div>
      </div>

      <div class="ludik-foot__cols">
        {capture name="footer_main_top"}{hook h='displayFooter'}{/capture}
        {if $smarty.capture.footer_main_top}
          {$smarty.capture.footer_main_top nofilter}
        {/if}
      </div>
    </div>

    {capture name="footer_main_bottom"}{hook h='displayFooterAfter'}{/capture}
    {if isset($smarty.capture.footer_main_bottom) && $smarty.capture.footer_main_bottom}
      <div class="footer__main-bottom row">{$smarty.capture.footer_main_bottom nofilter}</div>
    {/if}

    {include file='_partials/copyright.tpl'}
  </div>
</div>
