<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/4/16
 * Time: 22:42
 */

namespace sinri\enoch\mvc;


use sinri\enoch\core\LibSession;
use sinri\enoch\core\Spirit;

class Lamech
{
    protected $session_dir;
    protected $controller_dir;
    protected $view_dir;
    protected $error_page;

    protected $router;
    private $default_controller_name = 'Welcome';
    private $default_method_name = 'index';

    public function __construct($session_dir = null, $controller_dir = null, $view_dir = null, $error_page = null)
    {
        $this->session_dir = $session_dir;
        $this->controller_dir = $controller_dir;
        $this->view_dir = $view_dir;
        $this->error_page = $error_page;

        $this->router = new Naamah();
    }

    /**
     * @return Naamah
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @return string
     */
    public function getDefaultControllerName()
    {
        return $this->default_controller_name;
    }

    /**
     * @param string $default_controller_name
     */
    public function setDefaultControllerName($default_controller_name)
    {
        $this->default_controller_name = $default_controller_name;
    }

    /**
     * @return string
     */
    public function getDefaultMethodName()
    {
        return $this->default_method_name;
    }

    /**
     * @param string $default_method_name
     */
    public function setDefaultMethodName($default_method_name)
    {
        $this->default_method_name = $default_method_name;
    }

    /**
     * @return null
     */
    public function getErrorPage()
    {
        return $this->error_page;
    }

    /**
     * @param null $error_page
     */
    public function setErrorPage($error_page)
    {
        $this->error_page = $error_page;
    }

    /**
     * @return mixed
     */
    public function getSessionDir()
    {
        return $this->session_dir;
    }

    /**
     * @param mixed $session_dir
     */
    public function setSessionDir($session_dir)
    {
        $this->session_dir = $session_dir;
    }

    /**
     * @return mixed
     */
    public function getControllerDir()
    {
        return $this->controller_dir;
    }

    /**
     * @param mixed $controller_dir
     */
    public function setControllerDir($controller_dir)
    {
        $this->controller_dir = $controller_dir;
    }

    /**
     * @return mixed
     */
    public function getViewDir()
    {
        return $this->view_dir;
    }

    /**
     * @param mixed $view_dir
     */
    public function setViewDir($view_dir)
    {
        $this->view_dir = $view_dir;
    }

    public function startSession()
    {
        LibSession::sessionStart($this->session_dir);
    }

    public function viewFromRequest()
    {
        $spirit = Spirit::getInstance();
        $act = $spirit->getRequest("act", 'index', "/^[A-Za-z0-9_]+$/", $error);
        if ($error === Spirit::REQUEST_REGEX_NOT_MATCH) {
            $spirit->errorPage("Act input does not correct.", null, $this->error_page);
        } else {
            //act 种类
            try {
                $view_path = $this->view_dir . '/' . $act . ".php";
                if (!file_exists($view_path)) {
                    throw new BaseCodedException("Act missing", BaseCodedException::ACT_NOT_EXISTS);
                }
                $spirit->displayPage($view_path, []);
            } catch (\Exception $exception) {
                $spirit->errorPage("Act met error: " . $exception->getMessage(), $exception, $this->error_page);
            }
        }
    }

    public function apiFromRequest($api_namespace = "\\")
    {
        $spirit = Spirit::getInstance();
        $act = $spirit->getRequest("act", '', "/^[A-Za-z0-9_]+$/", $error);
        if ($error !== Spirit::REQUEST_NO_ERROR) {
            $spirit->jsonForAjax(Spirit::AJAX_JSON_CODE_FAIL, "不正常的请求不理：" . $error);
            return;
        }
        try {
            $target_class = $api_namespace . $act;
            $target_class_path = $this->controller_dir . '/' . $act . '.php';
            if (!file_exists($target_class_path)) {
                throw new BaseCodedException("模块已经死了");
            }
            require_once $target_class_path;
            $api = new $target_class();
            $api->_work();
        } catch (BaseCodedException $exception) {
            $spirit->jsonForAjax(
                Spirit::AJAX_JSON_CODE_FAIL,
                ["error_code" => $exception->getCode(), "error_msg" => "请求处理异常：" . $exception->getMessage()]
            );
        }
    }

    public function restfullyHandleRequest($api_namespace = "\\")
    {
        $spirit = Spirit::getInstance();
        $request_method = $_SERVER['REQUEST_METHOD'];//HEAD,GET,POST,PUT,etc.
        $query_string = $_SERVER['QUERY_STRING'];//act=ExampleAPI&method=test
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';// /a/b/c

        $act = $this->getController($sub_paths);
        $method = $this->default_method_name;//default method
        if (isset($sub_paths[0]) && $sub_paths[0] !== '') {
            $method = $sub_paths[0];
            unset($sub_paths[0]);
        }

        try {
            $target_class = $api_namespace . $act;
            $target_class_path = $this->controller_dir . '/' . $act . '.php';
            if (!file_exists($target_class_path)) {
                throw new BaseCodedException("模块已经死了:" . $target_class_path);
            }
            require_once $target_class_path;
            $api = new $target_class();
            return call_user_func_array([$api, $method], $sub_paths);
        } catch (BaseCodedException $exception) {
            $spirit->jsonForAjax(
                Spirit::AJAX_JSON_CODE_FAIL,
                ["error_code" => $exception->getCode(), "error_msg" => "请求处理异常：" . $exception->getMessage()]
            );
        }
    }

