var DnolbonColumns = new function () {
    this.type = '';

    this.init = function (type) {
        this.type = type;

        var html = '';
        var columns = jQuery(".wp-list-table:eq(0) thead th");
        var counter = 0;

        var columnsToHide = [];

        var columnsToShow = localStorage.getItem('affiliate_show_columns_' + DnolbonColumns.type);
        if (columnsToShow !== '') {
            columnsToHide = JSON.parse(columnsToShow);
        }

        columns.each(function () {
            var text = jQuery(this).text();
            if (text != '') {
                var isVisible = jQuery.inArray(counter, columnsToHide) <= -1;
                html += ' <label>';
                html += '<input data-columns="' + counter + '" type="checkbox" ' + (isVisible ? ' checked' : '') + '> ';
                html += text + '</label>';
            }
            counter++;
        });
        jQuery("#screen-options-wrap").append(html);

        DnolbonColumns.showColumns();

        jQuery('input[data-columns]').change(function () {
            setTimeout(function () {
                DnolbonColumns.setColumsToHide();
            }, 100);

        });
    };

    this.showColumns = function () {
        var columnsToShow = localStorage.getItem('affiliate_show_columns_' + DnolbonColumns.type);

        if (columnsToShow !== '' && columnsToShow != null) {
            var columns = JSON.parse(columnsToShow);
            jQuery(".wp-list-table tr").each(function () {
                jQuery('td', this).show();
                jQuery('th', this).show();
                for (var i = 0; i < columns.length; i++) {
                    jQuery('td:eq(' + (columns[i]) + ')', this).hide();
                    jQuery('th:eq(' + columns[i] + ')', this).hide();
                }
            });
        }
    }

    this.setColumsToHide = function () {
        var columns = [];
        var counter = 0;
        jQuery('input[data-columns]').each(function () {
            if (!jQuery(this).is(':checked')) {
                columns.push(jQuery(this).attr('data-columns'));
            }
            counter++;
        });
        localStorage.setItem('affiliate_show_columns_' + DnolbonColumns.type, JSON.stringify(columns));
        this.showColumns();
    }
};