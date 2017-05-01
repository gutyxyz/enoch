<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/4/16
 * Time: 23:08
 */

namespace sinri\enoch\mvc;


use sinri\enoch\core\Spirit;

class ApiInterface
{
    protected $spirit;
    protected $request_guid;

    public function __construct()
    {
        $this->request_guid = uniqid();
        $this->spirit = new Spirit();
    }

    public function _work($defaultMethod = '')
    {
        $this->_beforeWork();

        $method = $this->spirit->getRequest("method", $defaultMethod, "/^[a-zA-Z0-9]+$/");
        if (empty($method) || !method_exists($this, $method)) {
            throw new BaseCodedException("Method not exists", BaseCodedException::METHOD_NOT_EXISTS);
        }

        try {
            $data = $this->$method();
            $this->_sayOK($data);
        } catch (BaseCodedException $exception) {
            $this->_sayFail([
                "error_code" => $exception->getCode(),
                "error_msg" => $exception->getMessage(),
            ]);
        }
    }

    protected function _beforeWork()
    {
    }

    protected function _sayOK($data = "")
    {
        $this->spirit->jsonForAjax(Spirit::AJAX_JSON_CODE_OK, $data);
    }

    protected function _sayFail($error = "")
    {
        $this->spirit->jsonForAjax(Spirit::AJAX_JSON_CODE_FAIL, $error);
    }
}