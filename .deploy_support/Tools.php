<?php 

class Tools {
	static public $sudo_setup = false;
	static public $sudo = '';
	static public $column_widths = [];
	static public $_internal;
	static public $complete;

	static public function set($name,$value) {
		self::$_internal[$name] = $value;
	}

	static public function complete($complete) {
		self::$complete = $complete;
	}

	static public function run($command,$parameters) {
		global $callable;
	
		$one = substr($command,0,1);
		
		self::merge($command);
		
		if ($one == '%') {
			/* description do nothing */
		} elseif($one == '*') {
			/* another group */
			self::grouping(substr($command,1),'');
		} elseif($one == '#') {
			/* php function */
			$function = self::get_function(substr($command,1));
			$args = self::get_cli($parameters);
			
			if (!method_exists($callable,$function)) {
				self::error("Callable Function $function Not Found.");
			}
			
			call_user_func_array([$callable,$function],$args);
		} elseif ('/') {
			/* comment skip */
		} else {
			/* direct cli */
			self::e('<off>'.self::$sudo.$command);

			passthru(self::$sudo.$command,$exit_code);
			
			if ($exit_code > 0) {
				break;
			}
		}
	}

	static public function grouping($group_name,$parameters) {
		if (!isset(self::$complete[$group_name])) {
			self::error("Grouping $group_name Not Found.");
		}
	
		foreach (self::$complete[$group_name] as $command) {
			self::run($command,$parameters);
		}
	}

	static public function copyr($source, $dest) {
		if (is_dir($source)) {
			$dir_handle = opendir($source);
			
			while ($file=readdir($dir_handle)) {
				if ($file!="." && $file!="..") {
					if (is_dir($source."/".$file)) {
						if (!is_dir($dest."/".$file)) {
							mkdir($dest."/".$file);
						}
						
						self::copyr($source."/".$file, $dest."/".$file);
					} else {
						copy($source."/".$file, $dest."/".$file);
					}
				}
			}
			
			closedir($dir_handle);
		} else {
			copy($source, $dest);
		}
	}

	static public function rmdirr($dirname) {
		if (!file_exists($dirname)) {
			return false;
		}
		
		if (is_file($dirname) || is_link($dirname)) {
			return unlink($dirname);
		}
	
		$dir = dir($dirname);
		
		while (false !== $entry = $dir->read()) {
			if ($entry == '.' || $entry == '..') {
				continue;
			}
			
			self::rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
		}
	
		$dir->close();
		
		return rmdir($dirname);
	}
	
	static public function get_function($cli) {
		$parts = explode(' ',$cli);
	
		return array_shift($parts);
	}
	
	static public function get_cli($cli) {
		$cli = str_replace('\ ',chr(9),trim($cli));
	
		$args = str_getcsv($cli,' ',"'");
	
		foreach ($args as $idx=>$val) {
			$args[$idx] = str_replace(chr(9),'\ ',$val);
		}
	
		return (array)$args;
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
		foreach ($_ENV as $key=>$val) {
			$input = str_replace('{'.strtolower($key).'}',$val,$input);
		}
		
		$input = str_replace(['{rootpath}','{erootpath}','{filename_date}'],[ROOTPATH,ESCROOTPATH,date('Y-m-d-H:ia')],$input);
	
		foreach ((array)self::$_internal as $key=>$val) {
			$input = str_replace('{'.strtolower($key).'}',$val,$input);
		}
	}
		
	static public function s($input) {
		return str_replace(' ','\ ',$input);
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
	
	static public function get_env() {
		$env_file = getcwd().'/.env';

		$return = false;
	
		if (!file_exists($env_file)) {
			self::error('Could not locate '.getcwd().'/.env file',false);
		} else {
			self::heading('Using ENV File '.getcwd().'/.env');
		
			$return = require $env_file;
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
		$json_obj = json_decode(phar_file_get_contents('hard_actions'));
	
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

	static public function after($tag,$searchthis) {
		if (!is_bool(strpos($searchthis,$tag)))
		return substr($searchthis,strpos($searchthis,$tag)+strlen($tag));
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
			if (starts_with('<file name="',$line)) {
				$filename = self::between('"','"',$line);
			} elseif(starts_with('<find>',$line)) {
				$action = 'find';
			} elseif(starts_with('</find>',$line)) {
				$action = '';
			} elseif(starts_with('<replace>',$line)) {
				$action = 'replace';
			} elseif(starts_with('</replace>',$line)) {
				$action = '';

				self::build_find_replace($filename,$find,$replace);

				$find = '';
				$replace = '';
			} elseif(starts_with('<create>',$line)) {
				$action = 'create';
			} elseif(starts_with('</create>',$line)) {
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
	
} /* end class */