<?php
if(!class_exists('OctopusWP_Plugin_Updater')) {
    class OctopusWP_Plugin_Updater
    {
        private $plugin_slug;

        private $plugin_name;

        private $octopuswp_plugin_id;

        private $octopuswp_plugin_name;

        private $remote_server_url = 'https://www.octopuswp.com/wp-admin/admin-ajax.php';

        public function __construct($plugin_file, $plugin_id, $plugin_name)
        {
            $this->plugin_name = plugin_basename($plugin_file);
            $this->plugin_slug = basename($plugin_file, '.php');
            $this->octopuswp_plugin_id = $plugin_id;
            $this->octopuswp_plugin_name =$plugin_name;
            
            $this->register_hooks();
        }
        
        private function register_hooks()
        {
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'plugins_api_filter' ], 10, 3);
        }

        public function check_update($transient)
        {
            if (!is_object($transient)) {
                $transient = new \stdClass();
            }

            $current_version = get_option("{$this->octopuswp_plugin_id}_version");
            $latest_version = $this->remote_check_version($this->octopuswp_plugin_id);
            if($latest_version) {
                if(version_compare($current_version, $latest_version->version, '<')) {
                    $version_obj = new \stdClass();
                    $version_obj->id           = $this->octopuswp_plugin_id;
                    $version_obj->slug         = $this->plugin_slug;
                    $version_obj->plugin       = $this->plugin_name;
                    $version_obj->new_version  = $latest_version->version;
                    $version_obj->url          = $latest_version->url;
                    $version_obj->package      = $latest_version->package;
                    $version_obj->tested       = $latest_version->tested;
                    $version_obj->requires_php = $latest_version->requires_php;
                    $version_obj->icons        = (array)$latest_version->icons;
                    $version_obj->banners      = (array)$latest_version->banners;
                    $transient->response[$this->plugin_name]=  $version_obj;
                }
            }
            return $transient;
        }

        public function plugins_api_filter($data, $action = '', $args = null)
        {
            if (!isset($args->slug) || ($args->slug !== $this->plugin_slug)) {
                return $data;
            }

            $latest_version = $this->remote_check_version($this->octopuswp_plugin_id);
            if($latest_version) {
                $api_obj                = new \stdClass();
                $api_obj->name          = $this->octopuswp_plugin_name;
                $api_obj->slug          = $this->plugin_slug;
                $api_obj->author        = $latest_version->author;
                $api_obj->homepage      = $latest_version->url;
                $api_obj->requires      = $latest_version->requires;
                $api_obj->tested        = $latest_version->tested;
                $api_obj->version       = $latest_version->version;
                $api_obj->last_updated  = $latest_version->published_at;
                $api_obj->download_link = $latest_version->package;
                $api_obj->banners       = (array)$latest_version->banners;
                $api_obj->icons         = (array)$latest_version->icons;
                $api_obj->sections      = unserialize($latest_version->sections);
                $data = $api_obj;
            }
            return $data;
        }

        private function remote_check_version($plugin_id)
        {
            $plugin_option = get_option($plugin_id);
            $response = wp_remote_post($this->remote_server_url, array(
                'method' => 'POST',
                'timeout' => 60,
                'body' => array(
                    'action'    => 'octopuswp-check-plugin-version',
                    'plugin_id' => $plugin_id,
                    'domain' => $_SERVER['HTTP_HOST'],
                    'activation_code' => @$plugin_option['activation_code']
                )
            ));
            if(is_wp_error($response) ||  (!is_wp_error($response) && $response['response']['code'] != 200)) {
                return false;
            }
            return json_decode($response['body']);
        }
    }
}