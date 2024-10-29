String.prototype.replaceAllTags = function (tag) {
    var div = document.createElement('div');
    div.innerHTML = this;
    var scripts = div.getElementsByTagName(tag);
    var i = scripts.length;
    while (i--) {
        scripts[i].parentNode.removeChild(scripts[i]);
    }
    return div.innerHTML;
}

var SYNCHRONIZE_IMPORT = false;

function buildGoodsId(goods, dlv) {
    return goods.type + dlv + goods.external_id + ((goods.variation_id !== "" && goods.variation_id !== "-") ? dlv + goods.variation_id : "");
}

jQuery(function () {


});

function showRequest(formData, jqForm, options) {
    if (jQuery(jqForm).find("#upload_image").val() !== '') {
        jQuery(jqForm).find('#upload_progress').html('Sending...');
        jQuery(jqForm).find('input[name="submit-ajax"]').attr("disabled", "disabled");
        return true;
    } else {
        jQuery(jqForm).find('#upload_progress').html('<font color="red">Please select a file first</font>');
        jQuery(jqForm).find('input[name="submit-ajax"]').removeAttr("disabled");
        return false;
    }

}
function showResponse(responseText, statusText, xhr, $form) {
    var json = jQuery.parseJSON(responseText);
    if (json.state == 'ok') {
        jQuery("#affiliate_container").find('tr').each(function () {
            var row_id = jQuery(this).attr('id');
            if (row_id === buildGoodsId(json.goods, '#')) {
                jQuery(this).find('.column-image img').attr('src', json.cur_image);
            }
        });

        jQuery("#select-image-dlg-" + buildGoodsId(json.goods, '-')).html(json.images_content);
    } else {
        console.log(json.state + "; " + json.message);
    }


    jQuery($form).find('input[name="submit-ajax"]').removeAttr("disabled");
    jQuery($form).find('#upload_image').val('');
    jQuery($form).find('#upload_product_id').val('');
    jQuery($form).find('#upload_progress').html('');

    aeidn_tb_remove();
}


function affiliateEdit(object) {
    var block = jQuery(object).parents(".block_field");
    var text = jQuery(block).find(".field_text").html();

    jQuery(block).find(".field_edit").val(text);

    jQuery(block).find(".field_text").hide();
    jQuery(block).find(".edit_btn").hide();
    jQuery(block).find(".field_edit").show();
    jQuery(block).find(".save_btn").show();
    jQuery(block).find(".cancel_btn").show();
    return false;
}

function affiliateBeforeSearch() {
    jQuery("input[name='_wp_http_referer']").attr("disabled", "disabled");
    jQuery("input[name='_wpnonce']").attr("disabled", "disabled");

    jQuery(this).find(":input").filter(function () {
        return !this.value;
    }).attr("disabled", "disabled");
    
    jQuery("#search-form").find("#reset").val("1");
    jQuery("#search-form").submit();
}

function affiliateProductEdit(object) {
    var id = jQuery(object).parents('tr').attr('id');
    var block = jQuery(object).parents(".block_field");

    var field_code = jQuery(block).find(".field_code").val();
    var text = jQuery(block).find(".field_edit").val();

    jQuery(block).find(".field_text").show();
    jQuery(block).find(".edit_btn").show();
    jQuery(block).find(".field_edit").hide();
    jQuery(block).find(".save_btn").hide();
    jQuery(block).find(".cancel_btn").hide();

    jQuery(block).find(".field_text").html(text);

    var data = {
        'action': classPrefix + '_edit_goods', 'id': id,
        'field': (field_code.lastIndexOf('user_', 0) === 0) ? field_code : ('user_' + field_code),
        'value': text
    };

    jQuery.post(ajaxurl, data, function (response) {
    });
}

function affiliateProductEditCancel(object) {
    var block = jQuery(object).parents(".block_field");

    jQuery(block).find(".field_text").show();
    jQuery(block).find(".edit_btn").show();
    jQuery(block).find(".field_edit").hide();
    jQuery(block).find(".save_btn").hide();
    jQuery(block).find(".cancel_btn").hide();
}

