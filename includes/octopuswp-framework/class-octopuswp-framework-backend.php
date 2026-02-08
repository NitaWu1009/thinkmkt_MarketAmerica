<?php
if(!defined('ABSPATH')) exit;

if(!class_exists('OctopusWP_Framework_Backend')) {
    class OctopusWP_Framework_Backend
    {
        use OctopusWP_Plugin_Instance, OctopusWP_Loggable;

        #private $auth_server_url = 'https://www.thinkmkt.com/wp-admin/admin-ajax.php';
        private $auth_server_url = ''; // 移除遠端位址 nitawu
        public function __construct()
        {
            /*
            if (!wp_next_scheduled ('octopuswp_verify_remote')) {
                $this->verify_remote();
                //wp_schedule_event(time() + 10, 'twicedaily', 'octopuswp_verify_remote'); //nitawu
            }
            */ //nitawu
            $this->log_enabled = true;
            $this->log_filename = 'octopuswp-framework-backend';
            $this->register_hooks();
            
        }

        private function register_hooks()
        {
            add_action('octopuswp_verify_remote', [$this, 'verify_remote']);
            add_action('wp_ajax_octopuswp_plugin_activate', [$this, 'ajax_plugin_activate']);
            add_action('wp_ajax_octopuswp_plugin_deactivate', [$this, 'ajax_plugin_deactivate']);
            add_action('admin_menu', [$this, 'add_admin_menu']);
        }

        public function add_admin_menu()
        {
            add_menu_page('Octopus WP', 'Octopus WP', 'manage_options', 'octopuswp');
            add_submenu_page('octopuswp', 'Plugins', 'Plugins', 'manage_options', 'octopuswp', [$this, 'menu_page_plugins']);
            do_action('octopuswp_admin_menu');
        }

        public function menu_page_plugins()
        {
            include_once plugin_dir_path(__FILE__) . 'templates/menu-page-plugins.php';
        }

        private function get_plugins_table_html()
        {
            include OctopusWP_Framework_Path . 'templates/octopuswp-plugins-table.php';
        }

        private function get_reason_text($reason_code)
        {
            $reason = '';
            switch ($reason_code) {
                case 'ACTIVATION_CODE_ERROR':
                    $reason = '無效的啟用碼';
                    break;
                case 'DOMAIN_NOT_MATCHED':
                    $reason = '無效的網域';
                    break;
                case 'PLUGIN_ID_NOT_MATCHED':
                    $reason = '錯誤的外掛ID';
                    break;
                case 'EXPIRED':
                    $reason = '已過期';
                    break;
            }
            return $reason;
        }

        private function get_request_data_text($request_data, $reason_code)
        {
            $request_text = '';
            switch ($reason_code) {
                case 'ACTIVATION_CODE_ERROR':
                    $request_text = '請求的啟用碼:' . $request_data;
                    break;
                case 'DOMAIN_NOT_MATCHED':
                    $request_text = '請求的網域:' . $request_data;
                    break;
                case 'PLUGIN_ID_NOT_MATCHED':
                    $request_text = '請求的外掛ID:' . $request_data;
                    break;
            }
            return $request_text;
        }

        public function ajax_plugin_activate()
        {
            $plugin_id = $_REQUEST['plugin_id'];
            $activation_code = $_REQUEST['activation_code'];
            if($activation_code && $plugin_id) {
                $response = $this->remote_verify_plugin($plugin_id, $activation_code);
                ob_start();
                $this->get_plugins_table_html();
                $html = ob_get_clean();
                $resp = [
                    'activated' => $response->activated,
                    'html'      => $html,
                ];
                if(!$response->activated) {
                    $resp['reason'] = $this->get_reason_text($response->reason);
                    $resp['request'] = $this->get_request_data_text($response->request, $response->reason);
                }
                wp_send_json($resp);
            } else {
                wp_send_json([
                    'activated' => false,
                    'reason' => '外掛ID或啟用碼為空'
                ]);
            }
        }

        public function ajax_plugin_deactivate()
        {
            $plugin_id = $_REQUEST['plugin_id'];
            $option = get_option($plugin_id);
            $option['activated'] = false;
            $option['activation_code'] = '';
            $option['expiration_time'] = '';
            update_option($plugin_id, $option);
            ob_start();
            $this->get_plugins_table_html();
            $html = ob_get_clean();
            wp_send_json([
                'deactivated' => true,
                'html'        => $html
            ]);
        }

        private function get_host()
        {
            switch(OctopusWP_Framework_Site_Url_Source)
            {
                case 'fixed':
                    if(defined('WP_SITEURL')) {
                        $host = parse_url(WP_SITEURL, PHP_URL_HOST);
                    } else {
                        $host = parse_url(get_option('siteurl'), PHP_URL_HOST);
                    }
                    break;
                default:
                    $host = $_SERVER['HTTP_HOST'];
                    break;
            }

            return $host;
        }

        private function remote_verify_plugin($plugin_id, $activation_code)
        {
            $activation_code = trim($activation_code);
            $host = $this->get_host();
            $response = wp_remote_post($this->auth_server_url, array(
                'method' => 'POST',
                'timeout' => 60,
                'body' => array(
                    'action'    => 'octopuswp-plugin-auth',
                    'plugin_id' => $plugin_id,
                    'domain'    => $host,
                    'activation_code' => $activation_code,
                    'version' => get_option("{$plugin_id}_version")
                )
            ));

            $option = get_option($plugin_id);

            $this->log('-------------------------remote_verify_plugin start');

            $auth_connection_error = false;
            if(is_wp_error($response) ||  (!is_wp_error($response) && $response['response']['code'] != 200)) {
                $auth_connection_error = true;
                $this->log($response);
            } else {
                $this->log($response['body']);
                $response = json_decode($response['body']);
            }

            if($response->activated || $auth_connection_error) {
                $option['activation_code'] = $activation_code;
                /**
                 * 如與授權伺服器連線失敗，則使用本地紀錄判斷是否到期
                 */
                if($auth_connection_error) {
                    if(@$option['expiration_time']) {
                        $expiration_time = new DateTime(@$option['expiration_time']);
                        $date_now = new DateTime(current_time('mysql'));
                        if($date_now > $expiration_time) {
                            $option['activated'] = false;
                        } else {
                            $option['activated'] = true;
                        }
                    } else {
                        $option['activated'] = true;
                    }
                } else {
                    $option['activated'] = true;
                    $option['expiration_time'] = $response->expiration_time;
                }
            } else {
                $option['activation_code'] = '';
                $option['activated'] = false;
                $option['expiration_time'] = '';
            }

            update_option($plugin_id, $option);

            return $auth_connection_error ? [
                'activated' => $option['activated'],
                'expiration_time' => @$option['expiration_time']
            ] : $response;
        }

        public function verify_remote()
        {
            $octopuswp_plugins = OctopusWP_Framework::get_octopus_plugins();
            foreach ($octopuswp_plugins as $octopuswp_plugin) {
                $option = get_option($octopuswp_plugin['id']);
                if(@$option['activation_code']) {
                    $this->remote_verify_plugin($octopuswp_plugin['id'], @$option['activation_code']);
                } else {
                    $option['activated'] = false;
                    update_option($octopuswp_plugin['id'], $option);
                }
            }
        }
    }

    $GLOBALS['OctopusWP_Framework_Backend'] = OctopusWP_Framework_Backend::get_instance();
}
