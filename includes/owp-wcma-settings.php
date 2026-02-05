<?php
defined('ABSPATH') || exit;

$xml_url = site_url('/wc-api/octopuswp-wc-mei-an-product-catalog');
$product_cats = get_terms( [
    'taxonomy' => 'product_cat',
    'hide_empty' => false
]);

return array(
    'general' => array(
        array(
            'title'    => '美安',
            'type'     => 'title',
            'desc'     => '<a href="'. $xml_url . '" target="_blank">商品目錄XML</a><br>網址：' . $xml_url
        ),
        array(
            'title'    => 'OFFER_ID',
            'desc'     => '必填，美安提供的OFFER ID',
            'id'       => 'owp_wcma_offer_id',
            'default'  => '',
            'type'     => 'text',
        ),
        array(
            'title'    => 'ADVERTISER_ID',
            'desc'     => '必填，美安提供的ADVERTISER ID',
            'id'       => 'owp_wcma_advertiser_id',
            'default'  => '',
            'type'     => 'text',
        ),
        array(
            'title'    => '傭金百分比',
            'desc'     => '必填，商家的COMMISSION RATE(傭金百分比，請填入百分比數；如10%，請填入10)',
            'id'       => 'owp_wcma_commission_rate',
            'default'  => '',
            'type'     => 'text',
            'required' => true,
        ),
        array(
            'title' => '訂單成立時機',
            'desc'  => '當訂單狀態變為設定的狀態時，會將訂單資料送到美安',
            'type'  => 'multiselect',
            'id'    => 'owp_wcma_order_creating_statuses',
            'class' => 'wc-enhanced-select',
            'options' => wc_get_order_statuses(),
            'default' => array('wc-completed', 'wc-processing')
        ),
        array(
            'title' => '訂單取消時機',
            'desc'  => '當訂單狀態變為設定的狀態時，會將取消訂單資料送到美安',
            'type'  => 'multiselect',
            'id'    => 'owp_wcma_order_cancelling_statuses',
            'class' => 'wc-enhanced-select',
            'options' => wc_get_order_statuses(),
            'default' => array('wc-cancelled', 'wc-failed')
        ),
        array(
            'title'    => '報表的購買人姓名格式',
            'id'       => 'owp_wcma_exportation_buyer_name_format',
            'default'  => 'last_first',
            'type'     => 'select',
            'desc_tip' => true,
            'options'  => array(
                'only_first' => '只有First Name',
                'only_last' => '只有Last Name',
                'first_last' => 'First Name + Last Name',
                'last_first' => 'Last Name + First Name',
            )
        ),
        array(
            'title'    => '報表的商品編號',
            'id'       => 'owp_wcma_exportation_product_number',
            'default'  => 'id',
            'type'     => 'select',
            'desc_tip' => true,
            'options'  => array(
                'id' => 'ID',
                'sku' => '貨號',
            )
        ),
        array(
            'title'   => '產品XML目錄的建立模式',
            'id'      => 'owp_wcma_xml_generation_mode',
            'default' => 'schedule',
            'desc'    => "
                <ul>
                    <li><strong>即時</strong>(當美安的程式來查詢XML商品目錄時，即時撈取最新的商品資料，在商品數量大的時候，可能會造成逾時，請改用排程模式)</li>
                    <li><strong>排程</strong>(系統每隔12小時自動在背景產生XML商品目錄，當美安的程式來查詢時，撈取已產生好的XML商品目錄)</li>
                </ul>",
            'type'    => 'select',
            'options' => array(
                'instant'  => '即時',
                'schedule' => '排程',
            )
        ),
        array(
            'title'   => '產品XML目錄中可變商品的的產生方式',
            'id'      => 'owp_wcma_catalog_variable_product_mode',
            'default' => 'all_variations',
            'type'    => 'radio',
            'options' => array(
                'all_variations' => '一組可變商品的規格產生一筆商品',
                'one_variation' => '一個可變商品只產生一筆商品，會用第一項規格的資料來產生',
            )
        ),
        array(
            'title'   => '產品XML目錄的商品篩選方式',
            'desc' => '選擇要出現在美安網站上的商品篩選方式。當選擇"使用商品分類篩選"時，請在下方選擇分類',
            'id'      => 'owp_wcma_product_category_from_catalog',
            'default' => 'none',
            'type'    => 'radio',
            'options' => array(
                'none'     => '不篩選',
                'included' => '使用商品分類篩選 - 只包含所選分類的商品',
                'excluded' => '使用商品分類篩選 - 顯示所有並排除所選分類的商品'
            )
        ),
        array(
            'title'   => '產品XML目錄篩選的商品分類',
            'id'      => 'owp_wcma_catalog_product_categories',
            'type'    => 'multiselect',
            'class'   => 'wc-enhanced-select',
            'options' => array_combine(array_map(function($term){
                return $term->term_id;
            }, $product_cats), array_map(function($term){
                return $term->name;
            }, $product_cats))
        ),
        array(
            'title'   => '產品XML目錄的商品描述欄位',
            'desc' => "<p>選擇要出現在美安網站上的商品描述所使用的欄位</p>
            <ul>
                    <li><strong>WooCommerce商品預設簡短描述</strong>(使用WooCommerce預設的商品簡短描述欄位，通常會與佈景主題共用並顯示在網站商品頁)</li>
                    <li><strong>美安專用商品描述</strong>(使用外掛產生的商品描述欄位，如果網站商品頁與美安商品頁的描述需要不一致的情況，可選擇)</li>
                </ul>",
            'id'      => 'owp_wcma_catalog_product_desc_column',
            'default' => 'default',
            'type'    => 'radio',
            'options' => array(
                'default'     => 'WooCommerce商品預設簡短描述',
                'extra' => '美安專用商品描述',
            )
        ),

        array(
            'title'   => '啟用API紀錄',
            'type'    => 'checkbox',
            'id'      => 'owp_wcma_api_log',
            'desc'    => '記錄所有API請求與回應的資料，記錄檔會產生於<code>/wp-content/uploads/wc-logs/owp-wcma-xxx</code>',
            'default' => 'yes'
        ),
        array('type' => 'sectionend'),
    )
);