<?php

/**
 * This file parse the css file and output a list of constants to use in your projects
 * Just copy/paste and good to go!
 */

$re = '/(.*) /m';
$str = file_get_contents(dirname(__DIR__) . '/node_modules/admini/static/icons.txt');

preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);

// Print the entire match result
// var_dump($matches);

$constants = '';
foreach ($matches as $row) {
    $icon = $row[1];
    $uc = strtoupper(str_replace('-', '_', $icon));
    $firstChar = $icon[0];

    // Prefix with X invalid constants
    if (is_numeric($firstChar)) {
        $uc = "X_$uc";
    }
    if ($uc == "CLASS") {
        $uc = "X_CLASS";
    }
    $constants .= "    const $uc = '$icon';\n";
}
echo $constants;

$constants = rtrim($constants);
$code = <<<PHP
<?php

namespace LeKoala\Admini;

/**
 * Helper class to use material icons from your php code
 * This class is generated, do not edit manually!
 */
class MaterialIcons
{
$constants
}

PHP;

file_put_contents(dirname(__DIR__) . '/src/MaterialIcons.php', $code);
