{**
 * Fiche technique du produit.
 *
 * Le gabarit du thème parent affichait des intitulés anglais et deux listes
 * séparées - « Product Details » et « Data sheet » - pour la même nature
 * d'information. Ici, une seule table, en français, et seuls les champs
 * renseignés. L'EAN13 y figure : c'est la référence que le client utilise en
 * boutique pour retrouver un article.
 *}
<div class="js-product-details" data-product="{$product.embedded_attributes|json_encode}">
  <div class="accordion-item" id="product_details">
    <h2 class="accordion-header" id="product_details_heading">
      <button class="accordion-button{if $product.description} collapsed{/if}" type="button"
              data-bs-toggle="collapse" data-bs-target="#product_details_collapse"
              aria-expanded="{if !$product.description}true{else}false{/if}"
              aria-controls="product_details_collapse">
        Fiche technique
      </button>
    </h2>

    <div id="product_details_collapse"
         class="accordion-collapse collapse{if !$product.description} show{/if}"
         aria-labelledby="product_details_heading">
      <div class="accordion-body">
        <dl class="lud-specs">
          {if !empty($product_manufacturer->name) && !empty($product_manufacturer->url)}
            <div class="lud-specs__row">
              <dt>Éditeur</dt>
              <dd><a href="{$product_manufacturer->url}">{$product_manufacturer->name}</a></dd>
            </div>
          {/if}

          {if !empty($product.reference_to_display)}
            <div class="lud-specs__row">
              <dt>Référence</dt>
              <dd>{$product.reference_to_display}</dd>
            </div>
          {/if}

          {if !empty($product.ean13)}
            <div class="lud-specs__row">
              <dt>EAN13</dt>
              <dd>{$product.ean13}</dd>
            </div>
          {/if}

          {if !empty($product.category_name)}
            <div class="lud-specs__row">
              <dt>Rayon</dt>
              <dd>{$product.category_name}</dd>
            </div>
          {/if}

          {if $product.show_quantities}
            <div class="lud-specs__row">
              <dt>En stock</dt>
              <dd data-stock="{$product.quantity}" data-allow-oosp="{$product.allow_oosp}">
                {$product.quantity} {$product.quantity_label}
              </dd>
            </div>
          {/if}

          {if $product.availability_date}
            <div class="lud-specs__row">
              <dt>Disponible le</dt>
              <dd>{$product.availability_date}</dd>
            </div>
          {/if}

          {if isset($product.weight) && $product.weight > 0}
            <div class="lud-specs__row">
              <dt>Poids</dt>
              {* PrestaShop stocke le poids en décimal à six chiffres :
                 « 0.450000 kg » se lit mal, on ramène à « 0,45 kg » *}
              <dd>{$product.weight|string_format:"%g"|replace:'.':','} {$product.weight_unit}</dd>
            </div>
          {/if}

          {* PrestaShop range l'EAN13 et l'UPC à la fois dans le produit et dans
             specific_references : sans ce filtre, la table affiche deux fois
             la même ligne *}
          {if !empty($product.specific_references)}
            {foreach from=$product.specific_references item=reference key=key}
              {if $reference == $product.ean13|default:'' || $reference == $product.reference_to_display|default:''}
                {continue}
              {/if}
              <div class="lud-specs__row">
                <dt>{$key}</dt>
                <dd>{$reference}</dd>
              </div>
            {/foreach}
          {/if}

          {if $product.grouped_features}
            {foreach from=$product.grouped_features item=feature}
              <div class="lud-specs__row">
                <dt>{$feature.name}</dt>
                <dd>{$feature.value|escape:'htmlall'|nl2br nofilter}</dd>
              </div>
            {/foreach}
          {/if}
        </dl>
      </div>
    </div>
  </div>
</div>
