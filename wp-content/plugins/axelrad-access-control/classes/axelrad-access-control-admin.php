<?php

add_action('admin_menu', 'ax_access_control_admin_menu', -100);

function ax_access_control_admin_menu() 
{
    add_menu_page(
        'Access Control', 'Access Control', 'manage_options', 'ax_access_control', 'ax_access_control_admin_show_list');
}

function ax_access_control_admin_show_list() 
{
    
    $groups_list = '';
    $groups = AxelradUserMgmt::groups_fetch();
    echo 'there are '.count($groups).' groups';
    $base_url = admin_url().'/admin.php?page=ax_access_control&group_id=';

    foreach ($groups as $group)
    {
        $item = '<a href="'.$base_url.$group['id'].'">'.$group['display_name'].'</a><br>';
        if ($group['id'] == $_GET['group_id'])
            $groups_list.='<strong>'.$item.'</strong>';
        else
            $groups_list.=$item;
    }

    if ($_GET['group_id'])
    {

    }

    echo '<div style="padding: 30px;">'.$groups_list.'</div>';
}


function ax_access_admin_get_group_assets($group_id)
{
    
}