function affiliateLoadMoreDetails(object) {
    var block = jQuery(object).parent();
    var curr_row = jQuery(object).parents("tr");
    var id = jQuery(object).parents("tr").attr('id');

    jQuery(block).html("<i>loading...</i> | ");

    var edit_fields = '';
    jQuery(curr_row).find(".block_field").each(function () {
        var field_code = jQuery(this).find(".field_code").val();
        if (jQuery(this).hasClass('edit')) {
            edit_fields += (edit_fields.length > 0 ? ',' : '') + field_code;
        }
    });

    var data = {'action': classPrefix + '_load_details', 'id': id, 'edit_fields': edit_fields};

    jQuery.post(ajaxurl, data, function (response) {
        jQuery(block).html('<i>Details loaded</i> | ');
        //console.log('json: ', response);
        var json = jQuery.parseJSON(response);
        //console.log('json: ', json);

        if (json.state == 'ok') {
            jQuery(curr_row).find("#select-image-dlg-" + buildGoodsId(json.goods, '-')).html(json.images_content);

            if (jQuery(curr_row).find(".seller_url_block").is(':hidden')) {
                jQuery(curr_row).find(".seller_url_block").find('a').attr('href', json.goods.seller_url);
                jQuery(curr_row).find(".seller_url_block").show();
            }

            jQuery(curr_row).find(".block_field").each(function () {

                var field_code = '';
                if (jQuery(this).find(".field_code").length > 0) {
                    field_code = jQuery(this).find(".field_code").val();
                    jQuery(this).find('.field_text').html(json.goods[field_code]);
                }

                if (jQuery(this).find(".meta_field_code").length > 0) {
                    field_code = jQuery(this).find(".meta_field_code").val();
                    jQuery(this).find('.field_text').html(json.goods.additional_meta[field_code]);
                }

                jQuery(this).find('.field_text').show();
                jQuery(this).find('.edit_btn').show();
            });
            //console.log('[' + json.state + ']message: ', json.message);
        } else {
            console.log('[' + json.state + ']message: ', json.message);
        }
    });

    return false;
}

function affiliatePostImport(object) {
    var id = jQuery(object).parents("tr").attr('id');
    var curr_row = jQuery(object).parents("tr");
    var block = jQuery(object).parent();
    jQuery(block).html('<i>Posting...</i> | ');

    var edit_fields = '';
    jQuery(curr_row).find(".block_field").each(function () {
        var field_code = jQuery(this).find(".field_code").val();
        if (jQuery(this).hasClass('edit')) {
            edit_fields += (edit_fields.length > 0 ? ',' : '') + field_code;
        }
    });

    var data = {'action': classPrefix + '_import_goods', 'id': id, 'edit_fields': edit_fields};

    jQuery.post(ajaxurl, data, function (response) {
        //console.log('response: ', response);
        var json = jQuery.parseJSON(response);
        //console.log('json: ', json);

        if (json.state === 'error') {
            jQuery(block).html('<i>Posting error</i> | ');
            console.log(json);
        } else {
            if (jQuery.isArray(json.js_hook)) {
                jQuery.each(json.js_hook, function (index, value) {
                    eval(value.name)(value.params);
                });
            }

            //jQuery(this).parents("tr").find('input[type=checkbox]').attr('disabled', 'disabled');
            jQuery(block).html('<i>Posted</i>');
            jQuery(block).parents('.row-actions').find('.schedule_import').remove();
            jQuery(block).parents("tr").find('input[type=checkbox]').attr('disabled', 'disabled');

            // update row content
            jQuery(curr_row).find('.load_more_detail').html('<i>Details loaded</i> | ');
            jQuery(curr_row).find("#select-image-dlg-" + buildGoodsId(json.goods, '-')).html(json.images_content);

            if (jQuery(curr_row).find(".seller_url_block").is(':hidden')) {
                jQuery(curr_row).find(".seller_url_block").find('a').attr('href', json.goods.seller_url);
                jQuery(curr_row).find(".seller_url_block").show();
            }

            jQuery(curr_row).find(".block_field").each(function () {
                var field_code = jQuery(this).find(".field_code").val();
                jQuery(this).find('.field_text').html(json.goods[field_code]);
                jQuery(this).find('.field_text').show();
                jQuery(this).find('.edit_btn').show();
            });

        }
    });

    return false;
}

