<?php

namespace App\Controllers;

use Symfony\Component\Process\Process;

class CodeController
{
    public function compileAndReturn($code): string
    {
        $filePath = tempdir() . 'code.php';
        file_put_contents($filePath, $code);

        $process = new Process(["php $filePath"]);
        $process->run();

//        unlink($filePath);

        if ($process->isSuccessful()) {
            return json_encode([
                'result' => $process->getOutput(),
            ]);
        } else {
            return json_encode([
                'result' => [],
                'error' => $process->getErrorOutput(),
            ]);
        }
    }
}