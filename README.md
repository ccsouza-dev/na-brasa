# Espetinhos da Jaque

Sistema de mesas, comandas, estoque, financeiro e caderneta (fiado) do bar Espetinhos da Jaque.

O manual de uso está em [docs/manual-do-usuario.md](docs/manual-do-usuario.md).

## Como rodar

É preciso PHP 8.2, Composer e MySQL (no XAMPP, Apache e MySQL ligados). O banco não vem no repositório. Na primeira abertura o sistema cria o banco `na_brasa_db`, as tabelas e o cardápio, com 30 unidades de cada produto.

Na pasta do projeto:

```
composer install
copy .env.example .env
php artisan key:generate
php artisan na-brasa:install
```

No Windows com XAMPP, use o `php.exe` do XAMPP se o comando `php` não estiver no caminho. Abra `http://localhost/Na_Brasa/public`.

Quem só copiar o `.env`, gerar a chave e abrir o endereço também dispara a criação do banco na primeira visita. O MySQL precisa estar ligado, com usuário `root` e senha vazia, como no XAMPP. Se a senha for outra, ajuste `DB_PASSWORD` no `.env` antes de abrir.

Front-end em HTML, CSS e JavaScript. Back-end em Laravel (PHP), no padrão MVC. Banco relacional MySQL.
