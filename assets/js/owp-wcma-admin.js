jQuery(function($){
    $(document.body).on('click', '.owp-wcma-status-action', function(e){
        e.preventDefault();
        let $button = $(e.currentTarget),
            orderId = $button.data('id'),
            nonce = $button.data('nonce'),
            ajaxAction = $button.data('ajax-action')
        if(orderId && nonce && ajaxAction) {
            $button.closest('.owp-wcma-status').block({message: null})
            $.ajax({
                method: 'POST',
                url: typeof ajaxurl === 'undefined' ? '/wp-admin/admin-ajax.php' : ajaxurl,
                data: {
                    action: ajaxAction,
                    order_id: orderId,
                    _wpnonce: nonce
                },
                success: function(resp){
                    $button.closest('.owp-wcma-status').replaceWith(resp.html)
                }
            })
        }
    });
});