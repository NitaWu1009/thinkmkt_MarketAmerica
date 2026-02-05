<?php
if(!defined('ABSPATH')) exit;

if(!class_exists('OctopusWP_WC_Order_Compatible')) {
    /**
     * Class OctopusWP_WC_Order_Compatible
     *
     * Original Methods
     * @method float get_total
     * @method add_order_note($note, $is_customer_note = 0, $added_by_user = false)
     *
     * Property Methods
     * @method int get_id
     * @method string get_billing_email
     * @method string get_billing_address_1
     * @method string get_billing_address_2
     * @method string get_billing_state
     * @method string get_billing_city
     * @method string get_billing_postcode
     * @method string get_billing_first_name
     * @method string get_billing_last_name
     * @method string get_billing_country
     * @method string get_billing_company
     * @method string get_billing_phone
     * @method string get_shipping_email
     * @method string get_shipping_address_1
     * @method string get_shipping_address_2
     * @method string get_shipping_state
     * @method string get_shipping_city
     * @method string get_shipping_postcode
     * @method string get_shipping_first_name
     * @method string get_shipping_last_name
     * @method string get_shipping_country
     * @method string get_shipping_company
     * @method string get_payment_method
     * @method string get_order_key
     * @method string get_payment_method_title
     *
     * Compatible Methods (Wrap Behavior)
     * @method int get_customer_id
     * @method float get_shipping_total
     * @method WC_DateTime|DateTime get_date_created
     * @method WC_DateTime|DateTime get_date_completed
     * @method WC_DateTime|DateTime get_date_paid
     * @method WC_DateTime|DateTime get_date_modified
     *
     * Compatible Method (Change Signature Only)
     * @method array get_items($type = '')
     */
    class OctopusWP_WC_Order_Compatible
    {
        /**
         * @var WC_Order|WC_Refund
         */
        public $order;

        public $is_gt_3;

        public $pending_updated_meta_data = [];

        public $property_getters = [
            'get_id',
            'get_billing_email',
            'get_billing_address_1',
            'get_billing_address_2',
            'get_billing_state',
            'get_billing_city',
            'get_billing_postcode',
            'get_billing_first_name',
            'get_billing_last_name',
            'get_billing_country',
            'get_billing_company',
            'get_billing_phone',
            'get_shipping_email',
            'get_shipping_address_1',
            'get_shipping_address_2',
            'get_shipping_state',
            'get_shipping_city',
            'get_shipping_postcode',
            'get_shipping_first_name',
            'get_shipping_last_name',
            'get_shipping_country',
            'get_shipping_company',
            'get_payment_method',
            'get_order_key',
            'get_payment_method_title'
        ];

        public function __construct($order)
        {
            if(is_object($order)) {
                $this->order = $order;
            } else {
                $this->order = wc_get_order($order);
            }

            $this->is_gt_3 = version_compare(WC()->version, '3.0.0', '>=');
        }

        /**
         * @param $function_name
         * @param $args
         * @return DateTime|WC_DateTime|mixed
         * @throws Exception
         */
        public function __call($function_name, $args)
        {
            try {
                if(in_array($function_name, $this->property_getters)) {
                    if($this->is_gt_3) {
                        return call_user_func_array([$this->order, $function_name], $args);
                    } else {
                        $property = preg_replace('/^get_/', '', $function_name);
                        return $this->order->$property;
                    }
                } elseif('get_customer_id' == $function_name) {
                    if($this->is_gt_3) {
                        return $this->order->get_customer_id();
                    } else {
                        return $this->order->get_user_id();
                    }
                } elseif('get_shipping_total' == $function_name) {
                    if($this->is_gt_3) {
                        return $this->order->get_shipping_total();
                    } else {
                        return $this->order->get_total_shipping();
                    }
                } elseif('get_date_created' == $function_name) {
                    if($this->is_gt_3) {
                        return $this->order->get_date_created();
                    } else {
                        return new DateTime($this->order->order_date);
                    }
                } elseif('get_date_modified' == $function_name) {
                    if($this->is_gt_3) {
                        return $this->order->get_date_modified();
                    } else {
                        return new DateTime($this->order->modified_date);
                    }
                } elseif('get_date_completed' == $function_name || 'get_date_paid' == $function_name) {
                    if($this->is_gt_3) {
                        return $this->$function_name();
                    } else {
                        throw new Exception("Version below WooCommerce 3.0.0, $function_name won't work exactly");
                    }
                } else {
                    return call_user_func_array([$this->order, $function_name], $args);
                }
            } catch(Exception $e) {
                throw $e;
            }
        }

        public function get_meta($key, $single = true)
        {
            if($this->is_gt_3) {
                return $this->order->get_meta($key, $single);
            } else {
                return get_post_meta($this->get_id(), $key, $single);
            }
        }

        public function update_meta($key, $value)
        {
            if($this->is_gt_3) {
                $this->order->update_meta_data($key, $value);
            } else {
                $this->pending_updated_meta_data[$key] = $value;
            }
        }

        public function save()
        {
            if($this->is_gt_3) {
                $this->order->save();
            } else {
                foreach ($this->pending_updated_meta_data as $key => $value) {
                    update_post_meta($this->get_id(), $key, $value);
                }
                $this->pending_updated_meta_data = [];
            }
        }
    }
}