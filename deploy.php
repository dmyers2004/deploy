#!/usr/bin/env php
<?php
/*
// comment
// % used in the description
command
set name value
capture name shell commands
import filename (return php array ie. .env)
sudo on
sudo off
task name

gitx show
gitx status
gitx update
selfupdate
self-update

Most common Bash date commands for timestamping
https://zxq9.com/archives/795

*/

ini_set('memory_limit','512M');
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

define('VERSION','4.0');

define('ROOTPATH',realpath($_SERVER['PWD'])); /* path to the folder we are in now */
define('SCRIPTPATH',realpath(dirname(__FILE__))); /* path to this scripts folder */

$deploy = new deploy($_SERVER,$_ENV,$_SERVER['argv']);

/* get the cli arguments */
$argv = $_SERVER['argv'];

/* shift off the programs name */
array_shift($argv);

$task_name = implode(' ',$argv);

/* if the task doesn't show all available tasks and their help */
if (!$deploy->task_exists($task_name)) {
	$deploy->e('<red>Task "'.$task_name.'" is not defined.</red>');

	/* so we can format everything nice like */
	$length = 0;

	$descriptions = $deploy->get_help($length);

	$deploy->e('<orange>Available Tasks:</orange>');

	foreach ($descriptions as $task_name=>$desc) {
		$deploy->e('<green>'.str_pad($task_name,$length).'</green>'.$desc);
	}
} else {
	$deploy->task($task_name);
}

/* exit clean */
exit(1);

/* finished */

class deploy {
	public $sudo = '';
	public $column_widths = [];
	public $env = [];
	public $deploy_json = [];
	public $switch_storage = [];

	public function __construct($server,$env,$args) {
		$this->env = $server + $env;

		$this->deploy_json = array_merge($this->get_hard_actions(),$this->get_deploy());
		
		$this->heading('Deploy Version '.VERSION);
	}

	public function run($command) {
		/* smart explode (don't break on spaces inside single quotes) */
		$args = str_getcsv(str_replace(chr(39),chr(34),$command),chr(32),chr(34));
		
		/* function names since they are php methods/functions can't have dashes */
		$function = str_replace('-','_',$args[0]);

		/* convert @ to a switch method */
		$function = str_replace('@','switch_',$function);

		if (in_array(substr($command,0,1),['/','%','#'])) {
			/* it's a comment */
		} elseif (method_exists($this,$function)) {
			/* it's a method that exists on this class */
			array_shift($args);

			foreach ($args as $k=>$v) {
				$args[$k] = $this->merge($v);
			}

			call_user_func_array([$this,$function],$args);
		} else {
			/* else it's a raw shell command */
			$exit_code = $this->cli($command);

			if ($exit_code > 0) {
				$this->error('Exit Code '.$exit_code);
			}
		}
	}

	public function cli($command) {
		$exit_code = 0;

		$cli = $this->merge($command);

		$this->e('<off>'.$cli);

		$this->shell($this->sudo.$cli,$exit_code);

		return $exit_code;
	}

	public function e($txt) {
		echo $this->color($txt).chr(10);
	}

	public function heading($txt,$pad='-') {
		$this->e('<cyan>'.str_pad('- '.$txt.' ',exec('tput cols'),'-',STR_PAD_RIGHT).'</cyan>');
	}

	public function error($txt,$exit=true) {
		$this->e('<red>'.str_pad('* '.$txt.' ',exec('tput cols'),'*',STR_PAD_RIGHT).'</red>');

		if ($exit) {
			exit(6);
		}
	}

	public function table_heading() {
		$input = func_get_args()[0];
		$text = '';

		foreach ($input as $txt=>$val) {
			$text .= str_pad($txt,$val,' ',STR_PAD_RIGHT).' ';

			$column_widths[] = $val;
		}

		$this->e('<yellow>'.$text.'</yellow>');
	}

	public function table_columns() {
		$input = func_get_args();
		$text = '';

		foreach ($input as $idx=>$val) {
			$text .= str_pad($val,$column_widths[$idx],' ',STR_PAD_RIGHT).' ';
		}

		$this->e($text);
	}

	public function merge($input) {
		/* find all the {???} and make sure we have keys */
		$found = preg_match_all('/{(.+?)}/m', $input, $matches, PREG_SET_ORDER, 0);

		$merge = array_change_key_case($this->env,CASE_LOWER);

		if ($found > 0) {
			foreach ($matches as $match) {
				if (!isset($this->env[$match[1]])) {
					$this->error('Missing Merge Key for {'.$match[1].'}');
				}
			}

			foreach ($this->env as $key=>$val) {
				$input = str_replace('{'.$key.'}',$val,$input);
			}
		}

		return $input;
	}

	public function color($input) {
		// Set up shell colors
		$foreground_colors['off'] = '0;0';

		$foreground_colors['black'] = '0;30';
		$foreground_colors['dark_gray'] = '1;30';
		$foreground_colors['blue'] = '0;34';
		$foreground_colors['light_blue'] = '1;34';
		$foreground_colors['green'] = '0;32';
		$foreground_colors['light_green'] = '1;32';
		$foreground_colors['cyan'] = '0;36';
		$foreground_colors['light_cyan'] = '1;36';
		$foreground_colors['red'] = '0;31';
		$foreground_colors['light_red'] = '1;31';
		$foreground_colors['purple'] = '0;35';
		$foreground_colors['light_purple'] = '1;35';
		$foreground_colors['brown'] = '0;33';
		$foreground_colors['yellow'] = '1;33';
		$foreground_colors['light_gray'] = '0;37';
		$foreground_colors['white'] = '1;37';
		$foreground_colors['orange'] = '0;33';

		foreach ($foreground_colors as $color=>$console) {
			$input = str_replace('<'.$color.'>',"\033[".$console."m",$input);
			$input = str_replace('</'.$color.'>',"\033[0m",$input);
		}

		return $input;
	}

