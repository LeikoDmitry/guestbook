<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentTypeFormType;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\Spam\Checker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use RuntimeException;

class ConferenceController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="homepage")
     *
     * @param ConferenceRepository $conferenceRepository
     * @return Response
     */
    public function index(ConferenceRepository $conferenceRepository)
    {
        $conferences = $conferenceRepository->findAll();

        return $this->render('conference/index.html.twig', compact('conferences'));
    }

    /**
     * @Route("/conference/{slug}", name="conference")
     *
     * @param Request $request
     * @param Conference $conference
     * @param CommentRepository $commentRepository
     * @param Checker $checker
     * @param $photoDir string
     * @param LoggerInterface $logger
     *
     * @return Response
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
        Checker $checker,
        string $photoDir,
        LoggerInterface $logger
    )
    {
        $comment = new Comment();
        $form = $this->createForm(CommentTypeFormType::class, $comment);
        $commentForm = $form->createView();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);
            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(8)) . '.' . $photo->guessExtension();
                try {
                    $photo->move($photoDir, $filename);
                } catch (FileException $fileException) {
                    $logger->debug($fileException->getMessage());
                }
                $comment->setPhotoFilename($filename);
            }
            $this->entityManager->persist($comment);
            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            if (2 === $checker->getSpamScope($comment, $context)) {
                throw new RuntimeException('Blatant spam, go away!');
            }
            $this->entityManager->flush();
            $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }
        $offset = max(0, $request->query->getInt('offset', 0));
        $comments = $commentRepository->getCommentPaginator($conference, $offset);
        $prev = $offset - CommentRepository::PAGINATOR_PER_PAGE;
        $next = min(count($comments), $offset + CommentRepository::PAGINATOR_PER_PAGE);

        return $this->render('conference/show.html.twig', compact(
            'conference',
            'comments',
            'prev',
            'next',
            'commentForm'
        ));
    }
}
