<?php
if(!defined('ABSPATH')) exit;

if(!trait_exists('OctopusWP_Loggable')) {
    trait OctopusWP_Loggable
    {
        private $log_enabled = false;

        private $log_filename = 'log';

        protected function log($data, $filename = '')
        {
            if($this->log_enabled) {
                $logger = null;
                if(function_exists('wc_get_logger')) {
                    $logger = wc_get_logger();
                } else if(class_exists('WC_Logger')){
                    $logger = new WC_Logger();
                } else {
                    $logger = new OctopusWP_Logger();
                }
                $logger->add($filename ? $filename : $this->log_filename, print_r($data, 1));
            }
        }
    }
}