    protected function getController(&$sub_paths = array())
    {
        $controller_name = $this->default_controller_name;
        $sub_paths = [];
        $spirit = Spirit::getInstance();
        if ($spirit->isCLI()) {
            global $argv;
            global $argc;
            $sub_paths = array();
            for ($i = 1; $i < $argc; $i++) {
                if ($i == 1) {
                    $controller_name = $argv[$i];
                } else {
                    $sub_paths[] = $argv[$i];
                }
            }
        } else {
            $controllerIndex = $this->getControllerIndex();
            $pattern = '/^\/([^\?]*)(\?|$)/';
            $r = preg_match($pattern, $controllerIndex, $matches);
            $controller_array = explode('/', $matches[1]);
            if (count($controller_array) > 0) {
                $controller_name = $controller_array[0];
                if (count($controller_array) > 1) {
                    unset($controller_array[0]);
                    $sub_paths = array_filter($controller_array, function ($var) {
                        return $var !== '';
                    });
                    $sub_paths = array_values($sub_paths);
                }
            }
        }
        if (empty($controller_name)) {
            $controller_name = $this->default_controller_name;
        }
        return $controller_name;
    }

    protected function getControllerIndex()
    {
        $prefix = $_SERVER['SCRIPT_NAME'];
        if (strpos($_SERVER['REQUEST_URI'], $prefix) !== 0) {
            if (strrpos($prefix, '/index.php') + 10 == strlen($prefix)) {
                $prefix = substr($prefix, 0, strlen($prefix) - 10);
            }
        }
        return substr($_SERVER['REQUEST_URI'], strlen($prefix));
    }

    public function handleRequestWithRoutes($api_namespace = "\\")
    {
        $parts = $this->dividePath($path_string);
        //echo $path_string.PHP_EOL;
        //var_dump($parts);

        $route = $this->router->seekRoute($path_string);
        //var_dump($route);

        if ($route[Naamah::ROUTE_PARAM_TYPE] == Naamah::ROUTE_TYPE_FUNCTION) {
            $callable = $route[Naamah::ROUTE_PARAM_TARGET];
            if (is_array($callable)) {
                $act = $this->default_controller_name;
                $method = $this->default_method_name;
                if (count($callable) > 0) {
                    $act = $callable[0];
                    if (count($callable) > 1) {
                        $method = $callable[1];
                    }
                }
                $target_class = $api_namespace . $act;
                $target_class_path = $this->controller_dir . '/' . $act . '.php';
                if (!file_exists($target_class_path)) {
                    throw new BaseCodedException("模块已经死了:" . $target_class_path);
                }
                require_once $target_class_path;
                $api = new $target_class();

                call_user_func_array([$api, $method], $parts);
            } elseif (is_callable($callable)) {
                call_user_func_array($callable, $parts);
            } else {
                throw new BaseCodedException("DIED");
            }
        } elseif ($route[Naamah::ROUTE_PARAM_TYPE] == Naamah::ROUTE_TYPE_VIEW) {
            $spirit = Spirit::getInstance();
            $target = $route[Naamah::ROUTE_PARAM_TARGET];
            $view_path = $this->view_dir . '/' . $target . ".php";
            if (!file_exists($view_path)) {
                throw new BaseCodedException("View missing", BaseCodedException::VIEW_NOT_EXISTS);
            }
            $spirit->displayPage($view_path, ["url_path_parts" => $parts]);
        } else {
            throw new BaseCodedException("Naamah Error with unknown type");
        }
    }

    protected function dividePath(&$path_string = '')
    {
        $sub_paths = array();
        $spirit = Spirit::getInstance();
        if ($spirit->isCLI()) {
            global $argv;
            global $argc;
            for ($i = 1; $i < $argc; $i++) {
                $sub_paths[] = $argv[$i];
            }
        } else {
            $path_string = $this->getControllerIndex();
            $pattern = '/^\/([^\?]*)(\?|$)/';
            $r = preg_match($pattern, $path_string, $matches);
            $controller_array = explode('/', $matches[1]);
            if (count($controller_array) > 0) {
                $sub_paths = array_filter($controller_array, function ($var) {
                    return $var !== '';
                });
                $sub_paths = array_values($sub_paths);
            }
        }
        return $sub_paths;
    }
}