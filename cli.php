#!/usr/bin/env php
<?php
/*
# php function
* another command group
cli command group
*/

$o = new stdclass();

require 'Tools.php';
require 'Callable_functions.php';

o()->tools = new Tools;
o()->callable = new Callable_functions;

o()->tools->heading('Deploy Version 2.0');

chdir($_SERVER['PWD']);

define('ROOTPATH',realpath($_SERVER['PWD']));
define('ESCROOTPATH',str_replace(' ','\ ',ROOTPATH));

/* actions in the deploy file */
$soft_actions_c = o()->tools->get_deploy();
$soft_actions = ($soft_actions_c) ? array_keys($soft_actions_c) : [];

$hard_actions_c = o()->tools->get_hard_actions();
$hard_actions = ($hard_actions_c) ? array_keys($hard_actions_c) : [];

$actions = array_merge($hard_actions,$soft_actions);
$complete = array_merge($hard_actions_c,$soft_actions_c);

/* get option */
$args = $_SERVER['argv'];

/* shift of the script */
array_shift($args);

/* put them back together */
$option = trim(implode(' ',$args));

usort($actions,function($a,$b){
  return strlen($b)-strlen($a);
});

foreach ($actions as $a) {
	if (strtolower(substr($option,0,strlen($a))) == strtolower($a)) {
		/* we got a match! */
		$arg1 = strtolower(substr($option,0,strlen($a)));
		$arg2 = trim(substr($option,strlen($arg1)));
		
		o()->tools->complete($complete)->run($arg1,$arg2);
		exit(1);
	}
}

/* else show all the available options for where I am at */

sort($actions);

$length = 0;

$descriptions = o()->tools->get_descriptions($complete,$length);

o()->tools->e('<orange>Available commands:</orange>');

foreach ($actions as $a) {
	$c = strtolower(substr($a,0,1));
	
	if (ord($c) >= 97 && ord($c) <= 122) {
		o()->tools->e('<green>'.str_pad($a,$length).'</green>'.$descriptions[$a]);
	}
}

exit(1);

function o() {
  /* bring the global scope variable into local scope */
	global $o;

  /* return it */
	return $o;
}
