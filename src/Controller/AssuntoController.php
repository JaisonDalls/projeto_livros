<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Assunto;
use App\Form\AssuntoType;
use App\Repository\AssuntoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/assuntos', name: 'assunto_')]
final class AssuntoController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(AssuntoRepository $repository): Response
    {
        return $this->render('referencia/index.html.twig', [
            'pageTitle' => 'Assuntos',
            'items' => array_map(
                static fn (Assunto $assunto): array => ['id' => $assunto->getId(), 'label' => $assunto->getDescricao()],
                $repository->findBy([], ['descricao' => 'ASC'])
            ),
            'newRoute' => 'assunto_new',
            'editRoute' => 'assunto_edit',
            'deleteRoute' => 'assunto_delete',
            'deleteTokenPrefix' => 'delete-assunto',
        ]);
    }

    #[Route('/novo', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $assunto = new Assunto();
        $form = $this->createForm(AssuntoType::class, $assunto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($assunto);
            $entityManager->flush();
            $this->addFlash('success', 'Assunto cadastrado com sucesso.');

            return $this->redirectToRoute('assunto_index');
        }

        return $this->render('referencia/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'Cadastrar assunto',
            'backRoute' => 'assunto_index',
        ]);
    }

    #[Route('/{id<\d+>}/editar', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Assunto $assunto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AssuntoType::class, $assunto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Assunto atualizado com sucesso.');

            return $this->redirectToRoute('assunto_index');
        }

        return $this->render('referencia/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'Editar assunto',
            'backRoute' => 'assunto_index',
        ]);
    }

    #[Route('/{id<\d+>}/excluir', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Assunto $assunto, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete-assunto'.$assunto->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $entityManager->remove($assunto);
        $entityManager->flush();
        $this->addFlash('success', 'Assunto excluído com sucesso.');

        return $this->redirectToRoute('assunto_index');
    }
}
