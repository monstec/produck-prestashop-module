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

// run for at most of 60s in background
set_time_limit(60);

// ingore all errors (due to prestashop overhead)
error_reporting(0);
@ini_set('display_errors', 0);

/**
 * Async update trigger
 */
class ProduckUpdateModuleFrontController extends ModuleFrontController
{
    //public function __construct()
    public function init()
    {
        // remove all pending output
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Connection: close');
        ignore_user_abort(true);
        ob_start();
        echo'Connection Closed';
        $size = ob_get_length();
        header('Content-Length: '.$size);
        // flush the remains to user
        ob_end_flush();
        flush();

        // function to perform after user request is closed
        register_shutdown_function(array(&$this, 'workload'));

        // close session and request
        session_write_close();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // kill myself and let shutdownfunction do the work
        die();
    }

    // actual workload executed after client request is closed
    private function workload()
    {
        // parse quack id
        $quackId = 0;
        if (isset($_REQUEST['quackId']) && !empty($_REQUEST['quackId'])) {
            // cast to integer to limit wrong quack id space
            $quackId = (int) $_REQUEST['quackId'];
        }

        if ($quackId <= 0) {
            $this->module->updateCacheData(
                $this->module->cacheKeys["quackOverview"],
                array($this->module->produckFetch, 'getQuacks'),
                array()
            );
        } else {
            $this->module->updateCacheData(
                $this->module->cacheKeys["specificQuack"].$quackId,
                array($this->module->produckFetch, 'getQuack'),
                array($quackId)
            );
        }
    }
}
