<?php
/**
 * Mini projeto demonstrativo em PHP
 * Tema: Gestão de pedidos de suporte técnico
 *
 * Como usar:
 * 1. Guardar este ficheiro como index.php
 * 2. Executar com: php -S localhost:8000
 * 3. Abrir http://localhost:8000
 *
 * Nota:
 * - Este exemplo usa sessão para persistência simples
 * - A função getPdoConnection() mostra como preparar ligação MySQL
 */

declare(strict_types=1);
session_start();

if (!isset($_SESSION['tickets'])) {
    $_SESSION['tickets'] = [
        [
            'id' => 1,
            'nome' => 'Ana Silva',
            'email' => 'ana.silva@example.com',
            'categoria' => 'Aplicação',
            'prioridade' => 'Alta',
            'descricao' => 'Erro ao autenticar na aplicação interna.',
            'estado' => 'Aberto',
            'created_at' => date('Y-m-d H:i'),
        ],
        [
            'id' => 2,
            'nome' => 'Miguel Costa',
            'email' => 'miguel.costa@example.com',
            'categoria' => 'Equipamento',
            'prioridade' => 'Média',
            'descricao' => 'Portátil com lentidão e necessidade de manutenção.',
            'estado' => 'Em análise',
            'created_at' => date('Y-m-d H:i'),
        ],
    ];
}

