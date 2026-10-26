<?php

namespace App\Repository;

use App\Entity\Livro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Livro>
 */
class LivroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livro::class);
    }

    /**
     * @return Livro[]
     */
    public function search(string $term = '', ?int $assuntoId = null, string $sort = 'titulo_asc'): array
    {
        $queryBuilder = $this->createQueryBuilder('livro')
            ->leftJoin('livro.autores', 'autor')
            ->addSelect('autor')
            ->leftJoin('livro.assuntos', 'assunto')
            ->addSelect('assunto')
            ->distinct();

        if ($term !== '') {
            $queryBuilder
                ->andWhere('LOWER(livro.titulo) LIKE LOWER(:term) OR LOWER(livro.editora) LIKE LOWER(:term) OR LOWER(autor.nome) LIKE LOWER(:term)')
                ->setParameter('term', '%'.$term.'%');
        }

        if ($assuntoId !== null) {
            $queryBuilder
                ->andWhere('assunto.id = :assuntoId')
                ->setParameter('assuntoId', $assuntoId);
        }

        [$field, $direction] = match ($sort) {
            'titulo_desc' => ['livro.titulo', 'DESC'],
            'ano_asc' => ['livro.anoPublicacao', 'ASC'],
            'ano_desc' => ['livro.anoPublicacao', 'DESC'],
            default => ['livro.titulo', 'ASC'],
        };

        return $queryBuilder
            ->orderBy($field, $direction)
            ->addOrderBy('livro.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
