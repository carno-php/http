<?php

assert_options(ASSERT_CALLBACK, function ($file, $line, $help, $code) {
    echo '-------------', PHP_EOL;
    echo 'ASSERT FAILED', PHP_EOL;
    echo 'in ', $file, ':', $line, PHP_EOL;
    echo '-------------', PHP_EOL;
    echo $code, ' #', $help, PHP_EOL;
    exit(1);
});
