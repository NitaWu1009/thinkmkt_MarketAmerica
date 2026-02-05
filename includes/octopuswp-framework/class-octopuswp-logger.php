<?php
if(!defined('ABSPATH')) exit;

if(!class_exists('OctopusWP_Logger')) {
    class OctopusWP_Logger
    {
        private $api_log_path;

        private $opened_files = array();

        public function __construct()
        {
            $api_log_path = wp_upload_dir()['basedir'] . '/' . OctopusWP_Framework_Log_Dir;
            if(!is_dir($api_log_path)) {
                mkdir($api_log_path);
            }
            $this->api_log_path = $api_log_path;
        }

        public function __destruct()
        {
            foreach ($this->opened_files as $log_name) {
                if (is_resource($log_name)) {
                    fclose($log_name);
                }
            }
        }

        protected function get_log_file_path($log_name)
        {
            return trailingslashit($this->api_log_path) . sanitize_file_name($log_name . '-' . wp_hash($log_name)) . '.log';
        }

        protected function open($log_name, $mode = 'a')
        {
            if (isset( $this->opened_files[$log_name])) {
                return true;
            }

            if ($this->opened_files[$log_name] = @fopen($this->get_log_file_path($log_name), $mode)) {
                return true;
            }

            return false;
        }

        protected function close($log_name)
        {
            $result = false;

            if (is_resource($this->opened_files[$log_name])) {
                $result = fclose( $this->opened_files[$log_name]);
                unset($this->opened_files[$log_name]);
            }

            return $result;
        }

        public function add($log_name, $message)
        {
            $result = false;

            if ($this->open($log_name) && is_resource($this->opened_files[$log_name])) {
                $time   = date_i18n( 'm-d-Y @ H:i:s -' );
                $result = fwrite($this->opened_files[$log_name], $time . " " . $message . "\n");
            }

            return false !== $result;
        }

        public function clear( $log_name )
        {
            $result = false;

            $this->close($log_name);

            if ($this->open($log_name, 'w') && is_resource($this->opened_files[$log_name])) {
                $result = true;
            }

            return $result;
        }
    }
}