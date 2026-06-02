<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    private $storageFile = 'articles.json';

    private function getStoragePath()
    {
        return storage_path('app/' . $this->storageFile);
    }

    private function getArticles()
    {
        $path = $this->getStoragePath();
        if (!file_exists($path)) {
            file_put_contents($path, json_encode([]));
            return [];
        }

        $content = file_get_contents($path);
        $articles = json_decode($content, true) ?: [];

        // Trier par date de publication (du plus récent au plus ancien)
        usort($articles, function($a, $b) {
            return strtotime($b['published_at']) - strtotime($a['published_at']);
        });

        return $articles;
    }

    private function saveArticles($articles)
    {
        $path = $this->getStoragePath();
        file_put_contents($path, json_encode($articles, JSON_PRETTY_PRINT));
    }

    /**
     * GET /api/articles - Liste des articles
     */
    public function index(Request $request)
    {
        try {
            $articles = $this->getArticles();

            // Pagination simple
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);
            $total = count($articles);
            $lastPage = ceil($total / $perPage);

            $offset = ($page - 1) * $perPage;
            $paginatedArticles = array_slice($articles, $offset, $perPage);

            return response()->json([
                'success' => true,
                'data' => $paginatedArticles,
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur index articles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des articles'
            ], 500);
        }
    }

    /**
     * GET /api/articles/{id} - Récupérer un article spécifique
     */
    public function show($id)
    {
        try {
            $articles = $this->getArticles();

            foreach ($articles as $article) {
                if ($article['id'] === $id) {
                    return response()->json([
                        'success' => true,
                        'data' => $article
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erreur show article: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'article'
            ], 500);
        }
    }

    /**
     * POST /api/admin/articles - Créer un article
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'excerpt' => 'nullable|string|max:500',
                'image_url' => 'nullable|url',
                'tags' => 'nullable|array'
            ]);

            $articles = $this->getArticles();

            // Générer un slug unique
            $slug = Str::slug($validated['title']);
            $slug = $this->makeSlugUnique($slug, $articles);

            $id = Str::uuid()->toString();
            $excerpt = $validated['excerpt'] ?? substr(strip_tags($validated['content']), 0, 160);

            $article = [
                'id' => $id,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'excerpt' => $excerpt,
                'slug' => $slug,
                'image_url' => $validated['image_url'] ?? '',
                'tags' => $validated['tags'] ?? [],
                'published_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
                'author' => 'admin'
            ];

            $articles[] = $article;
            $this->saveArticles($articles);

            Log::info('Article créé', ['id' => $id, 'title' => $validated['title']]);

            return response()->json([
                'success' => true,
                'message' => 'Article créé avec succès',
                'data' => ['id' => $id, 'slug' => $slug]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur création article: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'article: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/admin/articles/{id} - Mettre à jour un article
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'excerpt' => 'nullable|string|max:500',
                'image_url' => 'nullable|url',
                'tags' => 'nullable|array'
            ]);

            $articles = $this->getArticles();
            $found = false;

            foreach ($articles as $key => $article) {
                if ($article['id'] === $id) {
                    // Mettre à jour les champs
                    if (isset($validated['title'])) {
                        $articles[$key]['title'] = $validated['title'];
                        $articles[$key]['slug'] = $this->makeSlugUnique(Str::slug($validated['title']), $articles, $id);
                    }
                    if (isset($validated['content'])) {
                        $articles[$key]['content'] = $validated['content'];
                        if (!isset($validated['excerpt'])) {
                            $articles[$key]['excerpt'] = substr(strip_tags($validated['content']), 0, 160);
                        }
                    }
                    if (isset($validated['excerpt'])) {
                        $articles[$key]['excerpt'] = $validated['excerpt'];
                    }
                    if (isset($validated['image_url'])) {
                        $articles[$key]['image_url'] = $validated['image_url'];
                    }
                    if (isset($validated['tags'])) {
                        $articles[$key]['tags'] = $validated['tags'];
                    }

                    $articles[$key]['updated_at'] = now()->toIso8601String();
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé'
                ], 404);
            }

            $this->saveArticles($articles);

            Log::info('Article mis à jour', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Article mis à jour'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/articles/{id} - Supprimer un article
     */
    public function destroy($id)
    {
        try {
            $articles = $this->getArticles();
            $found = false;

            foreach ($articles as $key => $article) {
                if ($article['id'] === $id) {
                    unset($articles[$key]);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé'
                ], 404);
            }

            $this->saveArticles(array_values($articles));

            Log::info('Article supprimé', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Article supprimé'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Vérifier et rendre un slug unique
     */
    private function makeSlugUnique($slug, $articles, $excludeId = null)
    {
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $articles, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Vérifier si un slug existe déjà
     */
    private function slugExists($slug, $articles, $excludeId = null)
    {
        foreach ($articles as $article) {
            if ($excludeId && $article['id'] === $excludeId) {
                continue;
            }
            if ($article['slug'] === $slug) {
                return true;
            }
        }
        return false;
    }
}
