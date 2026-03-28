<?php
error_reporting(0);
ini_set('display_errors', 0);

session_save_path(sys_get_temp_dir());
session_name('internlink_session');
session_start();
session_unset();
session_destroy();

header('Location: /internlink/html/index.html');
exit;
