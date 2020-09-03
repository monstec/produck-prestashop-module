<!--
 * NOTICE OF LICENSE
 *
 * Licensed under the MonsTec Prestashop Module License v.1.0
 *
 * With the purchase or the installation of the software in your application
 * you accept the license agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author    Monstec UG (haftungsbeschränkt)
 * @copyright 2019 Monstec UG (haftungsbeschränkt)
 * @license   LICENSE.txt
-->
<!-- Block produckchat -->
<div id="produck-chat-block-home" class="passive" title="Hilfe und Angebote im Chat">
  <div class="produck-chat-frame">
    <span class="produck-headline-text">
      Chatten und Kaufen
    </span>
    <div id="produck-close-chat" href="#">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/><path d="M0 0h24v24H0z" fill="none"/></svg>
    </div>
  </div>
  <a id="produck-chat-link" class="produck-chat-link" title="Chatten und Kaufen" data-cid="{$cid|escape:'htmlall':'UTF-8'}">
    <img src="{$ducky_image|escape:'html':'UTF-8'}" alt="helpful ducky"/>
  </a>
  <div id="produck-frame-wrapper">
    <div id='produck-frame' frameborder="0" allowfullscreen="" data-src="{$produck_chat_url|escape:'html':'UTF-8'}{$params|escape:'html':'UTF-8'}"></div>
  </div>
</div>
<!-- /Block produckchat -->