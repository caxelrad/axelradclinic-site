<?php

class Model_Usergroup extends AxDataObj
{ 
    protected function records_create() { return true; }
    protected function records_update() { return true; }
    protected function records_delete() { return true; }

    protected function add_props(\AxPropList $prop_list)
    {
        $prop_list->add_text('name')->required(true)->minlength(5)->maxlength(50)->must_not_contain(AxDataObj::ILLEGAL_NAME_CHARS);
        $prop_list->add_text('display_name')->required(true)->minlength(5)->maxlength(50)->must_not_contain(AxDataObj::ILLEGAL_NAME_CHARS);
        $prop_list->add_text('description')->required(false)->maxlength(500);
        //$prop_list->add_text('group_type')->required(true)->maxlength(25)->can_only_contain(AxDataObj::ALPHA_LOWER);
        $prop_list->add_bool('is_default')->default_value(0);
        $prop_list->add_bool('built_in')->default_value(0);
    }

    protected function on_check_exists()
    {
        return AxelradUserMgmt::group_name_exists($this->bean->name);
    }

    protected function on_update()
    {
        if ($this->bean->hasChanged('name') && AxelradUserMgmt::group_name_exists($this->bean->name))
            throw new Exception('EXISTS');
    }

    protected function on_delete()
    {
        if ($this->bean->is_default == 1
            || $this->bean->built_in == 1)
            throw new Exception('NOT_ALLOWED');
    }
}

class Model_Membership extends AxDataObj
{ 
    protected function records_create() { return true; }
    protected function records_delete() { return true; }

    protected function known_prop_names()
    {
        return ['usergroup_id', 'user_id'];
    }

    protected function add_props(\AxPropList $prop_list)
    {
        $prop_list->add_num('usergroup_id')->required(true);
        $prop_list->add_num('user_id')->required(true);
    }

    protected function on_update()
    {
        if (!AxelradUserMgmt::group_exists($this->bean->usergroup_id))
            throw new Exception('NO_GROUP');

        if (!AxelradUserMgmt::user_exists($this->bean->user_id))
            throw new Exception('NO_USER');
    }
}

