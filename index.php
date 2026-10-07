<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: painel.php');
    exit;
}

require_once 'config/connect.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']  ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($nome && $senha) {
        $res = db_query($conn, 'SELECT id, nome, senha FROM tbadministradores WHERE nome = ? LIMIT 1', 's', [$nome]);
        $admin = $res ? $res->fetch_assoc() : null;

        if ($admin && password_verify($senha, $admin['senha'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_nome'] = $admin['nome'];
            header('Location: painel.php');
            exit;
        } else {
            $erro = 'Nome ou senha incorretos.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — BiblioEscola</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <div class="login-logo-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div>
        <div class="login-logo-name">BiblioEscola</div>
        <div class="login-logo-school">Sistema de Gerenciamento de Biblioteca</div>
      </div>
    </div>

    <h1 class="login-title">Acesso ao Sistema</h1>

    <div class="login-error <?= $erro ? 'show' : '' ?>"><?= htmlspecialchars($erro) ?></div>

    <form method="POST" action="index.php">
      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">Nome do administrador</label>
        <input type="text" name="nome" class="form-control" placeholder="Seu nome completo" autocomplete="username" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
      </div>
      <div class="form-group" style="margin-bottom:24px">
        <label class="form-label">Senha</label>
        <input type="password" name="senha" class="form-control" placeholder="••••••••" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Entrar</button>
    </form>

    <p style="font-size:12px;color:var(--muted-fg);margin-top:24px;text-align:center">
      Senha padrão: <strong>admin123</strong>
    </p>
  </div>
</div>
<script src="js/app.js"></script>
</body>
</html>
