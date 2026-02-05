<?php
$order = wc_get_order($order_id);
$status = $order->get_meta('_owp_wcma_order_status');
$time = $order->get_meta('_owp_wcma_order_updated_time');
$nonce = wp_create_nonce('owp_wcma_admin_ajax');
?>
<div class="owp-wcma-status">
    <?php if($status == OctopusWP_WC_Mei_An::$order_statuses['created']): ?>
        <div class="owp-wcma-status__status">已建立</div>
        <div class="owp-wcma-status__time"><?=$time?></div>
    <?php elseif($status == OctopusWP_WC_Mei_An::$order_statuses['cancelled']): ?>
        <div class="owp-wcma-status__status">已取消</div>
        <div class="owp-wcma-status__time"><?=$time?></div>
    <?php elseif($status == OctopusWP_WC_Mei_An::$order_statuses['creating_failed']): ?>
        <div class="owp-wcma-status__status">建立失敗</div>
        <div class="owp-wcma-status__time"><?=$time?></div>
        <button class="owp-wcma-status-action button" data-id="<?=$order->get_id()?>" data-nonce="<?=$nonce?>" data-ajax-action="owp_wcma_create_order">重新建立</button>
    <?php elseif($status == OctopusWP_WC_Mei_An::$order_statuses['cancelling_failed']): ?>
        <div class="owp-wcma-status__status">取消失敗</div>
        <div class="owp-wcma-status__time"><?=$time?></div>
        <button class="owp-wcma-status-action button" data-id="<?=$order->get_id()?>" data-nonce="<?=$nonce?>" data-ajax-action="owp_wcma_cancel_order">重新取消</button>
    <?php else: ?>
        <?php if($order->get_meta('_owp_wcma_rid') && $order->get_meta('_owp_wcma_click_id')): ?>
            <div class="status">尚未建立</div>
        <?php endif; ?>
    <?php endif; ?>
</div>