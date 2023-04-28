<?php

namespace AJUR;

use ReflectionClass;
use ReflectionException;
use Smarty;
use SmartyException;

class TemplatePlugins
{
    public static $already_registred = [];

    /**
     * Регистрирует метод класса TemplatePlugins как плагин.
     * Тип плагина определяется ОБЯЗАТЕЛЬНЫМ параметром `@smarty_plugin_type` в аннотации, допустимые значения: function|block|compiler|modifier.
     * Кэширование плагина определяется параметром `@smarty_plugin_cacheable` в аннотации, допустимые значения: true|false (default false)
     *
     * NB: Дополнение: сейчас можно регистрировать только статический метод из класса TemplatePlugins
     *
     * TODO: Хорошо бы дополнить этот механизм возможность регистрировать плагином произвольную функцию произвольного класса:
     * \AJUR\TemplatePlugins::register($SMARTY, [ "size_format", "MyClass::convert", "_func" => "MyClass::func" ]);
     *
     * TODO: добавить обработку параметра `@smarty_plugin_name` - имя, под которым регистрируем метод. По умолчанию совпадает с именем функции.
     *
     * Как мы можем передавать функцию:
     * 1) строка - ищется сначала в TemplatePlugins::method, потом method в глобальной области видимости среди функций
     * 2) массив [class, method]
     * 3) хорошо бы расширенный строковый формат "Class::method" -> [Class, method]
     *
     * @throws ReflectionException
     * @throws SmartyException
     */
    public static function register(Smarty $smarty, $plugins = [])
    {
        foreach ($plugins as $entity) {
            if (in_array($entity, self::$already_registred)) {
                continue;
            }

            if (is_callable([self::class, $entity])) {
                // $smarty->registerPlugin()
                /*
                 * И вот тут вылезает проблема.
                 *
                 * Дело в том, что есть 4 типа плагина: function, modifier, block и еще что-то.
                 *
                 * По вызываемой функции совершенно непонятно, к какому типу плагина она относится.
                 *
                 * Что можно с этим сделать?
                 *
                 * Я вижу только одно решение: ПАРСИТЬ АННОТАЦИИ
                 * https://www.php.net/manual/en/reflectionclass.getdoccomment.php
                 *
                 * и искать там, к примеру, запись @smarty_plugin_type <string>
                 *
                 * Важно: нужно opcache.save_comments = 1
                 *
                 */
                $comment_string = (new ReflectionClass(self::class))->getMethod($entity)->getDocComment();

                // $pattern = "#(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#";
                // preg_match_all($pattern, $comment_string, $matches, PREG_PATTERN_ORDER);

                $pattern_smarty_plugin_type = "#@smarty_plugin_type\s([a-zA-Z]+)#";
                preg_match_all($pattern_smarty_plugin_type, $comment_string, $matches_type, PREG_PATTERN_ORDER);

                $modifier_type = empty($matches_type[1]) ? 'null' : $matches_type[1][0];
                $modifier_type = TemplateHelper::getAllowedValue($modifier_type, [
                    'function', 'block', 'compiler', 'modifier'
                ], null);

                $pattern_smarty_plugin_type = "#@smarty_plugin_cacheable\s([a-zA-Z]+)#";
                preg_match_all($pattern_smarty_plugin_type, $comment_string, $matches_cacheable, PREG_PATTERN_ORDER);
                $matches_cacheable = empty($matches_cacheable[1]) ? 'true' : $matches_cacheable[1][0];

                $modifier_cacheable = TemplateHelper::getAllowedValue($matches_cacheable, [
                    'true', 'false'
                ], 'false');
                $modifier_cacheable = !(($modifier_cacheable === 'false'));

                // теперь регистрируем хэндлер:
                $smarty->registerPlugin($modifier_type, $entity, [self::class, $entity], $modifier_cacheable);

                self::$already_registred[] = $entity;
            }
        }

    }

    /**
     * @smarty_plugin_type modifier
     * @smarty_plugin_cacheable false
     *
     * @usage {$value|size_format}
     *
     * @param int $size
     * @param int $decimals
     * @param string $decimal_separator
     * @param string $thousands_separator
     * @return string
     */
    public static function size_format(int $size, int $decimals = 0, string $decimal_separator = '.', string $thousands_separator = ','):string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $index = min(floor((strlen(strval($size)) - 1) / 3), count($units) - 1);
        $number = number_format($size / pow(1000, $index), $decimals, $decimal_separator, $thousands_separator);
        return sprintf('%s %s', $number, $units[$index]);
    }

    /**
     * @smarty_plugin_type modifier
     *
     * @usage `{$value|dd}`
     *
     * @return void
     */
    public static function dd()
    {
        if (php_sapi_name() !== "cli") echo '<pre>';
        if (func_num_args()) {
            foreach (func_get_args() as $arg) {
                var_dump($arg);
            }
        }
        if (php_sapi_name() !== "cli") echo '</pre>';

        die;
    }

    /**
     * @smarty_plugin_type function
     * @smarty_plugin_name _env
     *
     * @usage `var VALUE = {_env key='ENV_VAR' default=250};`
     *
     * @param $key
     * @param $value
     * @return array|false|mixed|string|null
     */
    public static function _env($key, $value = null)
    {
        return array_key_exists($key, getenv()) ? getenv($key) : $value;
    }

}