# Projeto Livros

## Executar com Docker Compose

Com Docker Engine e o plugin Docker Compose instalados, inicie a aplicação e os serviços necessários com:

```sh
docker compose up -d
```

O Compose constrói a imagem da aplicação na primeira execução. A aplicação fica disponível em <http://localhost:8080>; o Mailpit, em <http://localhost:8025>. O PostgreSQL usa a porta `5432` e mantém os dados no volume `database_data`.

Se a porta `8080` já estiver ocupada, defina outra porta na execução, por exemplo `APP_PORT=8081 docker compose up -d`.

Esta base ainda não tem rotas de aplicação; por isso, a raiz pode retornar `404` até uma página inicial ser implementada.

Para reconstruir a imagem depois de alterar o código ou as dependências:

```sh
docker compose up -d --build
```

Para parar os serviços, preservando o banco de dados:

```sh
docker compose down
```
