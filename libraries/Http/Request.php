<?php

namespace Nahid\FaceBot\Http;

use Symfony\Component\HttpFoundation\Request as HttpRequest;

class Request
{
    protected $contents;
    protected $http;
    protected $object = null;

    public function __construct()
    {
        $this->http = HttpRequest::createFromGlobals();
        $this->contents = $this->http->getContent();

        $this->object = $this->getBodyAsObject();
    }


    public function getHttpClient()
    {
        return $this->http;
    }

    public function getContentTypes()
    {
        $response = $this->http->headers->get('Content-Type');

        $types = explode(';', $response);

        return $types;
    }

    public function getContentType()
    {
        $types = $this->getContentTypes();

        return $types[0];
    }

    public function getBodyAsObject()
    {
        if ($this->getContentType() == 'text/javascript' ||
            $this->getContentType() == 'text/json' ||
            $this->getContentType() == 'application/javascript' ||
            $this->getContentType() == 'application/json') {
            return json_decode($this->contents);
        }

        return null;
    }

    public function getContent()
    {
        return $this->contents;
    }

    public function getMessaging($entry = 0)
    {
        if (isset($this->object->entry[$entry]->messaging)) {
            return $this->object->entry[$entry]->messaging;
        }

        return false;
    }

    public function isEcho()
    {
        $messaging = $this->getMessaging();
        $echo = $messaging[0];

        if (isset($echo->message->is_echo)) {
            return true;
        }

        return false;
    }

    public function isMessageRecieved()
    {
        $messaging = $this->getMessaging();
        if(isset($messaging[0]->message)) {
            return $messaging[0]->message;
        }

        return false;
    }

    public function getMessage()
    {
        if ($message = $this->isMessageRecieved()) {
            return $message;
        }

        return [];
    }
    

    public function hasMessageAndNoEcho()
    {
        if ($this->isMessageRecieved()) {
            if (!$this->isEcho()) {
                return true;
            }
        }

        return false;
    }

    public function isDelivered()
    {
        $message = $this->getMessaging();

        if(isset($message[0]->delivery)) {
            return $message[0]->delivery;
        }

        return false;
        
    }

    public function getDeliveredIds()
    {
        if ($deliver = $this->isDelivered()) {
            return $deliver->mids;
        }

        return null;
    }

    public function isRead()
    {
        $message = $this->getMessaging();

        if(isset($message[0]->read)) {
            return $message[0]->read;
        }

        return false;
    }

    public function getSender()
    {
        $messaging = $this->getMessaging();

        if ($messaging) {
            return $messaging[0]->sender;
        }

        return false;
    }

    public function hasAttachment($entry = 0, $message = 0)
    {
        if (isset($this->object->entry[$entry]->messaging[$message]->message->attachments)) {
            return true;
        }

        return false;
    }

    public function getAttachments($entry = 0, $message = 0)
    {
        if (isset($this->object->entry[$entry]->messaging[$message]->message->attachments)) {
            return $this->object->entry[$entry]->messaging[$message]->message->attachments;
        }

        return false;
    }


    public function verifyToken()
    {

        $verify_token = _env("VERIFY_TOKEN", "my-fb-token");
        $hub_verify_token = null;

        if ($this->http->query->has('hub_challenge')) {
            $challenge = $this->http->query->get('hub_challenge');
            $hub_verify_token = $this->http->query->get('hub_verify_token');
        }

        if ($hub_verify_token === $verify_token) {
            return $challenge;
        }

        return false;
    }
}