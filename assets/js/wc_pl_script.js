var aeidn_reload_page_after_ajax = false;
jQuery(function ($) {

    $(document).on("click", ".aeidn-product-info", function () {
        var id = $(this).attr('id').split('-')[1];
        $.aeidn_show(id);
        return false;
    });

    $.aeidn_show = function (id) {
        $('<div id="aeidn-dialog' + id + '"></div>').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            title: "AffiliateImporterAl Info (ID: " + id + ")",
            open: function () {
                $('#aeidn-dialog' + id).html('Please wait, data loads..');
                var data = {'action': 'aeidn_product_info', 'id': id};

                $.post(ajaxurl, data, function (response) {
                    //console.log('response: ', response);
                    var json = jQuery.parseJSON(response);
                    //console.log('result: ', json);

                    if (json.state === 'error') {

                        console.log(json);

                    } else {
                        //console.log(json);
                        $('#aeidn-dialog' + json.data.id).html(json.data.content.join('<br/>'));
                    }

                });


            },
            close: function (event, ui) {
                $("#aeidn-dialog" + id).remove();
            },
            buttons: {
                Ok: function () {
                    $(this).dialog("close");
                }
            }
        });

        return false;

    };

    $("body").on("click", "#doaction,#doaction2", function () {
        var check_action = ($(this).attr('id') == 'doaction') ? $('#bulk-action-selector-top').val() : $('#bulk-action-selector-bottom').val();

        if ('aeidn_product_update_manual' === check_action) {
            aeidn_reload_page_after_ajax = true;
            $("#aeidn_update_process_loader").remove();
            var num_to_update = $('input:checkbox[name="post[]"]:checked').length;
            if (num_to_update > 0) {
                $("#posts-filter .tablenav.top").after('<div id="aeidn_update_process_loader">Process update 0 of ' + num_to_update + '.</div>');

                var update_cnt = 0;
                var update_error_cnt = 0;
                var update_cnt_total = 0;

                $('input:checkbox[name="post[]"]:checked').each(function () {
                    var data = {'action': 'aeidn_update_goods', 'post_id': $(this).val()};
                    $.post(ajaxurl, data, function (response) {
                        var json = $.parseJSON(response);
                        //console.log('result: ', json);
                        if (json.state === 'error') {
                            console.log(json);
                            update_error_cnt++;
                        } else {
                            if (jQuery.isArray(json.js_hook)) {
                                jQuery.each(json.js_hook, function (index, value) {
                                    eval(value.name)(value.params);
                                });
                            }
                            update_cnt++;
                        }
                        update_cnt_total++;

                        jQuery("#aeidn_update_process_loader").html("Process update " + update_cnt + " of " + num_to_update + ". Errors: " + update_error_cnt + ".");
                        if (update_cnt_total === num_to_update) {
                            jQuery("#aeidn_update_process_loader").html("Complete! Result updated: " + update_cnt + "; errors: " + update_error_cnt + ".");
                        }
                    });
                });
            }

            return false;
        }
        return true;
    });
});

