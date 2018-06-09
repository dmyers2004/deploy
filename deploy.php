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

git clone https://github.com/dmyers2004/deploy.git ./deploy;chmod 755 ./deploy

*/

ini_set('memory_limit','512M');
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

$config = [
	'version'=>'4.0.0',
	'deploy_filename'=>'deploy.json',
	'rootpath'=>getcwd(),
	'env'=>$_SERVER + $_ENV,
	'args'=>$_SERVER['argv'],
];

$deploy = new deploy($config);

/* get the cli arguments */
$argv = $_SERVER['argv'];

/* shift off the programs name */
array_shift($argv);

$task_name = implode(' ',$argv);

/* if the task doesn't show all available tasks and their help */
if (!$deploy->task_exists($task_name)) {
	if (!empty($task_name)) {
		$deploy->e('<red>Task "'.$task_name.'" is not defined.</red>');
	}
	$deploy->table($deploy->get_help());
} else {
	$deploy->task($task_name);
}

/* exit clean */
exit(1);

/* finished */

class deploy {
	public $sudo = '';
	public $env = [];
	public $deploy_json = [];
	public $switch_storage = [];
	public $config;
	public $current_task = null;
	public $current_line = null;
	

	public function __construct($config) {
		$this->config = $config;

		$this->heading('Deploy Version '.$this->config['version']);

		$this->env = $this->config['env'];

		$this->deploy_json = array_merge($this->get_hard_actions(),$this->get_deploy());
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

	public function heading($txt) {
		$this->e('<cyan>'.str_pad('- '.$txt.' ',exec('tput cols'),'-',STR_PAD_RIGHT).'</cyan>');
	}

	public function sub_heading($txt) {
		$this->e('<blue># '.$txt.'</blue>');
	}

	public function error($txt,$exit=true) {
		$this->e('<red>'.str_pad('> '.$txt.' ',exec('tput cols'),'<',STR_PAD_RIGHT).'</red>');

		$this->e('<red>    - Task: </off>'.$this->current_task);
		$this->e('<red>    - Command:  </off>'.$this->current_line);

		if ($exit) {
			exit(6);
		}
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

	public function import($filetype,$filepath) {
		$return = false;

		if (!in_array($filetype,['json','ini','yaml','array'])) {
			$this->error($filetype.' is a unsupported import type.');
		}

		if (!file_exists($filepath)) {
			$this->error('Import could not locate '.$filepath);
		} else {
			$this->sub_heading('Importing '.$filepath);
		}

		switch($filetype) {
			case 'ini':
				$array = parse_ini_file($filepath);
			break;
			case 'array':
				$array = require $filepath;
			break;
			case 'yaml':
				if (!function_exists('yaml_parse_file')) {
					$this->error('yaml_parse_file() not found. Please verify you have the YAML PECL extension installed.');
				}
			
				$array = yaml_parse_file($filepath);
			break;
			case 'json':
				$array = json_decode(file_get_contents($filepath),true);
			break;
		}

		if (is_array($array)) {
			foreach ($array as $name => $value){
				$this->env[$name] = $value;
			}
		} else {
			$this->error('Your input file did return a Array.');
		}
	}

	public function get_deploy() {
		$deploy_filename = getcwd().'/'.$this->config['deploy_filename'];

		$array = [];

		if (!file_exists($deploy_filename)) {
			$this->error('Could not locate '.getcwd().'/'.$this->config['deploy_filename'].' file',false);
		} else {
			$this->sub_heading('Using Deploy File '.getcwd().'/'.$this->config['deploy_filename']);

			$array = json_decode(file_get_contents($deploy_filename));

			if ($array === null) {
				$this->error($this->config['deploy_filename'].' malformed');

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

	public function get_help() {
		$rows = [];

		foreach ($this->deploy_json as $key=>$values) {
			foreach ((array)$values as $value) {
				if (substr($value,0,4) == '// %') {
					$rows[] = ['<green>'.$key.'</green>',trim(substr($value,4))];
				}
			}
		}

		array_multisort($rows);

		$header[] = ['Available Tasks:',''];

		return $header + $rows;
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

	public function gitx() {
		$m = __FUNCTION__;
		$args = func_get_args();
		$method = array_shift($args);
	
		if (method_exists($this,$m.'_'.$method)) {
			call_user_func_array([$this,$m.'_'.$method],$args);
		} else {
			$this->error($m.' function '.$method.' is not found');	
		}
	}

	public function gitx_update($path=null,$branch=null) {
		if (!$branch) {
			$this->error('GIT Branch not specified please provide one');
		}

		if (!file_exists($path)) {
			$this->e('<red>Could not locate directory '.$path.'.</off>');
		}

		if (!file_exists($path.'/.git')) {
			$this->e('<red>Not a GIT repository '.$path.'.</off>');
		} else {
			$this->e('cd '.$path.';git fetch --all;git reset --hard origin/'.$branch);

			$this->shell('cd '.$path.';git fetch --all;git reset --hard origin/'.$branch);
		}
	}

	public function gitx_status($path=null) {
		if (!file_exists($path)) {
			$this->e('<red>Could not locate directory '.$path.'.</off>');
		}
		
		exec('find '.$path.' -name FETCH_HEAD',$output);

		$table[] = ['Package','Branch','Hash'];

		foreach ($output as $o) {
			$dirname = dirname(dirname($o));

			$branch = exec("cd ".str_replace(' ','\ ',$dirname).";git rev-parse --abbrev-ref HEAD");
			$hash = exec("cd ".str_replace(' ','\ ',$dirname).";git rev-parse --verify HEAD");

			$sections = explode('/',$dirname);
			$package = end($sections);

			$table[] = [$package,$branch,$hash];
		}

		$this->table($table);
	}

	public function gitx_generate($path=null) {
		if (!file_exists($path)) {
			$this->e('<red>Could not locate directory '.$path.'.</off>');
		}

		exec('find '.$path.' -name FETCH_HEAD',$output);

		/* xgit update {PWD} {GIT_BRANCH} */
		foreach ($output as $o) {
			$string = 'xgit update # {GIT_BRANCH}';
			$relative = str_replace($this->config['rootpath'],'{PWD}',dirname(dirname($o)));

			$this->e(str_replace('#',$relative,$string));
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
		if (!$this->task_exists($task_name)) {
			$this->error("Task \"$task_name\" Not Found.");
		}

		$this->current_task = $task_name;

		foreach ($this->deploy_json[$task_name] as $command) {
			$this->current_line = $command;
			
			$exit_code = $this->run($command);
		}
	}

	public function task_exists($task_name) {
		return (array_key_exists($task_name,$this->deploy_json));
	}

	public function selfupdate() {
		$this->self_update();
	}

	public function self_update() {
		$this->heading('Updating Self');

		exec('sudo rm -fdrv /tmp/deploy');
		exec('sudo git clone https://github.com/dmyers2004/deploy.git /tmp/deploy');
		exec('sudo mv /tmp/deploy/deploy.php '.__FILE__);
		exec('sudo chmod -v 755 '.__FILE__);

		$this->sub_heading('Update Complete');
	}

	public function table($table) {
		$widths = [];

		foreach ($table as $tr) {
			foreach ($tr as $idx=>$td) {
				$widths[$idx] = max(strlen($td)+2,$widths[$idx]);
			}
		}

		$table_with_widths = [];

		foreach ($table as $tr) {
			$new_row = [];

			foreach ($tr as $idx=>$td) {
				$new_row[$td] = $widths[$idx];
			}

			$table_with_widths[] = $new_row;
		}

		$this->table_heading(array_shift($table_with_widths));

		foreach ($table_with_widths as $row) {
			$this->table_columns($row);
		}
	}

	public function table_heading($kv=null) {
		$kv = ($kv) ? $kv : $this->table_key_value_set(func_get_args());

		foreach ($kv as $text=>$width) {
			echo $this->color('<yellow>'.str_pad($text,$width,' ',STR_PAD_RIGHT).' </yellow>');
		}

		echo chr(10);
	}

	public function table_columns($kv=null) {
		$kv = ($kv) ? $kv : $this->table_key_value_set(func_get_args());

		foreach ($kv as $text=>$width) {
			echo $this->color(str_pad($text,$width,' ',STR_PAD_RIGHT).' ');
		}

		echo chr(10);
	}

	public function table_key_value_set($input) {
		$count = count($input);
		$array = [];

		for ($i = 0; $i < $count; $i++) {
			$array[$input[$i]] = $input[++$i];
		}

		return $array;
	}

} /* end class */