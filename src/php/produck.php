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

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once 'classes/produckfetch.php';
include_once 'classes/produckcache.php';

class Produck extends Module
{
    // @if ENV='production'
    const PRODUCK_URL = 'https://produck.de/';
    // @endif
    // @if ENV!='production'
    const PRODUCK_URL = 'https://localhost/';
    // @endif

    public function __construct()
    {
        $this->name = 'produck';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'MonsTec UG haftungsbeschränkt';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->module_key = '2255ba0212baf69f3f57a5d3fed44e50';

        parent::__construct();

        $this->displayName = $this->l('ProDuck Quack and Chat Integration Module');
        $this->description = $this->l(
            'This module enables you to show Quacks in your shop and integrates the ProDuck Chat Service.'
        );

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the ProDuck integration?');

        if (!Configuration::get('PRODUCK_CUSTOMER_ID')) {
            $this->warning = $this->l('No customer ID provided');
        }

        // replacements for pretty urls
        $this->urlReplaceFrom = array(' ', 'ä', 'ö', 'ü', 'ß');
        $this->urlReplaceTo = array('-', 'ae', 'oe', 'ue', 'ss');
        $this->urlReplaceRegexp = '/[^a-z0-9 -]/';

        // currently used shop language
        $languageCode = Context::getContext()->language->iso_code;

        // cache keys - should be unique within the system
        $this->cacheKeys = array("quackOverview" => $languageCode."_produckQuackOverview");
        // for single quacks, its just a prefix with the quackid appended
        $this->cacheKeys["specificQuack"] = $languageCode."_produckQuack_";

        // object for fetching data - init with current language
        $this->produckFetch = new ProduckFetch($languageCode);

        // object for accessing cache
        $this->produckCache = ProduckCache::getInstance();
    }

    /*
     * Performs necessary tasks when adding the quack module to the active modules
     * of the current shop.
     */
    public function install()
    {
        if (!parent::install()
            // register at default hook to display quacks in - all other (implemented, see below)
            // hooks can be configured by customer
            || !$this->registerHook('displayHome')
            // register exceptions for hooks
            || !$this->registerExceptions(
                Hook::getIdByName('displayHome'),
                array(
                    // for the quack overview controller (to not show quacks twice)
                    'module-produck-overview',
                    // for the quack detail controller (to not show quacks twice)
                    'module-produck-display',
                    // for the quack async update (because hooks are not used here)
                    'module-produck-update',
                )
            )
            // used for chat overlay
            || !$this->registerHook('displayFooterBefore')
            // header is used to provide custom css and js
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('actionFrontControllerSetMedia')
            // required for advanced URL rewriting
            || !$this->registerHook('moduleRoutes')
            // use a default of 10 quacks
            || !Configuration::updateValue('PRODUCK_NUMBER_OF_QUACKS', 10)
            // default: open links in new tab/window
            || !Configuration::updateValue('PRODUCK_OPEN_QUACK_IN_WINDOW', true)
            // use a default of 100 chars in pretty quack URL
            || !Configuration::updateValue('PRODUCK_QUACK_URL_LENGTH', 100)
        ) {
            return false;
        }

        return true;
    }

    /*
     * Removes the module from the shop it has been installed to previously.
     */
    public function uninstall()
    {
        // try to delete but actually dont fail if it didnt work
        ProduckCache::getInstance()->deleteCacheDirectory();
        if (!parent::uninstall()
            || !Configuration::deleteByName('PRODUCK_CUSTOMER_ID')
            || !Configuration::deleteByName('PRODUCK_NUMBER_OF_QUACKS')
            || !Configuration::deleteByName('PRODUCK_OPEN_QUACK_IN_WINDOW')
            || !Configuration::deleteByName('PRODUCK_QUACK_URL_LENGTH')
        ) {
            return false;
        }

        return true;
    }

