<style>
    #octopuswp-plugins-table {
        font-size: 1rem;
        border-collapse: collapse;
        width: 100%;
    }

    #octopuswp-plugins-table th {
        text-align: left;
    }

    #octopuswp-plugins-table td.status {
        font-size: .8rem;
        font-weight: bold;
    }

    #octopuswp-plugins-table td.name {
        font-weight: bold;
    }

    #octopuswp-plugins-table td.status .activated {
        color: green;
    }

    #octopuswp-plugins-table td.status .inactivated {
        color: red;
    }

    #octopuswp-plugins-table th {
        padding-bottom: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid lightgrey;
    }

    #octopuswp-plugins-table td {
        padding: 5px 0;
    }

    #octopuswp-plugins-table input {
        width: 100%;
    }

    #octopuswp-plugins-table td.action {
        text-align: center;
    }

    #octopuswp-plugins-table td.action button.deactivate {
        background: red;
        color: white;
        border: 0;
    }

    #octopuswp-plugins-table td.action button.activate {
        background: green;
        color: white;
        border: 0;
    }
</style>
<h1>OctopusWP 外掛</h1>
<?php
include OctopusWP_Framework_Path . 'templates/octopuswp-plugins-table.php';
?>
<script>
    jQuery(function($){
        $(document.body).on('click', '#octopuswp-plugins-table button.activate', function(e){
            let $thisButton = $(e.currentTarget),
                $thisRow = $thisButton.closest('tr'),
                activationCode = $thisRow.find('.octopuswp-activation-code').val(),
                pluginId = $thisRow.find('.octopuswp-plugin-id').val()

            if(activationCode && pluginId) {
                $thisRow.css('opacity', '.5')
                $thisButton.attr('disabled', true)
                $.ajax({
                    method: 'post',
                    url: typeof ajaxurl === 'undefined' ? '/wp-admin/admin-ajax.php' : ajaxurl,
                    data: {
                        action: 'octopuswp_plugin_activate',
                        activation_code: activationCode,
                        plugin_id: pluginId
                    },
                    success: function(resp){
                        if(resp.activated) {
                            $('#octopuswp-plugins-table').replaceWith(resp.html)
                        } else {
                            window.alert('啟用失敗，' + resp.reason + ',' + resp.request)
                            $thisRow.find('.octopuswp-activation-code').val(null)
                            $thisRow.css('opacity', 1)
                            $thisButton.attr('disabled', false)
                        }
                    }
                })
            }
        })

        $(document.body).on('click', '#octopuswp-plugins-table button.deactivate', function(e){
            let $thisButton = $(e.currentTarget),
                $thisRow = $thisButton.closest('tr'),
                pluginId = $thisRow.find('.octopuswp-plugin-id').val()
            if(pluginId) {
                $thisRow.css('opacity', '.5')
                $thisButton.attr('disabled', true)
                $.ajax({
                    method: 'post',
                    url: typeof ajaxurl === 'undefined' ? '/wp-admin/admin-ajax.php' : ajaxurl,
                    data: {
                        action: 'octopuswp_plugin_deactivate',
                        plugin_id: pluginId
                    },
                    success: function(resp){
                        if(resp.deactivated) {
                            $('#octopuswp-plugins-table').replaceWith(resp.html)
                        } else {
                            window.alert('停用失敗')
                            $thisRow.css('opacity', 1)
                            $thisButton.attr('disabled', false)
                        }
                    }
                })
            }
        })
    });
</script>