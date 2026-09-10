{**
 * Aperçu rapide, au survol du visuel.
 *
 * Le gabarit du thème parent rendait deux boutons - un pour la souris, un pour
 * le tactile - avec une icône Material et un libellé anglais. Un seul bouton
 * suffit : il apparaît au survol sur grand écran et reste accessible au clavier.
 *}
{block name='quick_view'}
  <button type="button"
          class="{$componentName}__quickview js-quickview"
          data-ps-action="open-quickview"
          data-ps-ref="quickview-button"
          aria-label="Aperçu rapide de {$product.name|escape:'html':'UTF-8'}">
    <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-zoom-in"></use></svg>
    <span class="{$componentName}__quickview-label">Aperçu rapide</span>
  </button>
{/block}

{block name='quick_view_touch'}{/block}
