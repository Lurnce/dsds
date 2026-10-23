<?php
require_once __DIR__ . '/conexao.php';
unset($_SESSION['usuario']);
flash('Sessão encerrada com sucesso.');
redirecionar('login.php');