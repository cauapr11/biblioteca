<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

require_once 'config/connect.php';

$busca  = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];
$types  = '';

if ($busca) {
    $where[] = '(nome LIKE ? OR matricula LIKE ? OR email LIKE ? OR turma LIKE ?)';
    $like = "%$busca%";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}
if ($status) {
    $where[] = 'status = ?';
    $params[] = $status;
    $types .= 's';
}

$sql = 'SELECT * FROM tbusuarios' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY nome';
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_ativos   = $conn->query("SELECT COUNT(*) AS c FROM tbusuarios WHERE status='ativo'")->fetch_assoc()['c'];
$total_inativos = $conn->query("SELECT COUNT(*) AS c FROM tbusuarios WHERE status='inativo'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuários — BiblioEscola</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1 class="page-title">Usuários</h1>
        <p class="page-sub"><?= $total_ativos ?> ativo(s) · <?= $total_inativos ?> inativo(s)</p>
      </div>
      <button class="btn btn-primary" onclick="openModal('modal-usuario'); document.getElementById('form-usuario').reset(); document.getElementById('modal-usuario-titulo').textContent='Novo Usuário'; document.getElementById('usuario-id').value=''">
        + Novo Usuário
      </button>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="search-wrap">
        <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <input type="text" id="search-input" placeholder="Buscar nome, matrícula ou turma…" value="<?= htmlspecialchars($busca) ?>" oninput="filtrarTabela(this.value)">
      </div>
      <a href="usuarios.php" class="filter-btn <?= !$status ? 'active' : '' ?>">Todos</a>
      <a href="usuarios.php?status=ativo" class="filter-btn <?= $status==='ativo' ? 'active' : '' ?>">Ativos</a>
      <a href="usuarios.php?status=inativo" class="filter-btn <?= $status==='inativo' ? 'active' : '' ?>">Inativos</a>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="table-wrap">
        <table id="tabela-usuarios">
          <thead>
            <tr>
              <th>#</th>
              <th>Nome</th>
              <th>Matrícula</th>
              <th>Tipo</th>
              <th>Série/Turma</th>
              <th>Telefone</th>
              <th>E-mail</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($usuarios)): ?>
              <tr><td colspan="9" class="empty">Nenhum usuário encontrado.</td></tr>
            <?php else: ?>
              <?php foreach ($usuarios as $u): ?>
                <tr>
                  <td class="muted"><?= $u['idusuario'] ?></td>
                  <td class="bold"><?= htmlspecialchars($u['nome']) ?></td>
                  <td class="muted"><?= htmlspecialchars($u['matricula'] ?? '—') ?></td>
                  <td><?= ucfirst(htmlspecialchars($u['tipo'] ?? '—')) ?></td>
                  <td class="muted"><?= htmlspecialchars(($u['serie'] ?? '') . ($u['turma'] ? ' — ' . $u['turma'] : '')) ?></td>
                  <td class="muted"><?= htmlspecialchars($u['telefone'] ?? '—') ?></td>
                  <td class="muted"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                  <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                  <td>
                    <div class="table-actions">
                      <button class="btn btn-ghost btn-sm" onclick='editarUsuario(<?= json_encode($u) ?>)'>Editar</button>
                      <button class="btn btn-sm" style="color:var(--danger)" onclick="confirmDelete('Excluir o usuário &quot;<?= addslashes(htmlspecialchars($u['nome'])) ?>&quot;?','api/usuarios.php?id=<?= $u['idusuario'] ?>')">Excluir</button>
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

<!-- Modal Usuário -->
<div class="modal-overlay" id="modal-usuario">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modal-usuario-titulo">Novo Usuário</span>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-usuario">
        <input type="hidden" name="id" id="usuario-id">
        <div class="form-grid" style="margin-bottom:16px">
          <div class="form-group full">
            <label class="form-label">Nome completo *</label>
            <input type="text" name="nome" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Matrícula</label>
            <input type="text" name="matricula" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-control">
              <option value="aluno">Aluno</option>
              <option value="professor">Professor</option>
              <option value="funcionario">Funcionário</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Curso</label>
            <input type="text" name="curso" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Série</label>
            <input type="text" name="serie" class="form-control" placeholder="Ex: 9">
          </div>
          <div class="form-group">
            <label class="form-label">Turma</label>
            <input type="text" name="turma" class="form-control" placeholder="Ex: A">
          </div>
          <div class="form-group">
            <label class="form-label">Telefone</label>
            <input type="text" name="telefone" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="ativo">Ativo</option>
              <option value="inativo">Inativo</option>
            </select>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-usuario')">Cancelar</button>
      <button class="btn btn-primary" onclick="document.getElementById('form-usuario').dispatchEvent(new Event('submit',{cancelable:true,bubbles:true}))">Salvar</button>
    </div>
  </div>
</div>

<script src="js/app.js"></script>
<script>
submitForm('form-usuario', 'api/usuarios.php', 'modal-usuario', 'Usuário salvo com sucesso!');

function editarUsuario(u) {
  document.getElementById('modal-usuario-titulo').textContent = 'Editar Usuário';
  const f = document.getElementById('form-usuario');
  for (const [k, v] of Object.entries(u)) {
    const el = f.querySelector(`[name="${k}"]`);
    if (el) el.value = v ?? '';
  }
  document.getElementById('usuario-id').value = u.idusuario;
  openModal('modal-usuario');
}

function filtrarTabela(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#tabela-usuarios tbody tr').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
</body>
</html>
