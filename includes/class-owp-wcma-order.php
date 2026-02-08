<?php
defined('ABSPATH') || exit;

if(!class_exists('OWP_WCMA_Order')) {
    class OWP_WCMA_Order
    {
        public $order;

        public $amount;

        public $commission_amount;

        public $rid = '';

        public $click_id = '';

        public $offer_id = '';

        public $advertiser_id = '';

        public $commission_rate = 0;

        public function __construct($order, $rid, $click_id, $offer_id, $advertiser_id, $commission_rate)
        {
            if($order instanceof WC_Order) {
                $this->order = $order;
            } else {
                $this->order = wc_get_order($order);
            }

            $this->offer_id = $offer_id;
            $this->advertiser_id = $advertiser_id;
            $this->commission_rate = $commission_rate;

            // 新增：從外掛設定讀取 Token 資訊  //nitawu
            $this->network_id    = OctopusWP_WC_Mei_An::get_setting('network_id');
            $this->network_token = OctopusWP_WC_Mei_An::get_setting('network_token');
            $this->affiliate_id   = OctopusWP_WC_Mei_An::get_setting('affiliate_id');

            $total = 0;
            foreach ($this->order->get_items() as $item) {
                /** @var WC_Order_Item_Product $item */
                if('line_item' != $item->get_type()) continue;
                $total += $item->get_total();
            }

            $this->amount = $total;
            $this->commission_amount = round($this->amount * $this->commission_rate / 100, 2);
            $this->rid      = $rid;
            $this->click_id = $click_id;
        }

        public function get_order_id()
        {
            return $this->order->get_id();
        }

        public function creating_data()
        {
            return array (
                'Format'                 => 'json',
                'Target'                 => 'Conversion',
                'Method'                 => 'create',
                'Service'                => 'HasOffers',
                'Version'                => '2',
                'NetworkId'              => $this->network_id,    // 替換原 'marktamerica'  nitawu
                'NetworkToken'           => $this->network_token, // 替換原 'NETPYKNAYO...'  nitawu
                'data[offer_id]'         => $this->offer_id,
                'data[advertiser_id]'    => $this->advertiser_id,
                'data[sale_amount]'      => $this->amount,
                'data[affiliate_id]'     => $this->affiliate_id,  // 替換原 12 nitawu
                'data[payout]'           => $this->commission_amount,
                'data[revenue]'          => $this->commission_amount,
                'data[advertiser_info]'  => $this->get_order_id(),
                'data[affiliate_info1]'  => $this->rid,
                'data[ad_id]'            => $this->click_id,
                'data[session_datetime]' => $this->order->get_date_created()->format('Y-m-d')
            );
        }

        public function cancelling_data()
        {
            return array(
                'Format'                 => 'json',
                'Target'                 => 'Conversion',
                'Method'                 => 'create',
                'Service'                => 'HasOffers',
                'Version'                => '2',
                'NetworkId'              => $this->network_id,    // 替換原 'marktamerica'
                'NetworkToken'           => $this->network_token, // 替換原 'NETPYKNAYO...'  nitawu
                'data[offer_id]'         => $this->offer_id,
                'data[advertiser_id]'    => $this->advertiser_id,
                'data[sale_amount]'      => -1 * $this->amount,
                'data[affiliate_id]'     => $this->affiliate_id,  // 替換原 12 nitawu
                'data[payout]'           => -1 * $this->commission_amount,
                'data[revenue]'          => -1 * $this->commission_amount,
                'data[advertiser_info]'  => $this->get_order_id(),
                'data[affiliate_info1]'  => $this->rid,
                'data[ad_id]'            => $this->click_id,
                'data[is_adjustment]'    => '1',
                'data[session_datetime]' => $this->order->get_date_created()->format('Y-m-d')
            );
        }
    }
}