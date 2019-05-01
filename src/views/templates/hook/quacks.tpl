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

<!-- Block produck -->
<div id="quacks-external-box" class="main">
  <section id="quacks-container">
    <h2>Aktuelle Quacks</h4>
    <div id="quacklist-wrapper-external-box" class="block_content flush-left">
      <div id="quack-overview-list-external-box" class="external-box">
        {if $quackData}
          {foreach from=$quackData key=id item=value}
            <div class="dialogue-summary narrow"><div class="summary-text"><h3><a class="question-hyperlink" href="{$quackDisplayLink|replace:'quackIdPlaceholder':{$value.quackId|escape:'htmlall':'UTF-8'}|replace:'quackTitlePlaceholder':{$value.title|lower|replace:$urlReplaceFrom:$urlReplaceTo|regex_replace:$urlReplaceRegexp:""|truncate:{$urlMaxLength|escape:'htmlall':'UTF-8'}:""}|escape:'html':'UTF-8'}" target="{$quackDisplayTarget|escape:'html':'UTF-8'}">{$value.title|escape:'htmlall':'UTF-8'}</a></h3></div></div>
          {/foreach}
        {else}
          <p>
            {if $produckLink}
              Frag Experten um Rat unter <a href="{$produckLink|escape:'html':'UTF-8'}" target="_blank">ProDuck.de</a>!
            {else}
              Frag Experten um Rat unter <a href="https://produck.de" target="_blank">ProDuck.de</a>!
            {/if}
          </p>
        {/if}
      </div>
      <div class="more-quacks-ref">
        <a href="{$quackOverviewLink|escape:'html':'UTF-8'}" target="{$quackDisplayTarget|escape:'html':'UTF-8'}">Mehr Quacks</a>
      </div>
    </div>
  <section>
</div>
<!-- /Block produck -->