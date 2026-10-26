# Projeto Livros

## Executar com Docker Compose

Com Docker Engine e o plugin Docker Compose instalados, inicie a aplicação e os serviços necessários com:

```sh
docker compose up -d
```

O Compose constrói a imagem da aplicação na primeira execução. A aplicação fica disponível em <http://localhost:8080>; o Mailpit, em <http://localhost:8025>. O PostgreSQL usa a porta `5432` e mantém os dados no volume `database_data`.

Se a porta `8080` já estiver ocupada, defina outra porta na execução, por exemplo `APP_PORT=8081 docker compose up -d`.

Na primeira execução, aplique as migrations do banco:

```sh
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

A página inicial (`/`) lista os livros cadastrados e oferece busca por título, editora ou autor, filtro por assunto e ordenação. O catálogo inclui as operações de cadastro, consulta, edição e exclusão de livros. Autores e assuntos podem ser gerenciados nas páginas `/autores` e `/assuntos` e associados aos livros.

Nos formulários de livros, autores e assuntos são informados como badges separados por ponto e vírgula. Itens novos são cadastrados automaticamente ao salvar; remover um badge desfaz apenas a associação com aquele livro.

Para reconstruir a imagem depois de alterar o código ou as dependências:

```sh
docker compose up -d --build
```

Para parar os serviços, preservando o banco de dados:

```sh
docker compose down
```
