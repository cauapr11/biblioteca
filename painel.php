<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

require_once 'config/connect.php';

// ── Stats ─────────────────────────────────────────────────────
$total_livros  = $conn->query('SELECT COUNT(*) AS c FROM tblivros')->fetch_assoc()['c'];
$total_usuarios= $conn->query('SELECT COUNT(*) AS c FROM tbusuarios WHERE status="ativo"')->fetch_assoc()['c'];
$total_ativos  = $conn->query('SELECT COUNT(*) AS c FROM tbemprestimos WHERE status="ativo"')->fetch_assoc()['c'];
$total_atrasados=$conn->query("SELECT COUNT(*) AS c FROM tbemprestimos WHERE status='atrasado' OR (status='ativo' AND dataprevista < CURDATE())")->fetch_assoc()['c'];

// ── Empréstimos recentes ──────────────────────────────────────
$emprestimos = $conn->query("
  SELECT e.idemprestimo, l.titulo, u.nome AS aluno, u.turma, e.dataprevista, e.status,
         CASE WHEN e.status='ativo' AND e.dataprevista < CURDATE() THEN 'atrasado' ELSE e.status END AS status_real
  FROM tbemprestimos e
  JOIN tblivros l ON l.idlivro = e.idlivro
  JOIN tbusuarios u ON u.idusuario = e.idusuario
  WHERE e.status != 'devolvido'
  ORDER BY e.dataemprestimo DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// ── Alertas de atraso ─────────────────────────────────────────
$atrasados = $conn->query("
  SELECT u.nome, l.titulo, e.dataprevista
  FROM tbemprestimos e
  JOIN tbusuarios u ON u.idusuario = e.idusuario
  JOIN tblivros l ON l.idlivro = e.idlivro
  WHERE e.status='atrasado' OR (e.status='ativo' AND e.dataprevista < CURDATE())
  ORDER BY e.dataprevista ASC
")->fetch_all(MYSQLI_ASSOC);

$hoje = date('d/m/Y');
$dia_semana = ['Sunday'=>'Domingo','Monday'=>'Segunda-feira','Tuesday'=>'Terça-feira','Wednesday'=>'Quarta-feira','Thursday'=>'Quinta-feira','Friday'=>'Sexta-feira','Saturday'=>'Sábado'][date('l')];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel — BiblioEscola</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1 class="page-title">Painel Geral</h1>
        <p class="page-sub"><?= $dia_semana ?>, <?= $hoje ?></p>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label green">Títulos</div>
        <div class="stat-value"><?= $total_livros ?></div>
        <div class="stat-sub">no acervo</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Empréstimos</div>
        <div class="stat-value"><?= $total_ativos ?></div>
        <div class="stat-sub">em andamento</div>
      </div>
      <div class="stat-card">
        <div class="stat-label red">Atrasados</div>
        <div class="stat-value"><?= $total_atrasados ?></div>
        <div class="stat-sub">requer atenção</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Usuários</div>
        <div class="stat-value"><?= $total_usuarios ?></div>
        <div class="stat-sub">ativos</div>
      </div>
    </div>

    <!-- Main grid -->
    <div class="dash-grid">
      <!-- Empréstimos recentes -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Empréstimos em Aberto</span>
          <a href="emprestimos.php" class="link-all">Ver todos →</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Livro</th>
                <th>Aluno</th>
                <th>Turma</th>
                <th>Devolução</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($emprestimos)): ?>
                <tr><td colspan="5" class="empty">Nenhum empréstimo em aberto.</td></tr>
              <?php else: ?>
                <?php foreach ($emprestimos as $e): ?>
                  <tr>
                    <td class="bold"><?= htmlspecialchars($e['titulo']) ?></td>
                    <td><?= htmlspecialchars($e['aluno']) ?></td>
                    <td class="muted"><?= htmlspecialchars($e['turma'] ?? '—') ?></td>
                    <td class="muted"><?= date('d/m/Y', strtotime($e['dataprevista'])) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($e['status_real']) ?>"><?= ucfirst($e['status_real']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Alertas de atraso -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Alertas de Atraso</span>
          <?php if (!empty($atrasados)): ?>
            <span class="badge badge-atrasado"><?= count($atrasados) ?></span>
          <?php endif; ?>
        </div>
        <?php if (empty($atrasados)): ?>
          <div class="empty">Nenhum atraso no momento.</div>
        <?php else: ?>
          <?php foreach ($atrasados as $a): ?>
            <div class="overdue-card">
              <div style="display:flex;justify-content:space-between;align-items:start">
                <span class="overdue-name"><?= htmlspecialchars($a['nome']) ?></span>
                <span class="badge badge-atrasado">Atrasado</span>
              </div>
              <div class="overdue-book"><?= htmlspecialchars($a['titulo']) ?></div>
              <div class="overdue-date">Venceu em <?= date('d/m/Y', strtotime($a['dataprevista'])) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<script src="js/app.js"></script>
</body>
</html>
