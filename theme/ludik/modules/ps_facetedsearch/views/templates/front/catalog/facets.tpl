{**
 * Panneau de filtres.
 *
 * Ce gabarit reprend celui du thème parent : il conserve ses points d'accroche
 * JavaScript (js-search-link, js-faceted-slider-container, js-faceted-values,
 * les champs cachés du curseur de prix) et ses attributs data, sans lesquels le
 * curseur de prix ne s'initialise pas et les cases à cocher ne rechargent pas la
 * grille. Seuls changent les libellés, les icônes et l'habillage, traités en CSS
 * sur l'accordéon Bootstrap.
 *
 * La facette « Catégories » est écartée : la section « Sous-catégories » de la
 * colonne présente déjà les mêmes liens, et deux listes identiques dans la même
 * colonne étaient précisément le défaut à corriger.
 *}
{$componentName = 'search-filters'}

{if $displayedFacets|count}
  <div id="search-filters" class="{$componentName} lud-facets" role="region" aria-label="Filtres">
    {block name='facets_clearall_button'}
      {if $activeFilters|count}
        <div class="{$componentName}__clear lud-facets__clear">
          <button type="button"
                  data-search-url="{$clear_all_link}"
                  class="lud-btn lud-btn--ghost lud-btn--sm js-search-filters-clear-all">
            <svg class="lud-i lud-i--sm" aria-hidden="true"><use href="#i-x"></use></svg>
            Tout effacer
          </button>
        </div>
      {/if}
    {/block}

    <div class="accordion accordion-flush accordion--small lud-facets__list">
      {foreach from=$displayedFacets item="facet" name="facets"}
        {if $facet.type == 'category'}{continue}{/if}

        <section class="accordion-item lud-facet" data-type="{$facet.type}" data-name="{$facet.label}">
          {assign var=_expand_id value=10|mt_rand:100000}
          {assign var=_collapse value=true}

          {foreach from=$facet.filters item="filter"}
            {if $filter.active}{assign var=_collapse value=false}{/if}
          {/foreach}
          {* le prix et la disponibilité restent ouverts : ce sont les deux
             filtres les plus utilisés, les replier coûterait un clic pour rien *}
          {if $facet.widgetType == 'slider' || $facet@iteration <= 2}{assign var=_collapse value=false}{/if}

          <button class="accordion-button lud-facet__head{if $_collapse} collapsed{/if}"
                  type="button"
                  data-bs-target="#facet_{$_expand_id}"
                  data-bs-toggle="collapse"
                  aria-expanded="{if !$_collapse}true{else}false{/if}">
            <span class="lud-facet__label">{$facet.label}</span>
          </button>

          <div id="facet_{$_expand_id}" class="accordion-collapse collapse{if !$_collapse} show{/if}">
            {if in_array($facet.widgetType, ['radio', 'checkbox'])}
              {block name='facet_item_other'}
                <ul class="accordion-body lud-facet__list">
                  {foreach from=$facet.filters key=filter_key item="filter"}
                    {assign var="isColorOrTexture" value=(isset($filter.properties.color) || isset($filter.properties.texture))}
                    {if !$filter.displayed}{continue}{/if}

                    <li>
                      <div class="{$componentName}__item facet-label{if $filter.active} active{/if}">
                        <div class="{$componentName}__form-check{if $isColorOrTexture} {$componentName}__form-check--color{/if} form-check">
                          <input class="form-check-input{if $isColorOrTexture} d-none{/if}"
                                 id="facet_input_{$_expand_id}_{$filter_key}"
                                 data-search-url="{$filter.nextEncodedFacetsURL}"
                                 type="{if $facet.multipleSelectionAllowed}checkbox{else}radio{/if}"
                                 {if !$facet.multipleSelectionAllowed}name="filtre-{$_expand_id}"{/if}
                                 {if $filter.active}checked{/if}>

                          <label class="{$componentName}__form-label form-check-label"
                                 for="facet_input_{$_expand_id}_{$filter_key}"
                                 {if $isColorOrTexture}data-ps-ref="color-label"{/if}>
                            {if $isColorOrTexture}
                              <span class="color color-sm{if $filter.active} active{/if}"
                                    {if isset($filter.properties.color)}style="background-color:{$filter.properties.color}"
                                    {else}style="background-image:url({$filter.properties.texture})"{/if}
                                    role="checkbox"
                                    aria-checked="{if $filter.active}true{else}false{/if}"
                                    tabindex="0"
                                    aria-labelledby="facet_label_{$_expand_id}_{$filter_key}"></span>

                              <span class="{$componentName}__color-label" id="facet_label_{$_expand_id}_{$filter_key}">
                                {$filter.label}
                                {if $filter.magnitude && $show_quantities}
                                  <span class="{$componentName}__magnitude">{$filter.magnitude}<span class="visually-hidden"> {if $filter.magnitude == 1}article{else}articles{/if}</span></span>
                                {/if}
                              </span>
                            {else}
                              <a href="{$filter.nextEncodedFacetsURL}"
                                 class="{$componentName}__link search-link js-search-link"
                                 rel="nofollow" tabindex="-1">
                                {$filter.label}
                                {if $filter.magnitude && $show_quantities}
                                  <span class="{$componentName}__magnitude">{$filter.magnitude}<span class="visually-hidden"> {if $filter.magnitude == 1}article{else}articles{/if}</span></span>
                                {/if}
                              </a>
                            {/if}
                          </label>
                        </div>
                      </div>
                    </li>
                  {/foreach}
                </ul>
              {/block}

            {elseif $facet.widgetType == 'dropdown'}
              {block name='facet_item_dropdown'}
                <ul class="accordion-body lud-facet__list">
                  <li>
                    <div class="{$componentName}__item facet-dropdown dropdown">
                      <button class="{$componentName}__dropdown-toggle lud-btn lud-btn--ghost lud-btn--sm dropdown-toggle"
                              type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {assign var="active_found" value=false}
                        {foreach from=$facet.filters item="filter"}
                          {if $filter.active}
                            {$filter.label}
                            {$active_found = true}
                          {/if}
                        {/foreach}
                        {if !$active_found}Tous{/if}
                      </button>

                      <div class="{$componentName}__dropdown-menu dropdown-menu dropdown-menu-start">
                        {foreach from=$facet.filters item="filter"}
                          {if !$filter.active}
                            <a rel="nofollow" href="{$filter.nextEncodedFacetsURL}"
                               class="dropdown-item select-list js-search-link">
                              {$filter.label}
                              {if $filter.magnitude && $show_quantities}
                                <span class="{$componentName}__magnitude">{$filter.magnitude}</span>
                              {/if}
                            </a>
                          {/if}
                        {/foreach}
                      </div>
                    </div>
                  </li>
                </ul>
              {/block}

            {elseif $facet.widgetType == 'slider'}
              {block name='facet_item_slider'}
                {foreach from=$facet.filters item="filter"}
                  <div class="{$componentName}__slider-container accordion-body js-faceted-filter-slider">
                    <div class="{$componentName}__slider js-faceted-slider-container"
                         data-slider-min="{$facet.properties.min}"
                         data-slider-max="{$facet.properties.max}"
                         data-slider-id="{$_expand_id}"
                         data-slider-values="{$filter.value|@json_encode}"
                         data-slider-unit="{$facet.properties.unit}"
                         {if isset($facet.properties.specifications.currencyCode) && $facet.properties.specifications.currencyCode != ''}
                           data-slider-currency="{$facet.properties.specifications.currencyCode}"
                         {/if}
                         data-slider-label="{$facet.label}"
                         data-slider-specifications="{$facet.properties.specifications|@json_encode}"
                         data-slider-encoded-url="{$filter.nextEncodedFacetsURL}"
                         data-slider-direction="{$language.is_rtl}"></div>

                    <div class="{$componentName}__slider-values js-faceted-values"></div>

                    <input type="hidden" class="form-range-start js-faceted-slider js-faceted-slider-start"
                           id="slider-range_{$_expand_id}-start">
                    <input type="hidden" class="form-range-start js-faceted-slider js-faceted-slider-end"
                           id="slider-range_{$_expand_id}-end">
                  </div>
                {/foreach}
              {/block}
            {/if}
          </div>
        </section>
      {/foreach}
    </div>
  </div>
{/if}
