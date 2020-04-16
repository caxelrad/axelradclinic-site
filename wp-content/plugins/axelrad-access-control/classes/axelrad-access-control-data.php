<?php

AxBin::load('axelrad-data-access');

include 'axelrad-access-control-models.php';

class AxAccessCtrlData
{
    public static function save_access_entry($access_type, $post_id, $parent_id = '0')
    {
        $entry = self::find_access_entry($post_id);
        if ($entry == null)
        {
            $entry = R::dispense('accessentry');
        }

        $entry->post_id = $post_id;
        $entry->parent_id = $parent_id;
        $entry->access_type = $access_type;

        return R::store($entry);
    }

    
    public static function delete_principal($principal)
    {
        $rows = R::find('accessprincipal', 'principal = ?', [$principal]);
        R::trashAll($rows);
    }

    public static function delete_access_entry($post_id)
    {
        $entry = self::find_access_entry($post_id);
        if ($entry == null)
            return;
        
        R::trash($entry);
    }

    public static function post_has_access_entry($post_id)
    {
        return R::count('accessentry', 'post_id = ?', [$post_id]) > 0;
    }

    public static function get_post_access_entry($post_id)
    {
        $entries = R::find('accessentry', 'post_id = ?', [$post_id]);
        if (count($entries) > 0)
            return $entries[0]->value;
        
        return null;
    }

    public static function get_post_principals($post_id)
    {
        $entry = self::get_post_access_entry($post_id);
        if (!$entry)
            return [];
        
        return self::get_entry_principals($entry->id);
    }

    public static function get_principal_access_entries($principal)
    {
        return AxData::_flatten(
            R::getAssoc("select * from accessentry where 
            id in (select accessentry_id from accessprincipal where principal = ?)", [$principal]));
    }

    public static function get_access_entry($entry_id)
    {
        return R::load('accessentry', $entry_id);
    }

    public static function get_entry_principals($entry_id)
    {
        return AxData::_flatten(R::find('accessprincipal', 'accessentry_id = ?', [$entry_id]));
    }

    public static function add_entry_principal($entry_id, $principal, $principal_type)
    {
        $p = self::find_principal($entry_id, $principal, $principal_type);
        if ($p == null)
        {
            $p = R::dispense('accessprincipal');
            $p->accessentry_id = $entry_id;
            $p->principal = $principal;
            $p->principal_type = $principal_type;
            R::store($p);
            return $p;
        }
        return $p;

    }

    public static function find_principal($entry_id, $principal, $principal_type)
    {
        $ps = R::find('accessprincipal', 'accessentry_id = ? AND principal = ? AND principal_type = ?', [$entry_id, $principal, $principal_type]);
        if (count($ps) > 0)
            return $ps[0]->value;
        
        return null;
    }

    public static function find_access_entry($post_id)
    {
        $entry = R::find('accessentry', 'post_id = ? ', [$post_id]);
        if (count($entry) == 1)
            return $entry[0]->value;

        return null;
    }

    
    public static function sync_to_parent($parent_id)
    {
        //get the access entry from the parent
        $parent_access = self::get_post_access_entry($parent_id);
        
        $children = _ax_util_get_child_pages_of($parent_id);

        foreach ($children as $child)
        {
            self::copy_access($parent_id, $child->ID);
            self::sync_to_parent($child->ID); //sync to children of children
        }
    }

    public static function copy_access($parent_id, $child_id)
    {
        self::delete_access_entry($child_id);

        $parent_entry = self::get_post_access_entry($parent_id);
        $entry_id = self::save_access_entry($parent_entry->access_type, $child_id, $parent_id);

        $principals = self::get_entry_principals($parent_entry->id);
        foreach ($principals as $principal)
        {
            self::add_entry_principal($entry_id, $principal->principal, $principal->principal_type);
        }
    }
}