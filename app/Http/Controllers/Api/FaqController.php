<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/faqs",
     *     tags={"FAQs"},
     *     summary="Tampilkan semua FAQ",
     *     description="Mengambil semua data FAQ yang tersedia.",
     *     @OA\Response(
     *         response=200,
     *         description="Daftar FAQ berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar FAQ berhasil diambil."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Faq")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $faqs = Faq::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar FAQ berhasil diambil.',
            'data' => $faqs,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/faqs",
     *     tags={"FAQs"},
     *     summary="Buat FAQ baru",
     *     description="Menambahkan FAQ baru ke sistem.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question"},
     *             @OA\Property(property="question", type="string", example="Bagaimana cara reset password?"),
     *             @OA\Property(property="answer", type="string", example="Anda dapat mereset password melalui menu profil."),
     *             @OA\Property(property="is_published", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="FAQ berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQ berhasil dibuat."),
     *             @OA\Property(property="data", ref="#/components/schemas/Faq")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $faq = Faq::create([
            'question' => $validated['question'],
            'answer' => $validated['answer'] ?? null,
            'is_published' => $validated['is_published'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil dibuat.',
            'data' => $faq,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/faqs/{id}",
     *     tags={"FAQs"},
     *     summary="Tampilkan satu FAQ",
     *     description="Mengambil satu FAQ berdasarkan ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID FAQ",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQ ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Faq")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="FAQ tidak ditemukan"
     *     )
     * )
     */
    public function show($id)
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $faq,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/faqs/{id}",
     *     tags={"FAQs"},
     *     summary="Perbarui FAQ",
     *     description="Memperbarui data FAQ berdasarkan ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID FAQ",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question"},
     *             @OA\Property(property="question", type="string", example="Bagaimana cara login?"),
     *             @OA\Property(property="answer", type="string", example="Gunakan email dan password yang terdaftar."),
     *             @OA\Property(property="is_published", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQ berhasil diperbarui",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQ berhasil diperbarui."),
     *             @OA\Property(property="data", ref="#/components/schemas/Faq")
     *         )
     *     ),
     *     @OA\Response(response=404, description="FAQ tidak ditemukan")
     * )
     */
    public function update(Request $request, $id)
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $faq->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil diperbarui.',
            'data' => $faq,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/faqs/{id}",
     *     tags={"FAQs"},
     *     summary="Hapus FAQ",
     *     description="Menghapus FAQ berdasarkan ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID FAQ",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=200, description="FAQ berhasil dihapus"),
     *     @OA\Response(response=404, description="FAQ tidak ditemukan")
     * )
     */
    public function destroy($id)
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ tidak ditemukan.',
            ], 404);
        }

        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil dihapus.',
        ]);
    }
}
