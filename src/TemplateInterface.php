<?php

namespace AJUR;

use Psr\Log\LoggerInterface;
use Smarty;

interface TemplateInterface
{
    const CONTENT_TYPE_RSS  = 'rss';
    const CONTENT_TYPE_JSON = 'json';
    const CONTENT_TYPE_404  = '404';
    const CONTENT_TYPE_HTML = 'html';
    const CONTENT_TYPE_JS   = 'js'; // 'application/javascript'

    /**
     * Available content types
     */
    const CONTENT_TYPES = [
        self::CONTENT_TYPE_RSS      =>  'Content-type: application/xml',
        self::CONTENT_TYPE_JSON     =>  'Content-Type: application/json; charset=utf-8',
        self::CONTENT_TYPE_404      =>  "HTTP/1.0 404 Not Found",
        self::CONTENT_TYPE_HTML     =>  "Content-Type: text/html; charset=utf-8",
        self::CONTENT_TYPE_JS       =>  "Content-Type: text/javascript;charset=utf-8",
        '_'                         =>  "Content-Type: text/html; charset=utf-8",
    ];

    /**
     * call: smarty, $_REQUEST, $options, $logger
     *
     * @param Smarty $smarty_instance
     * @param array $request
     * @param array $options
     * @param LoggerInterface|null $logger
     */
    public function __construct(Smarty $smarty_instance, array $request = [], array $options = [], LoggerInterface $logger = null);

    /**
     * Возвращает инстанс Smarty текущего класса
     *
     * @return Smarty
     */
    public function getSmartyInstance():Smarty;

    /**
     * Устанавливает возвращаемый тип данных
     *
     * const CONTENT_TYPE_RSS = 'rss';
     * const CONTENT_TYPE_JSON = 'json';
     * const CONTENT_TYPE_404 = '404';
     * const CONTENT_TYPE_HTML = 'html';
     *
     * @param string $type
     * @return void
     */
    public function setRenderType(string $type);

    /**
     * Отдает элемент из массива $_REQUEST
     *
     * @param $key
     * @param string $default
     * @return mixed|string
     */
    public function request($key, string $default = '');

    /**
     * Сохраняет в репозитории класса данные, которые потом нужно передать (assign) в SMARTY-инстанс
     *
     * @param string|array $key
     * @param $value
     * @return void
     */
    public function assign($key, $value = null);

    /**
     * Устанавливает параметры редиректа
     *
     * @param string $uri
     * @param int $code
     */
    public function setRedirect(string $uri = '/', int $code = 200);

    /**
     * Проверяет, указаны ли параметры редиректа
     *
     * @return bool
     */
    public function isRedirect():bool;

    /**
     * Выполняет редирект, используя переданные параметры или переданные через setRedirect() параметры редиректа
     *
     * @param string|null $uri
     * @param int|null $code
     * @param bool $replace_headers
     * @return false|void
     */
    public function makeRedirect(string $uri = null, int $code = null, bool $replace_headers = true);

    /**
     * Устанавливает файл шаблона.
     * Без него возможен только рендер Template::CONTENT_TYPE_JSON
     *
     * @param string|null $filename
     * @return void
     */
    public function setTemplate($filename = null);

    /**
     * Посылает header, соответствующий типу контента
     * Send header of given content type
     *
     * @param string $type
     * @return void
     */
    public function sendHeader(string $type = '');

    /**
     * helper
     *
     * @param array $json
     * @return void
     */
    public function assignJSON(array $json);

    /**
     * Очищает все установленные и переданные в шаблон переменные
     * как из репозитория шаблона, так и из Smarty
     *
     * @return void
     */
    public function clean();

    /**
     * Добавляет title в цепочку title'ов
     *
     * @param string $title_part
     * @return void
     */
    public function addTitle(string $title_part);

    /**
     * Строит результирующий title страницы с учетом вложенности.
     * Может быть использован также для остройки og:title
     *
     * @param string $separator
     * @param bool $sort
     * @param bool $reverse_order
     * @return string
     */
    public function makeTitle(string $separator = " ", bool $sort = true, bool $reverse_order = false);

}