<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InstansiService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class InstansiController extends Controller
{
    use ApiResponses;

    protected $instansiService;

    public function __construct(InstansiService $instansiService)
    {
        $this->instansiService = $instansiService;
    }

    public function index()
    {
        $response = $this->instansiService->getAllInstansi();

        return $this->sendSuccessResponse($response, 'List of Instansi retrieved successfully.', 200);
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
