<?php
namespace App\Core;

/**
 * Roteador mínimo: associa método + padrão de rota (ex.: /api/aliens/{id})
 * a um callable que recebe (Request $request, array $params) e devolve Response.
 */
class Router
{
    private $routes = array();

    public function add($method, $pattern, $handler)
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = array(strtoupper($method), $regex, $handler);
    }

    public function dispatch(Request $request)
    {
        $allowed = array();
        foreach ($this->routes as $route) {
            if (preg_match($route[1], $request->path, $matches)) {
                if ($route[0] === $request->method) {
                    $params = array();
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }
                    return call_user_func($route[2], $request, $params);
                }
                $allowed[] = $route[0];
            }
        }
        if (count($allowed) > 0) {
            throw new HttpException(405, 'Método não permitido para esta rota. Métodos aceitos: ' . implode(', ', array_unique($allowed)) . '.');
        }
        // A rota é devolvida ao cliente: escapada para evitar XSS refletido.
        throw new HttpException(404, 'Rota não encontrada: ' . htmlspecialchars($request->path, ENT_QUOTES, 'UTF-8'));
    }
}