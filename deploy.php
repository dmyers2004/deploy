#!/usr/bin/env php
<?php
/*
Most common Bash date commands for time stamping
https://zxq9.com/archives/795
 */

ini_set('memory_limit','512M');
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

$config = [
	'version'=>'4.0.12',
	'deploy_file'=>'deploy.json',
	'args'=>$_SERVER['argv'],
	'verbose'=>false,
];

(new deploy($config))->options()->process();

exit();

/* finished */

class deploy {
	/**
	 * $sudo
	 *
	 * if sudo is set to on this stores the cli prefix "sudo "...
	 *
	 * @var string
	 */
	public $sudo = '';

	/**
	 * $merge
	 *
	 * The available merge key/value pairs
	 *
	 * @var array
	 */
	public $merge = [];

	/**
	 * $deploy_json
	 *
	 * Deploys JSON
	 *
	 * @var array
	 */
	public $deploy_json = [];

	/**
	 * Store @ switch statement values
	 *
	 * true/false
	 *
	 * @exit
	 * @stderr
	 * @stdout
	 * @sudo
	 *
	 * @var array
	 */
	public $switch_storage = [];

	/**
	 * $config
	 *
	 * The configuration
	 *
	 * @var array
	 */
	public $config = [];

	/**
	 * $current_task
	 *
	 * The current task name
	 *
	 * @var null
	 */
	public $current_task = null;

	/**
	 * $current_line
	 *
	 * The current line being processed
	 *
	 * @var null
	 */
	public $current_line = null;

	/**
	 * $skip
	 * track if we are in a if () endif
	 *
	 * @var boolean
	 */
	public $skip = false;

