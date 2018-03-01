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
create_package(folder name)

*/

ini_set('memory_limit','512M');
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

chdir($_SERVER['PWD']);

define('ROOTPATH',realpath(dirname(__FILE__)));
define('ESCROOTPATH',str_replace(' ','\ ',ROOTPATH));

require ROOTPATH.'/.deploy_support/Callable_functions.php';
require ROOTPATH.'/.deploy_support/Tools.php';

$o = new stdclass();

$callable = new Callable_functions;

tools::heading('Deploy Version 2.0');

/* actions in the deploy file */
$hard_actions = tools::get_hard_actions();
$soft_actions = tools::get_deploy();

$env = tools::get_env();

if (is_array($env)) {
	$_ENV = $_ENV + $env;
}

$complete = array_merge($hard_actions,$soft_actions);
$actions = array_keys($complete);

/* get option */
$args = $_SERVER['argv'];

/* shift of the script */
array_shift($args);

/* put them back together */
$option = trim(implode(' ',$args));

/* sort by length so short commands don't override longer ones */
usort($actions,function($a,$b){
	return strlen($b)-strlen($a);
});

foreach ($actions as $a) {
	if (strtolower(substr($option,0,strlen($a))) == strtolower($a)) {
		/* we got a match! */
		$group_name = strtolower(substr($option,0,strlen($a)));
		$parameters = trim(substr($option,strlen($arg1)));
		
		tools::complete($complete);
		tools::group($group_name,$parameters);
		exit(1);
	}
}

/* else show all the available options for where I am at */

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

function starts_with($string,$line) {
	$string = strtolower(trim($string));
	return (substr($line,0,strlen($string)) == $string);
}
