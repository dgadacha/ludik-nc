{**
 * Vignette produit Ludik : visuel sur fond blanc, titre sur deux lignes,
 * prix mis en avant, une seule action. Pas de sélecteur de quantité dans les
 * grilles : le catalogue est surtout composé de pièces uniques (BD, boîtes).
 *}
{$componentName = 'product-miniature'}

{block name='product_miniature_item'}
  <article
    class="{$componentName} js-{$componentName}"
    data-id-product="{$product.id_product}"
    data-id-product-attribute="{$product.id_product_attribute}"
  >
    <div class="{$componentName}__inner">
      <div class="{$componentName}__top">
        <div class="{$componentName}__media">
          {include file='catalog/_partials/miniatures/product-image.tpl'}
        </div>
        {include file='catalog/_partials/product-flags.tpl'}

        {* Bouton « favori ».
           Le hook displayProductActions de blockwishlist ne convient pas ici :
           son gabarit lit $product dans la portée globale de Smarty, où une
           page de rayon n'expose aucun produit - le bouton sortait vide. On
           écrit donc le conteneur attendu par le script du module, avec
           l'identifiant de la vignette. Le script monte son composant sur
           toute .wishlist-button présente dans la page.
           La route est celle du contrôleur front du module. Le cœur suppose
           blockwishlist actif ; s'il est désactivé, retirer ce bloc. *}
          <div class="{$componentName}__favori">
            <div class="wishlist-button"
                 data-url="{$urls.base_url}module/blockwishlist/action?action=deleteProductFromWishlist"
                 data-product-id="{$product.id_product}"
                 data-product-attribute-id="{$product.id_product_attribute|default:0}"
                 data-is-logged="{if isset($customer) && $customer.is_logged}1{else}0{/if}"
                 data-list-id="1"
                 data-checked="true"
                 data-is-product="true"></div>
          </div>

        {include file='catalog/_partials/miniatures/product-quickview.tpl'}
      </div>

      <div class="{$componentName}__bottom">
        <a class="{$componentName}__title" href="{$product.url}">{$product.name}</a>

        <div class="{$componentName}__row">
          <div class="{$componentName}__infos">
            {if $product.show_price}
              <div class="{$componentName}__prices">
                <span class="{$componentName}__price">{$product.price}</span>
                {if $product.has_discount}
                  <span class="{$componentName}__regular-price">{$product.regular_price}</span>
                {/if}
              </div>
            {/if}
            <span class="{$componentName}__stock lud-stock {if $product.availability == 'in_stock' || $product.availability == 'last_remaining_items'}is-in{else}lud-stock--out is-out{/if}">
              {if $product.availability == 'in_stock' || $product.availability == 'last_remaining_items'}En stock{elseif $product.add_to_cart_url}Sur commande{else}Épuisé{/if}
            </span>
          </div>

          {if $product.add_to_cart_url}
            <form class="{$componentName}__form" action="{$urls.pages.cart}" method="post">
              <input type="hidden" value="{$product.id_product}" name="id_product">
              <input type="hidden" value="{$product.minimal_quantity|default:1}" name="qty">
              <input type="hidden" name="id_product_attribute" value="{$product.id_product_attribute}">
              <input type="hidden" name="token" value="{$static_token}">
              <button data-button-action="add-to-cart" class="{$componentName}__add lud-btn-square"
                      title="Ajouter « {$product.name|escape:'html':'UTF-8'} » au panier"
                      data-ps-ref="add-to-cart">
                <svg class="lud-i" aria-hidden="true"><use href="#i-shopping-cart"></use></svg>
                <span class="visually-hidden">Ajouter au panier</span>
              </button>
            </form>
          {else}
            <a href="{$product.url}" class="{$componentName}__add lud-btn-square lud-btn-square--ghost"
               aria-label="Voir la fiche de {$product.name|escape:'html':'UTF-8'}">
              <svg class="lud-i" aria-hidden="true"><use href="#i-chevron-right"></use></svg>
            </a>
          {/if}
        </div>
      </div>
    </div>
  </article>
{/block}
