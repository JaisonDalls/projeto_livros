<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Autor;
use App\Form\AutorType;
use App\Repository\AutorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/autores', name: 'autor_')]
final class AutorController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(AutorRepository $repository): Response
    {
        return $this->render('referencia/index.html.twig', [
            'pageTitle' => 'Autores',
            'items' => array_map(
                static fn (Autor $autor): array => ['id' => $autor->getId(), 'label' => $autor->getNome()],
                $repository->findBy([], ['nome' => 'ASC'])
            ),
            'newRoute' => 'autor_new',
            'editRoute' => 'autor_edit',
            'deleteRoute' => 'autor_delete',
            'deleteTokenPrefix' => 'delete-autor',
        ]);
    }

    #[Route('/novo', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $autor = new Autor();
        $form = $this->createForm(AutorType::class, $autor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($autor);
            $entityManager->flush();
            $this->addFlash('success', 'Autor cadastrado com sucesso.');

            return $this->redirectToRoute('autor_index');
        }

        return $this->render('referencia/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'Cadastrar autor',
            'backRoute' => 'autor_index',
        ]);
    }

    #[Route('/{id<\d+>}/editar', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Autor $autor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AutorType::class, $autor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Autor atualizado com sucesso.');

            return $this->redirectToRoute('autor_index');
        }

        return $this->render('referencia/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'Editar autor',
            'backRoute' => 'autor_index',
        ]);
    }

    #[Route('/{id<\d+>}/excluir', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Autor $autor, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete-autor'.$autor->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $entityManager->remove($autor);
        $entityManager->flush();
        $this->addFlash('success', 'Autor excluído com sucesso.');

        return $this->redirectToRoute('autor_index');
    }
}
