<?php
add_action('load-aeimporter_page_aeidn-add', function () {
    $option = 'columns';

    $args = array(
        'label' => ''
    );

    add_screen_option($option, $args);
});

add_action('load-aeimporter_page_aeidn-stats', function () {
    $option = 'columns';

    $args = array(
        'label' => ''
    );

    add_screen_option($option, $args);
});
