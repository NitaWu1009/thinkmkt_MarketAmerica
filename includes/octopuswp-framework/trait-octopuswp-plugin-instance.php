<?php
if(!defined('ABSPATH')) exit;

if(!trait_exists('OctopusWP_Plugin_Instance')) {
    trait OctopusWP_Plugin_Instance
    {
        protected static $instance;

        public static function get_instance()
        {
            if(is_null(static::$instance) || get_class(static::$instance) != static::class) {
                static::$instance = new static();
            }
            return static::$instance;
        }
    }
}