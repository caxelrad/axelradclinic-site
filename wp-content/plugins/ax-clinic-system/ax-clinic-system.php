<?php 
/*
Plugin Name: Axelrad Clinic Web System
Plugin URI:
Version: 1.1
Author: Chris Axelrad
Description: Fucking badass shit.
*/

//include AX_OS_FILE;
//AxelradOs::set_plugin_root(__DIR__);


require_once(__DIR__.'/../axelrad-bin/axelrad-bin.php');
AxBin::build_module('axelrad-util');
AxBin::build_module('axelrad-mercury');
AxBin::build_module('axelrad-user-mgmt');


include 'site-config.php';
include 'commands.php';
include 'classes/ax-clinic-data.php';
include 'ui/ui.php';

AxLogger::set_enabled($_GET['debug']);

AxBin::load('axelrad-user-mgmt');
