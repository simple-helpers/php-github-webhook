<?php

require "vendor/autoload.php";

use GitHubWebhook\Handler,
    Logentries\Handler\LogentriesHandler;

$githubWebhookSecret = "";
$logentriesToken = "";


$handler = new Handler($githubWebhookSecret, __DIR__);
$logHandler = new LogentriesHandler('YOUR_TOKEN');

$handler->startLogger($logHandler);

$handler->masterMerge(
    function ($data) {
        putenv("HOME=/home/myhome");
        shell_exec("/home/www/myproject/vendor/deployer/deployer/bin/dep deploy dev > deploy.log 2>deploy.log &");
        echo "deploying";
    }
);

$handler->handle();
