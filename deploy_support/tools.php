<?php 

class tools {
	static public $sudo_setup = false;
	static public $sudo = '';
	static public $column_widths = [];
	static public $_internal = [];
	static public $complete;
	static public $on_error_stop = false;

	static public function set($name,$value) {
		self::$_internal[$name] = $value;
	}
	
	/* group has no parameters */
	static public function grouping($group_name) {
		$parameters = $_SERVER['argv'];

		if (!isset(self::$complete[$group_name])) {
			self::error("Grouping $group_name Not Found.");
		}
	
		foreach (self::$complete[$group_name] as $command) {
			$exit_code = self::run($command);
		}
	}

	static public function run($command) {	
		self::merge($command);

		$f = substr($command,0,1);
		$c = substr($command,1);
				
		if ($f == '%') {
			/* description */
			self::e('<yellow>'.trim($c).'</yellow>');
		} elseif($f == '*') {
			/* run another group */
			
			self::e('<cyan>Run Grouping '.$c);
			
			$exit_code = self::grouping($c);
			
			if ($exit_code > 0) {
				self::error('Exit Code '.$exit_code);
			}
		} elseif ($f == '/') {
			/* comment skip */
		} elseif($f == '#') {
			/* run php function */
			$exit_code = self::func($c);

			if ($exit_code > 0) {
				self::error('Exit Code '.$exit_code);
			}
		} else {
			/* raw cli */
			$exit_code = self::cli($command);

			if ($exit_code > 0) {
				self::error('Exit Code '.$exit_code);
			}
		}
	}

	static public function func($command) {
		$callable = new callable_functions;

		$function = self::get_function($command);
		$args = self::get_arguments($command);
				
		if (!method_exists($callable,$function)) {
			self::error("Callable Function $function Not Found.");
		}

		self::e('<off>Calling function '.$function.'('.implode(' ',$args).')');

		return call_user_func_array([$callable,$function],$args);
	}

	static public function cli($command) {
		$exit_code = 0;

		self::e('<off>'.self::$sudo.$command);

		tools::shell(self::$sudo.$command,$exit_code);
			
		return $exit_code;
	}

	static public function e($txt) {
		echo self::color($txt).chr(10);
	}
	
	static public function heading($txt,$pad='-') {
		self::e('<cyan>'.str_pad('- '.$txt.' ',exec('tput cols'),'-',STR_PAD_RIGHT).'</cyan>');
	}
	
	static public function error($txt,$exit=true) {
		self::e('<red>'.str_pad('* '.$txt.' ',exec('tput cols'),'*',STR_PAD_RIGHT).'</red>');
		
		if ($exit) {
			exit(6);
		}
	}
	
	static public function table_heading() {
		$input = func_get_args()[0];
		$text = '';
	
		foreach ($input as $txt=>$val) {
			$text .= str_pad($txt,$val,' ',STR_PAD_RIGHT).' ';
			
			self::$column_widths[] = $val;
		}
		
		self::e('<yellow>'.$text.'</yellow>');
	}
	
	static public function table_columns() {
		$input = func_get_args();
		$text = '';
	
		foreach ($input as $idx=>$val) {
			$text .= str_pad($val,self::$column_widths[$idx],' ',STR_PAD_RIGHT).' ';
		}
	
		self::e($text);
	}
	
	static public function merge(&$input) {
		/* find all the {???} and make sure we have keys */
		$found = preg_match_all('/{(.+?)}/m', strtolower($input), $matches, PREG_SET_ORDER, 0);

		$merge = array_merge($_ENV,self::$_internal);

		if ($found > 0) {
			foreach ($matches as $match) {
				if (!isset($merge[$match[1]])) {
					self::error('Missing Merge Key "'.$match[1].'"');
				}
			}
			
			foreach ($merge as $key=>$val) {
				$input = str_replace('{'.strtolower($key).'}',$val,$input);
			}
		}
	}
		
