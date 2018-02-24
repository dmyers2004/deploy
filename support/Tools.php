<?php 

class Tools {
	public $sudo_setup = false;
	public $sudo = '';
	public $column_widths = [];
	public $_internal;
	public $complete;

	public function set($name,$value) {
		$this->_internal[$name] = $value;
	}

	public function complete($complete) {
		$this->complete = $complete;
	
		return $this;
	}

	public function copyr($source, $dest) {
		if (is_dir($source)) {
			$dir_handle = opendir($source);
			
			while ($file=readdir($dir_handle)) {
				if ($file!="." && $file!="..") {
					if (is_dir($source."/".$file)) {
						if (!is_dir($dest."/".$file)) {
							mkdir($dest."/".$file);
						}
						
						$this->copyr($source."/".$file, $dest."/".$file);
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

	public function rmdirr($dirname) {
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
			
			$this->rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
		}
	
		$dir->close();
		
		return rmdir($dirname);
	}

	public function run($arg1,$arg2) {
		if (!isset($this->complete[$arg1])) {
			$this->error("Grouping $arg1 Not Found.");
		}
	
		$commands = $this->complete[$arg1];
		
		foreach ($commands as $cli) {
			$one = substr($cli,0,1);
			
			$this->merge($cli);
			
			if ($one == '%') {
				/* description do nothing */
			} elseif($one == '*') {			
				/* another group */
				$this->run(substr($cli,1),'');
			} elseif($one == '#') {
				/* php function */
				$function = $this->get_function(substr($cli,1));
				$args = $this->get_cli(substr($cli,1));
				
				if (!method_exists(o()->callable,$function)) {
					$this->errors("Callable Function $function Not Found.");
				}
				
				call_user_func_array([o()->callable,$function],$args);
			} else {
				/* direct cli */
				$this->e('<off>'.$this->sudo.$cli);
	
				passthru($this->sudo.$cli,$exit_code);
				
				if ($exit_code > 0) {
					break;
				}
			}
		}
	}
	
	public function get_function($cli) {
		$parts = explode(' ',$cli);
	
		return array_shift($parts);
	}
	
	public function get_cli($cli) {
		$function = $this->get_function($cli);
	
		$cli = substr($cli,strlen($function)+1);
	
		$cli = str_replace('\ ',chr(9),$cli);
	
		$args = str_getcsv($cli,' ',"'");
	
		foreach ($args as $idx=>$val) {
			$args[$idx] = str_replace(chr(9),'\ ',$val);
		}
	
		return $args;
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
			
			$this->column_widths[] = $val;
		}
		
		$this->e('<yellow>'.$text.'</yellow>');
	}
	
	public function table_columns() {
		$input = func_get_args();
		$text = '';
	
		foreach ($input as $idx=>$val) {
			$text .= str_pad($val,$this->column_widths[$idx],' ',STR_PAD_RIGHT).' ';
		}
	
		$this->e($text);
	}
	
	public function merge(&$input) {
		foreach ($_ENV as $key=>$val) {
			$input = str_replace('{'.strtolower($key).'}',$val,$input);
		}
		
		$input = str_replace(['{rootpath}','{erootpath}','{filename_date}'],[ROOTPATH,ESCROOTPATH,date('Y-m-d-H:ia')],$input);
	
		foreach ((array)$this->_internal as $key=>$val) {
			$input = str_replace('{'.strtolower($key).'}',$val,$input);
		}
	}
		
	public function s($input) {
		return str_replace(' ','\ ',$input);
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
	
	public function get_env() {
		$env_file = '.env';

		$return = false;
	
		if (!file_exists($env_file)) {
			$this->error('Could not locate .env file',false);
		} else {
			$this->heading('Using ENV File '.ROOTPATH.'/.env');
		
			$return = require $env_file;
		}
		
		return $return;
	}
	
	public function get_deploy() {
		$deploy_filename = 'deploy.json';
		
		$return = false;
				
		if (!file_exists($deploy_filename)) {
			$this->error('Could not locate deploy.json file',false);
		} else {
			$build_obj = json_decode(file_get_contents($deploy_filename));
		
			if ($build_obj === null) {
				$this->error('deploy.json malformed',false);
			}
			
			$return = (array)$build_obj;
		}
		
		return $return;
	}

	public function get_hard_actions() {
		$hard_actions_filename = 'support/hard_actions.json';
		
		$return = false;
				
		if (!file_exists($hard_actions_filename)) {
			$this->error('Could not locate hard_actions.json file',false);
		} else {
			$build_obj = json_decode(file_get_contents($hard_actions_filename));
		
			if ($build_obj === null) {
				$this->error('hard_actions.json malformed',false);
			}
			
			$return = (array)$build_obj;
		}
		
		return $return;
	}

	public function get_descriptions($complete,&$length) {
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
	
} /* end class */