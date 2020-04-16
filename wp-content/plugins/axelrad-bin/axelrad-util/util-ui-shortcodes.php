<?php

add_shortcode('ax_sub_page_menu', 'ax_sub_page_menu_func');

function ax_sub_page_menu_func($atts)
{
    global $post;
    $page_id = $atts['page_id'] ? $atts['page_id'] : $post->ID;

    $items = _ax_util_get_child_pages_of($page_id);

    $html = '<nav class="nav flex-column">';
    foreach ($items as $item)
    {
        $active = $post->ID == $item->ID ? ' active' : '';

        $html.='<a class="nav-link'.$active.'" href="'.get_post_permalink($item->ID).'">'.$item->post_title.'</a>';
    }
    $html.='</nav>';

    return $html;
}