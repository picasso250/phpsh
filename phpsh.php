#!php
<?php

function trim_backslash(&$code)
{
    $rs = strpos($code, '\\') !== false;
    $code = preg_replace('/\\\\./', '', $code);
    return $rs;
}

function trim_string(&$code) {
    $rs = strpos($code, '"') !== false || strpos($code, "'") !== false;
    $code = preg_replace('/"[^"]*?"|'."'[^']*?'".'/', 'X', $code);
    return $rs;
}

function trim_comment(&$code) {
    $regex = '%//.+$|/\*.+\*/%';
    $rs = preg_match($regex, $code);
    $code = trim(preg_replace($regex, '', $code));
    return $rs;
}

function trim_params(&$code) {
    $rs = strpos($code, ',') !== false;
    $code = preg_replace('/(\$?\w+|^[\d.e]+)(,\s*(\$?\w+|^[\d.e]+))+/', 'X', $code);
    return $rs;
}
function trim_function(&$code) {
    $changed = false;
    $code = preg_replace_callback('/(\w+)\s*\(\s*(\$?\w+|^[\d.e]+)\s*\)/', function ($m) use (&$changed) {
        if (in_array($m[1], array('if', 'while', 'do', 'for'))) {
            return $m[0];
        } else {
            $changed = true;
            return 'X';
        }
    }, $code);
    return $changed;
}
function is_assign($code)
{
    return strpos($code, '=') !== false && strpos($code, '==') === false;
}

function formulize($code) {
    while (true) {
        $changed = false;
        $changed |= trim_backslash($code);
        $changed |= trim_string($code);
        $changed |= trim_comment($code);
        $changed |= trim_params($code);
        $changed |= trim_function($code);
        // $changed |= trim_expression($code);
        if (!$changed) {
            break;
        }
    }
    return $code;
}

function is_expression($code)
{
    $code = formulize($code);
    return (count(explode(';', $code)) === 1 && !is_assign($code));
    return preg_match('/^\$?\w+$|^[\d.e]+$/i', $code);
}

function complete_expr($code) {
    $code = trim($code);
    if (empty($code)) {
        return array(null, null);
    }
    if (in_array($code, array('q'))) {
        return array($code, null);
    }
    $last_char = $code[strlen($code)-1];
    if (is_expression($code)) {
        $code = '$_ = '.$code;
    }
    if ($last_char !== ';' && $last_char !== '}') {
        $code .= ';';
    }
    // var_dump($code);
    return array(null, $code);
}

function execute_command($cmd)
{
    if ($cmd === 'q') {
        exit();
    }
}

$help_msg = '-- Help --
Type php commands and they will be evaluted each time you hit enter. Ex:
php> $msg = "hello world"

Evaluate expression. Ex:
php> 2 + 2
int(4)

phpsh will print any returned value and also assign the last
returned value to the variable $_.

You can enter multiline input, such as a multiline if statement.  phpsh will
accept further lines until you complete a full statement, or it will error if
your partial statement has no syntactic completion.  You may also use ^C to
cancel a partial statement.

You can use tab to autocomplete function names, global variable names,
constants, classes, and interfaces.  If you are using ctags, then you can hit
tab again after you\'ve entered the name of a function, and it will show you
the signature for that function.  phpsh also supports all the normal
readline features, like ctrl-e, ctrl-a, and history (up, down arrows).

Note that stdout and stderr from the underlying php process are line-buffered;
so  php> for ($i = 0; $i < 3; $i++) {echo "."; sleep(1);}
will print the three dots all at once after three seconds.
(echo ".
" would print one a second.)

See phpsh -h for invocation options.

-- phpsh quick command list --
    h     Display this help text.
    q     Quit (ctrl-D also quits)
';
$php_func_list = file("func.txt");
readline_completion_function(
    function ($buffer, $pos) use ($php_func_list) {
        $ret = [];
        foreach ($php_func_list as $word) {
            if (strpos($word, $buffer) === 0 && $word !== $buffer) {
                $ret[] = $word;
            }
        }
        return array_slice($ret, 0, 3);
    }
);
while (true) {
    $str = readline("phpsh > ");
    // $str = fread(STDIN, 1000);
    if ($str === false) { // EOF
        echo "\n";
        break;
    }
    if ("" === trim($str)) {
        continue;
    }
    readline_add_history($str);
    if (trim($str) === 'h') {
        echo "$help_msg\n";
        continue;
    }
    list($cmd, $code) = complete_expr($str);
    if ($cmd) {
        execute_command($cmd);
    } elseif ($code) {
        eval($code);
    }
    if (isset($_)) {
        var_dump($_);
        unset($_);
    }
}
