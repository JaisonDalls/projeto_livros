<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use App\Form\LivroType;
use App\Repository\AssuntoRepository;
use App\Repository\AutorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\UnicodeString;

#[Route('/livros', name: 'livro_')]
final class LivroController extends AbstractController
{
    #[Route('/novo', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        AutorRepository $autorRepository,
        AssuntoRepository $assuntoRepository,
    ): Response
    {
        $livro = new Livro();
        $form = $this->createForm(LivroType::class, $livro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncAuthors($livro, $form->get('autoresTexto')->getData() ?? '', $autorRepository, $entityManager);
            $this->syncSubjects($livro, $form->get('assuntosTexto')->getData() ?? '', $assuntoRepository, $entityManager);
            $entityManager->persist($livro);
            $entityManager->flush();

            $this->addFlash('success', 'Livro cadastrado com sucesso.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('livro/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'Cadastrar livro',
            'submitLabel' => 'Cadastrar livro',
            'autoresDisponiveis' => array_map(
                static fn (Autor $autor): string => $autor->getNome(),
                $autorRepository->findBy([], ['nome' => 'ASC'])
            ),
            'assuntosDisponiveis' => array_map(
                static fn (Assunto $assunto): string => $assunto->getDescricao(),
                $assuntoRepository->findBy([], ['descricao' => 'ASC'])
            ),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(Livro $livro): Response
    {
        return $this->render('livro/show.html.twig', ['book' => $livro]);
    }

    #[Route('/{id<\d+>}/editar', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Livro $livro,
        EntityManagerInterface $entityManager,
        AutorRepository $autorRepository,
        AssuntoRepository $assuntoRepository,
    ): Response
    {
        $form = $this->createForm(LivroType::class, $livro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncAuthors($livro, $form->get('autoresTexto')->getData() ?? '', $autorRepository, $entityManager);
            $this->syncSubjects($livro, $form->get('assuntosTexto')->getData() ?? '', $assuntoRepository, $entityManager);
            $entityManager->flush();

            $this->addFlash('success', 'Livro atualizado com sucesso.');

            return $this->redirectToRoute('livro_show', ['id' => $livro->getId()]);
        }

        return $this->render('livro/form.html.twig', [
            'form' => $form,
            'pageTitle' => 'Editar livro',
            'submitLabel' => 'Salvar alterações',
            'autoresDisponiveis' => array_map(
                static fn (Autor $autor): string => $autor->getNome(),
                $autorRepository->findBy([], ['nome' => 'ASC'])
            ),
            'assuntosDisponiveis' => array_map(
                static fn (Assunto $assunto): string => $assunto->getDescricao(),
                $assuntoRepository->findBy([], ['descricao' => 'ASC'])
            ),
        ]);
    }

    #[Route('/{id<\d+>}/excluir', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Livro $livro, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$livro->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $entityManager->remove($livro);
        $entityManager->flush();

        $this->addFlash('success', 'Livro excluído com sucesso.');

        return $this->redirectToRoute('app_home');
    }

    private function syncAuthors(Livro $livro, string $value, AutorRepository $repository, EntityManagerInterface $entityManager): void
    {
        foreach ($livro->getAutores()->toArray() as $autor) {
            $livro->removeAutor($autor);
        }

        $seen = [];
        foreach ($this->parseTags($value) as $name) {
            $key = (new UnicodeString($name))->lower()->toString();

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $autor = $repository->findByName($name);

            if ($autor === null) {
                $autor = (new Autor())->setNome($name);
                $entityManager->persist($autor);
            }

            $livro->addAutor($autor);
        }
    }

    private function syncSubjects(Livro $livro, string $value, AssuntoRepository $repository, EntityManagerInterface $entityManager): void
    {
        foreach ($livro->getAssuntos()->toArray() as $assunto) {
            $livro->removeAssunto($assunto);
        }

        $seen = [];
        foreach ($this->parseTags($value) as $description) {
            $key = (new UnicodeString($description))->lower()->toString();

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $assunto = $repository->findByDescription($description);

            if ($assunto === null) {
                $assunto = (new Assunto())->setDescricao($description);
                $entityManager->persist($assunto);
            }

            $livro->addAssunto($assunto);
        }
    }

    /**
     * @return string[]
     */
    private function parseTags(string $value): array
    {
        return array_values(array_filter(
            array_map('trim', explode(';', $value)),
            static fn (string $tag): bool => $tag !== ''
        ));
    }
}
