<?php
// src/Service/SpeechService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SpeechService
{
    private $client;
    private $params;

    public function __construct(HttpClientInterface $client, ParameterBagInterface $params)
    {
        $this->client = $client;
        $this->params = $params;
    }

    public function sendMessageToSpeech(string $message)
    {
        if ($message !== '') {
            try {
                $response = $this->client->request('POST', 'https://api.openai.com/v1/audio/speech', [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->params->get('API_KEY'),
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        "model" => "tts-1",
                        "input" => $message,
                        "voice" => "shimmer",
                        'speed' => 1
                    ],
                ]);

                if ($response->getStatusCode() === 200) {
                    $audioContent = $response->getContent();

                    $response = new Response($audioContent);
                    $response->headers->set('Content-Type', 'audio/mpeg');
                    return $response;
                }

                return new Response('Erreur lors de la génération de l\'audio', 500);

            } catch (\Exception $e) {
                return new Response('Erreur lors du traitement : ' . $e->getMessage(), 500);
            }
        } else {
            return new Response('Message vide', 400);
        }
    }
}
