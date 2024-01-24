<?php

namespace AJUR;

use JsonException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Smarty;
use SmartyException;

class Template implements TemplateInterface
{
    /**
     * @var Smarty
     */
    private Smarty  $smarty;

    /**
     * Тип рендера
     * @var string
     */
    public string $render_type = self::CONTENT_TYPE_HTML;

    /**
     * Titles
     *
     * @var array
     */
    public array    $titles = [];

    public array    $breadcumbs = []; //@todo like as title (set of ['uri' => ..., 'title' => ...], на основе его можно строить и title

    private array   $REQUEST = [];

    private array   $template_vars = [];

    private string  $template_file = '';

    private array   $JSON = [];

    public \stdClass $options;

    public array    $redirect = [

    ];

    public string   $force_redirect = '';
    public int      $force_redirect_code = 200;

    public string   $raw_content = '';

    /**
     * @var LoggerInterface|NullLogger
     */
    private $logger;

    public function __construct(Smarty $smarty_instance, array $request = [], array $options = [], LoggerInterface $logger = null)
    {
        $this->logger = is_null($logger) ? new NullLogger() : $logger;
        $this->options = new \stdClass();

        if (array_key_exists('type', $options)) {
            $this->options->renderType = $render_type = TemplateHelper::getAllowedValue($options['type'], ['rss', 'json', '404', 'html'], 'html');
            $this->setRenderType( $render_type );
        }

        /*
         * PHP8: activate convert warnings about undefined or null template vars -> to notices
         */
        $smarty_instance->muteUndefinedOrNullWarnings();

        if (array_key_exists('file', $options)) {
            $this->setTemplate($options['file']);
        }

        if (array_key_exists('source', $options)) {
            $this->setTemplate($options['source']);
        }

        if (array_key_exists('force_assign', $options) && $options['force_assign']) {
            $this->options->forceAssign = true;
        }

        $this->smarty = $smarty_instance;
        $this->REQUEST = $request;
    }

    public function getSmartyInstance():Smarty
    {
        return $this->smarty;
    }

    public function setRenderType(string $type)
    {
        $this->render_type = $type;
    }

    public function request($key, string $default = '')
    {
        return array_key_exists($key, $this->REQUEST) ? $this->REQUEST[$key] : $default;
    }

    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->assign($k, $v);
            }
        } else {
            $this->template_vars[ $key ] = $value;
        }
    }

    public function setRedirect(string $uri = '/', int $code = 200)
    {
        $this->force_redirect = $uri;
        $this->force_redirect_code = $code;

        $this->redirect = [
            'uri'   =>  $uri,
            'code'  =>  $code
        ];

        $this->setRenderType( self::CONTENT_TYPE_REDIRECT );
    }

    public function isRedirect():bool
    {
        return !empty($this->redirect);
    }

    public function makeRedirect(string $uri = null, int $code = null, bool $replace_headers = true)
    {
        $_uri = is_null($uri) ? (array_key_exists('uri', $this->redirect) ? $this->redirect['uri'] : null) : $uri;
        $_code = is_null($code) ? (array_key_exists('code', $this->redirect) ? $this->redirect['code'] : null) : $code;

        if (empty($_uri)) {
            return false;
        }

        if (empty($_code)) {
            $_code = 200;
        }

        if ((strpos( $_uri, "http://" ) !== false || strpos( $_uri, "https://" ) !== false)) {
            header("Location: {$_uri}", $replace_headers, $_code);
            exit(0);
        }

        $scheme = (TemplateHelper::is_ssl() ? "https://" : "http://");
        $scheme = str_replace('://', '', $scheme);

        header("Location: {$scheme}://{$_SERVER['HTTP_HOST']}{$_uri}", $replace_headers, $_code);
        exit(0);
    }

    public function setTemplate($filename = null)
    {
        $this->template_file = empty($filename) ? '' : $filename;
    }

    public function sendHeader(string $type = '')
    {
        $content_type
            = empty($type)
            ? self::CONTENT_TYPES['_']
            : ( array_key_exists($type, self::CONTENT_TYPES)
                ? self::CONTENT_TYPES[$type]
                : self::CONTENT_TYPES['_']
            );
        header( $content_type );
    }

    public function clean($clear_cache = true): bool
    {
        $this->smarty->clearAllAssign();

        $this->template_vars = [];

        if (!$clear_cache) {
            return true;
        }

        foreach ($this->smarty->getTemplateVars() as $k => $v) {
            $this->smarty->clearCache($k);
        }

        return true;
    }

    public function assignJSON(array $json)
    {
        foreach ($json as $key => $value) {
            $this->assign($key, $value);
        }
    }

    public function assignRAW(string $html)
    {
        $this->raw_content = $html;
        $this->setRenderType(TemplateInterface::CONTENT_TYPE_RAW);
    }

    public function getTemplateVars($varName = null)
    {
        return
            $varName
            ? ( array_key_exists($varName, $this->template_vars) ? $this->template_vars[$varName] : '')
            : $this->template_vars;
    }

    public function getAssignedVars($varName = null)
    {
        return $this->smarty->getTemplateVars($varName);
    }

    public function render($send_header = false, $clean = false)
    {
        if ($this->render_type === self::CONTENT_TYPE_REDIRECT) {
            return null;
        }

        if ($this->render_type === self::CONTENT_TYPE_JSON) {
            return json_encode($this->template_vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        }

        if ($this->render_type === self::CONTENT_TYPE_RAW) {
            return $this->raw_content;
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

        if ($send_header) {
            $this->sendHeader($this->render_type);
        }

        return $rendered;
    }


    public function addTitle(string $title_part)
    {
        $this->titles[] = $title_part;
    }


    public function makeTitle(string $separator = " ", bool $sort = true, bool $reverse_order = false, bool $clean_extra_spaces = true):string
    {
        $t = $this->titles;

        if ($sort) {
            ksort($t);
        }

        if ($reverse_order) {
            $t = array_reverse($t);
        }

        $t = implode($separator, $t);

        if ($clean_extra_spaces) {
            $t = preg_replace('/\s+/', ' ', $t);
        }

        return $t;
    }


}