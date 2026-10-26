<?php

namespace App\Entity;

use App\Repository\AutorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AutorRepository::class)]
#[ORM\Table(name: 'autor')]
class Autor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'codau')]
    private ?int $id = null;

    #[ORM\Column(name: 'nome', length: 40)]
    private ?string $nome = null;

    /**
     * @var Collection<int, Livro>
     */
    #[ORM\ManyToMany(targetEntity: Livro::class, mappedBy: 'autores')]
    private Collection $livros;

    public function __construct()
    {
        $this->livros = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): ?string
    {
        return $this->nome;
    }

    public function setNome(string $nome): static
    {
        $this->nome = $nome;

        return $this;
    }

    /**
     * @return Collection<int, Livro>
     */
    public function getLivros(): Collection
    {
        return $this->livros;
    }

    public function addLivro(Livro $livro): static
    {
        if (!$this->livros->contains($livro)) {
            $this->livros->add($livro);
            $livro->addAutor($this);
        }

        return $this;
    }

    public function removeLivro(Livro $livro): static
    {
        if ($this->livros->removeElement($livro)) {
            $livro->removeAutor($this);
        }

        return $this;
    }
}
