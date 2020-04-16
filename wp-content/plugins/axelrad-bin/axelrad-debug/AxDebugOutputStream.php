<?php

$_ax_db_lines = [];

abstract class AxDebugOutputStream
{
  protected function write_line($line)
  {
    global $_ax_db_lines;
    $_ax_db_lines[] = $this->timestamp().' || '.$line;
  }
  
  protected function timestamp() { return date('h:i:s', time()); }
  
  protected function lines()
  {
    global $_ax_db_lines;
    return $_ax_db_lines;
  }
  
  public function write($line)
  {
    $this->on_write($line);
  }
  
  protected function on_write($value)
  {
    if (is_array($value) || is_object($value))
      $this->write_line(json_encode($value, JSON_PRETTY_PRINT));
    else
      $this->write_line($value);
  }
  
  public function open()
  {
    $this->on_open();
  }
  
  protected abstract function on_open();
  
  
  public function close()
  {
    $this->on_close();
  }
  
  protected abstract function on_close();
}

//----- some built-in standard streams ------//


class AxDebugInlineEchoStream extends AxDebugOutputStream
{
  
  protected function on_open() { echo '<br>'.$this->timestamp().':: Begin debug inline echo stream.'; }
  
  protected function on_write($value)
  {
    if (is_array($value) || is_object($value))
      echo '<br>'.$this->timestamp().':: '.json_encode($value, JSON_PRETTY_PRINT);
    else
      echo '<br>'.$this->timestamp().':: '.$value;
  }
  
  protected function on_close() 
  { 
    echo '<br>'.$this->timestamp().':: End debug inline echo stream.'; 
  }    
}


class AxDebugEchoStream extends AxDebugOutputStream
{
  
  protected function on_open() { $this->write_line('Begin debug echo stream.'); }
  
  protected function on_close() 
  { 
    $this->write_line('End debug echo stream.'); 
      
    foreach ($this->lines() as $line)
      echo 'DEBUG: '.$line.'<br>';
  }    
}

class AxDebugCommentStream extends AxDebugOutputStream
{
  
  protected function on_open() { $this->write_line('Begin debug comment stream.'); }
  
  protected function on_close() 
  { 
    $this->write_line('End debug echo stream.'); 
    
    foreach ($this->lines() as $line)
      echo '
<!-- '.$line.' -->';
    

  }
  
}