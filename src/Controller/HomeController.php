<?php
// src/Controller/HomeController.php
namespace App\Controller;

// Importation des classes nécessaires
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ChatGPTService;
use App\Service\WhisperService;
use App\Service\SpeechService;
use App\Service\DebugService;
use Symfony\Component\HttpFoundation\JsonResponse;

class HomeController extends AbstractController
{
    // Déclaration des propriétés pour les services injectés
    private $chatGPTService;
    private $whisperService;
    private $speechService;
    public $debugService;

    // Constructeur pour injecter les services nécessaires
    public function __construct(ChatGPTService $chatGPTService, WhisperService $whisperService, SpeechService $speechService, DebugService $debugService)
    {
        // Initialisation des services
        $this->chatGPTService = $chatGPTService;
        $this->whisperService = $whisperService;
        $this->speechService = $speechService;
        $this->debugService = $debugService;
    }

    /**
     * @Route("/home", name="Home")
     * Route pour la page d'accueil
     */
    public function index(): Response
    {
        // Rendu de la vue pour la page d'accueil
        return $this->render('home/index.html.twig');
    }

    /**
     * @Route("/entretien", name="entretien")
     * Route pour gérer un prompt utilisateur
     */
    public function handlePrompt(Request $request): Response
    {
        // Récupération du prompt de l'utilisateur depuis la requête
        $prompt = $request->request->get('prompt');

        // Création d'un tableau pour structurer le message
        $arrayMessage = [
            [
                "role" => "user", 
                "content" => $prompt
            ]
        ];

        // Vérification si le prompt n'est pas vide
        if($prompt !== '') {
            try {
                // Envoi du message à ChatGPT et récupération de la réponse
                $response = $this->chatGPTService->sendMessageToChatGPT($arrayMessage);
            } catch (\Exception $e) {
                // Gestion des erreurs
                return new Response('Erreur lors de la communication avec ChatGPT: ' . $e->getMessage());
            }
            
            // Encodage de la réponse en JSON et rendu de la vue avec la réponse
            $messagereturn = json_encode($response['messageReturn']);
            return $this->render('home/response.html.twig', ['response' => $response['choices'][0]["message"]["content"], 'messageReturn' => $messagereturn]);
        }
        else {
            // Gestion d'un prompt vide
            $messagereturn = "{}";
            return $this->render('home/response.html.twig', ['response' => 'Il semble que les données fournies ne sont pas adaptées...', 'messageReturn' => $messagereturn]);
        }
    }

    /**
     * @Route("/handle-ajax-message", name="handle_ajax_message")
     * Route pour gérer les messages AJAX
     */
    public function handleAjaxMessage(Request $request): Response
    {
        // Décodage du contenu JSON de la requête
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'];

        try {
            // Envoi du message utilisateur à ChatGPT et récupération de la réponse
            $response = $this->chatGPTService->sendMessageToChatGPT($userMessage);
        } catch (\Exception $e) {
            // Gestion des erreurs
            return new Response('Erreur lors de la communication avec ChatGPT: ' . $e->getMessage());
        }

        // Retourne la réponse sous forme de JSON
        return new JsonResponse($response);
    }

    /**
     * @Route("/upload-audio", name="upload_audio")
     * Route pour gérer l'upload d'un fichier audio
     */
    public function uploadAudio(Request $request): Response
    {
        // Récupération du fichier audio depuis la requête
        $audioFile = $request->files->get('audioFile');

        // Vérification de l'existence du fichier
        if (!$audioFile) {
            // Gestion de l'absence de fichier
            return new Response('Aucun fichier audio envoyé', 400);
        }

        try {
            // Traitement du fichier audio avec Whisper et récupération de la réponse
            $response = $this->whisperService->sendMessageToWhisper($audioFile);
        } catch (\Exception $e) {
            // Gestion des erreurs
            return new Response('Erreur lors du traitement du fichier audio: ' . $e->getMessage(), 500);
        }

        // Retourne la réponse
        return new Response($response);
    }

    /**
     * @Route("/return-audio", name="return_audio")
     * Route pour retourner une réponse audio
     */
    public function returnAudio(Request $request): Response
    {
        // Décodage du contenu JSON de la requête
        $data = json_decode($request->getContent(), true);
        $message = $data['message']['content'];

        // Vérification de l'existence du message
        if (!$message) {
            // Gestion d'un message vide
            return new Response('Aucun message reçu', 400);
        }

        try {
            // Traitement du message avec le service Speech pour obtenir une réponse audio
            $fileResponse = $this->speechService->sendMessageToSpeech($message);

            // Retourne la réponse audio
            return $fileResponse;
        } catch (\Exception $e) {
            // Gestion des erreurs
            return new Response('Erreur lors du traitement du fichier audio: ' . $e->getMessage(), 500);
        }
    }
}
