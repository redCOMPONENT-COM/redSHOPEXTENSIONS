jQuery(document).ready(function($) {
    $('#sync_categories').click(function() {
        callSyncAjax($, 'SyncCategories');
    });

    $('#sync_product').click(function() {
        var pids = {};

        $('[id^=cb]:checked').each(function(i, e) {
            pids[i] = $(e).val();
        });

        var data = {
            'productIds' : pids
        };

        callSyncAjax($, 'SyncProducts', data);
    });

    $('#sync_order').click(function() {
        callSyncAjax($, 'SyncOrders');
    });

    $('[id^=registration_webhook_]').click(function() {
        callSyncAjax($, 'RegistrationWebhook');
    });

    $('[id^=remove_webhook_]').click(function() {
        callSyncAjax($, 'RemoveWebhook');
    });
});

function callSyncAjax($, method, dataPost = {})
{
    $.ajax({
        type: 'POST',
        url: 'index.php?option=com_ajax&group=system&plugin='+ method +'&format=raw',
        data: dataPost,
        beforeSend: function() {
            $(
                '<div id="sync-ajax-loader" style="display: flex;position: fixed;top: 0;left: 0;width: 100vw;height: 100vh;align-items: center;justify-content: center;background: rgba(0,0,0,.5);">' +
                '<img src="/media/com_redshop/images/reloading.gif" alt="" border="0">' +
                '</div>'
            ).appendTo('body');
            $('body').css({'overflow' : 'hidden'});
        },
        success: function(data) {

            if (method == 'SyncProducts' && Object.keys(dataPost.productIds).length == 0)
            {
                syncProduct($, data, method);
            }

            window.location.reload();
        }
    });
}

function syncProduct($, data, method)
{
    var data = JSON.parse(data);
    var limit = 100;
    var total   = data.total;
    var totalPage = Math.ceil(total / limit);

    for (let i = 0; i < totalPage; i++)
    {
        var startLimit = limit * i;

        $.ajax({
            type: 'POST',
            url: 'index.php?option=com_ajax&group=system&plugin='+ method +'&format=raw',
            data: {
                'startLimit' : startLimit,
                'limit' : limit
            },
            async: false
        });
    }
}