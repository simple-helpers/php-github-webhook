<?php

require "vendor/autoload.php";

use GitHubWebhook\Handler;

$githubWebhookSecret = "";
$logentriesToken = "";


$handler = new Handler($githubWebhookSecret, __DIR__);

$handler->startLoggerInfo($logentriesToken);

$handler->masterMerge(
    function ($data) {
        shell_exec("/home/www/contest/vendor/deployer/deployer/bin/dep deploy dev");
        return true;
    }
);

$handler->handle();
