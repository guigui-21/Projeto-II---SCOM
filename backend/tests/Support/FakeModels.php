<?php
namespace Tests\Support;

use App\Models\AlienModel;
use App\Models\EraModel;
use App\Models\UsuarioModel;

/**
 * Models "falsos" que guardam os dados em memória.
 * Permitem testar a lógica dos controllers sem precisar de um MySQL rodando.
 * (Herdam dos models reais e NÃO chamam o construtor pai, que exigiria um PDO.)
 */
class FakeUsuarioModel extends UsuarioModel
{
    public $rows = array();

    public function __construct()
    {
    }

    public function adicionar($id, $nome, $email, $senhaPlana, $perfil)
    {
        $this->rows[$id] = array(
            'id' => $id,
            'nome' => $nome,
            'email' => $email,
            'senha' => password_hash($senhaPlana, PASSWORD_DEFAULT),
            'perfil' => $perfil,
        );
    }

    public function create($nome, $email, $senhaHash, $perfil)
    {
        $id = count($this->rows) > 0 ? max(array_keys($this->rows)) + 1 : 1;
        $this->rows[$id] = array(
            'id' => $id,
            'nome' => $nome,
            'email' => $email,
            'senha' => $senhaHash,
            'perfil' => $perfil,
        );
        return $id;
    }

    public function findByEmail($email)
    {
        foreach ($this->rows as $row) {
            if ($row['email'] === $email) {
                return $row;
            }
        }
        return null;
    }

    public function findById($id)
    {
        return isset($this->rows[(int) $id]) ? $this->rows[(int) $id] : null;
    }
}

class FakeEraModel extends EraModel
{
    public $rows = array();

    public function __construct()
    {
        $this->rows = array(
            1 => array('id' => 1, 'slug' => 'classico', 'nome' => 'Clássico (2005)', 'descricao' => 'Primeira era'),
            2 => array('id' => 2, 'slug' => 'alienForce', 'nome' => 'Força Alienígena (2008)', 'descricao' => 'Segunda era'),
        );
    }

    public function all()
    {
        return array_values($this->rows);
    }

    public function find($id)
    {
        return isset($this->rows[(int) $id]) ? $this->rows[(int) $id] : null;
    }

    public function findBySlug($slug)
    {
        foreach ($this->rows as $row) {
            if ($row['slug'] === $slug) {
                return $row;
            }
        }
        return null;
    }
}

class FakeAlienModel extends AlienModel
{
    public $rows = array();
    private $nextId = 1;

    public function __construct()
    {
    }

    public function paginate($page, $perPage, $eraId = null)
    {
        $lista = array();
        foreach ($this->rows as $row) {
            if ($eraId === null || $row['era_id'] === (int) $eraId) {
                $lista[] = $row;
            }
        }
        return array_slice($lista, ($page - 1) * $perPage, $perPage);
    }

    public function count($eraId = null)
    {
        return count($this->paginate(1, 100000, $eraId));
    }

    public function find($id)
    {
        return isset($this->rows[(int) $id]) ? $this->rows[(int) $id] : null;
    }

    public function create($data, $usuarioId)
    {
        $id = $this->nextId++;
        $this->rows[$id] = array_merge($data, array(
            'id' => $id,
            'era_slug' => 'classico',
            'era_nome' => 'Clássico (2005)',
            'usuario_id' => (int) $usuarioId,
            'criado_em' => date('Y-m-d H:i:s'),
        ));
        return $id;
    }

    public function update($id, $data)
    {
        if (!isset($this->rows[(int) $id])) {
            return false;
        }
        $this->rows[(int) $id] = array_merge($this->rows[(int) $id], $data);
        return true;
    }

    public function delete($id)
    {
        if (!isset($this->rows[(int) $id])) {
            return false;
        }
        unset($this->rows[(int) $id]);
        return true;
    }
}