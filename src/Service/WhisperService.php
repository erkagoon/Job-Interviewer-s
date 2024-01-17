<?php
// src/Service/WhisperService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class WhisperService
{
    private $client;
    private $params;

    public function __construct(HttpClientInterface $client, ParameterBagInterface $params)
    {
        $this->client = $client;
        $this->params = $params;
    }

    public function sendMessageToWhisper(UploadedFile $audio): string
    {
        $projectDir = $this->params->get('kernel.project_dir');
        $tmpDirectory = $projectDir . '\\var\\tmp\\';
        $newFileName = uniqid() . '.' . $audio->guessExtension();
        $audio->move($tmpDirectory, $newFileName);

        $data = [
            'model' => 'whisper-1',
            'file' => DataPart::fromPath($tmpDirectory.$newFileName),
        ];

        $formData = new FormDataPart($data);
        $headers = $formData->getPreparedHeaders()->toArray();
        $headers['Authorization'] = 'Bearer '.$this->params->get('API_KEY');
        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/audio/transcriptions', [
                'headers' => $headers,
                'body' => $formData->bodyToIterable(),
            ]);

            $content = $response->toArray();

            unlink($tmpDirectory.$newFileName);

            return $content['text'];
            
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
