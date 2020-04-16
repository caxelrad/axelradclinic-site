<?php

class HelloWorld extends AxelradOp
{
    private $_say_name;

    public function name($name = null)
    {
        if ($name)
            $this->_say_name = $name;
        else
            return $this->_say_name;
    }

    protected function on_run()
    {
        return 'Hello '.($this->name() ? $this->name() : ' World');
    }
}