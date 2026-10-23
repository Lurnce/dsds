<?php
require_once __DIR__ . '/conexao.php';
exigi_login();

$u = usuario_logado();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $nome      = trim($_POST['nome'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if (!$nome || mb_strlen($nome) > 100) {
        $erros[] = 'Informe um nome válido.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }

    // Processamento de Upload da Foto de Perfil
    $caminho_foto = $u['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $exts_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $exts_permitidas)) {
            $erros[] = 'Formato de imagem inválido. Use JPG, PNG ou WEBP.';
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) { // 2MB max
            $erros[] = 'A foto deve ter no máximo 2MB.';
        } else {
            $dir_uploads = __DIR__ . '/uploads';
            if (!is_dir($dir_uploads)) {
                mkdir($dir_uploads, 0755, true);
            }

            $nome_arquivo = 'avatar_' . $u['id'] . '_' . time() . '.' . $ext;
            $destino = $dir_uploads . '/' . $nome_arquivo;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                // Remove foto antiga se existir
                if ($u['foto'] && file_exists(__DIR__ . '/' . $u['foto'])) {
                    @unlink(__DIR__ . '/' . $u['foto']);
                }
                $caminho_foto = 'uploads/' . $nome_arquivo;
            } else {
                $erros[] = 'Falha ao salvar a imagem.';
            }
        }
    }

    if (!$erros) {
        // Verifica e-mail em uso por outro id
        $st = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email AND id <> :id");
        $st->execute([':email' => $email, ':id' => $u['id']]);
        if ((int)$st->fetchColumn() > 0) {
            $erros[] = 'Este e-mail já pertence a outro usuário.';
        } else {
            $st = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, descricao = :desc, foto = :foto WHERE id = :id");
            $st->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':desc' => $descricao,
                ':foto' => $caminho_foto,
                ':id'   => $u['id'],
            ]);

            // Atualiza sessão
            $_SESSION['usuario']['nome']      = $nome;
            $_SESSION['usuario']['email']     = $email;
            $_SESSION['usuario']['descricao'] = $descricao;
            $_SESSION['usuario']['foto']      = $caminho_foto;

            flash('Perfil atualizado com sucesso!');
            redirecionar('perfil.php');
        }
    }
}

$u = usuario_logado();
$titulo_pagina = 'Meu Perfil';
require_once __DIR__ . '/topo.php';
?>

<div class="pagina-topo">
  <div>
    <h1>Meu Perfil</h1>
    <p>Gerencie suas informações pessoais e foto de perfil.</p>
  </div>
</div>

<div class="layout-lateral">
  <section class="card" style="text-align: center;">
    <div style="margin-bottom: 16px;">
      <?php if ($u['foto'] && file_exists(__DIR__ . '/' . $u['foto'])): ?>
        <img src="<?= e($u['foto']) ?>" alt="Avatar" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--verde);">
      <?php else: ?>
        <div style="width: 120px; height: 120px; border-radius: 50%; background: var(--verde-claro); color: var(--verde); display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: bold; margin: 0 auto; border: 3px solid var(--verde);">
          <?= strtoupper(substr($u['nome'], 0, 1)) ?>
        </div>
      <?php endif; ?>
    </div>
    <h2 style="margin-bottom: 4px;"><?= e($u['nome']) ?></h2>
    <p class="sub"><?= e($u['email']) ?></p>
    <?php if ($u['descricao']): ?>
      <p style="margin-top: 12px; font-style: italic; color: var(--suave);"><?= e($u['descricao']) ?></p>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>Editar Perfil</h2>

    <?php if ($erros): ?>
      <div class="alerta alerta-erro">
        <ul style="margin:0; padding-left:18px;">
          <?php foreach ($erros as $e_msg): ?>
            <li><?= e($e_msg) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-pilha">
      <?= csrf_field() ?>
      <label class="campo">Nome Completo
        <input name="nome" value="<?= e($u['nome']) ?>" required maxlength="100">
      </label>
      <label class="campo">E-mail
        <input type="email" name="email" value="<?= e($u['email']) ?>" required>
      </label>
      <label class="campo">Foto de Perfil (JPG, PNG ou WEBP)
        <input type="file" name="foto" accept="image/*">
      </label>
      <label class="campo">Descrição / Biografia
        <textarea name="descricao" rows="4" style="width:100%; padding:10px; border-radius:8px; border:1px solid #c9d3ce; font-family:inherit;"><?= e($u['descricao'] ?? '') ?></textarea>
      </label>
      <div class="form-acoes">
        <button class="btn btn-primario">Salvar Perfil</button>
      </div>
    </form>
  </section>
</div>

<?php require_once __DIR__ . '/rodape.php'; ?>