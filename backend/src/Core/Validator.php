<?php
namespace App\Core;

/**
 * Validação (tipo, tamanho, formato) e sanitização das entradas do backend.
 * Toda entrada externa passa por aqui ANTES de chegar ao banco.
 */
class Validator
{
    /** Quantidade de caracteres (UTF-8). */
    public static function len($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    /**
     * Sanitiza um texto: exige UTF-8 válido, remove tags HTML e caracteres de
     * controle e normaliza espaços. Retorna null se o texto for inválido.
     */
    public static function cleanString($value)
    {
        if (!is_string($value) || preg_match('//u', $value) !== 1) {
            return null;
        }
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        if ($value === null) {
            return null;
        }
        $value = preg_replace('/\s+/u', ' ', $value);
        if ($value === null) {
            return null;
        }
        return trim($value);
    }

    /**
     * Valida e sanitiza o corpo de cadastro/edição de alien.
     * Retorna array($dadosLimpos, $erros). Se $erros estiver vazio, é válido.
     */
    public static function alien($input)
    {
        $errors = array();
        $data = array();

        if (!is_array($input)) {
            return array(array(), array('corpo' => 'Corpo da requisição inválido.'));
        }

        $campos = array(
            'nome' => array('Nome', 2, 100),
            'especie' => array('Espécie', 2, 100),
            'planeta_origem' => array('Planeta de origem', 2, 100),
            'descricao' => array('Descrição', 10, 2000),
        );

        foreach ($campos as $campo => $cfg) {
            $rotulo = $cfg[0];
            $raw = isset($input[$campo]) ? $input[$campo] : null;
            if ($raw === null || (is_string($raw) && trim($raw) === '')) {
                $errors[$campo] = $rotulo . ' é obrigatório.';
                continue;
            }
            if (!is_string($raw)) {
                $errors[$campo] = $rotulo . ' deve ser um texto.';
                continue;
            }
            $clean = self::cleanString($raw);
            if ($clean === null) {
                $errors[$campo] = $rotulo . ' contém caracteres inválidos.';
                continue;
            }
            $tamanho = self::len($clean);
            if ($tamanho < $cfg[1] || $tamanho > $cfg[2]) {
                $errors[$campo] = $rotulo . ' deve ter entre ' . $cfg[1] . ' e ' . $cfg[2] . ' caracteres.';
                continue;
            }
            $data[$campo] = $clean;
        }

        // habilidades: lista de textos (aceita também um texto com uma por linha)
        $hab = isset($input['habilidades']) ? $input['habilidades'] : null;
        if (is_string($hab)) {
            $hab = preg_split('/\r\n|\r|\n/', $hab);
        }
        if (!is_array($hab)) {
            $errors['habilidades'] = 'Informe ao menos uma habilidade.';
        } else {
            $lista = array();
            $invalida = false;
            foreach ($hab as $item) {
                if (!is_string($item)) {
                    $invalida = true;
                    break;
                }
                $clean = self::cleanString($item);
                if ($clean === null) {
                    $invalida = true;
                    break;
                }
                if ($clean === '') {
                    continue;
                }
                $tam = self::len($clean);
                if ($tam < 2 || $tam > 200) {
                    $invalida = true;
                    break;
                }
                $lista[] = $clean;
            }
            if ($invalida) {
                $errors['habilidades'] = 'Cada habilidade deve ser um texto de 2 a 200 caracteres.';
            } elseif (count($lista) < 1) {
                $errors['habilidades'] = 'Informe ao menos uma habilidade.';
            } elseif (count($lista) > 12) {
                $errors['habilidades'] = 'Informe no máximo 12 habilidades.';
            } else {
                $data['habilidades'] = $lista;
            }
        }

        // era_id: inteiro positivo
        $era = isset($input['era_id']) ? $input['era_id'] : null;
        if (is_string($era) && ctype_digit($era)) {
            $era = (int) $era;
        }
        if (!is_int($era) || $era < 1) {
            $errors['era_id'] = 'Era é obrigatória e deve ser um identificador válido.';
        } else {
            $data['era_id'] = $era;
        }

        // imagem_url: opcional; caminho local "assets/..." ou URL http(s)
        $url = isset($input['imagem_url']) ? $input['imagem_url'] : null;
        if ($url === null || (is_string($url) && trim($url) === '')) {
            $data['imagem_url'] = null;
        } elseif (!is_string($url)) {
            $errors['imagem_url'] = 'Imagem deve ser um texto.';
        } else {
            $url = trim($url);
            $padrao = '#^(assets/[A-Za-z0-9_\-./]+\.(png|jpe?g|gif|webp|svg)|https?://[^\s<>"\']+)$#i';
            if (strlen($url) > 255 || strpos($url, '..') !== false || preg_match($padrao, $url) !== 1) {
                $errors['imagem_url'] = 'Imagem inválida: use um caminho "assets/..." ou uma URL http(s) de até 255 caracteres.';
            } else {
                $data['imagem_url'] = $url;
            }
        }

        // imagem_alt: opcional
        $alt = isset($input['imagem_alt']) ? $input['imagem_alt'] : null;
        if ($alt === null || (is_string($alt) && trim($alt) === '')) {
            $data['imagem_alt'] = null;
        } elseif (!is_string($alt)) {
            $errors['imagem_alt'] = 'Texto alternativo deve ser um texto.';
        } else {
            $clean = self::cleanString($alt);
            if ($clean === null || self::len($clean) > 200) {
                $errors['imagem_alt'] = 'Texto alternativo deve ter no máximo 200 caracteres.';
            } else {
                $data['imagem_alt'] = $clean;
            }
        }

        return array($data, $errors);
    }
}