    /*
     * This method is responsible for showing and changing configuration values.
     */
    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name)) {
            $customerId = (string)(Tools::getValue('PRODUCK_CUSTOMER_ID'));
            $numberOfQuacks = (string)(Tools::getValue('PRODUCK_NUMBER_OF_QUACKS'));
            // _0 comes from the checkbox id in the form
            // inverse logic to catch all mistakes and have default off
            $openInNewWindow = (bool)((string)(
                Tools::getValue('PRODUCK_OPEN_QUACK_IN_WINDOW_0')) != "on" ? false : true
            );
            $lengthQuackUrl = (string)(Tools::getValue('PRODUCK_QUACK_URL_LENGTH'));
            if (!$customerId
                || empty($customerId)
                || !Validate::isUnsignedId($customerId)
                || !Validate::isUnsignedInt($numberOfQuacks)
                || !Validate::isBool($openInNewWindow)
                || !Validate::isUnsignedInt($lengthQuackUrl)
            ) {
                $output .= $this->displayError($this->l('Invalid Configuration'));
            } else {
                Configuration::updateValue('PRODUCK_CUSTOMER_ID', $customerId);
                Configuration::updateValue('PRODUCK_NUMBER_OF_QUACKS', $numberOfQuacks);
                Configuration::updateValue('PRODUCK_OPEN_QUACK_IN_WINDOW', $openInNewWindow);
                Configuration::updateValue('PRODUCK_QUACK_URL_LENGTH', $lengthQuackUrl);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }

    /*
     * Configuration form
     */
    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $fields_form = array();

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Produck Customer ID'),
                    'name' => 'PRODUCK_CUSTOMER_ID',
                    'size' => 20,
                    'required' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Number of Quacks to show'),
                    'name' => 'PRODUCK_NUMBER_OF_QUACKS',
                    'size' => 3,
                    'required' => true,
                ),
                array(
                    'type' => 'checkbox',
                    //! @TODO can this not be handled easier?
                    // use "switch" type maybe? https://stackoverflow.com/a/28065842/875020
                    'values' => array(
                        'query' => array(array('id' => '0', 'name' => '', 'value' => 'true')),
                        'id' => 'id',
                        'name' => 'name',
                        'value' => 'value'
                    ),
                    'label' => $this->l('Open Quack in new window'),
                    // ID "_0" will be appended
                    'name' => 'PRODUCK_OPEN_QUACK_IN_WINDOW'
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Length of title in pretty quack URLs'),
                    'name' => 'PRODUCK_QUACK_URL_LENGTH',
                    'size' => 5,
                    'required' => true,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['PRODUCK_CUSTOMER_ID'] = Configuration::get('PRODUCK_CUSTOMER_ID');
        $helper->fields_value['PRODUCK_NUMBER_OF_QUACKS'] = Configuration::get('PRODUCK_NUMBER_OF_QUACKS');
        $helper->fields_value['PRODUCK_OPEN_QUACK_IN_WINDOW_0']
            = (bool)Configuration::get('PRODUCK_OPEN_QUACK_IN_WINDOW');
        $helper->fields_value['PRODUCK_QUACK_URL_LENGTH'] = Configuration::get('PRODUCK_QUACK_URL_LENGTH');

        return $helper->generateForm($fields_form);
    }

    /**
     * URL rewriting/routing hook
     */
    public function hookmoduleRoutes($params)
    {
        $routes = array();

        $routes['module-produck-display'] = array(
            'controller'=>'display',
            'rule'=>'quack/{quackId}/{title}',
            'keywords'=>array(
                'quackId'=>array(
                    'regexp'=>'[0-9]+',
                    'param'=>'quackId'
                ),
                'title'=>array(
                    'regexp'=>'.*$',
                    'param'=>'title'
                )
            ),
            'params'=>array(
                'fc'=>'module',
                'module'=>'produck',
                'controller'=>'display'
            )
        );

        $routes['module-produck-overview'] = array(
            'controller'=>'overview',
            'rule'=>'quacks',
            'keywords'=>array(),
            'params'=>array(
                'fc'=>'module',
                'module'=>'produck',
                'controller'=>'overview'
            )
        );

        return $routes;
    }

    /**
     * Adds code to the head-tag of the html-page (e.g. inclusion of a css-file).
     */
    public function hookDisplayHeader()
    {
        // "server: remote" is required because the generated links are absolute URI (external) and not relative file
        // paths (local)
        $this->context->controller->registerStylesheet(
            'produck-quacks-style',
            $this->context->link->getMediaLink(
                _MODULE_DIR_.$this->name.'/views/css/quacks.min.css'
            ),
            array('media' => 'all','priority' => 200, 'server' => 'remote')
        );
        $this->context->controller->registerStylesheet(
            'produck-chat-style',
            $this->context->link->getMediaLink(
                _MODULE_DIR_.$this->name.'/views/css/produckchat.min.css'
            ),
            array('media' => 'all', 'priority' => 150, 'server' => 'remote')
        );
        $this->context->controller->registerJavascript(
            'produck-script',
            $this->context->link->getMediaLink(_MODULE_DIR_.$this->name.'/views/js/produck.min.js'),
            array('media' => 'all','priority' => 500, 'inline' => true, 'server' => 'remote', 'position' => 'bottom')
        );
        $this->context->controller->registerJavascript(
            'shariff-lib-cdn',
            'https://cdn.jsdelivr.net/npm/shariff@3.0.1/dist/shariff.min.js',
            array('media' => 'all','priority' => 300, 'inline' => true, 'server' => 'remote', 'position' => 'bottom')
        );
        $this->context->controller->registerStylesheet(
            'shariff-style-cdn',
            'https://cdn.jsdelivr.net/npm/shariff@3.0.1/dist/shariff.complete.css',
            array('media' => 'all','priority' => 202, 'inline' => true, 'server' => 'remote', 'position' => 'head')
        );
        $this->context->controller->registerJavascript(
            'cookie-lib-cdn',
            'https://cdn.jsdelivr.net/npm/js-cookie@2/src/js.cookie.min.js',
            array('media' => 'all','priority' => 400, 'inline' => true, 'server' => 'remote', 'position' => 'bottom')
        );
    }

    /**
     * Implement display hooks - user can configure to show plugin in those implemented hooks
     */
    public function hookDisplayRightColumn($params)
    {
        return $this->quackDisplay($params);
    }

    public function hookDisplayLeftColumn($params)
    {
        return $this->quackDisplay($params);
    }

    public function hookDisplayNotFound($params)
    {
        return $this->quackDisplay($params);
    }

    public function hookDisplayHome($params)
    {
        return $this->quackDisplay($params);
    }

    public function hookDisplayBeforeBodyClosingTag($params)
    {
        return $this->quackDisplay($params);
    }

    public function hookDisplayWrapperBottom($params)
    {
        return $this->quackDisplay($params);
    }

    public function hookDisplayBanner($params)
    {
        return $this->quackDisplay($params);
    }

    public function hookDisplayContentWrapperBottom($params)
    {
        return $this->quackDisplay($params);
    }

    public function hookDisplayFooter($params)
    {
        return $this->quackDisplay($params);
    }

    public function hookDisplayFooterBefore($params)
    {
        return $this->chatDisplay($params);
    }

    private function chatDisplay($params)
    {
        $cid = Configuration::get('PRODUCK_CUSTOMER_ID');

        // this will open a general chat (not customer specific)
        if ($cid == null || empty($cid) || $cid <=0) {
            $cid = '';
            $params = '';
        } else {
            $params = "?cid=".$cid;
        }

        $this->context->smarty->assign(
            array(
                'ducky_image' => $this->getImageURL('ducky.png'),
                'cid' => $cid,
                'params' => $params,
                'produck_url' => self::PRODUCK_URL
            )
        );

        return $this->display(__FILE__, 'views/templates/hook/chat.tpl');
    }

    public function quackDisplay()
    {
        // only display quacks when customer id is set
        $cid = Configuration::get('PRODUCK_CUSTOMER_ID');

        if ($cid === null || $cid === false || $cid <=0) {
            return;
        }

        if (!$this->generateQuackOverviewContext(
            $this->context,
            (int)Configuration::get('PRODUCK_NUMBER_OF_QUACKS')
        )) {
            return;
        }
        return $this->display(__FILE__, 'views/templates/hook/quacks.tpl');
    }

    // generate smarty context for quack overview page - also used in controller -> overview
    public function generateQuackOverviewContext($context, $quackCountLimit = 0)
    {
        $cacheData = $this->getCacheData(
            $this->cacheKeys["quackOverview"],
            array($this->produckFetch, 'getQuacks'),
            array()
        );

        // if endpoint data cant be verified, invalidate the cache so its refetched on next request
        $endpointData = $this->verifyCacheData($cacheData);
        if ($endpointData === false || !isset($endpointData[0]) || !isset($endpointData[0]['quackId'])) {
            $this->produckCache->delete($this->cacheKeys["quackOverview"]);
            return false;
        }

        // limit data array to number of entries
        if ($quackCountLimit > 0) {
            //! @TODO some sorting or filtering would be nice in the future
            $endpointData = array_slice($endpointData, 0, $quackCountLimit);
        }

        // contruct the link to the quack details page(s)
        $controllerLink = $context->link->getModuleLink(
            $this->name,
            'display',
            array('quackId' => 'quackIdPlaceholder', 'title' => 'quackTitlePlaceholder')
        );

        // fill template
        $context->smarty->assign(
            array(
                'templateDirectory' => _PS_MODULE_DIR_.$this->name.'/views/templates/',
                'urlReplaceFrom' => $this->urlReplaceFrom,
                'urlReplaceTo' => $this->urlReplaceTo,
                'urlReplaceRegexp' => $this->urlReplaceRegexp,
                'urlMaxLength' => Configuration::get('PRODUCK_QUACK_URL_LENGTH'),
                'produckLink' => $this->getCustomerProduckLink(),
                'quackOverviewLink' => $context->link->getModuleLink($this->name, 'overview', array()),
                'quackDisplayLink' => $controllerLink,
                'quackDisplayTarget' => ((bool)Configuration::get('PRODUCK_OPEN_QUACK_IN_WINDOW') ? "_blank" : ""),
                'quackData' => $endpointData,
                'duckyImage' => $this->getImageURL('ducky.png'),
            )
        );

        return true;
    }

    public function getImageURL($image)
    {
        return $this->context->link->getMediaLink(_MODULE_DIR_.$this->name.'/views/img/'.$image);
    }

    public function transformQuestionToUrlPart($question, $maxlength)
    {
        return Tools::substr(
            preg_replace(
                $this->urlReplaceRegexp,
                '',
                str_replace($this->urlReplaceFrom, $this->urlReplaceTo, Tools::strtolower($question))
            ),
            0,
            $maxlength
        );
    }

    // customer specific entry point
    public function getCustomerProduckLink()
    {
        return self::PRODUCK_URL.'?cid='.Configuration::get('PRODUCK_CUSTOMER_ID');
    }

    /**
     * Get data from cache and update it if possible
     */
    public function getCacheData($cacheKey, callable $updateMethod, $parameters, $ttl = 60)
    {
        $cacheValue = "";

        // get cached value
        if ($this->produckCache->exists($cacheKey)) {
            $cacheValue = $this->produckCache->get($cacheKey);

            // updateMethod is used to distinguish call for quacks or single quack
            if (!is_array($updateMethod) || count($updateMethod) < 2 || !isset($updateMethod[1])) {
                // not sure what to do
                return $cacheValue;
            }

            // only getQuack and getQuacks supported currently
            if ($updateMethod[1] != 'getQuack' && $updateMethod[1] != 'getQuacks') {
                return $cacheValue;
            }

            // getQuack requires the parameter (quackid) to be set
            if ($updateMethod[1] == 'getQuack' && (!is_array($parameters) || count($parameters) == 0)) {
                return $cacheValue;
            }

            // fetch quackid
            $quackId=0;
            if ($updateMethod[1] == 'getQuack') {
                $quackId = (int)$parameters[0];
            }

            // trigger async cache update
            $this->produckFetch->triggerUrl(
                $this->context->link->getModuleLink($this->name, 'update', array('quackId' => $quackId))
            );
        } else {
            $cacheValue = $this->updateCacheData($cacheKey, $updateMethod, $parameters, $ttl);
        }

        return $cacheValue;
    }

    public function updateCacheData($cacheKey, callable $updateMethod, $parameters, $ttl = 60)
    {
        $cacheValue = '';
        // perform a sync cache update
        $contents = call_user_func_array($updateMethod, $parameters);

        if ($contents !== false && Tools::strlen($contents) > 0) {
            $cacheValue = $contents;

            $this->produckCache->set($cacheKey, $contents, $ttl);
        }

        return $cacheValue;
    }

    public function verifyCacheData($cacheData)
    {
        // the cache data contains empty value - invalidate
        if (empty($cacheData) || Tools::strlen($cacheData) == 0) {
            return false;
        }

        // convert to array of arrays, hence second parameter "true" - also hide possible errors (checked later)
        $endpointData = @json_decode($cacheData, true);

        // verification of data
        if ($endpointData === null || json_last_error() !== JSON_ERROR_NONE
            || !is_array($endpointData)  || count($endpointData) == 0) {
            return false;
        }

        return $endpointData;
    }
}
