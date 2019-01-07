# Deploy

Deploy is used to provide project specific deploy "tasks".
By default deply will look in the directory you are currently in for a deploy.json file. If it cannot locate the deploy.json file in that folder it will try the next level up the directory tree.

Each task is one or more shell script commands and can also call other tasks as well as deploy built in commands.

You can then run a task by simply supplying the tasks name

```
deploy complete

deploy backup database

deploy fix permissions
```

You can also supply no task to get a list of all available tasks.

```
> deploy
- Deploy Version 4.0.8 --------------------------------------------------------------------------------------------------------------------------------------------------
Looking for deploy.json
/Users/randy/Projects/deploy/deploy.json √
# Using Deploy File /Users/randy/Projects/deploy/deploy.json
Available Tasks:                                                                           
add access               Auto add the permissions to the orange permissions db table.      
backup database data     Backup only the database data.                                    
basic                    Run Basic Site Update.                                            
clear caches             Delete all files in the cache, uploads, downloads folders.        
clear sessions           Delete all files in the session folder.                           
complete                 Run Complete Site Update.                                         
complete database backup Backup the entire database.                                       
copy public              Copy complete folders between package folders and public folders. 
git basic update         Fetch and Reset the basic git Modules.                            
git checkout             Checkout GIT repositories if they are not already checked out.    
git generate             Display all of the GIT repositories in your project               
git status               Show the GIT branches of the projects git folders.                
git update               Fetch and Reset all git Modules.                                  
migrate                  Run migration Up.                                                 
repair                   Repair folder permissions.                                        
self-update              Updates deploy to the latest version.                             
selfupdate               Updates deploy to the latest version.                             
site down                Site Down.                                                        
site up                  Site Up.                                                          
test                     test.                                                             
testing                  Create Symbolic links between package folders and public folders. 
```

## Command Line Options

`-v`	Be verbose when running commands.

`-f file`	Read this deployment file.

`-d directory` Changes deploys current directory to directory.

Example using all options
`deploy -v -f ~/foobar/deploy2.json -d ~/foobar/project/folder site down`


## Additional Commands

`e` - Echo with color `e '<yellow>This is yellow</yellow> this is not'`

`v` - Verbose echo with color which is shown only if the verbose option is used. `v 'hello world'`

`heading` - Heading `heading 'This is a heading'`

`sub_heading` - Heading `sub_heading 'This is a sub heading'`

`import` - Import a file into merge scope. Supported types include array, yaml, json, ini `import array {PWD}\.env`

`gitx update` - Extended git (gitx) command to simplify a git update based on a branch. `gitx update {PWD}/packages/projectorangebox/orange`

`gitx status` - Extended git (gitx) command to view the branch of all git repositories. `gitx status {PWD}/packages/projectorangebox/orange`

`gitx checkout` - Extended git (gitx) command to checkout a git repositories. `gitx checkout https://github.com/ProjectOrangeBox/orangev2.git {PWD}/packages/projectorangebox/orange {GITBRANCH}`

`set` - Sets a deploy merge variable. `set username 'Johnny Appleseed'`

`capture` - Capture shell script output into a deploy merge variable. `capture variable_name 'date +%F_%T'`

`task` - Run another task inside the current task. `task 'repair files'`

`if ()` - Run a Single Level If statement ie. "if ({PWD} == '123/abc')"

`endif` - Exit the Single Level If Statement

`true` - test if imported variable exists


// Comment. `// do something`

// % Comment used for the tasks description. `// % backup database`

## Switches

`@sudo on` / `@sudo off` - turn on and off the feature to automatically append `sudo` in front of all commands.

`@exit 1` / `@exit 0` exit deploy script with exit code

## Installation

To install deploy you simply checkout the GIT repository and make it executable.

You can place deploy anywhere you wish but, if you put it in a directory that is part of your PATH, you can access it globally.

```
git clone https://github.com/dmyers2004/deploy.git deploy
chmod 755 ./deploy/deploy.php
mv ./deploy/deploy.php /usr/local/bin/deploy

```

## Self Updating

Self updating will automatically checkout the GIT repository, make it executable and replace the deploy script which is currently running.

`deploy selfupdate` `deploy self-update`



