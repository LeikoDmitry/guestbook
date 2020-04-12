<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConferenceController extends AbstractController
{
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
     * @return Response
     */
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository )
    {
        $offset = max(0, $request->query->getInt('offset', 0));
        $comments = $commentRepository->getCommentPaginator($conference, $offset);
        $prev = $offset - CommentRepository::PAGINATOR_PER_PAGE;
        $next = min(count($comments), $offset + CommentRepository::PAGINATOR_PER_PAGE);

        return $this->render('conference/show.html.twig', compact('conference', 'comments', 'prev', 'next'));
    }
}
