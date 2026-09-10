{* Bloc compte de l'en-tête, présentation Ludik. *}

<div id="_desktop_ps_customersignin">
  <div class="ps-customersignin">
    {if $customer.is_logged}
      <div class="dropdown header-block">
        <button
          class="dropdown-toggle ludik-head__action header-block__action-btn border-0 bg-transparent"
          id="userMenuButton"
          data-bs-toggle="dropdown"
          aria-haspopup="true"
          aria-expanded="false"
          aria-label="{l s='View my account (%customerName%)' sprintf=['%customerName%' => $customerName] d='Shop.Theme.Customeraccount'}"
        >
          <svg class="lud-i" aria-hidden="true"><use href="#i-user"></use></svg>
          <span class="ludik-head__action-text">
            <strong class="header-block__title">{$customer.firstname|capitalize|truncate:14:"…":true}</strong>
            <em>Mon compte</em>
          </span>
        </button>

        <div class="dropdown-menu dropdown-menu-start" aria-labelledby="userMenuButton">
          <a
            href="{$urls.pages.my_account}"
            class="dropdown-item"
            rel="nofollow"
            {if $urls.current_url == $urls.pages.my_account}aria-current="page"{/if}
          >
            <i class="material-icons me-2" aria-hidden="true">&#xF02E;</i>
            Mon compte
          </a>

          <div class="dropdown-divider"></div>

          <a
            href="{$urls.pages.identity}"
            class="dropdown-item"
            rel="nofollow"
            {if $urls.current_url == $urls.pages.identity}aria-current="page"{/if}
          >
            <i class="material-icons me-2" aria-hidden="true">&#xE853;</i>
            Mes informations
          </a>

          {if $customer.addresses|count}
            <a
              href="{$urls.pages.addresses}"
              class="dropdown-item"
              rel="nofollow"
              {if $urls.current_url == $urls.pages.addresses}aria-current="page"{/if}
            >
              <i class="material-icons me-2" aria-hidden="true">&#xF00F;</i>
              Mes adresses
            </a>
          {else}
            <a
              href="{$urls.pages.address}"
              class="dropdown-item"
              rel="nofollow"
              {if $urls.current_url == $urls.pages.address}aria-current="page"{/if}
            >
              <i class="material-icons me-2" aria-hidden="true">&#xEF3A;</i>
              Ajouter une adresse
            </a>
          {/if}

          {if !$configuration.is_catalog}
            <a
              href="{$urls.pages.history}"
              class="dropdown-item"
              rel="nofollow"
              {if $urls.current_url == $urls.pages.history}aria-current="page"{/if}
            >
              <i class="material-icons me-2" aria-hidden="true">&#xE916;</i>
              Mes commandes
            </a>
          {/if}

          {if !$configuration.is_catalog}
            <a
              href="{$urls.pages.order_slip}"
              class="dropdown-item"
              rel="nofollow"
              {if $urls.current_url == $urls.pages.order_slip}aria-current="page"{/if}
            >
              <i class="material-icons me-2" aria-hidden="true">&#xE8B0;</i>
              Mes avoirs
            </a>
          {/if}

          {if $configuration.voucher_enabled && !$configuration.is_catalog}
            <a
              href="{$urls.pages.discount}"
              class="dropdown-item"
              rel="nofollow"
              {if $urls.current_url == $urls.pages.discount}aria-current="page"{/if}
            >
              <i class="material-icons me-2" aria-hidden="true">&#xE54E;</i>
              Mes bons de réduction
            </a>
          {/if}

          {if $configuration.return_enabled && !$configuration.is_catalog}
            <a
              href="{$urls.pages.order_follow}"
              class="dropdown-item"
              rel="nofollow"
              {if $urls.current_url == $urls.pages.order_follow}aria-current="page"{/if}
            >
              <i class="material-icons me-2" aria-hidden="true">&#xE860;</i>
              Mes retours
            </a>
          {/if}

          <div class="dropdown-divider"></div>

          <a 
            href="{$logout_url}"
            class="dropdown-item"
            rel="nofollow"
          >
            <i class="material-icons me-2" aria-hidden="true">&#xE879;</i>
            Se déconnecter
          </a>
        </div>
      </div>
    {else}
      <div class="header-block">
        <a
          href="{$urls.pages.authentication}?back={$urls.current_url|urlencode}"
          class="ludik-head__action header-block__action-btn"
          rel="nofollow"
          aria-label="{l s='Sign in' d='Shop.Theme.Actions'}"
        >
          <svg class="lud-i" aria-hidden="true"><use href="#i-user"></use></svg>
          <span class="ludik-head__action-text">
            <strong class="header-block__title">Connexion</strong>
            <em>Mon compte</em>
          </span>
        </a>
      </div>
    {/if}
  </div>
</div>
