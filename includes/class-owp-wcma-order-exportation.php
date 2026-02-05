<?php
defined('ABSPATH') || exit;

class OWP_WCMA_Order_Exportation
{
    public function __construct()
    {
        $this->register_hooks();
    }

    private function register_hooks()
    {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu()
    {
        add_submenu_page('woocommerce', '美安報表匯出', '美安報表匯出', 'manage_woocommerce', 'owp-wcma-order-exportation', [$this, 'export_page']);
    }

    public function export_page()
    {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-datepicker', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        if(isset($_POST['wtsp_export'])) {
            $date_begin = $_POST['date_begin'];
            $date_end = $_POST['date_due'];
            $date_query = [];
            if($date_begin) {
                $date_query[] = [
                    'column' => 'post_date',
                    'after' => $date_begin . ' 00:00:00'
                ];
            }
            if($date_end) {
                $date_query[] = [
                    'column' => 'post_date',
                    'before' => $date_end . ' 23:59:59'
                ];
            }

            $data_headers = [
                'order_date' => '訂購日期',
                'order_id' => '訂單編號',
                'order_buyer' => '購買人',
                'rid' => 'RID',
                'click_id' => 'Click_ID',
                'product_id' => '商品編號',
                'product_title' => '商品名稱',
                'product_qty' => '數量',
                'product_price' => '價格',
                'subtotal' => '總計'
            ];

            $orders_query = new WP_Query([
                'post_type' => 'shop_order',
                'post_status' => wc_get_order_statuses(),
                'posts_per_page' => -1,
                'meta_key' => '_owp_wcma_order_status',
                'meta_value' => OctopusWP_WC_Mei_An::$order_statuses['created'],
                'date_query' => $date_query
            ]);
            $order_posts = $orders_query->get_posts();
            $data = [];
            $data[] = array_values($data_headers);
            $total = 0;
            $buyer_name_format = OctopusWP_WC_Mei_An::get_setting('exportation_buyer_name_format');
            $product_number_format = OctopusWP_WC_Mei_An::get_setting('exportation_product_number');
            foreach ($order_posts as $order_post) {
                $order = wc_get_order($order_post->ID);
                /** @var WC_Order_Item_Product $item */
                $buyer_name = '';
                $order_date = $order->get_date_created()->format('Y-m-d');
                $rid = $order->get_meta('_owp_wcma_rid');
                $click_id = $order->get_meta('_owp_wcma_click_id');
                switch ($buyer_name_format) {
                    case 'only_first':
                        $buyer_name = $order->get_billing_first_name();
                        break;
                    case 'only_last':
                        $buyer_name = $order->get_billing_last_name();
                        break;
                    case 'first_last':
                        $buyer_name = $order->get_billing_first_name() . $order->get_billing_last_name();
                        break;
                    case 'last_first':
                        $buyer_name = $order->get_billing_last_name() . $order->get_billing_first_name();
                        break;
                }
                foreach ($order->get_items() as $item) {
                    $_row = [];
                    if('line_item' != $item->get_type()) continue;
                    $item_product = $item->get_product();
                    $product_id = '';
                    switch ($product_number_format) {
                        case 'id':
                            $product_id = $item_product ? $item_product->get_id() : '';
                            break;
                        case 'sku':
                            $product_id = $item_product ? $item_product->get_sku() : '';
                            break;
                    }
                    foreach ($data_headers as $name => $value) {
                        switch ($name) {
                            case 'order_date':
                                $_row[] = $order_date;
                                break;
                            case 'order_id':
                                $_row[] = $order->get_id();
                                break;
                            case 'order_buyer':
                                $_row[] = $buyer_name;
                                break;
                            case 'rid':
                                $_row[] = $rid;
                                break;
                            case 'click_id':
                                $_row[] = $click_id;
                                break;
                            case 'product_id':
                                $_row[] = $product_id;
                                break;
                            case 'product_title':
                                $_row[] = $item->get_name();
                                break;
                            case 'product_qty':
                                $_row[] = $item->get_quantity();
                                break;
                            case 'product_price':
                                $_row[] = $item->get_total() / $item->get_quantity();
                                break;
                            case 'subtotal';
                                $_row[] = $item->get_total();
                        }
                    }
                    $total += $item->get_total();
                    $data[] = $_row;
                }
            }
            $commission_rate = OctopusWP_WC_Mei_An::get_setting('commission_rate');
            $commission = round($total * $commission_rate / 100);
            $commission_tax = round($commission * 0.05);
            $data[] = [
                '', '', '', '', '', '', '', '', '', $total
            ];
            $data[] = [
                '', '', '', '', '', '', '', '佣金比例', $commission_rate . '%', $commission
            ];
            $data[] = [
                '', '', '', '', '', '', '', '營業稅', '5%', $commission_tax
            ];
            $data[] = [
                '', '', '', '', '', '', '', '應付佣金總數', '', $commission + $commission_tax
            ];
            $_date = ($date_begin ? date('Ymd', strtotime($date_begin)) : '') . ($date_begin && $date_end ? '-' : '') . ($date_end ? date('Ymd', strtotime($date_end)) : '');
            $file_name = sanitize_text_field(get_bloginfo('name')) . "-美安報表-{$_date}";
            $csv_downloader = new OctopusWP_Csv_Downloader();
            $csv_downloader->download($file_name, $data);
        }
        ?>
        <h1>美安報表匯出</h1>
        <form action="" method="POST" class="owp-wcma-order-exporation">
            <p>
                <label>
                    訂單起始日期:
                    <input type="text" class="datepicker" name="date_begin" value="<?=!empty($_POST['date_begin']) ? $_POST['date_begin'] : ''?>" readonly autocomplete="off">
                </label>
                <a class="button clear-date-begin">清除</a>
            </p>
            <p>
                <label>
                    訂單結束日期:
                    <input type="text" class="datepicker" name="date_due"  value="<?=!empty($_POST['date_due']) ? $_POST['date_due'] : date('Y-m-d')?>" readonly autocomplete="off">
                </label>
                <a class="button clear-date-due">清除</a>
            </p>
            <input type="hidden" name="wtsp_export">
            <button class="button">匯出</button>
        </form>
        <script>
            jQuery(function($){
                let $form = $('.owp-wcma-order-exporation');
                $form.find('.datepicker').datepicker({
                    dateFormat: 'yy-mm-dd'
                });
                $form.find('.clear-date-begin').on('click', function(e){
                    e.preventDefault();
                    $form.find('[name=date_begin]').val(null);
                })
                $form.find('.clear-date-due').on('click', function(e){
                    e.preventDefault();
                    $form.find('[name=date_due]').val(null);
                })
            });
        </script>
        <?php
    }
}
new OWP_WCMA_Order_Exportation();