## Sample
```
{
	"testing": [
		"// % Create Symbolic links between package folders and public folders.",
		"// @sudo on",
		"capture PWD pwd",
		"import array {PWD}/.env",
		"gitx update {PWD} {BRANCH}",
		"import ini {PWD}/env.ini",
		"e '{StatusPort}'",
		"import json {PWD}/env.json",
		"e '{color}'",
		"e '<green>{mary}</green>'",
		"e '<yellow>Testing</yellow>'",
		"rm -f {PWD}/testing",
		"mkdir -p {PWD}/testing/bin",
		"mkdir -p {PWD}/testing/support",
		"touch {PWD}/testing/foobar",
		"chmod -Rf 777 {PWD}/testing"
	],
	"test": [
		"// % test.",
		"capture PWD pwd",
		"import ini {PWD}/.env",
		"set foobar 123",
		"e 'Using GIT Branch {GITBRANCH}'",
		"if ('123/abc' == t)",
		"set PWD /foo/bar",
		"endif",
		"e '{PWD}'"
	],
	"git checkout": [
		"// % Checkout GIT repositories if they are not already checked out.",
		"@sudo on",
		"capture PWD pwd",
		"import ini {PWD}/.env",
		"e 'Using GIT Branch {GITBRANCH}'",
		"gitx checkout git@bitbucket.org:example/application.git {PWD}/~app {GITBRANCH}",
		"gitx checkout git@bitbucket.org:example/example-backorder.git {PWD}/packages/example/backorder {GITBRANCH}",
		"gitx checkout git@bitbucket.org:example/example-stock-status-check.git {PWD}/packages/example/stock-status-check {GITBRANCH}",
		"gitx checkout git@bitbucket.org:example/example-drop-ships.git {PWD}/packages/example/drop-ships {GITBRANCH}",
		"gitx checkout git@bitbucket.org:example/projectorangebox-opcache.git {PWD}/packages/misc/opcache {GITBRANCH}",
		"gitx checkout git@bitbucket.org:example/projectorangebox-config-viewer.git {PWD}/packages/misc/config-viewer {GITBRANCH}",
		"gitx checkout git@bitbucket.org:example/projectorangebox-login-success.git {PWD}/packages/misc/login-success {GITBRANCH}",
		"gitx checkout git@bitbucket.org:example/projectorangebox-extra-validations.git {PWD}/packages/projectorangebox/extra-validations {GITBRANCH}",
		"gitx checkout https://github.com/ProjectOrangeBox/Orange_v2_cli.git {PWD}/packages/projectorangebox/migrations {GITBRANCH}",
		"gitx checkout https://github.com/ProjectOrangeBox/orangev2.git {PWD}/packages/projectorangebox/orange {GITBRANCH}",
		"gitx checkout https://github.com/ProjectOrangeBox/theme-orangev2.git {PWD}/packages/projectorangebox/theme-orange {GITBRANCH}",
		"rm {PWD}/~app/.env",
		"cp -R {PWD}/~app/.git {PWD}/.git",
		"cp -R {PWD}/~app/* {PWD}",
		"rm -fdr {PWD}/~app"
	],
	"copy public": [
		"// % Copy complete folders between package folders and public folders.",
		"@sudo on",
		"capture PWD pwd",
		"cp -R {PWD}/packages/projectorangebox/theme-orange/public/theme/orange {PWD}/public/theme/orange",
		"cp -R {PWD}/packages/backorder/public/assets/backorder {PWD}/public/assets/backorder",
		"cp -R {PWD}/packages/stock-status-check/public/assets/stock-status-check {PWD}/public/assets/stock-status-check"
	],
	"clear sessions": [
		"// % Delete all files in the session folder.",
		"@sudo on",
		"capture PWD pwd",
		"rm -f {PWD}/var/sessions/*"
	],
	"clear caches": [
		"// % Delete all files in the cache, uploads, downloads folders.",
		"@sudo on",
		"capture PWD pwd",
		"rm -fdr {PWD}/var/cache/*",
		"rm -fdr {PWD}/var/uploads/*",
		"rm -fdr {PWD}/var/downloads/*"
	],
	"add access": [
		"// % Auto add the permissions to the orange permissions db table.",
		"capture PWD pwd",
		"cd {PWD}/public;php index.php cli/auto_add_permissions"
	],
	"repair": [
		"// % Repair folder permissions.",
		"@sudo on",
		"capture PWD pwd",
		"mkdir -p {PWD}/bin",
		"mkdir -p {PWD}/support",
		"mkdir -p {PWD}/support/migrations",
		"mkdir -p {PWD}/support/backups",
		"mkdir -p {PWD}/public/assets",
		"mkdir -p {PWD}/public/theme",
		"mkdir -p {PWD}/var",
		"mkdir -p {PWD}/var/cache",
		"mkdir -p {PWD}/var/uploads",
		"mkdir -p {PWD}/var/downloads",
		"mkdir -p {PWD}/var/emails",
		"mkdir -p {PWD}/var/logs",
		"mkdir -p {PWD}/var/sessions",
		"find {PWD} -type f -print0 | sudo xargs -0 chmod 664",
		"find {PWD} -type d -print0 | sudo xargs -0 chmod 775",
		"find {PWD} -type f -print0 | sudo xargs -0 chown {chown}",
		"find {PWD} -type d -print0 | sudo xargs -0 chown {chown}",
		"find {PWD} -type f -print0 | sudo xargs -0 chgrp {chgrp}",
		"find {PWD} -type d -print0 | sudo xargs -0 chgrp {chgrp}",
		"chmod -Rf 777 {PWD}/var",
		"chmod -Rf 777 {PWD}/var/cache/*",
		"chmod -Rf 777 {PWD}/var/uploads/*",
		"chmod -Rf 777 {PWD}/var/downloads/*",
		"chmod -Rf 777 {PWD}/var/emails/*",
		"chmod -Rf 777 {PWD}/var/logs/*",
		"chmod -Rf 777 {PWD}/var/sessions/*",
		"chmod -Rf 777 {PWD}/bin/*",
		"chmod -Rf 777 {PWD}/support/backups"
	],
	"git update": [
		"// % Fetch and Reset all git Modules.",
		"capture PWD pwd",
		"import array {PWD}/.env",
		"gitx update {PWD} {GIT_BRANCH}",
		"gitx update {PWD}/packages/projectorangebox/orange {GIT_BRANCH}",
		"gitx update {PWD}/packages/projectorangebox/register {GIT_BRANCH}",
		"gitx update {PWD}/packages/projectorangebox/remember {GIT_BRANCH}",
		"gitx update {PWD}/packages/projectorangebox/theme-orange {GIT_BRANCH}",
		"gitx update {PWD}/packages/projectorangebox/tooltips {GIT_BRANCH}",
		"gitx update {PWD}/packages/backorder {GIT_BRANCH}",
		"gitx update {PWD}/packages/drop-ships {GIT_BRANCH}",
		"gitx update {PWD}/packages/stock-status-check {GIT_BRANCH}"
	],
	"git basic update": [
		"// % Fetch and Reset the basic git Modules.",
		"capture PWD pwd",
		"import array {PWD}/.env",
		"gitx update {PWD} {GIT_BRANCH}",
		"gitx update {PWD}/packages/projectorangebox/orange",
		"gitx update {PWD}/packages/projectorangebox/theme-orange"
	],
	"git status": [
		"// % Show the GIT branches of the projects git folders.",
		"capture PWD pwd",
		"gitx status {PWD}"
	],
	"git generate": [
		"// % Display all of the GIT repositories in your project",
		"capture PWD pwd",
		"gitx generate {PWD}"
	],
	"complete database backup": [
		"// % Backup the entire database.",
		"@sudo on",
		"capture PWD pwd",
		"mkdir -p {PWD}/support/backups",
		"chmod -f 777 {PWD}/support/backups",
		"import array {PWD}/.env",
		"capture FILE_DATE 'date +%F_%T'",
		"mysqldump --extended-insert=FALSE --add-drop-table --add-drop-trigger --create-options --password={DB_BACKUP_PASSWORD} --events --routines --single-transaction --triggers --user={DB_BACKUP_USER} --databases {DB_BACKUP_DATABASE} | gzip -9 > {PWD}/support/backups/{FILE_DATE}.{DB_BACKUP_DATABASE}.sql.gz"
	],
	"backup database data": [
		"// % Backup only the database data.",
		"@sudo on",
		"capture PWD pwd",
		"mkdir -p {PWD}/support/backups",
		"chmod -f 777 {PWD}/support/backups",
		"import array {PWD}/.env",
		"capture FILE_DATE 'date +%F_%T'",
		"mysqldump --extended-insert=FALSE --add-drop-table --password={DB_BACKUP_PASSWORD} --user={DB_BACKUP_USER} --databases {DB_BACKUP_DATABASE} | gzip -9 > {PWD}/support/backups/{FILE_DATE}.data.{DB_BACKUP_DATABASE}.sql.gz"
	],
	"migrate": [
		"// % Run migration Up.",
		"capture PWD pwd",
		"cd {PWD}/public;php index.php cli/migrate/latest"
	],
	"site up": [
		"// % Site Up.",
		"@sudo on",
		"capture PWD pwd",
		"chmod -f 777 {PWD}/public",
		"rm {PWD}/public/index.html",
		"chmod -f 775 {PWD}/public"
	],
	"site down": [
		"// % Site Down.",
		"@sudo on",
		"capture PWD pwd",
		"chmod -f 777 {PWD}/public",
		"cp {PWD}/support/site_down.html {PWD}/public/index.html",
		"chmod -f 775 {PWD}/public"
	],
	"complete": [
		"// % Run Complete Site Update.",
		"task 'complete database backup'",
		"task 'site down'",
		"task 'git update'",
		"task repair",
		"task 'clear caches'",
		"task 'clear sessions'",
		"task relink",
		"task 'site up'",
		"task 'git status'"
	],
	"basic": [
		"// % Run Basic Site Update.",
		"task 'backup database data'",
		"task 'git basic update'",
		"task repair",
		"task 'clear caches'",
		"task relink",
		"task 'git status'"
	]
}
```