<?php

AxBin::load('axelrad-data-access');
AxBin::load('axelrad-user-mgmt');

include 'ax-clinic-models.php';

class AxClinicData
{
    
    /* #region Contact Stuff */

    public const CONTACT_TYPE_CONTACT = 'contact';
    public const CONTACT_TYPE_PATIENT = 'patient';
    public const CONTACT_TYPE_EMPLOYEE = 'employee';
    

    public static function get_contact_user_id($contact_id)
    {
        return R::getCell('select user_acct from contact where id = ?', [$contact_id]);
    }

    public static function contact_create($email, $first_name, $last_name, $props = [])
    {
        return self::_contact_create($email, $first_name, $last_name, self::CONTACT_TYPE_CONTACT, $props);
    }   

    
    static function _contact_create($email, $first_name, $last_name, $type, $props = [])
    {
        $contact = R::dispense('contact');
        $contact->first_name = $first_name;
        $contact->last_name = $last_name;
        $contact->email = $email;
        $contact->contact_type = $type;


        foreach ($props as $key => $value)
        {
            $contact-$key = $value;
        }

        if (!$contact->full_name) $contact->full_name = $first_name.' '.$last_name;

        $id = R::store($contact);

        //now create a user account so we have that part done... 

        $user_id = AxelradUserMgmt::create_user($email, $first_name, $last_name, 'all-contacts');

        $contact = R::load('contact', $id);
        $contact->user_acct = $user_id;
        R::store($contact);

        return $id;
    }

    public static function contact_id_exists($id)
    {
        return R::count('contact', 'id = ?', [$id]) > 0;
    }

    public static function contact_exists($email, $first_name, $last_name)
    {
        return R::count('contact', 'email = ? AND first_name = ? AND last_name = ?', [$email, $first_name, $last_name]) > 0;
    }

    public static function find_contacts_with_email($email)
    {
        return AxData::_flatten(R::getAssoc('select * from contact where email = ? ORDER BY first_name, last_name', [$email]));
    }

    public static function change_contact_to_patient($id)
    {

        $c = R::load('contact', $id);
        if ($c->contact_type != self::CONTACT_TYPE_PATIENT)
        {
            $c->contact_type = self::CONTACT_TYPE_PATIENT;
            R::store($c);

            AxelradUserMgmt::add_group_member(AxelradUserMgmt::get_cached_built_in_group_id('all-patients'), $id);
        }
    }

    public static function contact_get($id)
    {
        return R::load('contact', $id);
    }

    public static function delete_contact($id)
    {
        $contact = R::load('contact', $id);
        
        if ($contact != null)
        {
            $user_id = self::get_contact_user_id($id);
            AxelradUserMgmt::delete_user($user_id);
            R::trash($contact);
            
        }
    }

    /* #endregion */

    /* #region employee stuff */
    
    public static function employee_create($email, $first_name, $last_name, $props = [])
    {
        return self::_contact_create($email, $first_name, $last_name, self::CONTACT_TYPE_EMPLOYEE, $props);
    }  
    

    /* #endregion */

    /* #region patient stuff */

    public static function patient_create($email, $first_name, $last_name, $props = [])
    {
        $id = self::_contact_create($email, $first_name, $last_name, self::CONTACT_TYPE_PATIENT, $props);
        AxelradUserMgmt::add_group_member(
            AxelradUserMgmt::get_cached_built_in_group_id('all-patients'), 
            self::get_contact_user_id($id)
        );

        return $id;
    }

    public static function change_patient_to_contact($id)
    {

        $c = R::load('contact', $id);
        if ($c->contact_type != self::CONTACT_TYPE_CONTACT)
        {
            $c->contact_type = self::CONTACT_TYPE_CONTACT;
            R::store($c);

            $user_id = self::get_contact_user_id($id);
            if ($user_id)
            {
                AxelradUserMgmt::rmv_group_member(AxelradUserMgmt::get_cached_built_in_group_id('all-patients'), $user_id);

                $groups = AxelradUserMgmt::groups_fetch('patient');
                foreach ($groups as $group)
                {
                    AxelradUserMgmt::rmv_group_member($group->id, $user_id);
                }
            }
        }
    }

