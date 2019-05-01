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

{block name='head_seo'}	
	{if !isset($headTitle) || !$headTitle}
		{assign var=headTitle value="{$question}"}
	{/if}
	{if !isset($headDescription) || !$headDescription}
		{assign var=headDescription value="Lies mehr zu \"{$question}\""}
	{/if}
	{if !isset($headKeywords) || !$headKeywords}
		{assign var=headKeywords value="{$tags}"}
	{/if}
    {if !isset($headLink) || !$headLink}
		{assign var=headLink value="{$questionLink}"}
	{/if}
	<title>{$headTitle|escape:'htmlall':'UTF-8'}</title>
	<meta name="description" content="{$headDescription|escape:'htmlall':'UTF-8'}" />
    <meta name="keywords" content="{$headKeywords|escape:'htmlall':'UTF-8'}" />
    <link rel="shortlink" href="{$headLink|escape:'htmlall':'UTF-8'}">
    <meta property="og:title" content="{$headTitle|escape:'htmlall':'UTF-8'}" />
    <meta property="og:description" content="{$headDescription|escape:'htmlall':'UTF-8'}">
    <meta property="og:url" content="{$headLink|escape:'htmlall':'UTF-8'}">
    <meta property="og:type" content="website">
    <meta name="twitter:title" itemprop="title name" content="{$headTitle|escape:'htmlall':'UTF-8'}">
    <meta name="twitter:description" itemprop="description" content="{$headDescription|escape:'htmlall':'UTF-8'}">
    <meta name="twitter:card" content="summary">
    <meta name="mobile-web-app-capable" content="yes">
{/block}

{block name='page_content'}
<div id="quack-single-chat-container" class="main">
  <section id="quack-container" itemprop="mainEntity" itemscope itemtype="http://schema.org/Question">
		<h1 itemprop="name" id="question">{$question|escape:'htmlall':'UTF-8'}</h1>
		{if isset($messages) && $messages}
		<div id="quacklist-wrapper" class="flush-left">
			<div id="quack-overview-list" quack-data="{$quackId|escape:'htmlall':'UTF-8'}">
				<div id="stats-wrapper">
					<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating" class="votes">
						<div class="mini-counts"><meta itemprop="worstRating" content="1"><span itemprop="ratingValue" title="{$quackity|round:1|escape:'htmlall':'UTF-8'} rated quality">{$quackity|round:1|escape:'htmlall':'UTF-8'}</span><meta itemprop="bestRating" content="10"></div>
						<div>&nbsp;quackity</div>
					</div>
					<div itemprop="interactionStatistic" itemscope itemtype="http://schema.org/InteractionCounter" class="views">
						<div itemprop="interactionType" href="http://schema.org/WatchAction" class="mini-counts"><span itemprop="userInteractionCount" title="{$views|escape:'htmlall':'UTF-8'} views">{$views|escape:'htmlall':'UTF-8'}</span></div>
						<div>&nbsp;views</div>
					</div>
					<div class="question-date"><a href="{$produckLink|escape:'htmlall':'UTF-8'}" class="published" target="_blank"><span itemprop="dateCreated" datetime="{$date|escape:'htmlall':'UTF-8'}" title="beantwortet am {$date|escape:'htmlall':'UTF-8'}">vom {$date|escape:'htmlall':'UTF-8'}</span></a></div>
				</div>

				{assign var=answerCount value=0}
				{foreach from=$messages key=messageid item=message}
					{assign var=messageSenderIdentifyingClass value=($firstQuacker==$message.userId)?'left-duck':'right-duck'}
					{assign var=author value=($firstQuacker==$message.userId)?'Ducky':'Experte'}
					{assign var=itemPropAnswer value=($firstQuacker!=$message.userId) ? 'itemprop="acceptedAnswer" itemscope itemtype="http://schema.org/Answer"' : ''}
					{if $firstQuacker!=$message.userId}
						{assign var=answerCount value=$answerCount+1}
					{/if}

					<div class="dialogue-summary narrow {$messageSenderIdentifyingClass|escape:'htmlall':'UTF-8'}" {$itemPropAnswer|escape:'htmlall':'UTF-8'}>
						<div itemprop="author" itemscope itemtype="http://schema.org/Person" class="author"><span itemprop="name" class="author-name">{$author|escape:'htmlall':'UTF-8'}</span></div>
						<div class="summary-text"><h3 itemprop="text"><span class="question-hyperlink">{$message.text|escape:'htmlall':'UTF-8'}</span></h3></div>
					</div>
				{/foreach}

				<meta itemprop="answerCount" content="{$answerCount|escape:'htmlall':'UTF-8'}" />
				<div id="share-brand">
					<div class="share">
						<i class="material-icons">share</i>
					</div>
					<div id="host-wrap-wrapper">
					<a class="host-ref" href="{$produckLink|escape:'htmlall':'UTF-8'}" target="_blank">
						<span>Provided by ProDuck</span>
						<img src="{$duckyImage|escape:'htmlall':'UTF-8'}" alt="helpful ducky"/>
					</a>
				</div>
			</div>
    </div>
		{else}
			<p>Dieser Quack kann aktuell nicht angezeigt werden.</p>
		{/if}
  </section>
	<div class="more-quacks-ref">
    <a href="{$quackOverviewLink|escape:'htmlall':'UTF-8'}">Mehr Quacks</a>
  </div>
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
