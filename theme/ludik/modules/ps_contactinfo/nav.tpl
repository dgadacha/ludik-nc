{**
 * Surcharge Ludik : formulation française et rappel des deux boutiques.
 *}
<div id="_desktop_ps_contactinfo">
  <div class="ps-contactinfo">
    {if $contact_infos.phone}
      <a class="ps-contactinfo__phone" href="tel:+687{$contact_infos.phone|replace:' ':''}"
         aria-label="Appeler Ludik au {$contact_infos.phone}">
        Une question&nbsp;? {$contact_infos.phone}
      </a>
      <span class="ps-contactinfo__sep">·</span>
      <a class="ps-contactinfo__shops" href="{$urls.pages.stores}">Nos 2 boutiques</a>
      <span class="ps-contactinfo__sep">·</span>
      <a class="ps-contactinfo__shops" href="{$urls.pages.new_products}">Nouveautés</a>
      <span class="ps-contactinfo__sep">·</span>
      <a class="ps-contactinfo__shops ps-contactinfo__highlight" href="/content/6-soirees-jeux">Soirée jeux du mardi</a>
    {else}
      <a class="ps-contactinfo__email" href="{$urls.pages.contact}">Nous écrire</a>
    {/if}
  </div>
</div>
