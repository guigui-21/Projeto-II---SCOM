<?php
namespace App\Models;

use PDO;

/**
 * Model: acesso aos dados da tabela "aliens" (entidade principal, CRUD).
 * Todas as consultas usam PDO com prepared statements (anti SQL Injection).
 */
class AlienModel
{
    private $pdo;

    const SELECT = 'SELECT a.id, a.nome, a.especie, a.planeta_origem, a.descricao, a.habilidades,
                           a.imagem_url, a.imagem_alt, a.era_id, e.slug AS era_slug, e.nome AS era_nome,
                           a.usuario_id, a.criado_em
                    FROM aliens a
                    INNER JOIN eras e ON e.id = a.era_id';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function paginate($page, $perPage, $eraId = null)
    {
        $sql = self::SELECT;
        if ($eraId !== null) {
            $sql .= ' WHERE a.era_id = :era_id';
        }
        $sql .= ' ORDER BY a.era_id ASC, a.id ASC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        if ($eraId !== null) {
            $stmt->bindValue(':era_id', (int) $eraId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ((int) $page - 1) * (int) $perPage, PDO::PARAM_INT);
        $stmt->execute();

        $result = array();
        foreach ($stmt->fetchAll() as $row) {
            $result[] = $this->hydrate($row);
        }
        return $result;
    }

    public function count($eraId = null)
    {
        if ($eraId !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM aliens WHERE era_id = :era_id');
            $stmt->bindValue(':era_id', (int) $eraId, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) FROM aliens');
        }
        return (int) $stmt->fetchColumn();
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE a.id = :id LIMIT 1');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function create($data, $usuarioId)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO aliens (nome, especie, planeta_origem, descricao, habilidades, imagem_url, imagem_alt, era_id, usuario_id)
             VALUES (:nome, :especie, :planeta_origem, :descricao, :habilidades, :imagem_url, :imagem_alt, :era_id, :usuario_id)'
        );
        $stmt->execute(array(
            ':nome' => $data['nome'],
            ':especie' => $data['especie'],
            ':planeta_origem' => $data['planeta_origem'],
            ':descricao' => $data['descricao'],
            ':habilidades' => json_encode($data['habilidades'], JSON_UNESCAPED_UNICODE),
            ':imagem_url' => $data['imagem_url'],
            ':imagem_alt' => $data['imagem_alt'],
            ':era_id' => (int) $data['era_id'],
            ':usuario_id' => (int) $usuarioId,
        ));
        return (int) $this->pdo->lastInsertId();
    }

    public function update($id, $data)
    {
        $stmt = $this->pdo->prepare(
            'UPDATE aliens
                SET nome = :nome, especie = :especie, planeta_origem = :planeta_origem,
                    descricao = :descricao, habilidades = :habilidades,
                    imagem_url = :imagem_url, imagem_alt = :imagem_alt, era_id = :era_id
              WHERE id = :id'
        );
        return $stmt->execute(array(
            ':nome' => $data['nome'],
            ':especie' => $data['especie'],
            ':planeta_origem' => $data['planeta_origem'],
            ':descricao' => $data['descricao'],
            ':habilidades' => json_encode($data['habilidades'], JSON_UNESCAPED_UNICODE),
            ':imagem_url' => $data['imagem_url'],
            ':imagem_alt' => $data['imagem_alt'],
            ':era_id' => (int) $data['era_id'],
            ':id' => (int) $id,
        ));
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM aliens WHERE id = :id');
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function hydrate($row)
    {
        $habilidades = json_decode((string) $row['habilidades'], true);
        return array(
            'id' => (int) $row['id'],
            'nome' => $row['nome'],
            'especie' => $row['especie'],
            'planeta_origem' => $row['planeta_origem'],
            'descricao' => $row['descricao'],
            'habilidades' => is_array($habilidades) ? $habilidades : array(),
            'imagem_url' => $row['imagem_url'],
            'imagem_alt' => $row['imagem_alt'],
            'era_id' => (int) $row['era_id'],
            'era_slug' => $row['era_slug'],
            'era_nome' => $row['era_nome'],
            'usuario_id' => (int) $row['usuario_id'],
            'criado_em' => $row['criado_em'],
        );
    }
}