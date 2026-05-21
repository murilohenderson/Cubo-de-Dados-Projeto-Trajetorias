# Plataforma Trajetórias

![Banner](./public/banner.png)

## 📖 Sobre o Projeto

A **Plataforma Trajetórias** é um dashboard interativo desenvolvido em **Laravel 10** com **Livewire** e **Blade**, focado na visualização de indicadores epidemiológicos, ambientais e socioeconômicos da região do Baixo Tocantins. Utiliza um data‑cube multidimensional que permite cruzar doenças (Dengue, Malária, Leishmaniose, Chagas) com variáveis como população, renda, saneamento, entre outras.

## 🛠️ Requisitos

- **PHP >= 8.1**
- **Composer** (gerenciador de dependências PHP)
- **Node.js >= 18** e **npm** (para assets front‑end)
- **Git** (opcional, para versionamento)
- **Banco de dados** suportado pelo Laravel (ex.: MySQL ou PostgreSQL)

## 🚀 Instalação

```bash
# 1. Clone o repositório
git clone https://github.com/seu-usuario/plataforma-trajetorias.git
cd "Data Cube Trajetorias"

# 2. Copie o arquivo de ambiente
cp .env.example .env

# 3. Instale as dependências PHP
composer install

# 4. Instale as dependências JavaScript
npm install

# 5. Gere a chave da aplicação Laravel
php artisan key:generate
```

## 🗃️ Configuração do Banco de Dados

Edite o arquivo `.env` com as credenciais do seu banco de dados:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trajetorias_db
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

Em seguida, execute as migrations e seeders:

```bash
php artisan migrate --seed
```

## ⚙️ Compilação dos Assets

Durante o desenvolvimento use o Vite para hot‑reloading:

```bash
npm run dev
```

Para gerar os assets de produção:

```bash
npm run build
```

## ▶️ Executando a Aplicação

```bash
php artisan serve
```

Acesse **http://localhost:8000** no navegador.

## 📂 Estrutura Principal

- `app/Livewire/` – componentes Livewire (ex.: `HipercuboDashboard.php`).
- `resources/views/livewire/` – views Blade associadas aos componentes.
- `routes/web.php` – rotas web da aplicação.
- `database/migrations/` – migrações do esquema do data‑cube.
- `database/seeders/` – seeders de dados de exemplo.

## 🤝 Contribuindo

1. Fork o repositório.
2. Crie uma branch para sua feature (`git checkout -b minha-feature`).
3. Faça suas alterações e commit (`git commit -m "descrição"`).
4. Envie para o repositório remoto (`git push origin minha-feature`).
5. Abra um Pull Request.

Consulte o arquivo `CONTRIBUTING.md` para diretrizes detalhadas.

# Autores
[<img src="https://avatars.githubusercontent.com/u/200527859?v=4" width="95">](https://github.com/murilohenderson)
<br><sub>Murilo Henderson</sub>