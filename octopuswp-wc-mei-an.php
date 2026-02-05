<?php
/*
 * Plugin Name: OctopusWP WC Mei-An
 * Description: WooCommerce 美安串接
 * Author: OctopusWP
 * Plugin URI: https://www.octopuswp.com
 * Author URI: https://www.octopuswp.com
 * Version: 1.2.19
 * Date: 2024-03-28
 * WC requires at least: 3.0.0
 * WC tested up to: 5.4.1
 */

defined('ABSPATH') || exit;

if(!class_exists('OctopusWP_WC_Mei_An')) {

    require_once plugin_dir_path(__FILE__) . 'includes/octopuswp-framework/class-octopuswp-framework-loader.php';

    class OctopusWP_WC_Mei_An extends OctopusWP_Plugin
    {
        const PRODUCTS_XML_FILE_NAME = 'octopuswp-wc-mei-an-product-catalog.xml';

        use OctopusWP_WC_Settings_Accessible;

        private $apiUrl = 'https://api.hasoffers.com/Api';

        public static $plugin_prefix = 'owp_wcma_';

        public static $plugin_id = 'octopuswp_wc_mei_an';

        public static $plugin_version = '1.2.19';

        public static $order_statuses = array(
            'created' => 1,
            'cancelled' => 2,
            'creating_failed' => 3,
            'cancelling_failed' => 4
        );

        public function __construct()
        {
            parent::__construct(
                __FILE__,
                self::$plugin_id,
                'b2N0b3B1c3dwX3djX21laV9hbm9jdG9wdXN3cF93Y19tZWlfYW4=',
                'OctopusWP WC Mei-An',
                'WooCommerce 美安串接',
                self::$plugin_version
            );
            do_action('octopuswp_plugin_init', __CLASS__);
            if(!$this->octopuswp_framework->verify_plugin()) return;

            if(!did_action('woocommerce_init')) return;

            self::$settings = $this->require_file('owp-wcma-settings');

            $this->require_files();
            $this->register_hooks();

            do_action(self::$plugin_id . "_init", __CLASS__);
        }

        public static function maybe_schedule_generate_xml()
        {
            if('schedule' == OctopusWP_WC_Mei_An::get_setting('xml_generation_mode')) {
                if(!wp_next_scheduled('owp_wcma_generate_products_xml')) {
                    wp_schedule_event(time() + 10, 'twicedaily', 'owp_wcma_generate_products_xml');
                }
            } else {
                if(wp_next_scheduled('owp_wcma_generate_products_xml')) {
                    wp_clear_scheduled_hook('owp_wcma_generate_products_xml');
                }
            }
        }

        private function require_files()
        {
            $this->require_file('class-owp-wcma-order-exportation');
            $this->require_file('class-owp-wcma-order');
            $this->require_file('class-owp-wcma-order-api');
        }

        private function register_hooks()
        {
            add_action('get_header',                             array($this, 'record_id_to_session'), 0);
            add_action('admin_enqueue_scripts',                  array($this, 'enqueue_admin_scripts'));
            add_filter('woocommerce_get_settings_pages',         array($this, 'add_wc_admin_settings'));
            add_action('woocommerce_checkout_order_processed',   array($this, 'store_id_to_order'),10,3);
            add_action('woocommerce_order_status_changed',       array($this, 'shop_order_create'), 10, 3);
            add_action('woocommerce_order_status_changed',       array($this, 'shop_order_cancel'), 10, 3);
            add_filter('manage_edit-shop_order_columns',         array($this, 'add_column_in_orders_page'));
            add_action('manage_shop_order_posts_custom_column',  array($this, 'add_column_action'), 10, 2);
            add_action('wp_ajax_owp_wcma_create_order',          array($this, 'ajax_create_order'));
            add_action('wp_ajax_owp_wcma_cancel_order',          array($this, 'ajax_cancel_order'));
            add_action('add_meta_boxes',                         array($this, 'add_meta_boxes'));
            add_action('woocommerce_api_octopuswp-wc-mei-an-product-catalog', array($this, 'api_get_products_xml'));
            add_action('owp_wcma_generate_products_xml',         array($this, 'renew_products_xml'));
            add_action('save_post_product',                      array($this, 'save_product_meta'), 10, 2);
        }

        /**
         * 紀錄美安資訊至Session
         * @hooked init
         */
        public function record_id_to_session()
        {
            if( !empty($_GET['RID']) && !empty($_GET['Click_ID'])) {
                setcookie(self::$plugin_prefix . 'rid', $_GET['RID'], 0, '/');
                setcookie(self::$plugin_prefix . 'click_id', $_GET['Click_ID'], 0, '/');
            }
        }

        public function enqueue_admin_scripts()
        {
            global $current_screen;
            if(in_array($current_screen->base, array('edit', 'post')) && $current_screen->post_type == 'shop_order') {
                wp_enqueue_script('owp-wcma-admin', $this->assets_js_url($this->attach_min_suffix('owp-wcma-admin')), array('jquery'));
            }
        }

        /**
         * 加入WooCommerce 設定頁面
         * @hooked woocommerce_get_settings_pages
         *
         * @param $settings
         * @return array
         */
        public function add_wc_admin_settings($settings)
        {
            $setting_page = $this->require_file('class-owp-wcma-wc-settings-page');
            if($setting_page) {
                $settings[] = $setting_page;
            }
            return $settings;
        }

        /**
         * 紀錄美安資訊至訂單
         * @hooked woocommerce_checkout_order_processed
         *
         * @param $order_id
         * @param $posted_data
         * @param $order
         */
        public function store_id_to_order($order_id, $posted_data, $order)
        {
            $RID = $_COOKIE[self::$plugin_prefix . 'rid'];
            $Click_ID = $_COOKIE[self::$plugin_prefix . 'click_id'];

            if($RID && $Click_ID) {
                $order = wc_get_order($order_id);
                $order->add_order_note( "RID:" . $RID . '<br>Click_ID:' . $Click_ID);
                $order->update_meta_data('_owp_wcma_rid', $RID);
                $order->update_meta_data('_owp_wcma_click_id', $Click_ID);
                $order->save();
                do_action('owp_wcma_order_saved', $order_id, $RID, $Click_ID);
            }
        }

        private function create_order($order_id)
        {
            $order = wc_get_order($order_id);
            $RID               = $order->get_meta('_owp_wcma_rid');
            $Click_ID          = $order->get_meta('_owp_wcma_click_id');
            $commission_rate   = self::get_setting('commission_rate');
            $offer_id          = self::get_setting('offer_id');
            $advertiser_id     = self::get_setting('advertiser_id');
            $order_status      = $order->get_meta('_owp_wcma_order_status');
            if($commission_rate !== '' && $offer_id && $advertiser_id && $RID && $Click_ID &&
                (!$order_status || in_array($order_status, array(self::$order_statuses['cancelled'], self::$order_statuses['creating_failed'])))
            ) {
                $owp_wcma_order = new OWP_WCMA_Order($order, $RID, $Click_ID, $offer_id, $advertiser_id, $commission_rate);
                $owp_wcma_order_api = new OWP_WCMA_Order_API($owp_wcma_order);
                $owp_wcma_order_api->create_order();
            }
        }

        private function cancel_order($order_id)
        {
            $order             = wc_get_order($order_id);
            $RID               = $order->get_meta('_owp_wcma_rid');
            $Click_ID          = $order->get_meta('_owp_wcma_click_id');
            $commission_rate   = self::get_setting('commission_rate');
            $offer_id          = self::get_setting('offer_id');
            $advertiser_id     = self::get_setting('advertiser_id');
            $order_status      = $order->get_meta('_owp_wcma_order_status');
            if($commission_rate !== '' && $offer_id && $advertiser_id && $RID && $Click_ID &&
                in_array($order_status, array(self::$order_statuses['created'], self::$order_statuses['cancelling_failed']))
            ) {
                $owp_wcma_order = new OWP_WCMA_Order($order, $RID, $Click_ID, $offer_id, $advertiser_id, $commission_rate);
                $owp_wcma_order_api = new OWP_WCMA_Order_API($owp_wcma_order);
                $owp_wcma_order_api->cancel_order();
            }
        }

        /**
         * 傳送訂單資訊至美安
         * @hooked woocommerce_order_status_changed
         *
         * @param $order_id
         * @param $post_data
         * @param WC_Order $order
         */
        public function shop_order_create($order_id, $from, $to)
        {
            if(in_array('wc-' . $to , self::get_setting('order_creating_statuses'))) {
                $this->create_order($order_id);
            }
        }

        public function shop_order_cancel($order_id, $from, $to)
        {
            if(in_array('wc-' . $to , self::get_setting('order_cancelling_statuses'))) {
                $this->cancel_order($order_id);
            }
        }

        private function sanitize_product_data($text)
        {
            $text = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);
            $text = htmlspecialchars(sanitize_text_field($text), ENT_XML1, 'utf-8');
            return $text;
        }

        /**
         * @param WC_Product|WC_Product_Variation $product
         * @return array
         */
        private function get_product_data($product, $variable_mode = 'all_variations')
        {
            $parent_product = 'variation' == $product->get_type() ? wc_get_product($product->get_parent_id()) : $product;
            //產品編號
            $product_sku = $product->get_sku();
            $product_id = $product_sku ? $product_sku : $product->get_id();
            //產品名稱
            $product_name = $this->sanitize_product_data('variation' == $product->get_type() && 'all_variations' == $variable_mode ? $product->get_title() . '(' . wc_get_formatted_variation($product, true) . ')': $product->get_title());
            //產品敘述
            if(self::get_setting('catalog_product_desc_column') == 'extra') {
                $short_description = apply_filters('woocommerce_short_description', get_post_meta($parent_product->get_id(), '_owp_wcma_catalog_product_desc', true));
            } else {
                $short_description = apply_filters('woocommerce_short_description', $parent_product->get_short_description());
            }
            $product_description = $this->sanitize_product_data($short_description);

            //產品網址
            $product_page_link = get_permalink($parent_product->get_id());
            //產品售價
            $product_regular_price = $product->get_regular_price();
            //產品特色圖片網址
            $product_feature_image_url =  @wp_get_attachment_url($product->get_image_id());
            //產品優惠價
            $product_sales_price = $product->get_sale_price();
            //產品分類
            $product_cats = array();
            $terms = get_the_terms($parent_product->get_id(), 'product_cat');
            if(!empty($terms)) {
                $product_cats = array_map(function($term){
                    /** @var WP_Term $term */
                    return $term->name;
                }, $terms);
            }
            $product_cats = implode(',', $product_cats);
            return [
                'id'          => $product_id,
                'name'        => $product_name,
                'description' => $product_description,
                'permalink'   => $product_page_link,
                'regular_price'     => $product_regular_price,
                'feature_image_url' => $product_feature_image_url,
                'sales_price'       => $product_sales_price,
                'cats'              => $product_cats,
            ];
        }

        private function generate_product_xml($product_data)
        {
            $product_xml = '';
            if ($product_data['sales_price'] != 0) {
                $product_xml =
                    "<Product>" .
                    "<SKU>{$product_data['id']}</SKU>" .
                    "<Name>{$product_data['name']}</Name>" .
                    "<Description>{$product_data['description']}</Description>" .
                    "<URL>{$product_data['permalink']}</URL>" .
                    "<Price>{$product_data['regular_price']}</Price>" .
                    "<LargeImage>{$product_data['feature_image_url']}</LargeImage>" .
                    "<SalePrice>{$product_data['sales_price']}</SalePrice>" .
                    "<Category>{$product_data['cats']}</Category>" .
                    "</Product>";
            } else {
                $product_xml =
                    "<Product>" .
                    "<SKU>{$product_data['id']}</SKU>" .
                    "<Name>{$product_data['name']}</Name>" .
                    "<Description>{$product_data['description']}</Description>" .
                    "<URL>{$product_data['permalink']}</URL>" .
                    "<Price>{$product_data['regular_price']}</Price>" .
                    "<LargeImage>{$product_data['feature_image_url']}</LargeImage>" .
                    "<Category>{$product_data['cats']}</Category>" .
                    "</Product>";
            }
            return $product_xml;
        }

        private function generate_products_xml()
        {
            if(function_exists('set_time_limit')) {
                set_time_limit(0);
            }
            $query_args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,

            );
            $catalog_category_mode = self::get_setting('product_category_from_catalog');
            $product_cats = self::get_setting('catalog_product_categories');
            switch($catalog_category_mode) {
                case 'included':
                    if(!empty($product_cats)) {
                        $query_args['tax_query'] = array(
                            array(
                                'taxonomy' => 'product_cat',
                                'field'    => 'term_id',
                                'terms'    => $product_cats,
                                'operator' => 'IN',
                            )
                        );
                    }
                    break;
                case 'excluded':
                    if(!empty($product_cats)) {
                        $query_args['tax_query'] = array(
                            array(
                                'taxonomy' => 'product_cat',
                                'field'    => 'term_id',
                                'terms'    => $product_cats,
                                'operator' => 'NOT IN',
                            )
                        );
                    }
                    break;
            }


            $products = (new WP_Query($query_args))->get_posts();

            $products = array_map(function($post){
                return wc_get_product($post->ID);
            }, $products);

            $products = array_filter($products, function($product){
                /** @var WC_Product $product */
                return $product->is_visible();
            });

            $all_products_xml =
                '<?xml version="1.0" encoding="UTF-8"?>'.
                "<Products>";


            $variable_mode = self::get_setting('catalog_variable_product_mode');
            foreach ($products as $product) {
                /** @var WC_Product $product */
                if('variable' == $product->get_type()) {
                    /** @var WC_Product_Variable $product */
                    $product_variations = $product->get_available_variations();
                    foreach($product_variations as $variation) {
                        $variation_id = $variation['variation_id'];
                        $product = wc_get_product($variation_id);
                        $product_data = $this->get_product_data($product, $variable_mode);
                        $all_products_xml .= $this->generate_product_xml($product_data);
                        if('one_variation' == $variable_mode) {
                            break;
                        }
                    }
                } else {
                    $product_data = $this->get_product_data($product);
                    $all_products_xml .= $this->generate_product_xml($product_data);
                }
            }
            $all_products_xml .= "</Products>";
            return $all_products_xml;
        }

        private function get_product_xml_file_dir()
        {
            $upload_dir = wp_get_upload_dir()['basedir'];
            $xml_file = "$upload_dir/" . self::PRODUCTS_XML_FILE_NAME;
            return $xml_file;
        }

        public function api_get_products_xml()
        {
            ob_clean();
            header('Content-Type: text/xml');
            if('instant' == OctopusWP_WC_Mei_An::get_setting('xml_generation_mode')) {
                $xml = $this->renew_products_xml();
                echo $xml;
            } else {
                $xml_file = $this->get_product_xml_file_dir();
                try {
                    if(!file_exists($xml_file)) {
                        $this->renew_products_xml();
                    }
                    echo file_get_contents($xml_file);
                } catch (Exception $e) {
                    echo $this->generate_products_xml();
                }
            }
            exit;
        }

        public function renew_products_xml()
        {
            $xml = $this->generate_products_xml();
            $xml_file = $this->get_product_xml_file_dir();
            file_put_contents($xml_file, $xml);
            return $xml;
        }

        private function get_order_status_html($order_id)
        {
            ob_start();
            $this->include_template('shop-order-column-status', ['order_id' => $order_id]);
            return ob_get_clean();
        }

        public function add_column_in_orders_page($columns)
        {
            $columns['owp_wcma'] = '美安狀態';
            return $columns;
        }

        public function add_column_action($column, $order_id)
        {
            if('owp_wcma' == $column) {
                echo $this->get_order_status_html($order_id);
            }
        }

        public function ajax_create_order()
        {
            check_ajax_referer('owp_wcma_admin_ajax');
            $this->create_order($_REQUEST['order_id']);
            wp_send_json([
                'html' => $this->get_order_status_html($_REQUEST['order_id'])
            ]);
        }

        public function ajax_cancel_order()
        {
            check_ajax_referer('owp_wcma_admin_ajax');
            $this->cancel_order($_REQUEST['order_id']);
            wp_send_json([
                'html' => $this->get_order_status_html($_REQUEST['order_id'])
            ]);
        }

        public function add_meta_boxes()
        {
            add_meta_box('owp-wcma-status', '美安狀態', array($this, 'order_meta_box'), 'shop_order', 'side', 'high');
            if(self::get_setting('catalog_product_desc_column') == 'extra') {
                add_meta_box('owp-wcma-product', '美安商品設定', array($this, 'product_meta_box'), 'product', 'normal',
                    'high');
            }
        }

        public function order_meta_box()
        {
            echo $this->get_order_status_html(get_the_ID());
        }

        public function product_meta_box()
        {
            $product_desc = get_post_meta(get_the_ID(), '_owp_wcma_catalog_product_desc', true);
            $this->include_template('product-metabox', array('product_desc' => $product_desc));
        }

        public function save_product_meta($post_id, $post)
        {
            if(!empty($_POST['owp_wcma_catalog_product_desc'])) {
                update_post_meta($post_id, '_owp_wcma_catalog_product_desc', $_POST['owp_wcma_catalog_product_desc']);
            }
        }
    }
    add_action('init', ['OctopusWP_WC_Mei_An', 'get_instance'], 20);
    add_action('init', ['OctopusWP_WC_Mei_An', 'maybe_schedule_generate_xml'], 20);
}
?>