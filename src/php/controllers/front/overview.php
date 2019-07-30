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
 * Show overview of all quacks
 */
class ProduckOverviewModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        // only display quacks when quack token is set
        $token = Configuration::get('PRODUCK_QUACK_TOKEN');

        if ($token === null || $token === false || strlen($token) < 10) {
            Tools::redirect('page-not-found');
        }

        parent::initContent();

        // 0 means no limits on quackcount
        $this->module->generateQuackOverviewContext($this->context, 0);

        $this->setTemplate('module:produck/views/templates/front/overview.tpl');
    }
}
