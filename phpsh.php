<?php

// while($str = fread(STDIN,1000)){
//     echo "$str";
//     if ($str) {
//         $rs = eval($str);
//         echo "$rs\n";
//     }
// }

function is_expression($code)
{
    return preg_match('/^\$?\w+$|^[\d.e]$/i', $code);
}

function eval_expr($code) {
    $code = trim($code);
    $last_char = $code[strlen($code)-1];
    if (is_expression($code)) {
        $code = '$__rs = '.$code;
    }
    if ($last_char !== ';' && $last_char !== '}') {
        $code .= ';';
    }
    // var_dump($code);
    eval($code);
    if (isset($__rs)) {
        return $__rs;
    }
}

function input_and_eval()
{
    echo "phpsh > ";
    $str = fread(STDIN,1000);
    $rs = eval_expr($str);
    echo "$rs\n";
}
// eval_expr('function a(){return 1;}');
// echo $__rs;
input_and_eval();
