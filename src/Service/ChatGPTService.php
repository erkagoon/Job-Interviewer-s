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
                    "content" => "ROLE : Tu es un recruteur. Ton objectif est de faire passer un entretien d'embauche fictif.
                    COMPORTEMENT : Met toi dans la peau du recruteur et parle en tant que sa personne, ne te comporte plus comme un chatbot. 
                    DEROULEMENT: Une offre d'emploi te sera fourni lors du premier message, commence l'entretien dès ton premier message, ce message ne devra être qu'une question concise."
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
