<?php

/**
 * Response
 * This class is for handling client response for API call
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   2022 Alabian Solutions Limited
 * @version     1.0 => March 2022
 * @link        alabiansolutions.com
 */

class Response
{
    /** @var boolean*/
    private $_success;

    /** @var array information to be sent with along with response*/
    private $_messages = [];

    /** @var array data to be added to response*/
    private $_data;

    /** @var integer http code*/
    private $_httpStatusCode;

    /** @var boolean if data to be fetched from cache*/
    private $_toCache = false;

    /** @var integer response to be sent to client*/
    private $_responseData = [];

    /** @var integer http code ok*/
    public const OK = 200;

    /** @var integer http code created*/
    public const CREATED = 201;

    /** @var integer http code bad request*/
    public const BAD_REQUEST = 400;

    /** @var integer http code unauthorized*/
    public const UNAUTHORIZED = 401;

    /** @var integer http code forbidden*/
    public const FORBIDDEN = 403;

    /** @var integer http code not found*/
    public const NOT_FOUND = 404;

    /** @var integer http method not allowed*/
    public const METHOD_NOT_ALLOWED = 405;

    /** @var integer http code conflict*/
    public const CONFLICT = 406;

    /** @var integer http code internal server error*/
    public const INTERNAL_SERVER_ERROR  = 500;

    // define setSuccess flag method - true or false
    public function setSuccess($success)
    {
        $this->_success = $success;
    }

    /**
     * define addMessage method - can add any error or information info
     *
     * @param string $message message to be added to the messages collection
     * @return void
     */
    public function addMessage(string $message)
    {
        $this->_messages[] = $message;
    }

    /**
     * define setData method - can be used to add any data
     *
     * @param array $data
     * @return void
     */
    public function setData(array $data)
    {
        $this->_data = $data;
    }

    /**
     * define setHttpStatusCode method - numeric status code
     *
     * @param integer $httpStatusCode the http code
     * @return void
     */
    public function setHttpStatusCode(int $httpStatusCode)
    {
        $this->_httpStatusCode = $httpStatusCode;
    }

    /**
     * define toCache flag method - true or false
     *
     * @param boolean $toCache true to cache or false for otherwise
     * @return void
     */
    public function toCache(bool $toCache)
    {
        $this->_toCache = $toCache;
    }

    /**
     * this will send the built response object to the browser in json format
     *
     * @return void
     */
    public function send()
    {
        // set response header contact type to json utf-8
        header('Content-type:application/json;charset=utf-8');

        // if response is cacheable then add http cache-control header with a timeout of 60 seconds
        // else set no cache
        if ($this->_toCache == true) {
            header('Cache-Control: max-age=60');
        } else {
            header('Cache-Control: no-cache, no-store');
        }

        // if response is not set up correctly, e.g. not numeric in status code or success not true or false
        // send a error response
        if (!is_numeric($this->_httpStatusCode) || ($this->_success !== false && $this->_success !== true)) {
            // set http status code in response header
            http_response_code(Response::INTERNAL_SERVER_ERROR);
            // set statusCode in json response
            $this->_responseData['statusCode'] = Response::INTERNAL_SERVER_ERROR;
            // set success flag in json response
            $this->_responseData['success'] = false;
            // set custom error message
            $this->addMessage("Response creation error");
            // set messages in json response
            $this->_responseData['messages'] = $this->_messages;
        } else {
            // set http status code in response header
            http_response_code($this->_httpStatusCode);
            // set statusCode in json response
            $this->_responseData['statusCode'] = $this->_httpStatusCode;
            // set success flag in json response
            $this->_responseData['success'] = $this->_success;
            // set messages in json response
            $this->_responseData['messages'] = $this->_messages;
            // set data array in json response
            $this->_responseData['data'] = $this->_data;
        }

        // encode the responseData array to json response output
        echo json_encode($this->_responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT);
        exit;
    }

    /**
     * package and sending bad response to client
     *
     * @param int $code the http response code
     * @param array $message the message sent to the client
     */
    public static function sendBadResponse(int $code, array $messages)
    {
        $Response = new Response();
        $Response->setHttpStatusCode($code);
        $Response->setSuccess(false);
        foreach ($messages as $message) {
            $Response->addMessage($message);
        }
        $Response->send();
    }

    /**
     * package and sending good response to client
     *
     * @param array $message the message sent to the client
     * @param array $data the data sent to the client
     * @param bool $cacheData if false new data is sent to client
     * @param int $code the http response code
     */
    public static function sendGoodResponse(array $messages, array $data = [], bool $cacheData = false, int $code = Response::OK)
    {
        $Response = new Response();
        $Response->setHttpStatusCode($code);
        $Response->setSuccess(true);
        foreach ($messages as $message) {
            $Response->addMessage($message);
        }
        if ($data) {
            $Response->setData($data);
        }
        if ($cacheData) {
            $Response->toCache(true);
        }
        $Response->send();
    }
}
