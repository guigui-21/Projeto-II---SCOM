<?php
namespace App\Models;

use PDO;

/**
 * Model: acesso aos dados da tabela "usuarios" (somente consultas parametrizadas).
 */
class UsuarioModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmail($email)
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, email, senha, perfil FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(array(':email' => $email));
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, email, senha, perfil FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? $row : null;
    }
}