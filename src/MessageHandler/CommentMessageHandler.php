<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Spam\Checker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    /**
     * @var Checker
     */
    private $spamChecker;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CommentRepository
     */
    private $commentRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Checker $spamChecker
     * @param CommentRepository $commentRepository
     *
     * @return void
     */
    public function __construct(EntityManagerInterface $entityManager, Checker $spamChecker, CommentRepository $commentRepository)
    {
        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->commentRepository = $commentRepository;
    }

    /**
     * @param CommentMessage $commentMessage
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     *
     * @return void
     */
    public function __invoke(CommentMessage $commentMessage)
    {
        $comment = $this->commentRepository->find($commentMessage->getId());
        if (! $comment) {
            return;
        }
        if (2 === $this->spamChecker->getSpamScope($comment, $commentMessage->getContext())) {
            $comment->setState('spam');
        } else {
            $comment->setState('published');
        }
        $this->entityManager->flush();
    }
}