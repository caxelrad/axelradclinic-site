<?php

$_test_mode = false;
$_debug = false;

$_debug_scopes = array();


function _ax_get_debug()
{
  return AxLogger::$enabled;
}

function _ax_set_debug($value)
{
  AxLogger::set_enabled($value);
}

function _ax_set_debug_scope($scope_name, $on)
{
  AxLogger::set_scope($scope_name, $on);
}

function _ax_debug_is_in_scope($scope_name)
{
  return AxLogger::is_in_scope($scope_name);
}
function _ax_debug($value, $scope = null)
{
  AxLogger::write($value, $scope);
}

function _ax_debug_echo($value)
{
  AxLogger::_echo($value);
}
function _ax_debug_v($name, $value)
{
	AxLogger::write_v($name, $value);
}

function _ax_debug_enter($value)
{
  AxLogger::enter($value);
}

function _ax_debug_exit($value)
{
  AxLogger::leave($value);
}

function _ax_debug_json($object)
{
  AxLogger::write_json($object);
}


function _ax_debug_c_error($script, $fn_or_msg, $msg = '')
{
  AxLogger::write_error($script, $fn_or_msg, $msg); 
}

function _ax_debug_error($script, $fn_or_msg, $msg = '')
{
  AxLogger::write_error($script, $fn_or_msg, $msg); 
}


function _ax_is_test_mode()
{
  global $_test_mode;
  
	return $_test_mode || _ax_req('test-mode');
}

function _ax_set_test_mode($value)
{
  global $_test_mode;
  $_test_mode = $value;
}

