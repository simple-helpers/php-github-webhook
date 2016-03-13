<?php
namespace GitHubWebhook;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Handler
{
    private $secret;
    private $remote;
    private $gitDir;
    private $data;
    private $event;
    private $delivery;
    private $gitOutput;
    private $_logger;

    public function __construct($secret, $gitDir, $remote = null)
    {
        $this->secret = $secret;
        $this->remote = $remote;
        $this->gitDir = $gitDir;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getDelivery()
    {
        return $this->delivery;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function getGitDir()
    {
        return $this->gitDir;
    }

    public function getGitOutput()
    {
        return $this->gitOutput;
    }

    public function getRemote()
    {
        return $this->remote;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function startLoggerInfo($logentriesToken)
    {
        $this->startLogger();
        $this->_logger->pushHandler(new \Logentries\Handler\LogentriesHandler($logentriesToken, Logger::INFO));
        return true;
    }

    public function startLoggerDebug($logentriesToken)
    {
        $this->startLogger();
        $this->_logger->pushHandler(new \Logentries\Handler\LogentriesHandler($logentriesToken, Logger::DEBUG));
        return true;
    }

    public function masterMerge($callback)
    {
        $this->_logger->addInfo("Master Merge handler registered");
        $this->_handlers["master_merge"] = $callback;
    }

    public function register($event, $callback)
    {
        $this->_logger->addInfo("handler registered");
        $this->_handlers[$event][] = $callback;
    }

    public function handle()
    {
        if (!$this->validate()) {
            $this->_logger->addCritical("validation failed");
            return false;
        }
        $this->_logger->addInfo("handlers starting");
        if(count($this->_handlers["master_merge"]) && $this->data["pull_request"]["base"]["ref"] == "master" && $this->data["pull_request"]["merged"] == true) {

            $this->_logger->addInfo("Master Merge handler going to launch");
            $handler = $this->_handlers["master_merge"];
            $handler($this->data);
        }
        if(!array_key_exists($this->event, $this->_handlers)) {
            $this->_logger->addWarning("no custom handlers registered for event", ["event"=>$this->event]);
            return false;
        }
        foreach($this->_handlers[$this->event] as $key => $handler){
            $handler($this->data);
        }
        return true;
    }

    public function validate()
    {
        $signature = @$_SERVER['HTTP_X_HUB_SIGNATURE'];
        $event = @$_SERVER['HTTP_X_GITHUB_EVENT'];
        $delivery = @$_SERVER['HTTP_X_GITHUB_DELIVERY'];
        $payload = file_get_contents('php://input');

        if (!isset($signature, $event, $delivery)) {
            return false;
        }

        if (!$this->validateSignature($signature, $payload)) {
            return false;
        }

        $this->data = json_decode($payload, true);
        $this->event = $event;
        $this->delivery = $delivery;

        $this->_logger->addInfo("got event", ["event"=>$event]);
        $this->_logger->addInfo("decoding payload", ["result"=>json_last_error()]);
        $this->_logger->addDebug("retrived data", ["data"=>$this->data]);

        return true;
    }

    protected function startLogger()
    {
        $this->_logger = new Logger('githubWebhookHandler');
    }

    protected function validateSignature($gitHubSignatureHeader, $payload)
    {
        list ($algo, $gitHubSignature) = explode("=", $gitHubSignatureHeader);

        if ($algo !== 'sha1') {
            // see https://developer.github.com/webhooks/securing/
            return false;
        }

        $payloadHash = hash_hmac($algo, $payload, $this->secret);
        return ($payloadHash === $gitHubSignature);
    }
}
