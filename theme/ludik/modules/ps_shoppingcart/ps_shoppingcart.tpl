{**
 * Panier de l'en-tête : icône cerclée, compteur or, montant sous le libellé.
 * On conserve les classes attendues par le script de rafraîchissement.
 *}
<div id="_desktop_ps_shoppingcart">
  <div class="ps-shoppingcart">
    <div class="header-block blockcart cart-preview {if $cart.products_count > 0}header-block--active{else}inactive{/if}"
         data-refresh-url="{$refresh_url}">
      <a class="ludik-head__action header-block__action-btn" rel="nofollow" href="{$cart_url}"
         aria-label="Voir le panier">
        <span class="ludik-head__cart-icon">
          <svg class="lud-i" aria-hidden="true"><use href="#i-shopping-cart"></use></svg>
          <span class="header-block__badge cart-products-count">{$cart.products_count}</span>
        </span>
        <span class="ludik-head__action-text">
          <strong class="header-block__title">Panier</strong>
          <em>{$cart.totals.total.value}</em>
        </span>
      </a>
    </div>
  </div>
</div>
