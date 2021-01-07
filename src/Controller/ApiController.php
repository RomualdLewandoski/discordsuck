<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{
    /**
     * @Route("/getUsers", name="api_get_users")
     */
    public function getUsers(UserRepository $userRepository): JsonResponse
    {
        $obj = new stdClass();

        $users = $userRepository->findAll();
        $arr = [];
        foreach ($users as $user) {
            $tempo = new stdClass();
            $tempo->username = $user->getUsername();
            $tempo->isOnline = $user->isOnline();

            array_push($arr, $tempo);
        }
        $obj->users = $arr;
        return new JsonResponse($obj);
    }

    /**
     * @Route("/presence", name="api_presence")
     */
    public function setPresence(UserRepository $userRepository)
    {
        $username = $this->getUser()->getUsername();
        $userEntity = $userRepository->findOneBy(['username' => $username]);
        $em = $this->getDoctrine()->getManager();
        $userEntity->setLastSeen(new \DateTime('now'));
        $em->flush(); // le flush sert a appliquer les changements dans la base de donnée
        $obj = new stdClass();
        $obj->status = 200;
        return new JsonResponse($obj);
    }

    /**
     * @Route("/message/send", name="api_send_message", methods={"POST"})
     */
    public function sendMessage(UserRepository $userRepository, Request $request)
    {
        $username = $this->getUser()->getUsername();
        $userEntity = $userRepository->findOneBy(['username' => $username]);
        $content = $request->request->get('content'); // $_POST['content'] sous forme sécurisée
        $obj = new stdClass();
        if ($content == null) {
            $obj->status = 500;
            $obj->error = "Le contenu du message ne peut pas etre null";
        } else {
            $message = (new Message())
                ->setContent($content)
                ->setSendAt(new \DateTime('now'))
                ->setUser($userEntity);
            $em = $this->getDoctrine()->getManager();
            $em->persist($message);
            $em->flush(); // on insert dans la bdd
            $obj->status = "200";
        }

        return new JsonResponse($obj);
    }

    /**
     * @Route("/message/get/new/{offset}", name="api_get_new_messages")
     */
    public function retrieveNewMessage(MessageRepository $messageRepository, int $offset)
    {
        $obj = new stdClass();
        $arr = [];
        $messages = $messageRepository->getNewMessages($offset);

        foreach ($messages as $message) {
            $tempo = new stdClass();
            $tempo->content = $message->getContent();
            $tempo->sendAt = $message->getSendAt();
            $tempo->author = $message->getUser()->getUsername();
            array_push($arr, $tempo);
        }
        $obj->messages = $arr;

        return new JsonResponse($obj);
    }

    /**
     * @Route("/message/get/old/{offset}", name="api_get_old_messages")
     */
    public function retrieveOldMessage(MessageRepository $messageRepository, int $offset){
        $obj = new stdClass();
        $arr = [];
        $messages = $messageRepository->getOlderMessages($offset);

        foreach ($messages as $message) {
            $tempo = new stdClass();
            $tempo->content = $message->getContent();
            $tempo->sendAt = $message->getSendAt();
            $tempo->author = $message->getUser()->getUsername();
            array_push($arr, $tempo);
        }
        $obj->messages = $arr;

        return new JsonResponse($obj);
    }

    /**
     * @Route("/message/current", name="api_get_current_messages")
     */
    public function getCurrentMessages(MessageRepository $messageRepository){
        $obj = new stdClass();
        $arr = [];
        $messages = $messageRepository->getCurrentMessages();

        foreach ($messages as $message) {
            $tempo = new stdClass();
            $tempo->content = $message->getContent();
            $tempo->sendAt = $message->getSendAt();
            $tempo->author = $message->getUser()->getUsername();
            array_push($arr, $tempo);
        }
        $obj->messages = $arr;

        return new JsonResponse($obj);
    }

}