function affiliateBlacklistItem() {
    var num_to_import = jQuery("#affiliate_container input.gi_ckb:checked").length;
    jQuery("#affiliate_container .import_process_loader").html("Process blacklist 0 of " + num_to_import + ".");
    var import_cnt = 0;
    var import_error_cnt = 0;
    var import_cnt_total = 0;

    jQuery("#affiliate_container input.gi_ckb:checked").each(function () {
        var id = jQuery(this).parents("tr").attr('id');
        var curr_row = jQuery(this).parents("tr");
        var block = jQuery(this).parents("tr").find('.row-actions .import');

        var data = {
            'action': classPrefix + '_blacklist',
            'id': id
        };
        jQuery.post(ajaxurl, data, function (response) {
            //console.log('response: ', response);
            var json = jQuery.parseJSON(response);

            if (json.state === 'error') {
                jQuery(block).html('<i>Blacklist error</i> | ');
                console.log(json);
                import_error_cnt++;
            } else {
                jQuery(curr_row).remove();
                import_cnt++;
            }
            import_cnt_total++;
            jQuery("#affiliate_container .import_process_loader").html("Process blacklist " + import_cnt + " of " + num_to_import + ". Errors: " + import_error_cnt + ".");

            if (import_cnt_total == num_to_import) {
                jQuery("#affiliate_container .import_process_loader").html("Complete! Result blacklisted: " + import_cnt + "; errors: " + import_error_cnt + ".");
            }
        });
    });
}

function affiliatePostItems() {
    var num_to_import = jQuery("#affiliate_container input.gi_ckb:checked").length;

    if (num_to_import > 0) {
        jQuery("#affiliate_container .import_process_loader").html("Process import 0 of " + num_to_import + ".");
        var import_cnt = 0;
        var import_error_cnt = 0;
        var import_cnt_total = 0;

        var products_to_import = [];
        jQuery("#affiliate_container input.gi_ckb:checked").each(function () {
            var id = jQuery(this).parents("tr").attr('id');
            var curr_row = jQuery(this).parents("tr");
            var block = jQuery(this).parents("tr").find('.row-actions .import');

            var edit_fields = '';
            jQuery(curr_row).find(".block_field").each(function () {
                var field_code = jQuery(this).find(".field_code").val();
                if (jQuery(this).hasClass('edit')) {
                    edit_fields += (edit_fields.length > 0 ? ',' : '') + field_code;
                }
            });

            var data = {
                'action': classPrefix + '_import_goods',
                'id': id,
                'edit_fields': edit_fields,
                'status': check_action == 'import' ? 'publish' : 'draft'
            };

            //console.log('process: '+id);
            jQuery.post(ajaxurl, data, function (response) {
                //console.log('response: ', response);
                var json = jQuery.parseJSON(response);
                //console.log('result: ', json);

                if (json.state === 'error') {
                    jQuery(block).html('<i>Posting error</i> | ');
                    console.log(json);
                    import_error_cnt++;
                } else {
                    if (jQuery.isArray(json.js_hook)) {
                        jQuery.each(json.js_hook, function (index, value) {
                            eval(value.name)(value.params);
                        });
                    }
                    jQuery(block).html('<i>Posted</i>');
                    jQuery(block).parents('.row-actions').find('.schedule_import').remove();
                    jQuery(block).parents("tr").find('input[type=checkbox]').attr('disabled', 'disabled');
                    jQuery(block).parents("tr").find('input[type=checkbox]').removeAttr('checked');

                    // update row content
                    jQuery(curr_row).find('.load_more_detail').html('<i>Details loaded</i> | ');
                    jQuery(curr_row).find("#select-image-dlg-" + buildGoodsId(json.goods, '-')).html(json.images_content);

                    if (jQuery(curr_row).find(".seller_url_block").is(':hidden')) {
                        jQuery(curr_row).find(".seller_url_block").find('a').attr('href', json.goods.seller_url);
                        jQuery(curr_row).find(".seller_url_block").show();
                    }

                    jQuery(curr_row).find(".block_field").each(function () {
                        var field_code = jQuery(this).find(".field_code").val();
                        jQuery(this).find('.field_text').html(json.goods[field_code]);
                        jQuery(this).find('.field_text').show();
                        jQuery(this).find('.edit_btn').show();
                    });

                    import_cnt++;
                }
                import_cnt_total++;
                jQuery("#affiliate_container .import_process_loader").html("Process import " + import_cnt + " of " + num_to_import + ". Errors: " + import_error_cnt + ".");

                if (import_cnt_total == num_to_import) {
                    jQuery("#affiliate_container .import_process_loader").html("Complete! Result imported: " + import_cnt + "; errors: " + import_error_cnt + ".");
                }
            });
        });
    }
}

