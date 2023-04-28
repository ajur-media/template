## Архив методов 

```php
public static function registerHelpers(Smarty $smarty, $helpers = [])
    {
        if (!is_array($helpers)) {
            $helpers = explode(' ', $helpers);
        }



        if (in_array('_env', $helpers)) {
            $smarty->registerPlugin("function", "_env", static function($params, $smarty) {
                $default = (empty($params['default'])) ? '' : $params['default'];
                if (empty($params['key'])) {
                    return $default;
                }
                $k = getenv($params['key']);

                return ($k === false) ? $default : $k;
            }, false);
        }

        if (in_array('dd', $helpers)) {
            $smarty->registerPlugin("modifier", "dd", static function($params) {
                if (php_sapi_name() !== "cli") {
                    echo '<pre>';
                }
                if (!empty($params)) {
                    foreach ($params as $arg) {
                        var_dump($arg);
                    }
                }
                if (php_sapi_name() !== "cli") {
                    echo '</pre>';
                }
                die;
            }, false);
        }
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
```