    /* #endregion

    /* #region calendar stuff */
    const TABLE_NAME_CALENDAR = 'calendar';
    const TABLE_NAME_APPT_TYPE = 'appttype';
    const TABLE_NAME_CALENDAR_APPT_TYPE = 'calappttype';

    public static function calendar_create($name, $description, $notification_email = '')
    {
        $calendar = R::dispense(self::TABLE_NAME_CALENDAR);
        $calendar->name = $name;
        $calendar->description = $description;
        $calendar->notification_email = $notification_email;
        $cal_id = R::store($calendar);

        $calendar = R::dispense(self::TABLE_NAME_CALENDAR);
        $calendar->name = $name.' - Standby';
        $calendar->description = 'Standby calendar for '.$name;
        $calendar->standby_for = $cal_id;
        $sb_id = R::store($calendar);

        $calendar = R::load(self::TABLE_NAME_CALENDAR, $cal_id);
        $calendar->standby_cal = $sb_id;
        R::store($calendar);

        return $cal_id;
    }

    public static function calendar_appt_types_assign($calendar_id, $appt_type_ids)
    {
        $assigned = self::calendar_appt_types_get($calendar_id);
        $do_assign = [];

        foreach ($appt_type_ids as $new_type_id)
        {
            if (array_search($new_type_id, $assigned) === false) //not yet assigned
                $do_assign[] = $new_type_id;
        }

        foreach ($do_assign as $type_id)
        {
            self::calendar_appt_type_assign($calendar_id, $type_id);
        }
        
    }


    public static function calendar_appt_type_assign($calendar_id, $appt_type_id)
    {
        $cal_appt_type = R::dispense(self::TABLE_NAME_CALENDAR_APPT_TYPE);
        $cal_appt_type->calendar_id = $calendar_id;
        $cal_appt_type->appt_type_id = $appt_type_id;
        R::store($cal_appt_type);    
    }
    
    public static function calendar_has_appt_type($calendar_id, $appt_type_id)
    {
        return R::count(self::TABLE_NAME_CALENDAR_APPT_TYPE, 'calendar_id = ? AND appt_type_id = ?', [$calendar_id, $appt_type_id]) > 0;
    }

