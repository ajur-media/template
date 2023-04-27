<?php

namespace AJUR;

use Smarty;
use SmartyException;

class TemplateHelper
{
    public const HTTP_CODES = array(
        100 => "HTTP/1.1 100 Continue",
        101 => "HTTP/1.1 101 Switching Protocols",
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        203 => "HTTP/1.1 203 Non-Authoritative Information",
        204 => "HTTP/1.1 204 No Content",
        205 => "HTTP/1.1 205 Reset Content",
        206 => "HTTP/1.1 206 Partial Content",
        300 => "HTTP/1.1 300 Multiple Choices",
        301 => "HTTP/1.1 301 Moved Permanently",
        302 => "HTTP/1.1 302 Found",
        303 => "HTTP/1.1 303 See Other",
        304 => "HTTP/1.1 304 Not Modified",
        305 => "HTTP/1.1 305 Use Proxy",
        307 => "HTTP/1.1 307 Temporary Redirect",
        400 => "HTTP/1.1 400 Bad Request",
        401 => "HTTP/1.1 401 Unauthorized",
        402 => "HTTP/1.1 402 Payment Required",
        403 => "HTTP/1.1 403 Forbidden",
        404 => "HTTP/1.1 404 Not Found",
        405 => "HTTP/1.1 405 Method Not Allowed",
        406 => "HTTP/1.1 406 Not Acceptable",
        407 => "HTTP/1.1 407 Proxy Authentication Required",
        408 => "HTTP/1.1 408 Request Time-out",
        409 => "HTTP/1.1 409 Conflict",
        410 => "HTTP/1.1 410 Gone",
        411 => "HTTP/1.1 411 Length Required",
        412 => "HTTP/1.1 412 Precondition Failed",
        413 => "HTTP/1.1 413 Request Entity Too Large",
        414 => "HTTP/1.1 414 Request-URI Too Large",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        416 => "HTTP/1.1 416 Requested range not satisfiable",
        417 => "HTTP/1.1 417 Expectation Failed",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
        502 => "HTTP/1.1 502 Bad Gateway",
        503 => "HTTP/1.1 503 Service Unavailable",
        504 => "HTTP/1.1 504 Gateway Time-out"
    );

    /**
     * Заменяет кавычки-лапки на html-entities
     *
     * @param string $string
     * @return array|string|string[]
     */
    public static function escapeQuotes(string $string):string
    {
        return str_replace(['«', '»'], ['&laquo;', '&raquo;'], $string);
    }

    /**
     * ?
     * Сортирует и реверсирует порядок Titles
     *
     * @param $titles
     * @return array
     */
    public static function reverseTitles($titles): array
    {
        $t = $titles;
        ksort($t);
        return array_reverse($t);
    }

    /**
     * Регистрирует функцию _env в смарти
     * Для использования:
     * `var PARAM = {_env key='PARAM' default=250}`
     *
     * @throws SmartyException
     */
    public static function registerEnvFunction(Smarty $smarty)
    {
        $smarty->registerPlugin("function", "_env", static function($params, $smarty) {
            $default = (empty($params['default'])) ? '' : $params['default'];
            if (empty($params['key'])) {
                return $default;
            }
            $k = getenv($params['key']);

            return ($k === false) ? $default : $k;
        }, false);
    }

    /**
     *
     * @return bool
     */
    public static function is_ssl():bool
    {
        if (isset($_SERVER['HTTPS'])) {
            if ('on' == strtolower($_SERVER['HTTPS'])) {
                return true;
            }
            if ('1' == $_SERVER['HTTPS']) {
                return true;
            }
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        }
        return false;
    }

    /**
     * Проверяет заданную переменную на допустимость (на основе массива допустимых значений)
     * и если находит - возвращает её. В противном случае возвращает $default_value (по умолчанию NULL).
     *
     * @param $data
     * @param $allowed_values_array
     * @param $default_value
     * @return null|mixed
     */
    public static function getAllowedValue( $data, $allowed_values_array, $default_value = NULL)
    {
        if (empty($data)) {
            return $default_value;
        } else {
            $key = array_search($data, $allowed_values_array);
            return ($key !== FALSE )
                ? $allowed_values_array[ $key ]
                : $default_value;
        }
    }
}