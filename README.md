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













--- 
@todo:

https://www.smarty.net/docs/en/resources.string.tpl