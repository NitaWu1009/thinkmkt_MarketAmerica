<?php
defined('ABSPATH') || exit;

if(!class_exists('OWP_WCMA_Order_API')) {
    class OWP_WCMA_Order_API
    {
        use OctopusWP_Remote_API_Accessible;

        private $api_url = 'https://api.hasoffers.com/Api';

        private $owp_wcma_order;

        public function __construct(OWP_WCMA_Order $order)
        {
            $this->owp_wcma_order = $order;
            $this->log_enabled = OctopusWP_WC_Mei_An::get_setting('api_log') == 'yes';
            $this->log_filename = 'owp-wcma';
        }

        public function create_order()
        {
            try {
                $request_data = $this->owp_wcma_order->creating_data();
                $response = $this->remote_post($this->api_url, $request_data);
                $response = json_decode($response['body']);
                $this->log('creating order id--------------------' . $this->owp_wcma_order->get_order_id());
                $this->log($request_data);
                $this->log($response);
                $created = $response->response->status == 1;
                if($created) {
                    $this->log('created');
                    $this->owp_wcma_order->order->update_meta_data('_owp_wcma_order_status', OctopusWP_WC_Mei_An::$order_statuses['created']);
                    do_action('owp_wcma_order_created', $this->owp_wcma_order->get_order_id());
                } else {
                    $this->log('failed');
                    $this->owp_wcma_order->order->update_meta_data('_owp_wcma_order_status', OctopusWP_WC_Mei_An::$order_statuses['creating_failed']);
                    do_action('owp_wcma_order_creating_failed', $this->owp_wcma_order->get_order_id());
                }
                $this->owp_wcma_order->order->update_meta_data('_owp_wcma_order_updated_time', current_time('mysql'));
                $this->owp_wcma_order->order->save();
                if($created) {
                    return array(
                        'status' => true
                    );
                } else {
                    throw new Exception($response->response->errorMessage);
                }

            } catch (Exception $e) {
                $this->owp_wcma_order->order->update_meta_data('_owp_wcma_order_status', OctopusWP_WC_Mei_An::$order_statuses['creating_failed']);
                $this->owp_wcma_order->order->update_meta_data('_owp_wcma_order_updated_time', current_time('mysql'));
                $this->owp_wcma_order->order->add_order_note('美安訂單建立失敗<br>' . $e->getMessage());
                $this->owp_wcma_order->order->save();
                return array(
                    'status' => false
                );
            }
        }

        public function cancel_order()
        {
            try {
                $request_data = $this->owp_wcma_order->cancelling_data();
                $response = $this->remote_post($this->api_url, $request_data);
                $response = json_decode($response['body']);
                $this->log('cancelling order id--------------------' . $this->owp_wcma_order->get_order_id());
                $this->log($request_data);
                $this->log($response);
                $cancelled = $response->response->status == 1;
                if($cancelled) {
                    $this->log('cancelled');
                    $this->owp_wcma_order->order->update_meta_data('_owp_wcma_order_status', OctopusWP_WC_Mei_An::$order_statuses['cancelled']);
                    do_action('owp_wcma_order_cancelled', $this->owp_wcma_order->get_order_id());
                } else {
                    $this->log('failed');
                    $this->owp_wcma_order->order->update_meta_data('_owp_wcma_order_status', OctopusWP_WC_Mei_An::$order_statuses['cancelling_failed']);
                    do_action('owp_wcma_order_cancelling_failed', $this->owp_wcma_order->get_order_id());
                }
                $this->owp_wcma_order->order->update_meta_data('_owp_wcma_order_updated_time', current_time('mysql'));
                $this->owp_wcma_order->order->save();
                if($cancelled) {
                    return array(
                        'status' => true
                    );
                } else {
                        throw new Exception($response->response->errorMessage);
                }

            } catch (Exception $e) {
                $this->owp_wcma_order->order->update_meta_data('_owp_wcma_order_status', OctopusWP_WC_Mei_An::$order_statuses['cancelling_failed']);
                $this->owp_wcma_order->order->update_meta_data('_owp_wcma_order_updated_time', current_time('mysql'));
                $this->owp_wcma_order->order->add_order_note('美安訂單取消失敗<br>' . $e->getMessage());
                $this->owp_wcma_order->order->save();
                return array(
                    'status' => false
                );
            }
        }
    }
}
