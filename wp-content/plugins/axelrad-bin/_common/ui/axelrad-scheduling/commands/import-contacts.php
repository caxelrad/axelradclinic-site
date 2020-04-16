<?php

class ImportContacts
{
  public static function run()
  {
  }
  
  private static $patient_id_cache = [];
  private static $appt_type_cache = [];
  private static $calendar_cache = [];
  private static $employee_cache = [];
  
  static function db_str($value)
  {
    return str_replace("'", "''", $value);
  }
  
  
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

}