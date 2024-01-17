<?php
// src/Service/ChatGPTService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ChatGPTService
{
    private $client;
    private $params;

    public function __construct(HttpClientInterface $client, ParameterBagInterface $params)
    {
        $this->client = $client;
        $this->params = $params;
    }

    public function sendMessageToChatGPT(array $message): array
    {
        if ($message !== '') {
            $arrayMessage = [
                [
                    "role" => "system", 
                    "content" => "Règle: Une offre d'emploi te sera fourni lors du premier message, commence l'entretien dès ton premier message, ce message ne devra être qu'une question concise. Rôle: Tu sera recruteur d'une entreprise. Objectif: Faire passer un entretien d'embauche."
                ]
            ];

            $finalArrayMessage = array_merge($arrayMessage, $message);

            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->params->get('API_KEY'),
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    "model" => "gpt-3.5-turbo",
                    "messages" => $finalArrayMessage
                ],
            ]);

            $content = $response->toArray();

            $arrayMessagereturn = array_merge($message, [$content['choices'][0]["message"]]);

            $content["messageReturn"] = $arrayMessagereturn;

            return $content ?? 'Pas de réponse';
        }
    }
}
