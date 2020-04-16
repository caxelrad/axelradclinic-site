<?php 
/*
Plugin Name: Axelrad Access Control
Plugin URI:
Version: 1.1
Author: Chris Axelrad
Description: Controls access to pages / posts via a users/groups system.
*/


require_once(__DIR__.'/../axelrad-bin/axelrad-bin.php');

include 'classes/axelrad-access-control.php';
include 'ui/ui.php';

AxelradUtil::$load_bootstrap = true;


AxBin::load('axelrad-user-mgmt');

AxelradAccessControl::login_logo_path(AxelradUtil::to_virtual_path(__DIR__.'/login-logo.png'));