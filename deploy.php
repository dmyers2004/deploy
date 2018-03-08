#!/usr/bin/env php
<?php
/*
Deploy Script

@author Don Myers
@copyright 2018
@license http://opensource.org/licenses/MIT MIT License
@link https://github.com/dmyers2004/deploy
@version 2.0

Using the Prefix of:

#    - run a callable function in the Callable_functions class
*    - run another deploy group
none - Command line command

included functions include
set name value
sudo (on|off)
git_status (path)
git_update (path|branch)
show_git_repros (path)
self_update()
echo()

!todo
create_package(folder name)
#on error stop
#on error continue

*/

ini_set('memory_limit','512M');
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

define('ROOTPATH',realpath($_SERVER['PWD'])); /* path to the folder we are in now */
define('SCRIPTPATH',realpath(dirname(__FILE__))); /* path to this scripts folder */
define('SUPPORTPATH',SCRIPTPATH.'/deploy_support'); /* path to support files */
define('DEPLOYFILE',$_SERVER["SCRIPT_FILENAME"]);

require SUPPORTPATH.'/callable_functions.php';
require SUPPORTPATH.'/tools.php';

tools::set('rootpath',ROOTPATH);
tools::set('erootpath',str_replace(' ','\ ',ROOTPATH));
tools::set('filename_date',date('Y-m-d-H:ia'));
tools::set('scriptpath',SCRIPTPATH);
tools::set('supportpath',SUPPORTPATH);

tools::heading('Deploy Version 3.1');

$complete = array_merge(tools::get_hard_actions(),tools::get_deploy());
$group_name = implode(' ',array_slice($_SERVER['argv'],1));

tools::get_env(true);

$actions = array_filter(array_keys($complete),function($v) {
	return strtolower($v);
});

if (in_array($group_name,$actions)) {
	tools::$complete = $complete;

	tools::grouping($group_name);

	exit(1);
}

/* else show all the available options for where I am at */
$actions = array_keys($complete);

/* sort alphabetically */
sort($actions);

$length = 0;

$descriptions = tools::get_descriptions($complete,$length);

if (!empty($option)) {
	tools::e('<red>Command "'.$option.'" is not defined.</red>');
}

tools::e('<orange>Available commands:</orange>');

foreach ($actions as $a) {
	$c = strtolower(substr($a,0,1));
	
	if (ord($c) >= 97 && ord($c) <= 122) {
		tools::e('<green>'.str_pad($a,$length).'</green>'.$descriptions[$a]);
	}
}

exit(1);