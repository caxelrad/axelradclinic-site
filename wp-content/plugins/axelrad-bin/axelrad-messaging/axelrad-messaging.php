<?php


class AxelradMessaging
{
  public static $default_email_from_address = '';
  public static $default_email_from_name = '';

  public static function send_email(AxelradEmailMsg $msg)
  {
    _ax_debug('AxelradMessaging::send_email');
    
    $to_addr = self::get_address($msg->to_addr);
    if ($to_addr == '')
      throw new Exception('The to email is not specified.');
    
    if ($msg->body == '')
      throw new Exception('The email body is not specified.');
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    $to_addr = self::get_address($msg->to_addr);
    _ax_debug('to address set to '.$to_addr);
    
    if ($msg->from_addr == [])
      $msg->from(self::$default_email_from_address, self::$default_email_from_name);
    
    $from_addr = self::get_address($msg->from_addr);
    _ax_debug('from address set to '.$from_addr);
    
    $headers[] = 'From: '.$from_addr;
    
    if (count($msg->cc_list) > 0)
    {
      $cc_addr = self::get_address($msg->cc_list);
      _ax_debug('cc set to '.$cc_addr);
      
      $headers[] = 'Cc: '.$cc_addr;
    }
    
    if (count($msg->bcc_list) > 0)
    {
      $bcc_addr = self::get_address($msg->bcc_list);
      _ax_debug('bcc set to '.$bcc_addr);
      
      $headers[] = 'Bcc: '.$bcc_addr;
    }
    
    wp_mail( 
      $to_addr, 
      $msg->subject, 
      $msg->body, 
      $headers, 
      $msg->attachments
    );
  }
  
  static function get_address($addresses)
  {
    $list = '';
    foreach ($addresses as $item)
    {
      if ($list != '') $list.=',';
      
      $name = $item['name'];
      $email = $item['email'];
      $list.= $name ? $name.' <'.$email.'>' : $email;
    }
    
    return $list;
  }
  
}


class AxelradEmailMsg
{
  public $subject = '';
  public $body = '';
  public $to_addr = [];
  public $from_addr = [];
  public $bcc_list = [];
  public $cc_list = [];
  public $attachments = [];
  
  public function from($email, $name = '')
  {
    $this->from_addr = [];
    _ax_debug('AxelradMsg->from '.$email.' '.$name);
    $this->from_addr[] = $this->entry($email, $name);
  }
  
  function contains($list, $email)
  {
    foreach ($list as $item)
    {
      if ($item['email'] == $email)
        return true;
    }
    
    return false;
  }
  
  function entry($email, $name)
  {
    return ['email' => $email, 'name' => $name];
  }
  
  public function to($email, $name = '')
  {
    $this->to_addr = [];
    _ax_debug('AxelradMsg->to '.$email.' '.$name);
    $this->to_addr[] = $this->entry($email, $name);
  }
  
  public function add_cc($email, $name = '')
  {
    if (!$this->contains($this->cc_list, $email))
    {
      _ax_debug('AxelradMsg->cc '.$email.' '.$name);
      $this->cc_list[] = $this->entry($email, $name);
    }
  }
  
  public function add_bcc($email, $name = '')
  {
    if (!$this->contains($this->bcc_list, $email))
    {
      _ax_debug('AxelradMsg->bcc '.$email.' '.$name);
      $this->bcc_list[] = $this->entry($email, $name);
    }
  }
  
  public function attach($file)
  {
    if (array_search($file, $this->attachments) !== false)
      return;
    
    if (!file_exists($file))
      throw new Exception('The attachment does not exist.');
    
    _ax_debug('AxelradMsg->attachment '.$file);
    $this->attachments[] = $file;
  }
}