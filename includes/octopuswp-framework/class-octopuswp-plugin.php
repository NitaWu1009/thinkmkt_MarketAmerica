<?php
if(!defined('ABSPATH')) exit;

if(!class_exists('OctopusWP_Plugin')) {
    abstract class OctopusWP_Plugin
    {
        use OctopusWP_Plugin_Instance;

        /**
         * 外掛檔案位置
         *
         * @var string
         */
        private $plugin_file;

        /**
         * @var string
         */
        protected $asset_file_dev_suffix;

        /**
         * @var OctopusWP_Framework
         */
        public $octopuswp_framework;

        /**
         * @var OctopusWP_Plugin_Updater
         */
        public $octopuswp_plugin_updater;

        /**
         * @var string
         */
        protected $octopuswp_plugin_id = '';

        /**
         * @var string
         */
        protected $octopuswp_plugin_key = '';

        /**
         * @var string
         */
        protected $octopuswp_plugin_name = '';
        /**
         * @var string
         */
        protected $octopuswp_plugin_version = '';
        /**
         * @var string
         */
        protected $octopuswp_plugin_description = '';

        public function __construct($plugin_file, $plugin_id, $plugin_key, $plugin_name = '', $plugin_description = '', $plugin_version = '')
        {
            $this->asset_file_dev_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            $this->plugin_file = $plugin_file;
            $this->octopuswp_plugin_id = $plugin_id;
            $this->octopuswp_plugin_key = $plugin_key;
            $this->octopuswp_plugin_name = $plugin_name;
            $this->octopuswp_plugin_description = $plugin_description;
            $this->octopuswp_plugin_version = $plugin_version;
            $this->init_octopuswp_framework();
            $this->register_hooks();
            $this->init_octopuswp_plugin_updater($plugin_file, $plugin_id, $plugin_name);
        }

        private function init_octopuswp_framework()
        {
            $this->octopuswp_framework = new OctopusWP_Framework($this->octopuswp_plugin_id, $this->octopuswp_plugin_key);
        }

        private function init_octopuswp_plugin_updater($plugin_file, $plugin_id, $plugin_name)
        {
            $this->octopuswp_plugin_updater = new OctopusWP_Plugin_Updater($plugin_file, $plugin_id, $plugin_name);
        }

        private function register_hooks()
        {
            add_filter('octopuswp_plugins', [$this, 'add_octopus_plugin']);
            add_action('wp_footer', [$this, 'octopuswp_footer_copy_right']);
            add_action('octopuswp_plugin_init', [$this, 'perform_plugin_upgrade']);
        }

        public function add_octopus_plugin($plugins)
        {
            $plugins[] = [
                'id'   => $this->octopuswp_plugin_id,
                'name' => $this->octopuswp_plugin_name,
                'description' => $this->octopuswp_plugin_description,
                'version' => $this->octopuswp_plugin_version,
            ];
            return $plugins;
        }

        public function octopuswp_footer_copy_right()
        {
            $authorized = $this->octopuswp_framework->verify_plugin() ? 'authorized' : 'but not authorized';
            include OctopusWP_Framework_Path . 'templates/footer-plugin-authorization.php';
        }

        public function perform_plugin_upgrade($plugin_class)
        {
            $upgrade_file = plugin_dir_path($this->plugin_file) . 'upgrade.php';
            if(file_exists($upgrade_file)) {
                $version_key = $plugin_class::$plugin_id . '_version';

                $current_version = $plugin_class::$plugin_version;

                $record_version = get_option($version_key);

                if(!$record_version) {
                    $record_version = '0.0.0';
                }

                if($record_version == $current_version) {
                    return;
                }

                require_once $upgrade_file;
            }
        }

        protected function define_const($name, $value)
        {
            if(!defined($name)) {
                define($name, $value);
            }
        }

        protected function add_path_left_slash($path)
        {
            if(!$path) {
                return '';
            }
            return '/' . ltrim($path, '/');
        }

        private function normalize_sub_dir($sub_dir)
        {
            if(!$sub_dir) {
                return '';
            }
            return untrailingslashit($this->add_path_left_slash($sub_dir));
        }

        public function plugin_url($sub_dir = '')
        {
            return untrailingslashit(plugins_url('/', $this->plugin_file)) . $this->normalize_sub_dir($sub_dir);
        }

        public function plugin_dir($sub_dir = '')
        {
            return untrailingslashit(plugin_dir_path($this->plugin_file)) . $this->normalize_sub_dir($sub_dir);
        }

        public function require_file($file)
        {
            return require_once $this->plugin_dir('/includes') . "/{$file}.php";
        }

        public function include_template($template, $variables = [])
        {
            extract($variables);
            return include $this->plugin_dir('/templates') . "/{$template}.php";
        }

        public function assets_url($sub_dir = '')
        {
            return $this->plugin_url('/assets') . $this->normalize_sub_dir($sub_dir);
        }

        public function assets_js_url($file = '')
        {
            return $this->plugin_url('/assets/js') . $this->normalize_sub_dir("{$file}.js");
        }

        public function assets_css_url($file = '')
        {
            return $this->plugin_url('/assets/css') . $this->normalize_sub_dir("{$file}.css");
        }

        protected function attach_min_suffix($file)
        {
            return "{$file}{$this->asset_file_dev_suffix}";
        }
    }
}