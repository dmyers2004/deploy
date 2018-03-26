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

/* setup a few default NEVER changing values */
define('ROOTPATH',realpath($_SERVER['PWD'])); /* path to the folder we are in now */
define('SCRIPTPATH',realpath(dirname(__FILE__))); /* path to this scripts folder */
define('SUPPORTPATH',SCRIPTPATH.'/deploy_support'); /* path to support files */
define('DEPLOYFILE',$_SERVER['SCRIPT_FILENAME']);
define('VERSION','3.2');

/* bring in our libraries */
require SUPPORTPATH.'/callable_functions.php';
require SUPPORTPATH.'/tools.php';

/* add these to the tools merge array */
tools::set('rootpath',ROOTPATH);
tools::set('erootpath',str_replace(' ','\ ',ROOTPATH));
tools::set('filename_date',date('Y-m-d-H:ia'));
tools::set('scriptpath',SCRIPTPATH);
tools::set('supportpath',SUPPORTPATH);

/* echo out version */
tools::heading('Deploy Version '.VERSION);

/* merge the hard actions (internal) and one's in the local folders deploy file */
$complete = array_merge(tools::get_hard_actions(),tools::get_deploy());

/* what group of commands are we running? */
$group_name = implode(' ',array_slice($_SERVER['argv'],1));

/* try to load the local .env file */
tools::get_env(true);

/* convert all the actions to lowercase to normalize them */
$actions = array_filter(array_keys($complete),function($v) {
	return strtolower($v);
});

/* is the group they are calling a valid action? */
if (in_array(strtolower($group_name),$actions)) {
	/* yep! let tools know */
	tools::$complete = $complete;
		
	/* call grouping */
	tools::grouping($group_name);

	exit(1);
}

/* else show all the available options for where I am at */
$actions = array_keys($complete);

/* sort alphabetically */
sort($actions);

/* so we can format everything nice like */
$length = 0;

$descriptions = tools::get_descriptions($complete,$length);

if (!empty($option)) {
	tools::e('<red>Command "'.$option.'" is not defined.</red>');
}

tools::e('<orange>Available commands:</orange>');

foreach ($actions as $a) {
	$c = strtolower(substr($a,0,1));
	
	/* make sure the command starts with a letter */
	if (ord($c) >= 97 && ord($c) <= 122) {
		tools::e('<green>'.str_pad($a,$length).'</green>'.$descriptions[$a]);
	}
}

exit(1);