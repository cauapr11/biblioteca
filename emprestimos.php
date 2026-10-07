<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

require_once 'config/connect.php';

// Atualizar status de atrasados automaticamente
$conn->query("UPDATE tbemprestimos SET status='atrasado' WHERE status='ativo' AND dataprevista < CURDATE()");

$filtro = $_GET['filtro'] ?? 'todos';

$where = match($filtro) {
    'ativo'     => "WHERE e.status='ativo'",
    'atrasado'  => "WHERE e.status='atrasado'",
    'devolvido' => "WHERE e.status='devolvido'",
    default     => ''
};

$emprestimos = $conn->query("
  SELECT e.*, l.titulo, u.nome AS aluno, u.turma, u.matricula
  FROM tbemprestimos e
  JOIN tblivros l ON l.idlivro = e.idlivro
  JOIN tbusuarios u ON u.idusuario = e.idusuario
  $where
  ORDER BY e.dataemprestimo DESC
")->fetch_all(MYSQLI_ASSOC);

$counts = [
  'todos'    => $conn->query("SELECT COUNT(*) AS c FROM tbemprestimos")->fetch_assoc()['c'],
  'ativo'    => $conn->query("SELECT COUNT(*) AS c FROM tbemprestimos WHERE status='ativo'")->fetch_assoc()['c'],
  'atrasado' => $conn->query("SELECT COUNT(*) AS c FROM tbemprestimos WHERE status='atrasado'")->fetch_assoc()['c'],
  'devolvido'=> $conn->query("SELECT COUNT(*) AS c FROM tbemprestimos WHERE status='devolvido'")->fetch_assoc()['c'],
];

// Listas para o modal de novo empréstimo
$livros_disponiveis = $conn->query("SELECT idlivro, titulo, quantidadedisponivel FROM tblivros WHERE quantidadedisponivel > 0 ORDER BY titulo")->fetch_all(MYSQLI_ASSOC);
$usuarios_ativos    = $conn->query("SELECT idusuario, nome, turma FROM tbusuarios WHERE status='ativo' ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Empréstimos — BiblioEscola</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1 class="page-title">Empréstimos</h1>
        <p class="page-sub">Controle de retiradas e devoluções</p>
      </div>
      <button class="btn btn-primary" onclick="openModal('modal-emprestimo')">
        + Registrar Empréstimo
      </button>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <?php
      $tabs = [
        'todos'     => 'Todos',
        'ativo'     => 'Ativos',
        'atrasado'  => 'Atrasados',
        'devolvido' => 'Devolvidos',
      ];
      foreach ($tabs as $key => $label):
      ?>
        <a href="emprestimos.php?filtro=<?= $key ?>" class="tab-btn <?= $filtro === $key ? 'active' : '' ?>">
          <?= $label ?>
          <span class="tab-count"><?= $counts[$key] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="table-wrap">
        <table id="tabela-emprestimos">
          <thead>
            <tr>
              <th>#</th>
              <th>Livro</th>
              <th>Usuário</th>
              <th>Matrícula</th>
              <th>Turma</th>
              <th>Retirada</th>
              <th>Prev. Devolução</th>
              <th>Devolução Real</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($emprestimos)): ?>
              <tr><td colspan="10" class="empty">Nenhum registro encontrado.</td></tr>
            <?php else: ?>
              <?php foreach ($emprestimos as $e): ?>
                <tr>
                  <td class="muted"><?= $e['idemprestimo'] ?></td>
                  <td class="bold"><?= htmlspecialchars($e['titulo']) ?></td>
                  <td><?= htmlspecialchars($e['aluno']) ?></td>
                  <td class="muted"><?= htmlspecialchars($e['matricula'] ?? '—') ?></td>
                  <td class="muted"><?= htmlspecialchars($e['turma'] ?? '—') ?></td>
                  <td class="muted"><?= date('d/m/Y', strtotime($e['dataemprestimo'])) ?></td>
                  <td style="<?= $e['status'] === 'atrasado' ? 'color:var(--danger);font-weight:600' : '' ?>">
                    <?= date('d/m/Y', strtotime($e['dataprevista'])) ?>
                  </td>
                  <td class="muted">
                    <?= $e['datadevolucao'] ? date('d/m/Y', strtotime($e['datadevolucao'])) : '—' ?>
                  </td>
                  <td><span class="badge badge-<?= $e['status'] ?>"><?= ucfirst($e['status']) ?></span></td>
                  <td>
                    <div class="table-actions">
                      <?php if ($e['status'] !== 'devolvido'): ?>
                        <button class="btn btn-ghost btn-sm" onclick="devolverEmprestimo(<?= $e['idemprestimo'] ?>)">Devolver</button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Modal Novo Empréstimo -->
<div class="modal-overlay" id="modal-emprestimo">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Registrar Empréstimo</span>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-emprestimo">
        <div class="form-grid" style="margin-bottom:16px">
          <div class="form-group full">
            <label class="form-label">Usuário *</label>
            <select name="idusuario" class="form-control" required>
              <option value="">Selecione o usuário…</option>
              <?php foreach ($usuarios_ativos as $u): ?>
                <option value="<?= $u['idusuario'] ?>">
                  <?= htmlspecialchars($u['nome']) ?><?= $u['turma'] ? ' — ' . $u['turma'] : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group full">
            <label class="form-label">Livro *</label>
            <select name="idlivro" class="form-control" required>
              <option value="">Selecione o livro…</option>
              <?php foreach ($livros_disponiveis as $l): ?>
                <option value="<?= $l['idlivro'] ?>">
                  <?= htmlspecialchars($l['titulo']) ?> (<?= $l['quantidadedisponivel'] ?> disponíveis)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Data de Retirada *</label>
            <input type="date" name="dataemprestimo" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Data Prevista de Devolução *</label>
            <input type="date" name="dataprevista" class="form-control" required value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-emprestimo')">Cancelar</button>
      <button class="btn btn-primary" onclick="document.getElementById('form-emprestimo').dispatchEvent(new Event('submit',{cancelable:true,bubbles:true}))">Registrar</button>
    </div>
  </div>
</div>

<script src="js/app.js"></script>
<script>
submitForm('form-emprestimo', 'api/emprestimos.php', 'modal-emprestimo', 'Empréstimo registrado!');
</script>
</body>
</html>
