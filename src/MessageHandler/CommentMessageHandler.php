<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Spam\Checker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;

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
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var string 
     */
    private $adminEmail;

    /**
     * CommentMessageHandler constructor.
     * @param EntityManagerInterface $entityManager
     * @param Checker $spamChecker
     * @param CommentRepository $commentRepository
     * @param MailerInterface $mailer
     * @param string $adminEmail
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Checker $spamChecker,
        CommentRepository $commentRepository,
        MailerInterface $mailer,
        string $adminEmail,
        LoggerInterface $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->commentRepository = $commentRepository;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
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
        $this->mailer->send((new NotificationEmail())
            ->subject('New comment posted')
            ->htmlTemplate('emails/comment_notification.html.twig')
            ->from($this->adminEmail)
            ->to($this->adminEmail)
            ->context(['comment' => $comment])
        );
    }
}