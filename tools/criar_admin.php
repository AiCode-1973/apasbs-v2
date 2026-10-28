<?php
/**
 * Cria ou redefine o usuário administrador.
 *
 * Substitui o setup.php da v1, que ficava exposto na web com uma senha
 * padrão conhecida. Este script SÓ roda pela linha de comando:
 *
 *     php tools/criar_admin.php "Nome do Admin" 00000000000 "SenhaForte123"
 *
 * Se o CPF já existir, a senha é redefinida e o usuário vira administrador.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado pela linha de comando.');
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';

$nome  = $argv[1] ?? '';
$cpf   = preg_replace('/\D/', '', $argv[2] ?? '') ?? '';
$senha = $argv[3] ?? '';

if ($nome === '' || strlen($cpf) !== 11 || strlen($senha) < 8) {
    fwrite(STDERR, "Uso: php tools/criar_admin.php \"Nome Completo\" CPF \"SenhaComMin8Caracteres\"\n");
    exit(1);
}

$pdo = getPDO();

try {
    // Garante a coluna is_admin (bancos vindos da v1 antes da migration_v2)
    try {
        $pdo->exec('ALTER TABLE usuarios ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER ativo');
        echo "Coluna is_admin criada.\n";
    } catch (PDOException) {
        // já existe
    }

    // Precisa existir ao menos um setor para o vínculo obrigatório
    $setorId = $pdo->query('SELECT id FROM setores ORDER BY id LIMIT 1')->fetchColumn();

    if ($setorId === false) {
        $pdo->exec("INSERT INTO setores (nome) VALUES ('Administração')");
        $setorId = (int) $pdo->lastInsertId();
        echo "Setor 'Administração' criado.\n";
    }

    $hash = password_hash($senha, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE cpf = ?');
    $stmt->execute([$cpf]);
    $existente = $stmt->fetchColumn();

    if ($existente !== false) {
        $up = $pdo->prepare('UPDATE usuarios SET nome = ?, senha = ?, ativo = 1, is_admin = 1 WHERE id = ?');
        $up->execute([$nome, $hash, (int) $existente]);
        echo "Usuário atualizado: senha redefinida e status de administrador concedido.\n";
    } else {
        $in = $pdo->prepare(
            'INSERT INTO usuarios (nome, cpf, senha, setor_id, ativo, is_admin) VALUES (?, ?, ?, ?, 1, 1)'
        );
        $in->execute([$nome, $cpf, $hash, (int) $setorId]);
        echo "Administrador criado com sucesso.\n";
    }

    printf("CPF de acesso: %s.%s.%s-%s\n",
        substr($cpf, 0, 3), substr($cpf, 3, 3), substr($cpf, 6, 3), substr($cpf, 9, 2));
} catch (PDOException $e) {
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
    exit(1);
}
