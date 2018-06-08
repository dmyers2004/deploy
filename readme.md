# deploy

Deploy is used to build project specific deploy "tasks". Each task can have 1 or more shell script commands as well as call other tasks.

All of the available deploy classes methods are also avaiable for you to call.

Some of these include:

e - echo with color `e '<yellow>This is yellow</yellow> this is not'`

heading - heading `heading 'This is a heading'`

table_heading - a table heading with columns  `table_heading 'First Name' 32 'Last Name' 32 Age 4`

table_column - a table column `table_columns 'Johnny' 'AppleSeed' 21`

import - import a php file which must return a PHP array. That array is merged with the "merge" variables. `import {PWD}\.env`

`xgit update {PWD}/path {GIT_BRANCH}`

`xgit status {PWD}/path`

`xgit find {PWD}/path`

set - sets a deploy merge variable `set username 'Johnny Appleseed'`

capture - capture shell script output into a deploy merge variable `capture variable_name 'date +%F_%T'`

task - run another task inside the current task `task 'repair files'`


// Comment `// do something`

// % Comment used for the tasks description `// % backup database`



## Switches

sudo on/off - automatically appends sudo in front of all command line scripts `@sudo on` `@sudo off`

## Self Updating

selfupdate or self-update - automatically download and update this  deploy script manager

## Sample
```
{
  "relink": [
    "// % Create Symbolic links between package folders and public folders.",
    "@sudo on",
    "rm -f {PWD}/public/theme/orange",
    "rm -f {PWD}/public/assets/backorder",
    "rm -f {PWD}/public/assets/stock-status-check",
    "ln -s {PWD}/packages/projectorangebox/theme-orange/public/theme/orange {PWD}/public/theme/orange",
    "ln -s {PWD}/packages/backorder/public/assets/backorder {PWD}/public/assets/backorder",
    "ln -s {PWD}/packages/stock-status-check/public/assets/stock-status-check {PWD}/public/assets/stock-status-check"
  ],
  "copy public": [
    "// % Copy complete folders between package folders and public folders.",
    "@sudo on",
    "cp -R {PWD}/packages/projectorangebox/theme-orange/public/theme/orange {PWD}/public/theme/orange",
    "cp -R {PWD}/packages/backorder/public/assets/backorder {PWD}/public/assets/backorder",
    "cp -R {PWD}/packages/stock-status-check/public/assets/stock-status-check {PWD}/public/assets/stock-status-check"
  ],
  "clear sessions": [
    "// % Delete all files in the session folder.",
    "@sudo on",
    "rm -f {PWD}/var/sessions/*"
  ],
  "clear caches": [
    "// % Delete all files in the cache, uploads, downloads folders.",
    "@sudo on",
    "rm -fdr {PWD}/var/cache/*",
    "rm -fdr {PWD}/var/uploads/*",
    "rm -fdr {PWD}/var/downloads/*"
  ],
  "add access": [
    "// % Auto add the permissions to the orange permissions db table.",
    "@sudo on",
    "cd {PWD}/public;php index.php cli/auto_add_permissions"
  ],
  "repair": [
    "// % Repair folder permissions.",
    "@sudo on",
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
    "@sudo on",
    "#git_update {PWD}",
    "#git_update {PWD}/packages/projectorangebox/cache-viewer",
    "#git_update {PWD}/packages/projectorangebox/config-viewer",
    "#git_update {PWD}/packages/projectorangebox/extra-validations",
    "#git_update {PWD}/packages/projectorangebox/forgot",
    "#git_update {PWD}/packages/projectorangebox/general_addons",
    "#git_update {PWD}/packages/projectorangebox/handlebars",
    "#git_update {PWD}/packages/projectorangebox/librarian",
    "#git_update {PWD}/packages/projectorangebox/login-success",
    "#git_update {PWD}/packages/projectorangebox/migrations",
    "#git_update {PWD}/packages/projectorangebox/opcache",
    "#git_update {PWD}/packages/projectorangebox/orange",
    "#git_update {PWD}/packages/projectorangebox/register",
    "#git_update {PWD}/packages/projectorangebox/remember",
    "#git_update {PWD}/packages/projectorangebox/tasks",
    "#git_update {PWD}/packages/projectorangebox/theme-orange",
    "#git_update {PWD}/packages/projectorangebox/tooltips",
    "#git_update {PWD}/packages/projectorangebox/user_msgs",
    "#git_update {PWD}/packages/backorder",
    "#git_update {PWD}/packages/drop-ships",
    "#git_update {PWD}/packages/stock-status-check",
  ],
  "git basic update": [
    "// % Fetch and Reset the basic git Modules.",
    "@sudo on",
    "#git_update {PWD}",
    "#git_update {PWD}/packages/projectorangebox/orange",
    "#git_update {PWD}/packages/projectorangebox/theme-orange",
  ],
  "git status": [
    "// % Show the GIT branches of the projects git folders.",
    "@sudo on",
    "#git_status {PWD}"
  ],
  "git find": [
    "// % Show the GIT folders in the project.",
    "@sudo on",
    "#show_git_repros {PWD}"
  ],
  "complete database backup": [
    "// % Backup the entire database.",
    "@sudo on",
    "mkdir -p {PWD}/support/backups",
    "chmod -f 777 {PWD}/support/backups",
    "capture FILE_DATE 'date +%F_%T'
    "mysqldump --extended-insert=FALSE --add-drop-table --add-drop-trigger --create-options --password={DB_BACKUP_PASSWORD} --events --routines --single-transaction --triggers --user={DB_BACKUP_USER} --databases {DB_BACKUP_DATABASE} | gzip -9 > {PWD}/support/backups/{FILE_DATE}.{DB_BACKUP_DATABASE}.sql.gz"
  ],
  "backup database data": [
    "// % Backup only the database data.",
    "@sudo on",
    "mkdir -p {PWD}/support/backups",
    "chmod -f 777 {PWD}/support/backups",
    "capture FILE_DATE 'date +%F_%T'
    "mysqldump --extended-insert=FALSE --add-drop-table --password={DB_BACKUP_PASSWORD} --user={DB_BACKUP_USER} --databases {DB_BACKUP_DATABASE} | gzip -9 > {PWD}/support/backups/{FILE_DATE}.data.{DB_BACKUP_DATABASE}.sql.gz"
  ],
  "migrate": [
    "// % Run migration Up.",
    "cd {PWD}/public;php index.php cli/migrate/latest"
  ],
  "site up": [
    "// % Site Up.",
    "@sudo on",
    "chmod -f 777 {PWD}/public",
    "rm {PWD}/public/index.html",
    "chmod -f 775 {PWD}/public"
  ],
  "site down": [
    "// % Site Down.",
    "@sudo on",
    "chmod -f 777 {PWD}/public",
    "cp {PWD}/support/site_down.html {PWD}/public/index.html",
    "chmod -f 775 {PWD}/public"
  ],
  "complete": [
    "// % Run Complete Site Update.",
    "task 'complete database backup'",
    "task 'site down'",
    "task 'git update'",
    "task 'repair'",
    "task 'clear caches'",
    "task 'clear sessions'",
    "task 'relink'",
    "task 'site up'",
    "task 'git status'"
  ],
  "basic": [
    "// % Run Basic Site Update.",
    "task 'backup database data'",
    "task 'git basic update'",
    "task 'repair'",
    "task 'clear caches'",
    "task 'relink'",
    "task 'git status'"
  ]
}
```