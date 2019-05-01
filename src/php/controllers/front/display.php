<?php
/**
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
 */

/**
 * Show a quack
 */
class ProduckDisplayModuleFrontController extends ModuleFrontController
{
    // calculate integer hash based on string
    public function getHash($string)
    {
        return Tools::substr(base_convert(md5($string), 16, 10), -5);
    }

    public function initContent()
    {
        $quackId = (int)Tools::getValue('quackId');

        // dont act on invalid quacks
        if ($quackId <= 0) {
            Tools::redirect('page-not-found');
        }

        parent::initContent();

        $specificQuackCacheKey = $this->module->cacheKeys["specificQuack"].$quackId;

        $cacheData = $this->module->getCacheData(
            $specificQuackCacheKey,
            array($this->module->produckFetch, 'getQuack'),
            array($quackId)
        );

        // if endpoint data cant be verified, invalidate the cache so its refetched on next request
        $endpointData = $this->module->verifyCacheData($cacheData);
        if ($endpointData === false) {
            $this->module->produckCache->delete($specificQuackCacheKey);
            Tools::redirect('page-not-found');
        }

        // basic data verification - at least 2 entries: messages and title
        if (count($endpointData) < 2 || !isset($endpointData['title']) || !isset($endpointData['messages'])) {
            Tools::redirect('page-not-found');
        }

        // check if quack contains required properties
        $firstQuacker = 0;
        if (isset($endpointData['messages']) && isset($endpointData['messages'][0])
                && isset($endpointData['messages'][0]['userId'])) {
            $firstQuacker = $endpointData['messages'][0]['userId'];
        } else {
            Tools::redirect('page-not-found');
        }

        $question = '';
        if (isset($endpointData['title'])) {
            $question = $endpointData['title'];
        } else {
            Tools::redirect('page-not-found');
        }

        $prettyTitleUrl = $this->module->transformQuestionToUrlPart(
            $question,
            Configuration::get('PRODUCK_QUACK_URL_LENGTH')
        );

        // verify that title in URL is correct, otherwise redirect
        $prettyOwnLink = str_replace(
            array('quackIdPlaceholder', 'titlePlaceHolder'),
            array($quackId, $prettyTitleUrl),
            $this->context->link->getModuleLink(
                $this->module->name,
                'display',
                array('quackId' => 'quackIdPlaceholder', 'title' => 'titlePlaceHolder')
            )
        );

        if ($prettyTitleUrl !== Tools::getValue('title')) {
            Tools::redirect($prettyOwnLink);
        }

        $tags = '';
        if (isset($endpointData['tags'])) {
            $tags = implode(',', $endpointData['tags']);
        }

        $quackity = ($this->getHash($question) % 1000) / 100.0;
        if (isset($endpointData['quackity'])) {
            $quackity = round($endpointData['quackity'], 1);
        }

        $time = new DateTime();
        if (isset($endpointData['timestamp'])) {
            $time = new DateTime($endpointData['timestamp']);
        }

        $date = $time->format('d.m.Y');

        $views = ($this->getHash($question . $date) % 1000);
        if (isset($endpointData['views'])) {
            $views = $endpointData['views'];
        }

        // fill template
        $this->context->smarty->assign(
            array(
                'firstQuacker' => $firstQuacker,
                'quackId' => $quackId,
                'quackity' => $quackity,
                'views' => $views,
                'date' => $date,
                'tags' => $tags,
                'messages' => $endpointData['messages'],
                'question' => $question,
                'duckyImage' => $this->module->getImageURL('ducky.png'),
                'produckLink' => $this->module->getCustomerProduckLink(),
                'quackOverviewLink' => $this->context->link->getModuleLink($this->module->name, 'overview', array()),
                "questionLink" => $prettyOwnLink,
            )
        );

        // define template to be used
        $this->setTemplate('module:produck/views/templates/front/display.tpl');
    }
}
