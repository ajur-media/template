# AJUR Templater

### How to use?

```php
require_once __DIR__ . '/vendor/autoload.php';

$SMARTY = new Smarty();
$SMARTY->setTemplateDir( __DIR__ );
$SMARTY->setCompileDir( __DIR__ . '/cache/');
$SMARTY->setForceCompile(true);

// Global + inner templates

$global = new \AJUR\Template($SMARTY, $_REQUEST);
$global->setTemplate('1.tpl');

$inner = new \AJUR\Template($SMARTY);
$inner->setTemplate("2.tpl");
$inner->assign('value_1', 'FOO');
$inner->assign('value_2', 'BAR');

$global->assign("content", $inner->render());

echo $global->render();

// Global + include secondary template:

$global = new \AJUR\Template($SMARTY);
$global->setTemplate('0.tpl');
$global->assign("file", "2.tpl");
$global->assign('value_1', 'FOO');
$global->assign('value_2', 'BAR');

echo $global->render();

// JSON

$global = new \AJUR\Template($SMARTY);
$global->assign("file", "2.tpl");
$global->assign('value_1', 'FOO');
$global->assign('value_2', 'BAR');

// or use helper

$global->assignJSON([
    'file'      =>  '2.tpl',
    'value_1'   =>  'FOO',
    'value_2'   =>  'BAR',
]);

$global->setRenderType(\AJUR\Template::CONTENT_TYPE_JSON);

echo $global->render();

```

### Плагины шаблонизатора

Для корректной работы плагина нужен параметр `opcache.save_comments = 1` в php.ini 

Передача параметров в (регистрируемые) плагины:

#### modifier

Есть два способа и зависят они от способа регистрации функции:

А) через перечисление аргументов (причем опущенные аргументы принимают значение по умолчанию)

```php
{$size|size_format:decimals:separator:separator}
```

Тогда функция должна быть определена так:
```php
public static function size_format(int $size, int $decimals = 0, string $decimal_separator = '.', string $thousands_separator = ','):string;
```

Б) через массив аргументов
```php
{$size|size_format:[3,',','-']}
```
Тогда функция должна быть определена иначе:
```php
public static function sf(int $size, array $params):string 
{
    $decimals = $params['decimals'] ?? 3;
    $decimal_separator = $params['decimal_separator'] ?? '.';
    $thousands_separator = $params['thousands_separator'] ?? ',';
    // ...
}
```

#### function

Используется так:
```php
{sum a=11 b=14}
```

Метод должен быть определен ТОЛЬКО так:
```php
function sum($params)
{
    return ($params['a'] ?? 0) + ($params['b'] ?? 0);
}
```












--- 
@todo:

https://www.smarty.net/docs/en/resources.string.tpl