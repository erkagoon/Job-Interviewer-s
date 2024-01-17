<?php
// src/Controller/AudioController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\WhisperService;

class AudioController extends AbstractController
{
    private $whisperService;

    public function __construct(WhisperService $whisperService)
    {
        $this->whisperService = $whisperService;
    }

    
}
