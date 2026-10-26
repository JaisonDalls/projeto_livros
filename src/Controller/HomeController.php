<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AssuntoRepository;
use App\Repository\LivroRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(Request $request, LivroRepository $livroRepository, AssuntoRepository $assuntoRepository): Response
    {
        $term = trim($request->query->getString('q'));
        $subjectValue = $request->query->get('assunto');
        $subjectId = is_string($subjectValue) && ctype_digit($subjectValue) ? (int) $subjectValue : null;
        $sort = $request->query->getString('ordem', 'titulo_asc');
        $allowedSorts = ['titulo_asc', 'titulo_desc', 'ano_asc', 'ano_desc'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'titulo_asc';
        }

        return $this->render('home/index.html.twig', [
            'books' => $livroRepository->search($term, $subjectId, $sort),
            'subjects' => $assuntoRepository->findBy([], ['descricao' => 'ASC']),
            'searchTerm' => $term,
            'selectedSubject' => $subjectId,
            'sortOrder' => $sort,
        ]);
    }
}
