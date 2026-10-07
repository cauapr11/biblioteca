<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

require_once 'config/connect.php';

$busca     = trim($_GET['q'] ?? '');
$categoria = trim($_GET['cat'] ?? '');

$where = [];
$params = [];
$types  = '';

if ($busca) {
    $where[] = '(titulo LIKE ? OR autor LIKE ? OR isbn LIKE ?)';
    $like = "%$busca%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}
if ($categoria) {
    $where[] = 'categoria = ?';
    $params[] = $categoria;
    $types .= 's';
}

$sql = 'SELECT * FROM tblivros' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY titulo';
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$livros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$categorias = $conn->query('SELECT DISTINCT categoria FROM tblivros WHERE categoria IS NOT NULL AND categoria != "" ORDER BY categoria')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acervo — BiblioEscola</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1 class="page-title">Acervo</h1>
        <p class="page-sub"><?= count($livros) ?> título(s) encontrado(s)</p>
      </div>
      <button class="btn btn-primary" onclick="openModal('modal-livro'); document.getElementById('form-livro').reset(); document.getElementById('modal-livro-titulo').textContent='Novo Livro'; document.getElementById('livro-id').value=''">
        + Novo Livro
      </button>
    </div>

    <!-- Toolbar -->
    <form method="GET" action="livros.php">
      <div class="toolbar">
        <div class="search-wrap">
          <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          <input type="text" name="q" id="search-input" placeholder="Buscar título, autor ou ISBN…" value="<?= htmlspecialchars($busca) ?>">
        </div>
        <a href="livros.php" class="filter-btn <?= !$categoria ? 'active' : '' ?>">Todos</a>
        <?php foreach ($categorias as $cat): ?>
          <a href="livros.php?q=<?= urlencode($busca) ?>&cat=<?= urlencode($cat['categoria']) ?>" class="filter-btn <?= $categoria === $cat['categoria'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['categoria']) ?></a>
        <?php endforeach; ?>
        <?php if ($busca): ?><button type="submit" class="btn btn-secondary btn-sm">Buscar</button><?php endif; ?>
      </div>
    </form>

    <!-- Table -->
    <div class="card">
      <div class="table-wrap">
        <table id="tabela-livros">
          <thead>
            <tr>
              <th>#</th>
              <th>Título</th>
              <th>Autor</th>
              <th>Editora</th>
              <th>Categoria</th>
              <th>Estante</th>
              <th>Exemplares</th>
              <th>Disponíveis</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($livros)): ?>
              <tr><td colspan="9" class="empty">Nenhum livro encontrado.</td></tr>
            <?php else: ?>
              <?php foreach ($livros as $l): ?>
                <tr>
                  <td class="muted"><?= $l['idlivro'] ?></td>
                  <td class="bold"><?= htmlspecialchars($l['titulo']) ?></td>
                  <td><?= htmlspecialchars($l['autor'] ?? '—') ?></td>
                  <td class="muted"><?= htmlspecialchars($l['editora'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($l['categoria'] ?? '—') ?></td>
                  <td class="muted"><?= htmlspecialchars(($l['estante'] ?? '') . ' / ' . ($l['prateleira'] ?? '')) ?></td>
                  <td><?= $l['quantidade'] ?></td>
                  <td>
                    <?php if ($l['quantidadedisponivel'] == 0): ?>
                      <span class="badge badge-indisponivel">Indisponível</span>
                    <?php else: ?>
                      <span class="badge badge-disponivel"><?= $l['quantidadedisponivel'] ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="table-actions">
                      <button class="btn btn-ghost btn-sm" onclick='editarLivro(<?= json_encode($l) ?>)'>Editar</button>
                      <button class="btn btn-sm" style="color:var(--danger)" onclick="confirmDelete('Excluir o livro &quot;<?= addslashes(htmlspecialchars($l['titulo'])) ?>&quot;?','api/livros.php?id=<?= $l['idlivro'] ?>')">Excluir</button>
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

<!-- Modal Livro -->
<div class="modal-overlay" id="modal-livro">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modal-livro-titulo">Novo Livro</span>
      <button class="modal-close">✕</button>
    </div>
    <div class="modal-body">
      <form id="form-livro">
        <input type="hidden" name="id" id="livro-id">
        <div class="form-grid" style="margin-bottom:16px">
          <div class="form-group full">
            <label class="form-label">Título *</label>
            <input type="text" name="titulo" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Autor</label>
            <input type="text" name="autor" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Editora</label>
            <input type="text" name="editora" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Ano</label>
            <input type="number" name="ano" class="form-control" min="1000" max="2099">
          </div>
          <div class="form-group">
            <label class="form-label">ISBN</label>
            <input type="text" name="isbn" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Categoria</label>
            <input type="text" name="categoria" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Quantidade total</label>
            <input type="number" name="quantidade" class="form-control" min="1" value="1">
          </div>
          <div class="form-group">
            <label class="form-label">Disponíveis</label>
            <input type="number" name="quantidadedisponivel" class="form-control" min="0" value="1">
          </div>
          <div class="form-group">
            <label class="form-label">Estante</label>
            <input type="text" name="estante" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Prateleira</label>
            <input type="text" name="prateleira" class="form-control">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-livro')">Cancelar</button>
      <button class="btn btn-primary" onclick="document.getElementById('form-livro').dispatchEvent(new Event('submit',{cancelable:true,bubbles:true}))">Salvar</button>
    </div>
  </div>
</div>

<script src="js/app.js"></script>
<script>
submitForm('form-livro', 'api/livros.php', 'modal-livro', 'Livro salvo com sucesso!');

function editarLivro(l) {
  document.getElementById('modal-livro-titulo').textContent = 'Editar Livro';
  const f = document.getElementById('form-livro');
  for (const [k, v] of Object.entries(l)) {
    const el = f.querySelector(`[name="${k}"]`);
    if (el) el.value = v ?? '';
  }
  document.getElementById('livro-id').value = l.idlivro;
  openModal('modal-livro');
}
</script>
</body>
</html>
