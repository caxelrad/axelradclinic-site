<?php

class Model_Calendar extends AxDataObj
{
    protected function records_create() { return true; }
    protected function records_update() { return true; }
    protected function records_delete() { return true; }

    protected function add_props(\AxPropList $prop_list)
    {
        $prop_list->add_text('name')->required(true)->minlength(5)->maxlength(50)->must_not_contain(AxDataObj::ILLEGAL_NAME_CHARS);
        $prop_list->add_text('description')->required(false)->maxlength(500);
        $prop_list->add_email('notification_email')->required(false);
        $prop_list->add_num('standby_for')->required(false);
        $prop_list->add_num('standby_cal')->required(false);
        $prop_list->add_bool('is_deleted')->default_value(0);
    }

    protected function on_update()
    {
        //make sure the name is reasonable
        if (strlen($this->bean->name) < 2)
            throw new Exception('NAME_TOO_SHORT');


        if ($this->bean->id == 0)
        {
            if (AxClinicData::calendar_exists($this->bean->name))
                throw new Exception('EXISTS');
        }

        if ($this->bean->notification_email != '')
        {
            //make sure the email is good...
            if (!AxelradUtil::email_is_valid($this->bean->notification_email))
                throw new Exception('INvALID_EMAIL');
        }

    }
}


class Model_Appt extends AxDataObj
{
    protected function records_create() { return true; }
    protected function records_update() { return true; }
    protected function records_delete() { return true; }

    protected function add_props(\AxPropList $prop_list)
    {
        $prop_list->add_text('subject')->required(true)->minlength(5)->maxlength(255)->must_not_contain(AxDataObj::ILLEGAL_NAME_CHARS);
        $prop_list->add_text('description')->required(false)->maxlength(500);
        $prop_list->add_email('notification_email')->required(false);
        $prop_list->add_num('calendar_id')->required(true);
        $prop_list->add_text('calendar_name')->required(true);

        $prop_list->add_text('status')->required(false)->maxlength(25)->minlength(3)->can_only_contain(AxDataObj::ALPHA_LOWER);
        $prop_list->add_text('cancel_reason')->required(false)->maxlength(125);
        
        $prop_list->add_num('contact_id')->required(false);
        $prop_list->add_text('contact_name')->required(false);
        
        $prop_list->add_num('appttype_id')->required(true);
        $prop_list->add_text('appt_type_name')->required(true);

        $prop_list->add_num('provider_id')->required(false);
        $prop_list->add_text('provider_name')->required(false);

        $prop_list->add_date('start')->required(true);
        $prop_list->add_num('duration')->required(true);

        $prop_list->add_bool('is_deleted')->default_value(0);
    }

    protected function on_update()
    {
        //make sure the name is reasonable
        if (strlen($this->bean->name) < 2)
            throw new Exception('NAME_TOO_SHORT');


        if ($this->bean->id == 0)
        {
            if (AxClinicData::calendar_exists($this->bean->name))
                throw new Exception('EXISTS');
        }
    }
}


class Model_Contact extends AxDataObj
{ 
    protected function records_create() { return true; }
    protected function records_update() { return true; }
    protected function records_delete() { return true; }