    public static function calendar_appt_types_get($calendar_id)
    {
        return AxData::_flatten(R::getAssoc('select * from ? where id in (select appt_type_id from ? where calendar_id = ?) 
            ORDER BY name DESC', [self::TABLE_NAME_APPT_TYPE, self::TABLE_NAME_CALENDAR_APPT_TYPE, $calendar_id]));
    }

    public static function calendar_appt_types_clear($calendar_id)
    {
        return AxData::_flatten(R::getAssoc('delete from ? where calendar_id = ?', [self::TABLE_NAME_CALENDAR_APPT_TYPE, $calendar_id]));
    }

    public static function calendar_with_name_exists($name)
    {
        return R::count(self::TABLE_NAME_CALENDAR, 'name = ? AND (is_deleted = 0 OR is_deleted is NULL)', [$name]) > 0;
    }

    public static function calendar_exists($id)
    {
        return R::count(self::TABLE_NAME_CALENDAR, 'id = ? AND (is_deleted = 0 OR is_deleted is NULL)', [$id]) > 0;
    }

    public static function calendar_get($id)
    {
        return R::load(self::TABLE_NAME_CALENDAR, $id);
    }

    public static function calendars_find($filter = '')
    {
        if ($filter != '')
            return AxData::_flatten(R::getAssoc('select * from ? where name LIKE \'%?%\' OR description like \'%?%\' ORDER BY name DESC LIMIT 25'), [self::TABLE_NAME_CALENDAR, $filter, $filter]);
        else
            return AxData::_flatten(R::getAssoc('select * from ? ORDER BY name DESC LIMIT 25', [self::TABLE_NAME_CALENDAR]));
    }

    public static function calendar_rename($id, $new_name)
    {
        if (!self::calendar_exists($id))
        {
            throw new Exception('NOT_EXISTS');
        }

        $c = R::load(self::TABLE_NAME_CALENDAR, $id);
        $c->name = $new_name;

        R::store($c);

        $sb_id = $c->standby_cal;
        $sb = R::load(self::TABLE_NAME_CALENDAR, $sb_id);
        $sb->name = $new_name.' - Standby';
        $sb->description = 'Standby calendar for '.$new_name;

        R::store($sb);

    }

    public static function calendar_delete($id)
    {
        if (!self::calendar_exists($id))
            throw new Exception('NOT_EXISTS');

        $cal = R::load(self::TABLE_NAME_CALENDAR, $id);
        $cal->is_deleted = 1;
        R::store($cal);
    }

    public static function calendar_obliterate($id)
    {
        if (!self::calendar_exists($id))
            throw new Exception('NOT_EXISTS');

        $cal = R::load(self::TABLE_NAME_CALENDAR, $id);
        R::trash($cal);
    }

    /* #endregion */


    /* #region appointment stuff */

    public const TABLE_NAME_APPT = 'appt';
    public const TABLE_NAME_APPT_TYPE_DURATION = 'atypeduration';

    public const APPT_STATUS_PENDING        = 'pending';
    public const APPT_STATUS_ARRIVED        = 'arrived';
    public const APPT_STATUS_IN_PROGRESS    = 'in-progress';
    public const APPT_STATUS_PT_LATE        = 'pt-late';
    public const APPT_STATUS_CHECKING_OUT   = 'checking-out';
    public const APPT_STATUS_COMPLETE       = 'complete';
    public const APPT_STATUS_CANCELED       = 'canceled';
    public const APPT_STATUS_NO_SHOW        = 'no-show';
    

    public static function appt_create_for_contact($calendar_id, $contact_id, $start, $duration, $appt_type_id, $provider_id)
    {
        return self::_appt_create($calendar_id, '', $contact_id, $start, $duration, $appt_type_id, $provider_id);
        
    }

    public static function appt_create_no_contact($calendar_id, $subject, $start, $duration, $appt_type_id, $provider_id)
    {
        return self::_appt_create($calendar_id, $subject, 0, $start, $duration, $appt_type_id, $provider_id);
    }

    static function _appt_create($calendar_id, $subject, $contact_id, $start, $duration, $appt_type_id, $provider_id)
    {
        if ($contact_id && !self::contact_id_exists($contact_id))
            throw new Exception('CONTACT_NOT_EXISTS');

        if (!self::calendar_exists($calendar_id))
            throw new Exception('CALENDAR_NOT_EXISTS');
        
        if (!self::appt_type_exists($appt_type_id))
            throw new Exception('APPT_TYPE_NOT_EXISTS');

        if (!self::contact_id_exists($provider_id))
            throw new Exception('PROVIDER_NOT_EXISTS');

        $contact   = self::contact_get($contact_id);
        $provider  = self::contact_get($provider_id);
        $calendar  = self::calendar_get($calendar_id);
        $appt_type = self::appt_type_get($appt_type_id);

        $appt                     = R::dispense(self::TABLE_NAME_APPT);
        $appt->start              = $start;
        $appt->duration           = $duration;
        $appt->calendar_id        = $calendar_id;
        $appt->calendar_name      = $calendar->name;
        $appt->appttype_id        = $appt_type_id;
        $appt->appttype_name      = $appt_type->name;
        if ($contact_id)
        {
            $appt->contact_id = $contact_id;
            $appt->subject    = $contact->fullname;
        }
        else 
            $appt->subject    = $subject;

        $appt->provider_id    = $provider_id;
        $appt->provider_name  = $provider->fullname;

        return R::store($appt);
        
    }

    public static function appt_cancel($id, $is_no_show = false, $reason = '')
    {
        $appt = R::load(self::TABLE_NAME_APPT, $id);
        $appt->status        = $is_no_show ? self::APPT_STATUS_NO_SHOW : self::APPT_STATUS_CANCELED;
        $appt->cancel_reason = $reason;
        R::store($appt);
    }

    public static function appt_status_update($id, $new_status)
    {
        $appt = R::load(self::TABLE_NAME_APPT, $id);
        $appt->status = $new_status;
        R::store($appt);
    }

    public static function appt_type_create($name, $description, $color)
    {
        $t = R::dispense(self::TABLE_NAME_APPT_TYPE);
        $t->name        = $name;
        $t->description = $description;
        $t->color       = $color;
        return R::store($t);
    }

    public static function appt_type_get($id)
    {
        return R::load(self::TABLE_NAME_APPT_TYPE,$id);
    }

    public static function appt_type_exists($id)
    {
        return R::count(self::TABLE_NAME_APPT_TYPE, 'id = ?', [$id]) > 0;
    }

    public static function appt_type_duration_option_add($appt_type_id, $duration_minutes)
    {
        $options = self::appt_type_duration_options($appt_type_id);

        foreach ($options as $option)
        {
            if ($option->minutes == $duration_minutes)
                return; //just let it go
        }


        $appt_type_duration          = R::dispense(self::TABLE_NAME_APPT_TYPE_DURATION);
        $appt_type_duration->appt_type_id = $appt_type_id;
        $appt_type_duration->minutes = $duration_minutes;
        R::store($appt_type_duration);
    }

    public static function appt_type_duration_options($appt_id)
    {
        return AxData::_flatten(R::findAll(self::TABLE_NAME_APPT_TYPE_DURATION, 'appt_id = ? ORDER BY minutes', [$appt_id]));
    }

    public static function appt_type_duration_option_remove($appt_id, $duration_minutes)
    {
        $option = R::find(self::TABLE_NAME_APPT_TYPE_DURATION, 'appt_id = ? AND minutes = ?', [$appt_id, $duration_minutes]);
        
        if ($option == null) return;

        R::trash($option);
    }
    /* #endregion */


    /* #region other code */
    static function ensure_built_in_groups()
    {
    if (get_option('all-contacts-id') && get_option('all-patients-id'))
      return;

    $groups = [];

    
    $groups[] = $g = R::dispense('usergroup'); 
    $g->name = 'all-contacts';
    $g->display_name = 'All Contacts';
    $g->description = 'All contacts are in this group and cannot be removed from it.';
    $g->built_in = 1;
    $g->is_default = 1;


    $groups[] = $g = R::dispense('usergroup'); 
    $g->name = 'all-patients';
    $g->display_name = 'All Patients';
    $g->description = 'All patients are in this group.';
    $g->built_in = 1;
    
    $groups[] = $g = R::dispense('usergroup'); 
    $g->name = 'active-patients';
    $g->display_name = 'Active Patients';
    $g->description = 'All active patients are automatically added to this group.';
    $g->built_in = 1;
    
    $groups[] = $g = R::dispense('usergroup'); 
    $g->name = 'inactive-patients';
    $g->display_name = 'Inactive Patients';
    $g->description = 'All inactive patients are automatically added to this group when they go inactive.';
    $g->built_in = 1;
    
    $groups[] = $g = R::dispense('usergroup'); 
    $g->name = 'lapsing-patients';
    $g->display_name = 'Lapsing Patients';
    $g->description = 'All lapsing patients are automatically added to this group when they reach the pre-laspe threshold.';
    $g->built_in = 1;
    
    $groups[] = $g = R::dispense('usergroup'); 
    $g->name = 'lapsed-patients';
    $g->display_name = 'Lapsed Patients';
    $g->description = 'All lapsed patients are automatically added to this group when they reach the lasped threshold.';
    $g->built_in = 1;
    

    foreach ($groups as $group)
    {
      if (!AxelradUserMgmt::group_name_exists($group->name))
      {
        $id = R::store($group);
        AxelradUserMgmt::set_cached_built_in_group_id($group->name, $id);
      }
      else 
      {
        $id = AxelradUserMgmt::get_group_id_from_name($group->name);
        AxelradUserMgmt::set_cached_built_in_group_id($group->name, $id);
      }
    }
  }
  /* #endregion */
}