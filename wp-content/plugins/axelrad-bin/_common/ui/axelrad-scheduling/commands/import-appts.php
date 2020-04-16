<?php

class ImportAppts
{
  
  public static function run()
  {
    echo 'hi i am ImportAppts.';
  }
  
  private static $patient_id_cache = [];
  private static $appt_type_cache = [];
  private static $calendar_cache = [];
  private static $employee_cache = [];
  
  static function db_str($value)
  {
    return str_replace("'", "''", $value);
  }
  
  public static function import_appts($filename)
  {

    
    $calendars = AxClinicDatabase::current()->search('AxClinicCalendarTable', []);
    
    //cache calendars by name
    foreach ($calendars as $calendar)
    {
      self::$calendar_cache[$calendar[AxClinicCalendarTable::PROP_NAME]] = $calendar;
      _ax_debug('<div>caching calendar '.$calendar[AxClinicCalendarTable::PROP_NAME].'</div>');
    }
    
    $appt_types = AxClinicDatabase::current()->search('AxClinicApptTypeTable', []);
    
    //cache appt types by 'name duration' i.e. 'follow-up 15' and 'follow-up 30'
    foreach ($appt_types as $type)
    {
      self::$appt_type_cache[$type[AxClinicApptTypeTable::PROP_NAME].' '.$type[AxClinicApptTypeTable::PROP_DURATION]] = $type;
     _ax_debug('<div>caching appt_type '.$type[AxClinicApptTypeTable::PROP_NAME].' '.$type[AxClinicApptTypeTable::PROP_DURATION].' ('.$type[AxClinicApptTypeTable::PROP_ID].')</div>');
    }
    
    
    $employees = AxClinicDatabase::current()->search('AxClinicEmployeeTable', []);
     
    //cache employees by first name
    foreach ($employees as $employee)
    {
      self::$employee_cache[$employee[AxClinicEmployeeTable::PROP_FIRST_NAME]] = $employee;
      _ax_debug('<div>caching employee '.$employee[AxClinicEmployeeTable::PROP_FIRST_NAME].' ('.$employee[AxClinicEmployeeTable::PROP_ID].')</div>');
    }
    
    //return;
    
    $file = fopen($filename, "r");
    $field_index = [];

    $row = -1;
    
    $created = 0;
    $failed = 0;
    $skipped = 0;
    
    //Start Time [0], End Time [1], First Name [2], Last Name [3], Phone [4], Email [5], Type [6], Calendar [7], 
    //Appointment Price [8], Paid? [9], Amount Paid Online [10], Certificate Code [11], Notes [12], Date Scheduled [13], Label [14], Canceled [15], Appointment ID [16]
    //$max = self::db()->select('ax_appts', 'MAX()');
    while (($getData = fgetcsv($file, 10000, ",")) !== FALSE)
    {
      //wpdb::insert( string $table, array $data
      $row++;
      if ($row == 0)
      {
        $i = 0;
        foreach ($getData as $field)
        {
          $field_index[$field] = $i;
          $i++;
        }
      }
      else 
      {
        
        $appt_id = $getData[$field_index['Appointment ID']];
        $sched_date = strtotime($getData[$field_index['Date Scheduled']]);
        $start_time = strtotime($getData[$field_index['Start Time']]);
        
        $end_time = strtotime($getData[$field_index['End Time']]);
        $duration = ($end_time - $start_time) / 60;
        
        $sched_date_val = date('Y-m-d', $sched_date);
        $start_time_val = date("Y-m-d H:i:s", $start_time);
        $end_time_val = date("Y-m-d H:i:s", $end_time);

        $first_name = $getData[$field_index['First Name']];
        $last_name = $getData[$field_index['Last Name']];
        $phone = $getData[$field_index['Phone']];
        $email = $getData[$field_index['Email']];
        $appt_calendar_name = $getData[$field_index['Calendar']];
        $appt_notes = $getData[$field_index['Notes']];
        $appt_type_name = $getData[$field_index['Type']];
        $cancelled = ($getData[$field_index['Canceled']] ? 1 : 0);
        $no_show = ($cancelled && $getData[$field_index['Canceled']] == 'no-show');
       
        _ax_debug('$appt_type_name = '.$appt_type_name);
        _ax_debug('$duration = '.$duration);
        
        $status = 'pending';
        if ($cancelled)
          $status = 'cancelled';
        else if ($no_show)
          $status = 'no-show';
        else if ($start_time < time())
          $status = 'complete';
        
        
        $patient_id = self::read_patient_id($appt_notes);
        
        if ($patient_id == 0)
        {
          $key = $first_name.$last_name.$email;
          $patient_id = self::$patient_id_cache[$key];
         _ax_debug('cached patient id is '.$patient_id);
        
          if ($patient_id == 0)
            $patient_id = self::find_or_create_patient($first_name, $last_name, $email, $phone, '');
          
          if ($patient_id == 0)
            _ax_debug('<div style="color: red;">Error creating patient ('.$first_name.' '.$last_name.' '.$email.')</div>');
          else
          {
            _ax_debug('<div style="color: #009900;">patient created or retrieved with id: '.$patient_id.'</div>');
            self::$patient_id_cache[$key] = $patient_id;
          }
        }
        
        $clinician_id = self::$employee_cache['Chris']['id'];
        if ($appt_calendar_name == 'Katy')
          $clinician_id = self::$employee_cache['Vy']['id'];
        else if ($appt_calendar_name == 'Sugar Land')
          $clinician_id = self::$employee_cache['Reagan']['id'];
        else if ($appt_calendar_name == 'The Woodlands')
          $clinician_id = self::$employee_cache['Jaime']['id'];
        else if ($appt_calendar_name == 'Houston' && 
                 (date('D', $start_time) == 'Tue' || date('D', $start_time) == 'Thu'))
          $clinician_id = self::$employee_cache['Jaime']['id'];
        
        
        $calendar_id = '';
        $appt_type_id = '';
        
        if (self::$calendar_cache[$appt_calendar_name])
          $calendar_id = self::$calendar_cache[$appt_calendar_name]['id'];
        
        _ax_debug('looking for type: '.$appt_type_name.' '.$duration);
        if (self::$appt_type_cache[$appt_type_name.' '.$duration])
          $appt_type_id = self::$appt_type_cache[$appt_type_name.' '.$duration]['id'];
        
        
        $appts = AxClinicDatabase::current()->_new('AxClinicApptTable');
        
        $params = [
              AxClinicApptTable::PROP_TYPE_ID => $appt_type_id,
              AxClinicApptTable::PROP_CALENDAR_ID => $calendar_id,
              AxClinicApptTable::PROP_CONTACT_ID => $patient_id,
              AxClinicApptTable::PROP_CLINICIAN_ID => $clinician_id,
              AxClinicApptTable::PROP_START => $start_time_val,
              AxClinicApptTable::PROP_EXTERNAL_ID => $appt_id,
              AxClinicApptTable::PROP_NOTES => $appt_notes,
              AxClinicApptTable::PROP_STATUS => $status
            ];


        $dump = '';
        foreach ($params as $pkey => $pvalue)
        {
          if ($dump != '') $dump .= ' | ';
          $dump .= $pkey.' :: '.$pvalue;
        }

        _ax_debug('<div>creating appt: '.$dump.'</div>');
        
        //if ($row > 10) break;
        
        if ($calendar_id && $appt_type_id)
        {
          
          try
          {
            
            if (AxClinicDatabase::current()->_new('AxClinicApptTable')->exists(
              new AxDataFilter('AND', [[AxClinicApptTable::PROP_EXTERNAL_ID, '=', $appt_id]])))
            {
              _ax_debug('<div style="color: #009900;">SKIPPED: appt id '.$appt_id.' already exists in the database</div>');
              $skipped++;
            }
            else 
            {
              $result = AxClinicDatabase::current()->create('AxClinicApptTable', $params);
              _ax_debug('<div style="color: #009900;">SUCCESS: appt id = '.$result['id'].'</div>');
              $created++;
            }
          }
          catch (Exception $ex)
          {
            _ax_debug('<div style="color: red;">ERROR during appointment creation: '.$ex->getMessage().'</div>');  
            $failed++;
          }
        }
        else
        {
          _ax_debug('<div style="color: red;">SKIPPED appointment due to bad calendar or appt type.</div>');  
          $skipped++;
        }
        
        //if ($row > 10) break;
      }
    }

    fclose($file);  
    
    _ax_debug($created.' successfully created. '.$skipped.' skipped. '.$failed.' failed');
  }

