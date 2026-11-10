<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\EraModel;

/**
 * Controller das eras do Omnitrix (somente leitura, público).
 */
class EraController
{
    private $eras;

    public function __construct(EraModel $eras)
    {
        $this->eras = $eras;
    }

    /** GET /api/eras */
    public function index(Request $request, $params = array())
    {
        return Response::success($this->eras->all());
    }
}