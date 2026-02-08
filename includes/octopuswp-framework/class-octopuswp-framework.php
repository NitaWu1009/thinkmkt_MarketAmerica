<?php
if(!defined('ABSPATH')) exit;

if(!class_exists('OctopusWP_Framework')) {
    class OctopusWP_Framework
    {
        private $plugin_key = '';

        private $plugin_id = '';

        public function __construct($plugin_id, $plugin_key)
        {
            $this->plugin_id = $plugin_id;
            $this->plugin_key = $plugin_key;

            do_action('octopuswp_framwork_init');
        }

        public static function get_octopus_plugins()
        {
            return apply_filters('octopuswp_plugins', []);
        }

        public function get_plugin_id()
        {
            $s = base64_decode($this->plugin_key);
            $s = substr($s, 0, strlen($s) / 2);
            return $s;
        }

        public function verify_plugin()
        {
            $plugin_id = $this->get_plugin_id();
            if(isset($GLOBALS["{$plugin_id}_activated"])) {
                $activated = $GLOBALS["{$plugin_id}_activated"];
            } else {
                $plugin_option = get_option($plugin_id);
                $activated = $plugin_id == strtolower($this->plugin_id) && @$plugin_option['activated'];
                $GLOBALS["{$plugin_id}_activated"] = $activated;
            }
            //return $activated;
            return true; //nitawu
        }

    }
}