  static function read_patient_id($appt_notes)
  {
    if (!$appt_notes)
      return 0;
    else
    {
      if (strpos($appt_notes, '|') !== false)
      {
        return trim(explode('|', $appt_notes)[1]);
      }
      else
        return 0;
    }
  }
  public static function import_appt($data)
  {
    /*
    AxOpCreateAppt 
    public const OUT_PARAM_ID = 'id';
    public const PARAM_CONTACT_ID = 'contact_id';
    public const PARAM_START = 'start';
    public const PARAM_TYPE_ID = 'type_id';
    public const PARAM_CLINICIAN_ID = 'clinician_id';
    public const PARAM_NOTES = 'notes';
    public const PARAM_CALENDAR_ID = 'calendar_id';
    */
    
    //create the patient record first...
    
  }
  
//   public function set_client_external_ids()
//   {
//     $rows = AxClinicDatabase::current()->query('select * from ax_appts order by contact_id');
    
//     $contact_id = null;
//     foreach ($rows as $row)
//     {
//       if($contact_id != $row['contact_id'])
//       {
//         //get the notes fields
//         $contact_id = $row['contact_id'];
        
//         $notes = $row['notes'];
//         if (strpos($notes, '|') !== false)
//         {
//           $external_id = trim(explode('|', $notes)[1]);
//           if ($external_id)
//           {
//             AxClinicDatabase::current()->execute('update ax_clients set external_contact_id = '.$external_id.' where id = '.$contact_id);
//             echo '<br>external id updated to '.$external_id.' for contact '.$contact_id;
//           }
//         }
//       }
//     }
//   }
  
