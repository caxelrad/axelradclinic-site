<?php 

AxBin::load('axelrad-data-access');

class Model_Accessentry extends AxDataObj
{ 
    protected function records_create() { return true; }
    protected function records_delete() { return true; }

    protected function known_prop_names()
    {
        return ['post_id', 'parent_id', 'access_type', 'access_denied_url', 'access_denied_msg', 'registration_url'];
    }

    protected function add_props(\AxPropList $prop_list)
    {
        $prop_list->add_num('post_id')->required(true);
        $prop_list->add_num('parent_id')->required(true);
        $prop_list->add_text('access_type')->required(true);
        $prop_list->add_text('access_denied_url')->required(false)->maxlength(500);
        $prop_list->add_text('access_denied_msg')->required(false)->maxlength(500);
        $prop_list->add_text('registration_url')->required(false)->maxlength(500);
    }

    protected function on_update()
    {
        if ($this->bean->access_type == AxelradAccessControl::ACCESS_TYPE_ROLE)
        {
            $roles = explode('|', $this->bean->principals);
            foreach ($roles as $role)
            {
                if (!get_role($role))
                    throw new Exception('NO_ROLE');
            }
        }
        if ($this->bean->access_type == AxelradAccessControl::ACCESS_TYPE_GROUP)
        {
            $groups = explode('|', $this->bean->principals);
            foreach ($groups as $id)
            {
                if (!AxelradUserMgmt::group_exists($id))
                    throw new Exception('NO_GROUP');
            }
        }

        if (!get_post($this->bean->post_id))
            throw new Exception('NO_POST');
        
        if (!get_post($this->bean->parent_id))
            throw new Exception('NO_PARENT');
    }
}


class Model_Accessprincipal extends AxDataObj
{ 
    protected function records_create() { return true; }
    protected function records_delete() { return true; }

    protected function known_prop_names()
    {
        return ['accessentry_id', 'principal', 'principal_type'];
    }

    protected function add_props(\AxPropList $prop_list)
    {
        $prop_list->add_num('accessentry_id')->required(true);
        $prop_list->add_text('principal')->required(true);
        $prop_list->add_text('principal_type')->required(true);
    }

    protected function on_update()
    {
        if ($this->bean->principal_type == 'role')
        {
            if (!get_role($this->bean->principal))
                throw new Exception('NO_ROLE');
        }

        if ($this->bean->principal_type == 'group')
        {
            if (!AxelradUserMgmt::group_exists($this->bean->principal))
                throw new Exception('NO_GROUP');
        }

        if (!AxAccessCtrlData::get_access_entry($this->bean->entry_id))
            throw new Exception('NO_ENTRY');
    }
}