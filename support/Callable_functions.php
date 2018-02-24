<?php

class Callable_functions {
	public function set($name,$value) {
		o()->tools->e("set $name to $value");
		
		o()->tools->set($name,$value);
	}

	public function git_status($path) {
		exec('find '.$path.' -name FETCH_HEAD',$output);
		
		o()->tools->table_heading(['Package'=>32,'Branch'=>16,'Hash'=>42]);
		
		foreach ($output as $o) {
			$dirname = dirname(dirname($o));
		
			$branch = exec("cd ".s($dirname).";git rev-parse --abbrev-ref HEAD");
			$hash = exec("cd ".s($dirname).";git rev-parse --verify HEAD");
			
			$sections = explode('/',$dirname);
			$package = end($sections);
		
			o()->tools->table_columns($package,$branch,$hash);
		}
	}

	public function git_update($path,$branch=null) {
		$branch = ($branch) ? $branch : $_ENV['GITBRANCH'];
	
		if (!file_exists($path.'/.git')) {
			o()->tools->e('<red>Not a git folder '.$path.'</off>');
		} else {
			passthru('cd '.$path.';git fetch --all;git reset --hard origin/'.$branch);
		}
	}
	
	public function show_git_repros($path) {
		exec('find '.$path.' -name FETCH_HEAD',$output);
		
		foreach ($output as $o) {
			o()->tools->e(str_replace(ROOTPATH,'{erootpath}',dirname(dirname($o))));
		}
	}

	public function sudo($on) {
		o()->tools->e("sudo $on");

		if ($on == 'on') {
			if (!o()->tools->sudo_setup) {
				passthru('sudo touch -c foo');
			}
			
			o()->tools->sudo = 'sudo ';
		} else {
			o()->tools->sudo = '';
		}
	}

	public function self_update() {
		o()->tools->heading('Updating Self');
	
		passthru('rm -fdr /tmp/deploy;git clone https://github.com/dmyers2004/deploy.git /tmp/deploy;mv /tmp/deploy/deploy "'.__FILE__.'";chmod 755 "'.__FILE__.'"');
	
		o()->tools->heading('Update Complete');
		exit();
	}

	public function create_package($folder='') {
		if (empty($folder)) {
			o()->tools->error('Please provide a folder name');
		}
		
		$folder = $_SERVER['PWD'].'/'.$folder;
		
		if (file_exists($folder)) {
			o()->tools->error('Folder already exists');
		}
		
		o()->tools->heading('Create Folder');
		
		@mkdir($folder,0777,true);
		
		chdir($folder);
		
		o()->tools->heading('Copy Repository');
		
		passthru("cd ".str_replace(' ','\ ',$folder).";composer require codeigniter/framework");
		
		o()->tools->heading('Make Directories');
		
		/* create folders */
		@mkdir('public');
		@mkdir('public/assets');
		@mkdir('public/theme');
		@mkdir('application');
		@mkdir('support');
		@mkdir('support/migrations');
		@mkdir('support/misc');
		@mkdir('support/backups');
		@mkdir('support/import');
		@mkdir('packages');
		@mkdir('var');
		@mkdir('var/logs');
		@mkdir('var/cache');
		@mkdir('var/downloads');
		@mkdir('var/uploads');
		@mkdir('var/emails');
		@mkdir('var/sessions');
		
		o()->tools->heading('Create Needed Files');
		
		file_put_contents('.env',env_content());
		file_put_contents('deploy.json',deploy_content());
		file_put_contents('public/.htaccess',htaccess_content());
		
		/* copy application */
		o()->tools->heading('Copy Application to Application');
		
		o()->tools->copyr($folder.'/vendor/codeigniter/framework/application',$folder.'/application');
		
		o()->tools->heading('Remove index.html from Application');
		
		/* remove index.html */
		$dir = new RecursiveDirectoryIterator($folder.'/application');
		$ite = new RecursiveIteratorIterator($dir);
		
		foreach($ite as $file) {
			if ($file->getBasename() == 'index.html' || $file->getBasename() == '.DS_Store') {
				unlink($file->getRealPath());
			}
		}
		
		/* move index.php to public */
		
		o()->tools->heading('Copy Index to Index');
		
		copy($folder.'/vendor/codeigniter/framework/index.php',$folder.'/public/index.php');
		
		$content = file_get_contents('public/index.php');
		
		$content = str_replace("require_once BASEPATH.'core/CodeIgniter.php';","require_once ORANGEPATH.'/core/Orange.php';",$content);
		$content = str_replace("\$system_path = 'system';","\$system_path = '../vendor/codeigniter/framework/system';",$content);
		$content = str_replace("\$application_folder = 'application';","\$application_folder = '../application';",$content);
		$content = str_replace("define('ENVIRONMENT', isset(\$_SERVER['CI_ENV']) ? \$_SERVER['CI_ENV'] : 'development');",index_content(),$content);
		$content = str_replace("error_reporting(-1);","error_reporting(E_ALL & ~E_NOTICE);",$content);
		
		file_put_contents('public/index.php',$content);
		
		$content = file_get_contents('application/config/config.php');
		
		$content = str_replace("\$config['base_url'] = '';","\$config['base_url'] = env('DOMAIN');",$content);
		$content = str_replace("\$config['index_page'] = 'index.php';","\$config['index_page'] = '';",$content);
		$content = str_replace("\$config['composer_autoload'] = FALSE;","\$config['composer_autoload'] = ROOTPATH.'/vendor/autoload.php';",$content);
		$content = str_replace("\$config['log_threshold'] = 0;","\$config['log_threshold'] =  env('LOG_THRESHOLD',0);",$content);
		$content = str_replace("\$config['log_path'] = '';","\$config['log_path'] = ROOTPATH.'/var/logs/';",$content);
		$content = str_replace("\$config['log_file_extension'] = '';","\$config['log_file_extension'] = 'log';",$content);
		$content = str_replace("\$config['cache_path'] = '';","\$config['cache_path'] = ROOTPATH.'/var/cache/';",$content);
		$content = str_replace("\$config['cache_path'] = '';","\$config['cache_path'] = ROOTPATH.'/var/cache/';\n\$config['cache_default'] = env('config.cache_default','dummy');\n\$config['cache_backup'] = 'dummy';\n\$config['cache_ttl'] = env('config.cache_ttl',0);",$content);
		$content = str_replace("\$config['sess_save_path'] = NULL;","\$config['sess_save_path'] = ROOTPATH.'/var/sessions/';",$content);
		$content = str_replace("\$config['encryption_key'] = '';","\$config['encryption_key'] = env('encryption_key');",$content);
		
		file_put_contents('application/config/config.php',$content);
		
		o()->tools->heading('Remove unneeded folders');
		
		/* remove application folders */
		unlink($folder.'/application/config/hooks.php');
		unlink($folder.'/application/config/smileys.php');
		
		o()->tools->rmdir($folder.'/application/cache');
		o()->tools->rmdir($folder.'/application/core');
		o()->tools->rmdir($folder.'/application/hooks');
		o()->tools->rmdir($folder.'/application/logs');
		o()->tools->rmdir($folder.'/application/language/english');
		o()->tools->rmdir($folder.'/application/language');
		o()->tools->rmdir($folder.'/application/third_party');
	}

} /* end class */