	/**
	 * __construct
	 *
	 * @param mixed $config
	 * @return void
	 */
	public function __construct($config)
	{
		/* save the configuration sent in */
		$this->config = $config;

		/* show the name and current version */
		$this->heading('Deploy Version '.$this->config['version']);

		/* get the current directory */
		$dir = getcwd();

		/* move up the folder until we find deploy.json */
		$this->e('<yellow>Looking for '.$this->config['deploy_file'].'</off>');

		while (1 == 1) {
			$search_path = '/'.trim($dir.'/'.$this->config['deploy_file'],'/');

			if (file_exists($search_path)) {
				$this->config['deploy_file'] = $search_path;

				$this->e($search_path.' <green>√</off>');

				/* change directory this the current directory and leave while loop */
				chdir($dir);
				break;
			}

			$this->e($search_path.' <red>X</off>');

			/* if we make it to root then fail */
			if (strlen($dir) == 1) {
				break;
			}

			$dir = dirname($dir);
		}

		/* setup our error handler */
		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			if ($errno == 1024) {
				$this->e('<red>'.str_pad('> '.$errstr.' ',exec('tput cols'),'<',STR_PAD_RIGHT).'</red>');

				if ($this->current_task) {
					$this->e('<red>    - Task: </off>'.$this->current_task);
				}

				if ($this->current_line) {
					$this->e('<red>    - Command:  </off>'.$this->current_line);
				}

				exit(6);
			}
		});

		/* done construct */
	}

	/**
	 * options
	 *
	 * capture the options
	 *
	 * -d directory to search in
	 * -v verbose
	 * -d deploy.json to use
	 *
	 *
	 * @return void
	 */
	public function options()
	{
		/* process arguments */

		/* get the cli arguments */
		$args = $this->config['args'];

		/* shift off the programs name */
		array_shift($args);

		/* now handle verbose and file and directory */
		foreach ($args as $idx=>$val) {
			switch ($val) {
				/* change the directory */
				case '-d':
					$dir = $args[$idx+1];

					$this->directory_exists($dir);

					chdir($dir);

					unset($args[$idx],$args[$idx+1]);
				break;
				/* verbose */
				case '-v':
					$this->config['verbose'] = true;

					unset($args[$idx]);
				break;
				/* different deploy file */
				case '-f':
					$deploy_file = $args[$idx+1];

					if (substr($deploy_file,0,1) == '/') {
						$this->config['deploy_file'] = $deploy_file;
					} else {
						$this->config['deploy_file'] = getcwd().'/'.$deploy_file;
					}

					$this->file_exists($this->config['deploy_file']);

					unset($args[$idx],$args[$idx+1]);
				break;
			}
		}

		/* took care of the options */
		$this->config['args'] = $args;

		return $this;
	}

	/**
	 * process
	 *
	 * process the deploy.json file
	 *
	 * @return void
	 */
	public function process()
	{
		/* get our deploy file */
		$this->deploy_json = array_merge($this->get_hard_actions(),$this->get_deploy());

		$task_name = implode(' ',$this->config['args']);

		/* if the task doesn't exist but it's not empty then show a error */
		if (!$this->task_exists($task_name)) {
			if (!empty($task_name)) {
				$this->e('<light_red>Task "'.$task_name.'" is not defined.</off>');
			}
			/* show help in a table */
			$this->table($this->get_help());
		} else {
			/* run this task */
			$this->task($task_name);
		}
	}

	/**
	 * run
	 *
	 * @param mixed $command
	 * @return void
	 */
	public function run($command)
	{
		/* smart explode (don't break on spaces inside single quotes) */
		$args = str_getcsv(str_replace(chr(39),chr(34),$command),chr(32),chr(34));

		/* function names since they are php methods/functions can't have dashes */
		$function = str_replace('-','_',$args[0]);

		/* convert @ to a switch methods */
		$function = str_replace('@','switch_',$function);

		/* handle comments */
		if (in_array(substr($command,0,1),['/','%','#'])) {
			/* it's a comment skip */
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
				trigger_error('Command Exit Exception '.$exit_code.'.');
			}
		}
	}

	/**
	 * if
	 *
	 * single level does not support nesting
	 *
	 * @return void
	 */
	public function if()
	{
		$logic = trim(substr($this->current_line,3),'() ');
		
		$this->skip = !$this->formula($logic,$this->merge);
	}

	/**
	 * endif
	 *
	 * single level does not support nesting
	 *
	 * @return void
	 */
	public function endif()
	{
		$this->skip = false;
	}

	/**
	 * formula
	 *
	 * handle if logic
	 *
	 * @param mixed $field
	 * @return bool
	 */
	public function formula($logic,$arguments,&$function='')
	{
		$re = '/{([^}]+)}/'; /* {variable_name} format */

		/* merge in the other variables */
		if (preg_match_all($re, $logic, $matches, PREG_SET_ORDER, 0)) {
			foreach ($matches as $each_match) {
				$variable = trim($each_match[0],'${} ');
				
				/* merge variable not found so bail now returning null and the function will contain the error if you care */
				if (!array_key_exists($variable,$arguments)) {
					$function = 'Variable "'.$variable.'" not found.';

					$this->e($function);

					return null;
				}

				$dynamic_value = $arguments[$variable];

				/* if it's truly a boolean then use the string version of the boolean */
				if (is_bool($dynamic_value)) {
					$dynamic_value = ($dynamic_value) ? 'true' : 'false';
				} elseif (is_string($dynamic_value)) {
					/* if it's a string then we need to wrap it but, only if it's not the string true or false */
					if ($dynamic_value != 'true' && $dynamic_value != 'false') {
						$dynamic_value = "'".str_replace("'","\'",$dynamic_value)."'";
					}
				}

				$logic = str_replace($each_match[0],$dynamic_value,$logic);
			}
		}

		/* the logic funciton */
		$function = 'return('.$logic.');';

		/* create a closure in it's own box */
		$closure = eval('return function(){'.$function.'};');

		/* call the closure */
		$bool = $closure();

		$this->v('Testing: '.$logic.' > '.(($bool) ? '<green>true</off>' : '<red>false</off>'));

		return $bool;
	}
	/**
	 * task_exists
	 *
	 * @param mixed $task_name
	 * @return void
	 */
	public function task_exists($task_name)
	{
		return (array_key_exists($task_name,$this->deploy_json));
	}

	/**
	 * selfupdate
	 *
	 * @return void
	 */
	public function selfupdate()
	{
		$this->self_update();
	}

	/**
	 * self_update
	 *
	 * @return void
	 */
	public function self_update()
	{
		$this->heading('Self Updating');

		/**
		 * remove the temp deploy file if it's there
		 * git clone the repro to the temp location
		 * move the temp file over this file
		 * change file mode to 755 so it can be executed
		 */
		exec('rm -fdr /tmp/deploy;git clone https://github.com/dmyers2004/deploy.git /tmp/deploy;mv /tmp/deploy/deploy.php '.__FILE__.';chmod 755 '.__FILE__);

		$this->sub_heading('Complete');
	}

	/**
	 * cli
	 *
	 * @param mixed $command
	 * @return void
	 */
	public function cli($command)
	{
		$exit_code = 0;

		$cli = $this->merge($command);

		$this->v('<off>'.$cli);

		$this->shell($this->sudo.$cli,$exit_code);

		return $exit_code;
	}

	/**
	 * merge
	 *
	 * @param mixed $input
	 * @return void
	 */
	public function merge($input)
	{
		/* find all the {???} and make sure we have keys */
		$found = preg_match_all('/{(.+?)}/m', $input, $matches, PREG_SET_ORDER, 0);

		if ($found > 0) {
			foreach ($matches as $match) {
				if (!isset($this->merge[$match[1]])) {
					trigger_error('Missing Merge Key for {'.$match[1].'}.');
				} else {
					$input = str_replace('{'.$match[1].'}',$this->merge[$match[1]],$input);
				}
			}
		}

		return $input;
	}

	/**
	 * color
	 *
	 * @param mixed $input
	 * @return void
	 */
	public function color($input)
	{
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

	/**
	 * get_deploy
	 *
	 * @return void
	 */
	public function get_deploy()
	{
		$array = [];

		if (!file_exists($this->config['deploy_file'])) {
			$this->e('<light_red>** Could not locate '.$this->config['deploy_file'].' file</off>');
		} else {
			$this->sub_heading('Using Deploy File '.$this->config['deploy_file']);

			$array = json_decode(file_get_contents($this->config['deploy_file']));

			if ($array === null) {
				trigger_error($this->config['deploy_file'].' malformed.');
			}
		}

		return (array)$array;
	}

	/**
	 * get_hard_actions
	 *
	 * @return void
	 */
	public function get_hard_actions()
	{
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

	/**
	 * get_help
	 *
	 * @return void
	 */
	public function get_help()
	{
		$rows['aaaaaaaaa'][] = ['Available Tasks:',''];

		foreach ($this->deploy_json as $key=>$values) {
			if (!in_array(substr($key,0,1),['/','%','#'])) {
				foreach ((array)$values as $value) {
					if (in_array(substr($value,0,1),['/','%','#'])) {
						$prefix = substr($value,0,4);
						$line = trim(substr($value,4));

						switch ($prefix) {
							case '// %':
								$rows[strtolower($key)][] = ['<green>'.$key.'</green>',$line];
							break;
							case '// #':
								$rows[strtolower($key)][] = [' ',$line];
							break;
						}
					}
				}
			}
		}

		ksort($rows);

		$formatted = [];

		foreach ($rows as $r1=>$r2) {
			foreach ($r2 as $v) {
				$formatted[] = [$v[0],$v[1]];
			}
		}

		return $formatted;
	}

	/**
	 * shell
	 *
	 * @param mixed $cmd
	 * @param mixed &$stdout=null
	 * @param mixed &$stderr=null
	 * @return void
	 */
	public function shell($cmd, &$stdout=null, &$stderr=null)
	{
		$proc = proc_open($cmd,[
				1 => ['pipe','w'],
				2 => ['pipe','w'],
		],$pipes);

		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		if ($this->switch_storage['stdout']) {
			echo $stdout;
		}

		if ($this->switch_storage['stderr']) {
			echo $stderr;
		}

		return proc_close($proc);
	}

	/** table */
	/**
	 * table
	 *
	 * @param mixed $table
	 * @return void
	 */
	public function table($table)
	{
		$extra_pad = 1;
		$widths = [];

		foreach ($table as $tr) {
			foreach ($tr as $idx=>$td) {
				$widths[$idx] = max(strlen(strip_tags($td)) + $extra_pad,$widths[$idx]);
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

	/**
	 * table_heading
	 *
	 * @param mixed $kv=null
	 * @return void
	 */
	public function table_heading($kv=null)
	{
		$kv = ($kv) ? $kv : $this->table_key_value_set(func_get_args());

		foreach ($kv as $text=>$width) {
			echo $this->paddy('<yellow>'.$text.'</yellow>',$width);
		}

		echo chr(10);
	}

	/**
	 * table_columns
	 *
	 * @param mixed $kv=null
	 * @return void
	 */
	public function table_columns($kv=null)
	{
		$kv = ($kv) ? $kv : $this->table_key_value_set(func_get_args());

		foreach ($kv as $text=>$width) {
			echo $this->paddy($text,$width);
		}

		echo chr(10);
	}

	/**
	 * table_key_value_set
	 *
	 * @param mixed $input
	 * @return void
	 */
	public function table_key_value_set($input)
	{
		$count = count($input);
		$array = [];

		for ($i = 0; $i < $count; $i++) {
			$array[$input[$i]] = $input[++$i];
		}

		return $array;
	}

	/**
	 * paddy
	 *
	 * @param mixed $input
	 * @param mixed $width
	 * @return void
	 */
	public function paddy($input,$width)
	{
		return $this->color($input).str_repeat(' ',($width - strlen(strip_tags($input))));
	}

	/** @ switches */

	/**
	 * switch_sudo
	 *
	 * @param mixed $switch='on'
	 * @return void
	 */
	public function switch_sudo($switch='on')
	{
		$switch = trim($switch);

		$this->v('switch sudo '.$switch);

		if (!$this->switch_storage['sudo setup'] && $switch == 'on') {
			$this->switch_storage['sudo setup'] = true;

			$this->shell('sudo touch -c acbd18db4cc2f85cedef654fccc4a4d8');
		}

		$this->sudo = ($switch == 'on') ? 'sudo ' : '';
	}

	/**
	 * switch_exit
	 *
	 * @param mixed $switch=null
	 * @return void
	 */
	public function switch_exit($switch=null)
	{
		$switch = ($switch) ? trim($switch) : null;

		$this->v('switch exit '.$switch);

		exit($switch);
	}

	/**
	 * switch_stdout
	 *
	 * @param mixed $switch=null
	 * @return void
	 */
	public function switch_stdout($switch=null)
	{
		$this->switch_storage['stdout'] = ($switch == 'on') ? true : false;
	}

	/**
	 * switch_stderr
	 *
	 * @param mixed $switch=null
	 * @return void
	 */
	public function switch_stderr($switch=null)
	{
		$this->switch_storage['stderr'] = ($switch == 'on') ? true : false;
	}

	/** add-on commands */

	/* git something... */
	/**
	 * gitx
	 *
	 * @return void
	 */
	public function gitx()
	{
		$m = __FUNCTION__;
		$args = func_get_args();
		$method = array_shift($args);

		if (method_exists($this,$m.'_'.$method)) {
			call_user_func_array([$this,$m.'_'.$method],$args);
		} else {
			trigger_error($m.' function '.$method.' is not found.');
		}
	}

	/*
	gitx checkout https://github.com/ProjectOrangeBox/orangev2.git {PWD}/packages/projectorangebox/orange {GITBRANCH}

	clone a specific branch to local folder
	 */
	/**
	 * gitx_checkout
	 *
	 * @param mixed $repro_uri=null
	 * @param mixed $path=null
	 * @param mixed $branch=null
	 * @return void
	 */
	public function gitx_checkout($repro_uri=null,$path=null,$branch=null)
	{
		if (!$repro_uri) {
			trigger_error('GIT repository URI not specified please provide one.');
		}
		if (!$path) {
			trigger_error('GIT path not specified please provide one.');
		}
		if (!$branch) {
			trigger_error('GIT branch not specified please provide one.');
		}

		if (file_exists($path.'/.git')) {
			$branch = exec("cd ".str_replace(' ','\ ',$path).";git rev-parse --abbrev-ref HEAD")	;

			$this->e('<red>** '.$path.' repository is already checked out and on branch <cyan>'.$branch.'</off>');
		} else {
			$use_branch = $branch;

			$options = [];

			$exists = $this->get_remote_branches($repro_uri,$branch,$options);

			if (!$exists) {
				$parts = explode(':',$repro_uri);

				$use_branch = $this->select_option($options,'The GIT branch "'.$branch.'" is not available on "'.substr(end($parts),0,-4).'".');
			}

			$cli = 'git clone -b '.$use_branch.' '.$repro_uri.' '.str_replace(' ','\ ',$path);

			$this->v($cli);

			$this->shell($cli);

			$this->gitx_status($path);
		}
	}

	/*
	gitx update {PWD}/packages/projectorangebox/orange

	git fetch and hard reset
	 */
	/**
	 * gitx_update
	 *
	 * @param mixed $path=null
	 * @return void
	 */
	public function gitx_update($path=null)
	{
		$this->directory_exists($path);

		if (!file_exists($path.'/.git')) {
			trigger_error('Not a GIT repository '.$path.'.');
		} else {
			$branch = exec("cd ".str_replace(' ','\ ',$path).";git rev-parse --abbrev-ref HEAD");

			$this->v('cd '.$path.';git fetch --all;git reset --hard origin/'.$branch);

			$this->shell('cd '.$path.';git fetch --all;git reset --hard origin/'.$branch);

			$this->gitx_status($path);
		}
	}

	/*
	gitx status {PWD}/packages/projectorangebox/orange

	read the branch and current commit hash
	 */
	/**
	 * gitx_status
	 *
	 * @param mixed $path=null
	 * @return void
	 */
	public function gitx_status($path=null)
	{
		$this->directory_exists($path);

		if (!file_exists($path.'/.git')) {
			trigger_error('Not a GIT repository '.$path.'.');
		} else {
			$branch = exec("cd ".str_replace(' ','\ ',$path).";git rev-parse --abbrev-ref HEAD");
			$hash = exec("cd ".str_replace(' ','\ ',$path).";git rev-parse --verify HEAD");
			$package = '/'.trim(str_replace(getcwd(),'',$path),'/');

			$this->e('<red>'.$hash.'</red>  <cyan>'.str_pad($branch,16,' ').'</cyan><white>'.$package.'</white>');
		}
	}

	/**
	 * set
	 *
	 * @param mixed $name
	 * @param mixed $value
	 * @return void
	 */
	public function set($name,$value)
	{
		$this->v('Setting <blue>'.$name.'</off> to <cyan>'.$value.'</off>');
		
		$this->merge[$name] = $value;
	}

	/**
	 * capture
	 *
	 * @param mixed $name
	 * @param mixed $shell
	 * @return void
	 */
	public function capture($name,$shell)
	{
		$cmd = $this->merge($this->sudo.$shell);
		$stdout = '';
		$stderr = '';

		$error_code = $this->shell($cmd,$stdout,$stderr);

		$this->merge[$name] = trim($stdout);

		return $error_code;
	}

	/**
	 * task
	 *
	 * @param mixed $task_name
	 * @return void
	 */
	public function task($task_name)
	{
		if (!$this->task_exists($task_name)) {
			trigger_error('Task "'.$task_name.'" Not Found.');
		}

		$this->sub_heading('Task '.$task_name);

		$this->current_task = $task_name;

		foreach ($this->deploy_json[$task_name] as $command) {
			$this->current_line = $command;

			if ($this->skip === false || $command == 'endif') {
				$this->run($command);
			}
		}
	}

	/**
	 * import
	 *
	 * @param mixed $filetype
	 * @param mixed $filepath
	 * @return void
	 */
	public function import($filetype,$filepath)
	{
		$return = false;

		if (!in_array($filetype,['json','ini','yaml','array'])) {
			trigger_error($filetype.' is a unsupported import type.');
		}

		$this->file_exists($filepath);

		$this->sub_heading('Importing '.$filepath.' as an '.$filetype.'.');

		switch($filetype) {
			case 'ini':
				$array = parse_ini_file($filepath);
			break;
			case 'array':
				$array = require $filepath;
			break;
			case 'yaml':
				if (!function_exists('yaml_parse_file')) {
					trigger_error('yaml_parse_file() not found. Please verify you have the YAML PECL extension installed.');
				}

				$array = yaml_parse_file($filepath);
			break;
			case 'json':
				$array = json_decode(file_get_contents($filepath),true);
			break;
			default:
				trigger_error('Unknown file type "'.$filetype.'".');
		}

		if (is_array($array)) {
			foreach ($array as $name => $value){
				$this->merge[$name] = $value;
			}
		} else {
			trigger_error('Your import file did return an Array.');
		}
	}

	/**
	 * heading
	 *
	 * @param mixed $txt
	 * @return void
	 */
	public function heading($txt)
	{
		$this->e('<cyan>'.str_pad('- '.$txt.' ',exec('tput cols'),'-',STR_PAD_RIGHT).'</cyan>');
	}

	/**
	 * sub_heading
	 *
	 * @param mixed $txt
	 * @return void
	 */
	public function sub_heading($txt)
	{
		$this->e('<blue># '.$txt.'</blue>');
	}

	/**
	 * e
	 *
	 * @param mixed $txt
	 * @return void
	 */
	public function e($txt)
	{
		echo $this->color($txt).chr(10);
	}

	/**
	 * v
	 *
	 * @param mixed $txt
	 * @return void
	 */
	public function v($txt)
	{
		if ($this->config['verbose']) {
			$this->e($txt);
		}
	}

	/**
	 * file_exists
	 *
	 * @param mixed $path
	 * @param $extra='file '
	 * @return void
	 */
	public function file_exists($path,$extra='file ')
	{
		if (!file_exists($path)) {
			trigger_error('Could not locate '.$extra.$path);
		}
	}

	/**
	 * directory_exists
	 *
	 * @param mixed $path
	 * @return void
	 */
	public function directory_exists($path)
	{
		$this->file_exists($path,'directory ');
	}

	/**
	 * readline
	 *
	 * @param mixed $name=null
	 * @param mixed $text=null
	 * @return void
	 */
	public function readline($name=null,$text=null)
	{
		if ($text) {
			echo $text;
		}

		$handle = fopen("php://stdin","r");
		$line = fgets($handle);
		fclose($handle);

		$line = trim($line);

		if ($name) {
			$this->merge[$name] = $line;
		}

		return $line;
	}

	/**
	 * select_option
	 *
	 * @param mixed $options
	 * @param mixed $text=null
	 * @return void
	 */
	protected function select_option($options,$text=null)
	{
		$loop = true;

		while ($loop) {
			if ($text) {
				$this->e($text);
			}

			$this->e('Your choices are.');

			foreach ($options as $o) {
				$this->e(chr(9).'<cyan>'.$o.'</cyan>');
			}

			$line = $this->readline();

			if (in_array($line,$options)) {
				$loop = false;
			} else {
				$this->e('<red>'.$line.' not found.</red>');
			}
		}

		return $line;
	}

	/**
	 * get_remote_branches
	 *
	 * @param mixed $uri
	 * @param mixed $branch
	 * @param mixed &$found
	 * @return void
	 */
	protected function get_remote_branches($uri,$branch,&$found)
	{
		if (!$uri) {
			trigger_error('GIT repository URI not specified please provide one.');
		}

		if (!$branch) {
			trigger_error('GIT Branch not specified please provide one.');
		}

		$stdout = $stderr = '';

		$this->shell('git ls-remote --heads '.$uri,$stdout,$stderr);

		foreach (explode(chr(10),trim($stdout)) as $txt) {
			$parts = explode('/',$txt);

			$found_branch = end($parts);

			$found[] = $found_branch;

			if ($found_branch == $branch) {
				return true;
			}
		}

		return false;
	}

} /* end class */