function affiliateUnblacklist() {
    var num_to_import = jQuery("div[rel='blacklist'] input.gi_ckb:checked").length;
    jQuery("div[rel='blacklist'] .import_process_loader").html("Process blacklist 0 of " + num_to_import + ".");
    var import_cnt = 0;
    var import_error_cnt = 0;
    var import_cnt_total = 0;

    jQuery("div[rel='blacklist'] input.gi_ckb:checked").each(function () {
        var id = jQuery(this).parents("tr").attr('id');
        var curr_row = jQuery(this).parents("tr");
        var block = jQuery(this).parents("tr").find('.row-actions .import');

        var data = {
            'action': classPrefix + '_unblacklist',
            'id': id
        };
        jQuery.post(ajaxurl, data, function (response) {
            //console.log('response: ', response);
            var json = jQuery.parseJSON(response);

            if (json.state === 'error') {
                jQuery(block).html('<i>Blacklist error</i> | ');
                console.log(json);
                import_error_cnt++;
            } else {
                jQuery(curr_row).remove();
                import_cnt++;
            }
            import_cnt_total++;
            jQuery("div[rel='blacklist'] .import_process_loader").html("Process blacklist " + import_cnt + " of " + num_to_import + ". Errors: " + import_error_cnt + ".");

            if (import_cnt_total == num_to_import) {
                jQuery("div[rel='blacklist'] .import_process_loader").html("Complete! Result blacklisted: " + import_cnt + "; errors: " + import_error_cnt + ".");
            }
        });
    });
}

function affiliateShowDatePicker(object) {
    jQuery(object).prev().datetimepicker('show');
}

function affiliateDescriptionEditor(object) {
    var id = jQuery(object).parents("tr").attr('id');

    jQuery('#edit_desc_dlg').empty();
    jQuery('#edit_desc_dlg').append('<div><h2>Edit description</h2><div id="edit_desc_content">Loading...</div></div>');

    var data = {'action': classPrefix + '_description_editor', 'id': id};
    jQuery.post(ajaxurl, data, function (response) {
        //console.log('response: ', response);
        jQuery('body').find('#edit_desc_content').html(response);
    });
    return true;
}

function getTinymceContent(id) {
    var content;
    var inputid = id;
    var editor = tinyMCE.get(inputid);
    var textArea = jQuery('textarea#' + inputid);
    if (textArea.length > 0 && textArea.is(':visible')) {
        content = textArea.val();
    } else {
        content = editor.getContent();
    }
    return content;
}

function affiliateSaveDescription(object) {
    var save_btn = this;
    jQuery(save_btn).val('Saving...');
    jQuery(save_btn).prop('disabled', true);

    var id = jQuery(object).parent().find('.item_id').val();
    var editor_id = jQuery(object).parent().find('.editor_id').val();
    var data = {
        'action': classPrefix + '_edit_goods',
        'id': id,
        'field': 'user_description',
        'value': getTinymceContent(editor_id)/*jQuery(this).parent().find('textarea').val()*/
    };
    jQuery.post(ajaxurl, data, function (response) {
        jQuery(save_btn).val('Save description');
        jQuery(save_btn).prop('disabled', false);
        aeidn_tb_remove();
    });
}