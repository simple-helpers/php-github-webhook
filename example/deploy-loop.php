<?php

require "vendor/autoload.php";

use GitHubWebhook\Handler;

$githubWebhookSecret = "";
$logentriesToken = "";


$handler = new Handler($githubWebhookSecret, __DIR__);

$handler->startLoggerInfo($logentriesToken);

$handler->masterMerge(
    function ($data) {
        putenv("HOME=/home/myhome");
        shell_exec("/home/www/myproject/vendor/deployer/deployer/bin/dep deploy dev > deploy.log 2>deploy.log &");
        echo "deploying";
    }
);

$handler->handle();
