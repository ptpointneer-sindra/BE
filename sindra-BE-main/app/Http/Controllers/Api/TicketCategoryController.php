<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class TicketCategoryController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $categories = TicketCategory::all()->toArray();

        return $this->sendSuccessResponse($categories, 'List of ticket categories retrieved successfully.', 200);
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
