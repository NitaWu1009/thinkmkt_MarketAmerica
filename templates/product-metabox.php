<?php
/** @var $product_desc */
?>
<p>美安專用商品描述</p>
<?php
wp_editor($product_desc, 'owp_wcma_catalog_product_desc', [
    'media_buttons' => false,
    'textarea_rows' => 10,
    'tinymce' => false,
    'quicktags' => false,
]);
?>