$errors = [];
$successMessage = '';
$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['categoria'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $prioridade = trim($_POST['prioridade'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($nome === '') {
        $errors[] = 'O nome é obrigatório.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Deve indicar um email válido.';
    }

    $validCategories = ['Aplicação', 'Equipamento', 'Acesso', 'Base de Dados', 'Rede'];
    if (!in_array($categoria, $validCategories, true)) {
        $errors[] = 'A categoria selecionada é inválida.';
    }

    $validPriorities = ['Baixa', 'Média', 'Alta'];
    if (!in_array($prioridade, $validPriorities, true)) {
        $errors[] = 'A prioridade selecionada é inválida.';
    }

    if ($descricao === '' || mb_strlen($descricao) < 10) {
        $errors[] = 'A descrição deve ter pelo menos 10 caracteres.';
    }

    if (empty($errors)) {
        $nextId = empty($_SESSION['tickets'])
            ? 1
            : (max(array_column($_SESSION['tickets'], 'id')) + 1);

        $_SESSION['tickets'][] = [
            'id' => $nextId,
            'nome' => $nome,
            'email' => $email,
            'categoria' => $categoria,
            'prioridade' => $prioridade,
            'descricao' => $descricao,
            'estado' => 'Aberto',
            'created_at' => date('Y-m-d H:i'),
        ];

        $successMessage = 'Pedido de suporte registado com sucesso.';
    }
}

$tickets = array_values(array_filter(
    $_SESSION['tickets'],
    static function (array $ticket) use ($search, $categoryFilter): bool {
        $matchesSearch = $search === ''
            || str_contains(mb_strtolower($ticket['nome']), mb_strtolower($search))
            || str_contains(mb_strtolower($ticket['email']), mb_strtolower($search))
            || str_contains(mb_strtolower($ticket['descricao']), mb_strtolower($search));

        $matchesCategory = $categoryFilter === '' || $ticket['categoria'] === $categoryFilter;

        return $matchesSearch && $matchesCategory;
    }
));

usort($tickets, static fn(array $a, array $b): int => $b['id'] <=> $a['id']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function priorityClass(string $priority): string
{
    return match ($priority) {
        'Alta' => 'priority-high',
        'Média' => 'priority-medium',
        default => 'priority-low',
    };
}

/**
 * Exemplo de ligação MySQL para evolução futura.
 */
function getPdoConnection(): PDO
{
    $host = '127.0.0.1';
    $db = 'support_portal';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Suporte Técnico</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --border: #e2e8f0;
            --accent: #0f172a;
            --success-bg: #ecfdf5;
            --success-text: #166534;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
            --high-bg: #fee2e2;
            --high-text: #991b1b;
            --medium-bg: #fef3c7;
            --medium-text: #92400e;
            --low-bg: #dcfce7;
            --low-text: #166534;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }

        .hero, .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .hero {
            padding: 28px;
            margin-bottom: 24px;
        }

        h1, h2, h3 { margin: 0; }
        .subtitle {
            margin-top: 10px;
            color: var(--muted);
            line-height: 1.6;
            max-width: 780px;
        }

        .badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--border);
            font-size: 13px;
            background: #f8fafc;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1.25fr;
            gap: 24px;
        }

        .card {
            padding: 24px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            background: #fff;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group { margin-bottom: 16px; }

        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .btn {
            border: 0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-secondary {
            background: #fff;
            color: var(--text);
            border: 1px solid var(--border);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid #fecaca;
        }

        .filter-bar {
            display: grid;
            grid-template-columns: 1.4fr 1fr auto;
            gap: 12px;
            margin-top: 18px;
            margin-bottom: 18px;
        }

        .ticket-list {
            display: grid;
            gap: 14px;
        }

        .ticket {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
            background: #fff;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: start;
        }

        .ticket-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .priority-high { background: var(--high-bg); color: var(--high-text); }
        .priority-medium { background: var(--medium-bg); color: var(--medium-text); }
        .priority-low { background: var(--low-bg); color: var(--low-text); }

        .muted { color: var(--muted); }
        .small { font-size: 13px; }
        .section-title { margin-bottom: 8px; }
        .top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .empty {
            padding: 20px;
            border: 1px dashed var(--border);
            border-radius: 14px;
            color: var(--muted);
            background: #f8fafc;
        }

        @media (max-width: 900px) {
            .grid, .row-2, .filter-bar {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <section class="hero">
        <h1>Portal de Suporte Técnico</h1>
        <p class="subtitle">
            Mini projeto demonstrativo em PHP para registo e acompanhamento de pedidos de suporte.
            Inclui formulário com validação, pesquisa, filtragem e estrutura preparada para futura ligação a MySQL.
        </p>
        <div class="badge-row">
            <span class="badge">PHP</span>
            <span class="badge">Formulários</span>
            <span class="badge">Validação</span>
            <span class="badge">CRUD simples</span>
            <span class="badge">Preparado para MySQL</span>
        </div>
    </section>

    <div class="grid">
        <section class="card">
            <div class="section-title">
                <h2>Novo Pedido</h2>
                <p class="subtitle">Registo simples de incidentes ou pedidos de suporte técnico.</p>
            </div>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success"><?= e($successMessage) ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Foram encontrados erros:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input id="nome" name="nome" type="text" value="<?= e($_POST['nome'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label for="categoria">Categoria</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Selecionar</option>
                            <?php foreach (['Aplicação', 'Equipamento', 'Acesso', 'Base de Dados', 'Rede'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= (($_POST['categoria'] ?? '') === $option) ? 'selected' : '' ?>>
                                    <?= e($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="prioridade">Prioridade</label>
                        <select id="prioridade" name="prioridade" required>
                            <option value="">Selecionar</option>
                            <?php foreach (['Baixa', 'Média', 'Alta'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= (($_POST['prioridade'] ?? '') === $option) ? 'selected' : '' ?>>
                                    <?= e($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" required><?= e($_POST['descricao'] ?? '') ?></textarea>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">Registar Pedido</button>
                    <a class="btn btn-secondary" href="?">Limpar</a>
                </div>
            </form>
        </section>

        <section class="card">
            <div class="top-row">
                <div>
                    <h2>Pedidos Registados</h2>
                    <p class="subtitle">Lista demonstrativa com pesquisa e filtro por categoria.</p>
                </div>
                <div class="small muted"><?= count($tickets) ?> resultado(s)</div>
            </div>

            <form method="get" class="filter-bar">
                <input type="text" name="search" placeholder="Pesquisar por nome, email ou descrição" value="<?= e($search) ?>">
                <select name="categoria">
                    <option value="">Todas as categorias</option>
                    <?php foreach (['Aplicação', 'Equipamento', 'Acesso', 'Base de Dados', 'Rede'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= ($categoryFilter === $option) ? 'selected' : '' ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Filtrar</button>
            </form>

            <?php if (empty($tickets)): ?>
                <div class="empty">Não existem pedidos correspondentes aos filtros selecionados.</div>
            <?php else: ?>
                <div class="ticket-list">
                    <?php foreach ($tickets as $ticket): ?>
                        <article class="ticket">
                            <div class="ticket-header">
                                <div>
                                    <h3>#<?= (int)$ticket['id'] ?> — <?= e($ticket['nome']) ?></h3>
                                    <p class="small muted"><?= e($ticket['email']) ?> · <?= e($ticket['created_at']) ?></p>
                                </div>
                                <span class="pill <?= e(priorityClass($ticket['prioridade'])) ?>">
                                    <?= e($ticket['prioridade']) ?>
                                </span>
                            </div>

                            <div class="ticket-meta">
                                <span class="pill" style="background:#eff6ff;color:#1d4ed8;"><?= e($ticket['categoria']) ?></span>
                                <span class="pill" style="background:#f1f5f9;color:#334155;"><?= e($ticket['estado']) ?></span>
                            </div>

                            <p style="margin-top:14px; line-height:1.6;"><?= e($ticket['descricao']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
</body>
</html>