	public function import($filepath) {
		$return = false;

		if (!file_exists($filepath)) {
			$this->error('Could not locate '.$filepath.' file',false);
		} else {
			$this->heading('Importing '.$filepath);

			$return = require $filepath;
		}

		if (is_array($return)) {
			$this->env = $this->env + $return;
		}
	}

	public function get_deploy() {
		$deploy_filename = getcwd().'/deploy.json';

		$array = [];

		if (!file_exists($deploy_filename)) {
			error('Could not locate '.getcwd().'/deploy.json file',false);
		} else {
			$this->heading('Using Deploy File '.getcwd().'/deploy.json');

			$array = json_decode(file_get_contents($deploy_filename));

			if ($array === null) {
				$this->error('deploy.json malformed',false);

				$array = [];
			}
		}

		return (array)$array;
	}

	public function get_hard_actions() {
		return (array)json_decode('
	{
		"self-update": [
			"// % Updates deploy to the latest version.",
			"self_update"
		],
		"selfupdate": [
			"// % Updates deploy to the latest version.",
			"self_update"
		]
	}
		');
	}

	public function get_help(&$length) {
		$c = [];

		foreach ($this->deploy_json as $key=>$values) {
			foreach ((array)$values as $value) {
				if (substr($value,0,4) == '// %') {
					$c[$key] = trim(substr($value,4));

					$length = max(strlen($key)+2,$length);
				}
			}
		}

		ksort($c);

		return $c;
	}

	public function shell($cmd, &$stdout=null, &$stderr=null) {
		$proc = proc_open($cmd,[
				1 => ['pipe','w'],
				2 => ['pipe','w'],
		],$pipes);

		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		return proc_close($proc);
	}

	public function task_exists($task_name) {
		return (array_key_exists($task_name,$this->deploy_json));
	}
	
	/** @ switches */

	public function switch_sudo($switch) {
		$switch = trim($switch);

		if (!$this->switch_storage['sudo setup'] && $switch == 'on') {
			$this->switch_storage['sudo setup'] = true;

			$this->shell('sudo touch -c acbd18db4cc2f85cedef654fccc4a4d8');
		}

		$this->sudo = ($switch == 'on') ? 'sudo ' : '';
	}

	/** add-on commands */

	public function xgit($action,$path,$branch=null) {
		switch($action) {
			case 'update':
				$this->xgit_update($path,$branch);
			break;
			case 'status':
				$this->xgit_status($path);
			break;
			case 'find':
				$this->xgit_find($path);
			break;
		}
	}

	public function xgit_update($path,$branch) {
		if (empty($branch)) {
			$this->error('GIT Branch not specified please provide GITBRANCH');
		}

		if (!file_exists($path.'/.git')) {
			$this->e('<red>Not a git folder '.$path.'.</off>');
		} else {
			$this->e('cd '.$path.';git fetch --all;git reset --hard origin/'.$branch);
		
			$this->shell('cd '.$path.';git fetch --all;git reset --hard origin/'.$branch);
		}
	}

	public function xgit_status($path) {
		exec('find '.$path.' -name FETCH_HEAD',$output);

		$this->table_heading(['Package'=>32,'Branch'=>16,'Hash'=>42]);

		foreach ($output as $o) {
			$dirname = dirname(dirname($o));

			$branch = exec("cd ".str_replace(' ','\ ',$dirname).";git rev-parse --abbrev-ref HEAD");
			$hash = exec("cd ".str_replace(' ','\ ',$dirname).";git rev-parse --verify HEAD");

			$sections = explode('/',$dirname);
			$package = end($sections);

			$this->table_columns($package,$branch,$hash);
		}
	}

	public function xgit_find($path) {
		exec('find '.$path.' -name FETCH_HEAD',$output);

		foreach ($output as $o) {
			$this->e(str_replace(ROOTPATH,'{PWD}',dirname(dirname($o))));
		}
	}

	public function set($name,$value) {
		$this->env[$name] = $value;
	}

	public function capture($name,$shell) {
		$cmd = $this->merge($this->sudo.$shell);
		$stdout = '';
		$stderr = '';
	
		$error_code = $this->shell($cmd,$stdout,$stderr);
	
		$this->env[$name] = $stdout;
		
		return $error_code;
	}

	public function task($task_name) {
		if (!isset($this->deploy_json[$task_name])) {
			$this->error("Task \"$task_name\" Not Found.");
		}

		foreach ($this->deploy_json[$task_name] as $command) {
			$exit_code = $this->run($command);
		}
	}

	public function self_update() {
		$this->heading('Updating Self');

		exec('sudo rm -fdrv /tmp/deploy');
		exec('sudo git clone https://github.com/dmyers2004/deploy.git /tmp/deploy');
		exec('sudo mv /tmp/deploy/deploy.php '.SCRIPTPATH.'/deploy');
		exec('sudo chmod -v 755 '.SCRIPTPATH.'/deploy');

		$this->heading('Update Complete');
	}

} /* end class */