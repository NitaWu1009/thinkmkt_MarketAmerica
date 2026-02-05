<?php
if(!defined('ABSPATH')) exit;

if(!trait_exists('OctopusWP_Plugin_Prefix_Accessible')) {
    trait OctopusWP_Plugin_Prefix_Accessible
    {
        public static function get_plugin_prefix()
        {
            return isset(self::$plugin_prefix) ? self::$plugin_prefix : '';
        }
    }
}