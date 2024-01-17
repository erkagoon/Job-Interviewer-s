<?php
// src/Service/DebugService.php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DebugService
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function debugInTxt($debug, $fileName=false)
    {
        if(!$fileName) {
            $fileName = 'debug';
        }
        ob_start();
        var_dump($debug);
        $content_debug = ob_get_clean();

        $projectDir = $this->params->get('kernel.project_dir');
        $debugDirectory = $projectDir . '/var/debug/';

        file_put_contents($debugDirectory.$fileName.'.txt', $content_debug, FILE_APPEND);
    }
}
