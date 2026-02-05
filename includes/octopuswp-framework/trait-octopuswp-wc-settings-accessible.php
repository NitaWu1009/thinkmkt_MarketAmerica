<?php
if(!trait_exists('OctopusWP_WC_Settings_Accessible')) {
    trait OctopusWP_WC_Settings_Accessible
    {
        public static $settings;

        use OctopusWP_Plugin_Prefix_Accessible;

        public static function get_settings($section = null)
        {
            $plugin_prefix = self::get_plugin_prefix();
            $settings = apply_filters($plugin_prefix . "settings", @self::$settings);
            if($section) {
                return $settings[$section];
            } else {
                return $settings;
            }
        }

        public static function get_setting($key)
        {
            $plugin_prefix = self::get_plugin_prefix();
            $default = '';
            $key_exist = false;
            $options = null;
            foreach ((array)self::get_settings() as $section => $settings) {
                foreach ($settings as $setting) {
                    if("{$plugin_prefix}{$key}" == @$setting['id']) {
                        $default = isset($setting['default']) ? $setting['default'] : '';
                        $options = @$setting['options'];
                        $key_exist = true;
                        break;
                    }
                }
                if($key_exist)
                    break;
            }



            if($key_exist) {
                $value = get_option("{$plugin_prefix}{$key}", $default);
            } else {
                $value = apply_filters("{$plugin_prefix}none_exist_setting_key_value", '', $key);
            }


            if(!empty($options)) {
                if(!is_array($value) && !in_array($value, array_keys($options))) {
                    $value = $default;
                    update_option("{$plugin_prefix}{$key}", $value);
                }
            }

            if(is_numeric($value) && !preg_match('/^0/', $value)) {
                if(is_integer($value)) {
                    $value = (int)$value;
                } else {
                    $value = $value ? (float)$value : (int)$value;
                }
            }

            return apply_filters("{$plugin_prefix}setting_" . $key, $value);
        }
    }
}