  public static function import_clients($filename)
  {
    
    //$filename = __DIR__.'/../../../../_axelrad_files/clients.csv';
    $file = fopen($filename, "r");

    echo '<br>client file '.$filename.' is open';

    $field_index = [];

    $row = 0;

    //First Name [0]. Last Name [1], Phone [2], Email [3]


    while (($getData = fgetcsv($file, 10000, ",")) !== FALSE)
    {
      //wpdb::insert( string $table, array $data
      //echo '<br>loading row...';
      if ($row == 0)
      {
        $i = 0;
        foreach ($getData as $field)
        {
          $field_index[$field] = $i;
          $i++;
        }
      }
      else 
      {

        $first_name = $getData[$field_index['First Name']];
        $last_name = $getData[$field_index['Last Name']];
        $email = $getData[$field_index['Email']];
        $phone = $getData[$field_index['Phone']];
        $notes = $getData[$field_index['Notes']];
        
        if ($first_name != '' && $last_name != '' && $email != '')
        {
          $key = $first_name.$last_name.$email;
          $patient_id = self::$patient_id_cache[$key];
          echo '<br>cached patient_id '.$patient_id;

          if (!$patient_id)
          {
            echo '<br>calling find_or_create_patient('.$first_name.' '.$last_name.' '.$email.' '.$phone.')';

            $patient_id = self::find_or_create_patient($first_name, $last_name, $email, $phone, $notes);
            if ($patient_id != 0)
              self::$patient_id_cache[$key] = $patient_id;
          }
          else
          {
            echo '<br>The patient ('.$first_name.' '.$last_name.' '.$email.' '.$phone.') is already cached with id '.$patient_id;
          }
        }
      }

      $row++;
      
    }

    fclose($file);  
  }
  
  public static function find_or_create_patient($first_name, $last_name, $email, $phone, $notes)
  {
    //create the patient record if it doesn't exist...
    //AxClinicDatabase::current()->trace = true;
    $rows = AxClinicDatabase::current()->search('AxClinicContactTable', 
        [
          'filter' => new AxDataFilter('AND', 
          [
            ['first_name', '=', self::db_val($first_name)],
            ['last_name', '=', self::db_val($last_name)],
            ['email', '=', self::db_val($email)]
          ]
          )
        ]);

    if (count($rows) > 0) //there may be dups. If so, we're djust going to use the first ID.
    {
      _ax_debug('found existing contact ('.$first_name.' '.$last_name.' '.$email.' '.$phone.' with id '.$rows[0]['id']);
      return $rows[0]['id'];
    }
    else
    {
      try
      {
         $result = AxClinicDatabase::current()->create('AxClinicContactTable', 
            [
              AxClinicContactTable::PROP_FIRST_NAME => $first_name, 
              AxClinicContactTable::PROP_LAST_NAME => $last_name,
              AxClinicContactTable::PROP_EMAIL => $email,
              AxClinicContactTable::PROP_PHONE => $phone,
              AxClinicContactTable::PROP_NOTES => $notes,
            ]
        );

        _ax_debug('Patient created ('.$first_name.' '.$last_name.' '.$email.' '.$phone.' with id '.$result['id']);
        _ax_debug(json_encode($result, JSON_PRETTY_PRINT));

        return $result['id'];
      }
      catch (Exception $ex)
      {

        _ax_debug('patient create failed for ('.$first_name.' '.$last_name.' '.$email.' '.$phone.')');
        _ax_debug('<br> error = '.$ex->getMessage());
        return 0;
      }
    }
  }
  
  static function db_val($str)
  {
    if (strpos($str, '\\') !== false)
    {
      $str = str_replace("\\", "", $str);
    }
    
    return "'".str_replace("'", "''", trim($str))."'";
  }

  public static function fix_time()
  {
    AxClinicDatabase::current()->update('ax_appts', 'date_created', "concat(current_date(), ' ', TIME(mytime))");
    if (AxClinicDatabase::current()->error()[0] !== 0)
      echo '<br/>error making the change: '.AxClinicDatabase::current()->error()[2]; // this is the message on the error
    else 
      echo '<br/>change complete';
  }
  
  public static function populate_appt_counts()
  {
    echo 'the count is: '.AxClinicDatabase::current()->get_count('ax_appts', "DATE(start) = '2018-11-30' AND calendar_id = 14 and status = 'complete'");
    return;
    
    //gonna just use sql for this... 
    $rows = AxClinicDatabase::current()->query('select * from ax_appts order by start, calendar_id');
    
    $this_date = null;
    $appt_type_count = [];
    $calendar_id = 0;
    
    foreach ($rows as $row)
    {
      $date = explode(' ', $row['start'])[0];
      if ($this_date != $date)
      {
        if ($this_date != null)
        {
          //write the counts and everything
          
        }
        
        
        
        $this_date = $date;
        
      }
      
    }
  }
}