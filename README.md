# Espetinhos da Jaque

Sistema de mesas, comandas, estoque, financeiro e caderneta (fiado) do bar Espetinhos da Jaque.

O manual de uso está em [docs/manual-do-usuario.md](docs/manual-do-usuario.md).

## Como rodar

1. Ligue o Apache e o MySQL do XAMPP.
2. Copie `.env.example` para `.env` e ajuste o banco MySQL (`na_brasa_db`).
3. Rode `composer install` e `php artisan key:generate`.
4. Abra `http://localhost/Na_Brasa/public`.

Front-end em HTML, CSS e JavaScript. Back-end em Laravel (PHP), no padrão MVC. Banco relacional MySQL.
