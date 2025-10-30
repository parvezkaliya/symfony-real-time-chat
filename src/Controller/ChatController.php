<?php
// src/Controller/ChatController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/chat', name: 'app_chat')]
    public function index(): Response
    {
        // Provide available server ports to the view, easy to change later
        return $this->render('chat/index.html.twig', [
            'servers' => [8080, 8081, 8082],
        ]);
    }
}
