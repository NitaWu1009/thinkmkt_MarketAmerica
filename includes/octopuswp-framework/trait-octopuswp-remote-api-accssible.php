<?php
if(!defined('ABSPATH')) exit;

if(!trait_exists('OctopusWP_Remote_API_Accessible')) {
    trait OctopusWP_Remote_API_Accessible
    {
        use OctopusWP_Loggable;

        /**
         * @param $url
         * @param $data
         * @param int $timeout
         * @param array $args
         * @return array|WP_Error
         * @throws Exception
         */
        private function remote_post($url, $data, $timeout = 60, $args = [])
        {
            $post_args = wp_parse_args($args, array(
                    'method' => 'POST',
                    'timeout' => $timeout,
                    'body' => $data
                )
            );
            $response = wp_remote_post($url, $post_args);

            if(is_wp_error($response) ||  (!is_wp_error($response) && $response['response']['code'] != 200)) {
                $this->log('post error --------------------------------');
                $this->log($url);
                if(is_wp_error($response)) {
                    $this->log($response->errors);
                } else {
                    $this->log('status:' . $response['response']['code']);
                    $this->log('body:' . $response['body']);
                }
                throw new Exception('Failed to connect to API server');
            }

            return $response;
        }
    }
}
