<?php

class callable_functions {
	public function set($name,$value) {
		tools::e("set $name to $value");

		tools::set($name,$value);
	}

	public function copy_support_file($support_filename,$to_path) {
		copy(supportpath.'/'.$support_filename,$to_path);
	}

	public function git_status($path) {
		exec('find '.$path.' -name FETCH_HEAD',$output);

		tools::table_heading(['Package'=>32,'Branch'=>16,'Hash'=>42]);

		foreach ($output as $o) {
			$dirname = dirname(dirname($o));

			$branch = exec("cd ".str_replace(' ','\ ',$dirname).";git rev-parse --abbrev-ref HEAD");
			$hash = exec("cd ".str_replace(' ','\ ',$dirname).";git rev-parse --verify HEAD");

			$sections = explode('/',$dirname);
			$package = end($sections);

			tools::table_columns($package,$branch,$hash);
		}
	}

	public function git_update($path,$branch=null) {
		$branch = ($branch) ? $branch : $_ENV['GITBRANCH'];

		if (!file_exists($path.'/.git')) {
			tools::e('<red>Not a git folder '.$path.'.</off>');
		} else {
			tools::shell('cd '.$path.';git fetch --all;git reset --hard origin/'.$branch);
		}
	}

	public function show_git_repros($path) {
		exec('find '.$path.' -name FETCH_HEAD',$output);

		foreach ($output as $o) {
			tools::e(str_replace(ROOTPATH,'{erootpath}',dirname(dirname($o))));
		}
	}

	public function sudo($on) {
		if ($on == 'on') {
			if (!tools::$sudo_setup) {
				tools::shell('sudo touch -c foo');
			}
			tools::$sudo = 'sudo ';
		} else {
			tools::$sudo = '';
		}
	}

	public function self_update() {
		tools::heading('Updating Self');

		$dir = str_replace(' ','\ ',dirname(DEPLOYFILE));
		$file = $dir.'/deploy';
		$src_folder = $dir.'/.deploy-src';

		/*
		rm -fdr /tmp/deploy
		git clone https://github.com/dmyers2004/deploy.git /tmp/deploy
		mv -fv /tmp/deploy /home/shared/bin/.deploy-src
		ln -sfv /home/shared/bin/.deploy-src/deploy.php /home/shared/bin/deploy
		chmod -v 755 /home/shared/bin/deploy
		*/

		$cli = 'rm -fdrv /tmp/deploy;git clone https://github.com/dmyers2004/deploy.git /tmp/deploy;mv -fv /tmp/deploy '.$src_folder.';ln -sfv '.$src_folder.'/deploy.php '.$file.';chmod -v 755 '.$file;

		passthru($cli);

		tools::heading('Update Complete');
	}

	public function create_project($foldername='') {
		die('work in progress');
		if (empty($foldername)) {
			tools::error('Please provide a folder name');
		}

		$pwd = str_replace('phar://','',dirname(dirname(dirname(__FILE__))));

		$folder = $pwd.'/'.$foldername;

		tools::heading('Folder '.$folder);

		if (file_exists($folder)) {
			tools::error('Folder '.$folder.' already exists');
		}

		mkdir($folder);
		chdir($folder);

		tools::set('%md5%',md5(microtime()));
	}

	public function create_project_create_find_replace() {
		die('work in progress');
		tools::build(explode(chr(10),phar_file_get_contents('create-project')));
	}

} /* end class */