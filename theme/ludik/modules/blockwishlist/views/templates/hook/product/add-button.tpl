{**
 * Bouton « favori » de blockwishlist.
 *
 * Le gabarit d'origine lit uniquement $product.id, clé fournie par la fiche
 * produit. Dans une liste, PrestaShop expose id_product : le bouton sortait
 * alors sans identifiant, et le cœur restait inerte. On lit donc les deux
 * formes. Les classes et attributs data sont ceux attendus par le script du
 * module, qui monte son composant sur .wishlist-button.
 *}
{$ludik_id_produit = $product.id|default:$product.id_product|default:0}

{if $ludik_id_produit}
  <div
    class="wishlist-button"
    data-url="{$url}"
    data-product-id="{$ludik_id_produit}"
    data-product-attribute-id="{$product.id_product_attribute|default:0}"
    data-is-logged="{if isset($customer) && $customer.is_logged}1{else}0{/if}"
    data-list-id="1"
    data-checked="true"
    data-is-product="true"
  ></div>
{/if}