    protected function add_props(\AxPropList $prop_list)
    {
        $prop_list->add_text('first_name')->required(true)->minlength(2)->maxlength(75)->must_not_contain(AxDataObj::ILLEGAL_NAME_CHARS);
        $prop_list->add_text('last_name')->required(true)->minlength(2)->maxlength(75)->must_not_contain(AxDataObj::ILLEGAL_NAME_CHARS);
        $prop_list->add_text('full_name')->required(true)->minlength(5)->maxlength(156)->must_not_contain(AxDataObj::ILLEGAL_NAME_CHARS);


        $prop_list->add_text('contact_type')->required(true)->maxlength(25)->minlength(4);
        $prop_list->add_num('user_acct')->required(false)->default_value(0);
        
        $prop_list->add_text('nickname')->required(false);
        $prop_list->add_text('middle_name')->required(false);
        $prop_list->add_text('suffix')->required(false)->maxlength(5);

        $prop_list->add_email('email')->required(false);
        $prop_list->add_email('email2')->required(false);

        $prop_list->add_phone('mobile_phone')->required(false);
        $prop_list->add_phone('home_phone')->required(false);
        $prop_list->add_phone('work_phone')->required(false);

        $prop_list->add_text('occupation')->required(false)->maxlength(75);
        $prop_list->add_text('company')->required(false)->maxlength(75);

        $prop_list->add_text('emergency_contact_name')->required(false)->maxlength(75);
        $prop_list->add_phone('emergency_contact_phone')->required(false);
        $prop_list->add_text('emergency_contact_relation')->required(false)->maxlength(75);

        $prop_list->add_text('address_street')->required(false)->maxlength(75);
        $prop_list->add_text('address_street_2')->required(false)->maxlength(75);
        $prop_list->add_text('address_city')->required(false)->maxlength(75);
        $prop_list->add_text('address_state')->required(false)->maxlength(75);
        $prop_list->add_text('address_zip')->required(false)->maxlength(75);
        $prop_list->add_text('address_country')->required(false)->maxlength(75);

        $prop_list->add_text('billing_address_street')->required(false)->maxlength(75);
        $prop_list->add_text('billing_address_street_2')->required(false)->maxlength(75);
        $prop_list->add_text('billing_address_city')->required(false)->maxlength(75);
        $prop_list->add_text('billing_address_state')->required(false)->maxlength(75);
        $prop_list->add_text('billing_address_zip')->required(false)->maxlength(75);
        $prop_list->add_text('billing_address_country')->required(false)->maxlength(75);

        $prop_list->add_num('last_completed_appt')->required(false)->default_value(0);
        $prop_list->add_num('next_appt')->required(false)->default_value(0);
        $prop_list->add_num('last_cancelled_appt')->required(false)->default_value(0);
        $prop_list->add_num('last_no_show_appt')->required(false)->default_value(0);

        $prop_list->add_num('future_appt_count')->required(false)->default_value(0);
        $prop_list->add_num('completed_appt_count')->required(false)->default_value(0);
        $prop_list->add_num('cancelled_appt_count')->required(false)->default_value(0);
        $prop_list->add_num('no_show_appt_count')->required(false)->default_value(0);

        $prop_list->add_num('primary_clinician_id')->required(false)->default_value(0);

        
        $prop_list->add_bool('is_deleted')->default_value(0);
    }

    protected function on_update()
    {
        //make sure the email is good...
        if (!AxelradUtil::email_is_valid($this->bean->email))
            throw new Exception('INvALID_EMAIL');
            
        //make sure the name is reasonable
        if (strlen($this->bean->first_name) < 2 || strlen($this->bean->last_name) < 2)
            throw new Exception('NAME_TOO_SHORT');

        
        if ($this->bean->id == 0)
        {
            if (AxClinicData::contact_exists($this->bean->email, $this->bean->first_name, $this->bean->last_name))
            {
                throw new Exception('EXISTS');
            }

            if (AxelradUserMgmt::find_user_id_by_email($this->bean->email) != 0)
            {
                throw new Exception('USER_EXISTS');
            }
        }
    }
}

class Model_Appttype extends AxDataObj
{

    protected function records_create() { return true; }
    protected function records_update() { return true; }
    protected function records_delete() { return true; }

    protected function add_props(\AxPropList $prop_list)
    {
        $prop_list->add_text('name')->required(true)->minlength(5)->maxlength(75)->must_not_contain(AxDataObj::ILLEGAL_NAME_CHARS);
        $prop_list->add_text('description')->required(false)->maxlength(255);
        $prop_list->add_text('color')->required(true)->maxlength(10)->can_only_contain(AxDataObj::NUMERIC.'#');
        $prop_list->add_bool('is_deleted')->default_value(0);
    }
}

class Model_Atypeduration extends AxDataObj
{

    protected function records_create() { return true; }
    protected function records_update() { return true; }
    protected function records_delete() { return true; }

    protected function add_props(\AxPropList $prop_list)
    {
        $prop_list->add_num('appt_type_id')->required(true);
        $prop_list->add_num('duration')->required(true);
    }
}