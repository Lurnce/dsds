<?php
require_once __DIR__ . '/conexao.php';

$erros = [];
$aba   = $_GET['aba'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'login') {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (!$email || !$senha) {
            $erros[] = 'Preencha o e-mail e a senha.';
        } else {
            $st = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
            $st->execute([':email' => $email]);
            $usuario = $st->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Guarda dados na sessão
                unset($usuario['senha']);
                $_SESSION['usuario'] = $usuario;
                flash('Bem-vindo de volta, ' . $usuario['nome'] . '!');
                redirecionar('index.php');
            } else {
                $erros[] = 'E-mail ou senha incorretos.';
            }
        }
    } elseif ($acao === 'cadastrar') {
        $nome  = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (!$nome || mb_strlen($nome) > 100) {
            $erros[] = 'Informe um nome válido (máx. 100 caracteres).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um e-mail válido.';
        }
        if (mb_strlen($senha) < 6) {
            $erros[] = 'A senha deve ter no mínimo 6 caracteres.';
        }

        if (!$erros) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $st->execute([':email' => $email]);
            if ((int)$st->fetchColumn() > 0) {
                $erros[] = 'Este e-mail já está em uso.';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $st = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)");
                $st->execute([':nome' => $nome, ':email' => $email, ':senha' => $hash]);

                flash('Conta criada com sucesso! Faça login para continuar.');
                redirecionar('login.php');
            }
        }
        $aba = 'cadastrar';
    }
}

$titulo_pagina = 'Acesso ao Sistema';
require_once __DIR__ . '/topo.php';
?>

<div style="max-width: 420px; margin: 40px auto 0;">
  <div class="card">
    <div class="abas" style="justify-content: center; margin-bottom: 20px;">
      <a href="login.php?aba=login" class="<?= $aba === 'login' ? 'ativa' : '' ?>">Entrar</a>
      <a href="login.php?aba=cadastrar" class="<?= $aba === 'cadastrar' ? 'ativa' : '' ?>">Criar Conta</a>
    </div>

    <?php if ($erros): ?>
      <div class="alerta alerta-erro">
        <ul style="margin:0; padding-left:18px;">
          <?php foreach ($erros as $e_msg): ?>
            <li><?= e($e_msg) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($aba === 'login'): ?>
      <form method="post" class="form-pilha">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="login">
        <label class="campo">E-mail
          <input type="email" name="email" required autofocus>
        </label>
        <label class="campo">Senha
          <input type="password" name="senha" required>
        </label>
        <button class="btn btn-primario">Entrar</button>
      </form>
    <?php else: ?>
      <form method="post" class="form-pilha">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="cadastrar">
        <label class="campo">Nome Completo
          <input name="nome" required autofocus maxlength="100">
        </label>
        <label class="campo">E-mail
          <input type="email" name="email" required>
        </label>
        <label class="campo">Senha (mín. 6 caracteres)
          <input type="password" name="senha" minlength="6" required>
        </label>
        <button class="btn btn-primario">Cadastrar</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/rodape.php'; ?>