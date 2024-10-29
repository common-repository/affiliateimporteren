var aeidn_reload_page_after_ajax = false;
jQuery(function ($) {

	$(document).on("click", ".aeidn-order-info", function () {
		var id = $(this).attr('id').split('-')[1];
		$.aeidn_show_order(id);
		return false;
	});

	$.aeidn_show_order = function (id) {
		$('<div id="aeidn-dialog' + id + '"></div>').dialog({
			dialogClass: 'wp-dialog',
			modal: true,
			title: "AffiliateImporterAl Info (ID: " + id + ")",
			open: function () {
				$('#aeidn-dialog' + id).html('Please wait, data loads..');
				var data = {'action': 'aeidn_order_info', 'id': id};

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

});

