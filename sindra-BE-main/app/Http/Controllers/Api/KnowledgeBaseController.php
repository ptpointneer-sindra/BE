<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KnowledgeBaseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/knowledge-bases",
     *     tags={"Knowledge Base"},
     *     summary="Tampilkan semua Knowledge Base",
     *     description="Ambil daftar Knowledge Base dengan filter opsional berdasarkan kategori, status publikasi, atau pencarian judul.",
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter berdasarkan kategori",
     *         required=false,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="is_published",
     *         in="query",
     *         description="Filter berdasarkan status publikasi (true/false)",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Cari berdasarkan judul",
     *         required=false,
     *         @OA\Schema(type="string", example="Panduan Reset Password")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar Knowledge Base berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar knowledge base berhasil diambil."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/KnowledgeBase")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = KnowledgeBase::with(['category', 'author'])
            ->orderBy('created_at', 'desc');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_published')) {
            $query->where('is_published', $request->is_published);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->has('limit')) {
            $data = $query->limit($request->limit)->get();
        } else {
            $data = $query->get();
        }
        return response()->json([
            'success' => true,
            'message' => 'Daftar knowledge base berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/knowledge-bases",
     *     tags={"Knowledge Base"},
     *     summary="Buat Knowledge Base baru",
     *     description="Menambahkan artikel baru ke knowledge base.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"author_id", "title"},
     *             @OA\Property(property="author_id", type="integer", example=1),
     *             @OA\Property(property="category_id", type="integer", example=2),
     *             @OA\Property(property="title", type="string", example="Panduan Menggunakan Aplikasi"),
     *             @OA\Property(property="content", type="string", example="Langkah-langkah penggunaan aplikasi dijelaskan di sini."),
     *             @OA\Property(property="is_published", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Knowledge Base berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Knowledge base berhasil dibuat."),
     *             @OA\Property(property="data", ref="#/components/schemas/KnowledgeBase")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'title' => 'required|string|max:255',
            'brief' => 'nullable|string',
            'content' => 'nullable|string',
            'is_published' => 'boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $imageUrl = url('storage/placeholder/default.png');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('knowledge-base', 'public');
            $imageUrl = url('storage/' . $path);
        }

        $knowledgeBase = KnowledgeBase::create([
            'author_id' => Auth::id(),
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'],
            'brief' => $validated['brief'],
            'image_url' => $imageUrl,
            'slug' => Str::slug($validated['title']),
            'content' => $validated['content'] ?? null,
            'is_published' => $validated['is_published'] ?? false,
            'published_at' => ($validated['is_published'] ?? false) ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Knowledge base berhasil dibuat.',
            'data' => $knowledgeBase->load(['category', 'author']),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/knowledge-bases/{id}",
     *     tags={"Knowledge Base"},
     *     summary="Tampilkan detail Knowledge Base",
     *     description="Menampilkan satu Knowledge Base berdasarkan ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Knowledge Base",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Knowledge Base ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/KnowledgeBase")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Knowledge Base tidak ditemukan")
     * )
     */
    public function show($id)
    {
        $kb = KnowledgeBase::with(['category', 'author'])->find($id);

        if (!$kb) {
            return response()->json([
                'success' => false,
                'message' => 'Knowledge base tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $kb,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/knowledge-bases/{id}",
     *     tags={"Knowledge Base"},
     *     summary="Perbarui Knowledge Base",
     *     description="Memperbarui artikel knowledge base berdasarkan ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Knowledge Base",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title"},
     *             @OA\Property(property="category_id", type="integer", example=3),
     *             @OA\Property(property="title", type="string", example="Panduan Update Akun"),
     *             @OA\Property(property="content", type="string", example="Cara memperbarui data akun pengguna."),
     *             @OA\Property(property="is_published", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Knowledge Base berhasil diperbarui",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Knowledge base berhasil diperbarui."),
     *             @OA\Property(property="data", ref="#/components/schemas/KnowledgeBase")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Knowledge Base tidak ditemukan")
     * )
     */
    public function update(Request $request, $id)
    {
        $kb = KnowledgeBase::find($id);

        if (!$kb) {
            return response()->json([
                'success' => false,
                'message' => 'Knowledge base tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'title' => 'required|string|max:255',
            'brief' => 'nullable|string',
            'content' => 'nullable|string',
            'is_published' => 'boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $imageUrl = $kb->image_url; // default pakai gambar lama

        // Jika user upload gambar baru
        if ($request->hasFile('image')) {

            // Hapus gambar lama (jika bukan placeholder)
            if ($kb->image_url && str_contains($kb->image_url, 'storage/')) {
                $oldPath = str_replace(url('storage') . '/', '', $kb->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            // Simpan gambar baru
            $path = $request->file('image')->store('knowledge-base', 'public');
            $imageUrl = url('storage/' . $path);
        }

        $kb->update([
            'category_id' => $validated['category_id'] ?? $kb->category_id,
            'title' => $validated['title'],
            'brief' => $validated['brief'],
            'slug' => Str::slug($validated['title']),
            'content' => $validated['content'] ?? $kb->content,
            'is_published' => $validated['is_published'] ?? $kb->is_published,
            'published_at' => ($validated['is_published'] ?? $kb->is_published) ? now() : null,
            'image_url' => $imageUrl, // update gambar
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Knowledge base berhasil diperbarui.',
            'data' => $kb->load(['category', 'author']),
        ]);
    }


    /**
     * @OA\Delete(
     *     path="/api/knowledge-bases/{id}",
     *     tags={"Knowledge Base"},
     *     summary="Hapus Knowledge Base",
     *     description="Menghapus artikel Knowledge Base berdasarkan ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Knowledge Base",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=200, description="Knowledge Base berhasil dihapus"),
     *     @OA\Response(response=404, description="Knowledge Base tidak ditemukan")
     * )
     */
    public function destroy($id)
    {
        $kb = KnowledgeBase::find($id);

        if (!$kb) {
            return response()->json([
                'success' => false,
                'message' => 'Knowledge base tidak ditemukan.',
            ], 404);
        }

        $kb->delete();

        return response()->json([
            'success' => true,
            'message' => 'Knowledge base berhasil dihapus.',
        ]);
    }
}
