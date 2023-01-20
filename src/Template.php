<?php

namespace AJUR;

use JsonException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Smarty;
use SmartyException;

class Template
{
    const CONTENT_TYPE_RSS = 'rss';
    const CONTENT_TYPE_JSON = 'json';
    const CONTENT_TYPE_404 = '404';
    const CONTENT_TYPE_HTML = 'html';

    /**
     * Available content types
     */
    const CONTENT_TYPES = [
        self::CONTENT_TYPE_RSS      =>  'Content-type: application/xml',
        self::CONTENT_TYPE_JSON     =>  'Content-Type: application/json; charset=utf-8',
        self::CONTENT_TYPE_404      =>  "HTTP/1.0 404 Not Found",
        self::CONTENT_TYPE_HTML     =>  "Content-Type: text/html; charset=utf-8",
        '_'                         =>  "Content-Type: text/html; charset=utf-8"
    ];

    /**
     * @var string
     */
    public string $render_type = self::CONTENT_TYPE_HTML;

    /**
     * Titles
     *
     * @var array
     */
    public array $titles = [];

    /**
     * @var Smarty
     */
    private Smarty $smarty;

    private array $REQUEST = [];

    private array $template_vars = [];

    private string $force_redirect = '';

    private string $template_file = '';

    private array $JSON = [];

    /**
     * @var LoggerInterface|NullLogger
     */
    private $logger;

    private array $rss = [];

    /**
     * call: smarty, $_REQUEST, $options, $logger
     *
     * @param Smarty $smarty_instance
     * @param array $request
     * @param array $options
     * @param LoggerInterface|null $logger
     */
    public function __construct(Smarty $smarty_instance, array $request = [], array $options = [], LoggerInterface $logger = null)
    {
        $this->logger = is_null($logger) ? new NullLogger() : $logger;

        if (array_key_exists('type', $options)) {
            $this->setRenderType( self::getAllowedValue($options['type'], ['rss', 'json', '404', 'html'], 'html') );
        }

        if (array_key_exists('file', $options)) {
            $this->setTemplate($options['file']);
        }

        if (array_key_exists('source', $options)) {
            $this->setTemplate($options['source']);
        }

        $this->smarty = $smarty_instance;
        $this->REQUEST = $request;
    }

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
    public function setRenderType(string $type)
    {
        $this->render_type = $type;
    }

    /**
     * Отдает элемент из массива $_REQUEST
     *
     * @param $key
     * @param string $default
     * @return mixed|string
     */
    public function request($key, string $default = '')
    {
        return array_key_exists($key, $this->REQUEST) ? $this->REQUEST[$key] : $default;
    }

    /**
     * Сохраняет в репозитории класса данные, которые потом нужно сделать assign в смарти
     *
     * @param string $key
     * @param $value
     * @return void
     */
    public function assign(string $key, $value)
    {
        $this->template_vars[ $key ] = $value;
    }

    /**
     *
     *
     * @param string $url
     */
    public function setRedirect(string $url = '/')
    {
        $this->force_redirect = $url;
    }

    /**
     * Устанавливает файл шаблона
     *
     * @param string $filename
     * @return void
     */
    public function setTemplate(string $filename)
    {
        $this->template_file = $filename;
    }

    /**
     * ?
     *
     * @param $key
     * @param $value
     * @return void
     */
    public function setRSS($key, $value)
    {
        $this->rss[ $key ] = $value;
    }


    /**
     * Заменяет кавычки-лапки на html-entities
     *
     * @param $string
     * @return array|string|string[]
     */
    public static function escapeQuotes($string)
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
     * Посылает header, соответствующий типу контента
     * Send header of given content type
     *
     * @param string $type
     * @return void
     */
    public function sendHeader(string $type = '')
    {
        $content_type = empty($type) ? self::CONTENT_TYPES['_'] : (
        array_key_exists($type, self::CONTENT_TYPES) ? self::CONTENT_TYPES[$type] : self::CONTENT_TYPES['_']
        );
        header( $content_type );
    }

    /**
     * @throws SmartyException
     * @throws JsonException
     */
    public function render($clean = false)
    {
        if ($this->render_type === self::CONTENT_TYPE_JSON) {
            return json_encode($this->template_vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        }

        foreach ($this->template_vars as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        $rendered = '';

        if (!empty($this->template_file)) {
            $rendered = $this->smarty->fetch($this->template_file);
        }

        if ($clean) {
            $this->clean();
        }

        // А когда ставится header по MIMETYPE ?

        return $rendered;
    }

    public function clean()
    {
        $this->smarty->clear_all_assign(); // polymorph call
    }

    /**
     * helper
     *
     * @param $json
     * @return void
     */
    public function assignJSON($json)
    {
        foreach ($json as $key => $value) {
            $this->assign($key, $value);
        }
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

    private static function getAllowedValue( $data, $allowed_values_array, $default_value = NULL)
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