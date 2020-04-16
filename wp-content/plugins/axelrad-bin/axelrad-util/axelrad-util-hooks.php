<?php

add_action('wp_enqueue_scripts', 'ax_util_enqueue_scripts');

function ax_util_enqueue_scripts() 
{
    AxelradUtil::enqueue_scripts();
}


add_action('wp_footer', 'ax_util_footer');

function ax_util_footer()
{
    echo AxelradUtil::footer();
}