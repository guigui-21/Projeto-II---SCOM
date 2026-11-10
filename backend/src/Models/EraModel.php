<?php
namespace App\Models;

use PDO;

/**
 * Model: acesso aos dados da tabela "eras".
 */
class EraModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        $stmt = $this->pdo->query('SELECT id, slug, nome, descricao FROM eras ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare('SELECT id, slug, nome, descricao FROM eras WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public function findBySlug($slug)
    {
        $stmt = $this->pdo->prepare('SELECT id, slug, nome, descricao FROM eras WHERE slug = :slug LIMIT 1');
        $stmt->execute(array(':slug' => $slug));
        $row = $stmt->fetch();
        return $row ? $row : null;
    }
}