<?php
defined('ABSPATH') || exit;

if(!class_exists('OWP_WCMA_WC_Settings_Page')) {
    class OWP_WCMA_WC_Settings_Page extends WC_Settings_Page
    {
        public function __construct()
        {
            $this->id    = 'mai-an';
            $this->label = '美安';
            parent::__construct();
        }

        public function get_sections()
        {
            return array(
                '' => '一般設定'
            );
        }

        public function get_settings()
        {
            return OctopusWP_WC_Mei_An::get_settings()['general'];
        }
    }
    return new OWP_WCMA_WC_Settings_Page();
} else {
    return false;
}