	static public function color($input) {
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
	
	static public function get_env($load=false) {
		$env_file = getcwd().'/.env';

		$return = false;
	
		if (!file_exists($env_file)) {
			self::error('Could not locate '.getcwd().'/.env file',false);
		} else {
			self::heading('Using ENV File '.getcwd().'/.env');
		
			$return = require $env_file;
		}
		
		if ($load && is_array($return)) {
			$_ENV = $_ENV + $return;
		}
		
		return $return;
	}
	
	static public function get_deploy() {
		$deploy_filename = getcwd().'/deploy.json';
		
		$array = [];
				
		if (!file_exists($deploy_filename)) {
			self::error('Could not locate '.getcwd().'/deploy.json file',false);
		} else {
			self::heading('Using Deploy File '.getcwd().'/deploy.json');

			$array = json_decode(file_get_contents($deploy_filename));
		
			if ($array === null) {
				self::error('deploy.json malformed',false);
			
				$array = [];
			}
		}
		
		return (array)$array;
	}

	static public function get_hard_actions() {
		$json_obj = json_decode(file_get_contents(SUPPORTPATH.'/hard_actions.json'));
	
		if ($json_obj === null) {
			self::error('hard_actions.json malformed',false);
		
			$json_obj = [];
		}
		
		return (array)$json_obj;
	}

	static public function get_descriptions($complete,&$length) {
		$c = [];
		
		foreach ($complete as $key=>$values) {
			foreach ((array)$values as $value) {
				if (substr($value,0,1) == '%') {
					$c[$key] = trim(substr($value,1));
					
					$length = max(strlen($key)+2,$length);
				}
			}
		}
	
		return $c;
	}

	static public function get_function($cli) {
		$parts = explode(' ',$cli);
	
		return array_shift($parts);
	}
	
	static public function get_arguments($cli) {
		$cli = explode(' ',$cli);

		array_shift($cli);
		
		$cli = implode(' ',$cli);
	
		$cli = str_replace('\ ',chr(9),trim($cli));
	
		$args = str_getcsv($cli,' ',"'");
	
		foreach ($args as $idx=>$val) {
			$args[$idx] = str_replace(chr(9),'\ ',$val);
		}
	
		return (array)$args;
	}
	
	static public function after($tag,$searchthis) {
		return (!is_bool(strpos($searchthis,$tag))) ? substr($searchthis,strpos($searchthis,$tag)+strlen($tag)) : '';
	}
	
	static public function before($tag,$searchthis) {
		return substr($searchthis,0,strpos($searchthis, $tag));
	}
	
	static public function between($tag,$that,$searchthis) {
		return self::before($that,self::after($tag,$searchthis));
	}
	
	static public function build($build_array,$merge=[]) {
		$filename = '';
		$find = '';
		$replace = '';
		$create = '';
		$action = '';

		foreach ($build_array as $line) {
			if (self::starts_with('<file name="',$line)) {
				$filename = self::between('"','"',$line);
			} elseif(self::starts_with('<find>',$line)) {
				$action = 'find';
			} elseif(self::starts_with('</find>',$line)) {
				$action = '';
			} elseif(self::starts_with('<replace>',$line)) {
				$action = 'replace';
			} elseif(self::starts_with('</replace>',$line)) {
				$action = '';

				self::build_find_replace($filename,$find,$replace);

				$find = '';
				$replace = '';
			} elseif(self::starts_with('<create>',$line)) {
				$action = 'create';
			} elseif(self::starts_with('</create>',$line)) {
				$action = '';

				self::build_create($filename,$create);

				$create = '';
			} elseif($action == 'find') {
				$find .= $line.chr(10);
			} elseif($action == 'replace') {
				$replace .= $line.chr(10);
			} elseif($action == 'create') {
				$create .= $line.chr(10);
			}			
		}
	}

	static public function starts_with($string,$line) {
		$string = strtolower(trim($string));
		return (substr($line,0,strlen($string)) == $string);
	}
	
	static public function build_create($filename,$content) {
		self::merge($content);
	
		$filename = ltrim($filename,'/');
		
		return file_put_contents(getcwd().'/'.$filename, $content);
	}
	
	static public function build_find_replace($filename,$find,$replace) {
		self::merge($replace);

		$filename = ltrim($filename,'/');
	
		return file_put_contents(getcwd().'/'.$filename,str_replace($find,$replace,file_get_contents(getcwd().'/'.$filename)));
	}

	static public function shell($cmd, &$stdout=null, &$stderr=null) {
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
} /* end class */