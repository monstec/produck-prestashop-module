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

{extends file='page.tpl'}

{block name='page_content'}

<div id="quacks-main-div" class="main block">
    <section id="headline-container" debog="1">
        <h1>Quacks &#220;bersicht</h1>
    </section>
    <section itemscope="" itemtype="http://schema.org/Question" id="quacks-container" debog="2">        
        <h2>In der Quacks &#220;bersicht findest Du spannende Fragen von fachkundigen Experten beantwortet.</h2>
        <div id="quacklist-wrapper" class="flush-left">
            <div id="quack-overview-list">
                <div id="share-brand">
                    <div id="host-wrap-wrapper">
                        <a class="host-ref" href="{$produckLink|escape:'html':'UTF-8'}" target="_blank">
                            <span>Provided by ProDuck</span>
                            <img src="{$duckyImage|escape:'html':'UTF-8'}" alt="helpful ducky" />
                        </a>
                    </div>
                </div>
                {if $quackData}
                {foreach from=$quackData key=id item=value}
                <div class="dialogue-summary narrow">
                    <div class="stats-wrapper">
                        <div class="votes">
                            <div class="mini-counts">
                                <span title="{$value.quackity|round:1|escape:'htmlall':'UTF-8'} rated quality">{$value.quackity|round:1|escape:'htmlall':'UTF-8'}</span>
                            </div>
                            <div>&nbsp;quackity</div>
                        </div>
                        <div class="views">
                            <div class="mini-counts">
                                <span title="{$value.views|escape:'htmlall':'UTF-8'} views">{$value.views|escape:'htmlall':'UTF-8'}</span>
                            </div>
                            <div>&nbsp;views</div>
                            <div class="share"><i class="material-icons" onclick="return;">share</i></div>
                        </div>
                    </div>
                    <div class="summary-text">
                        <h3>
                            <a class="question-hyperlink" href="{$quackDisplayLink|replace:'quackIdPlaceholder':{$value.quackId|escape:'htmlall':'UTF-8'}|replace:'quackTitlePlaceholder':{$value.title|lower|replace:$urlReplaceFrom:$urlReplaceTo|regex_replace:$urlReplaceRegexp:" "|truncate:{$urlMaxLength|escape:'htmlall':'UTF-8'}:" "|escape:'htmlall':'UTF-8'}|escape:'html':'UTF-8'}" target="{$quackDisplayTarget|escape:'html':'UTF-8'}">{$value.title|escape:'htmlall':'UTF-8'}</a>
                        </h3>
                        <div class="tags">
                            {foreach from=$value.tags item=tag}
                            <div class="chip">
                                <a href="{$quackDisplayLink|replace:'quackIdPlaceholder':{$value.quackId|escape:'htmlall':'UTF-8'}|replace:'quackTitlePlaceholder':{$value.title|lower|replace:$urlReplaceFrom:$urlReplaceTo|regex_replace:$urlReplaceRegexp:" "|truncate:{$urlMaxLength|escape:'htmlall':'UTF-8'}:" "}|escape:'html':'UTF-8'}" title="show questions tagged {$tag|escape:'htmlall':'UTF-8'}">{$tag|escape:'htmlall':'UTF-8'}</a>
                            </div>
                            {/foreach}
                        </div>
                         <div class="question-date">
                            <a href="{$quackDisplayLink|replace:'quackIdPlaceholder':{$value.quackId|escape:'htmlall':'UTF-8'}|replace:'quackTitlePlaceholder':{$value.title|lower|replace:$urlReplaceFrom:$urlReplaceTo|regex_replace:$urlReplaceRegexp:" "|truncate:{$urlMaxLength|escape:'htmlall':'UTF-8'}:" "}|escape:'html':'UTF-8'}" class="published">
                                <span title="vom {$value.timestamp|escape:'htmlall':'UTF-8'}">vom {$value.timestamp|date_format:"%d.%m.%y"|escape:'htmlall':'UTF-8'}</span>
                            </a>
                        </div>
                    </div>
                </div>
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
        </div>
    </section>
</div>


<div id="share-modal">
    <div id="modal-content">
        <h2>Quack Teilen</h2>
        <div id="url-box">
            <input class="share-url" value="" />
            <i class="material-icons content-copy" title="Kopieren">content_copy</i>
        </div>
        <div id="share-btn-wrapper">
            <div class="share-shariff"></div>
        </div>
    </div>
    <div class="modal-footer">
        <a id="close-share-modal" href="#!" class="modal-close waves-effect waves-teal-light btn-flat">Schlie&#xDF;en</a>
    </div>
</div>


{/block}