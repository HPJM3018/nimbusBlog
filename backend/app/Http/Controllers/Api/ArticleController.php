<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    protected $dynamodb;
    protected $tableName;

    public function __construct()
    {
        $this->dynamodb = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);
        $this->tableName = env('DYNAMODB_TABLE_ARTICLES', 'articles');
    }

    /**
     * GET /api/articles - List all articles
     */
    public function index(Request $request)
    {
        try {
            $result = $this->dynamodb->scan([
                'TableName' => $this->tableName,
            ]);

            $articles = [];
            foreach ($result['Items'] as $item) {
                $article = [];
                foreach ($item as $key => $value) {
                    if ($key === 'tags') {
                        $article[$key] = isset($value['L']) ? array_map(fn($tag) => $tag['S'], $value['L']) : [];
                    } else {
                        $article[$key] = reset($value);
                    }
                }
                $articles[] = $article;
            }

            // Sort by most recent first
            usort($articles, function($a, $b) {
                return strtotime($b['published_at']) - strtotime($a['published_at']);
            });

            return response()->json([
                'success' => true,
                'data' => $articles,
                'meta' => [
                    'total' => count($articles),
                    'last_page' => 1,
                    'current_page' => 1,
                    'per_page' => count($articles)
                ]
            ]);

        } catch (DynamoDbException $e) {
            Log::error('DynamoDB error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch articles: ' . $e->getAwsErrorMessage()
            ], 500);
        }
    }

    /**
     * GET /api/articles/{id} - Get a single article
     */
    public function show($id)
    {
        try {
            $result = $this->dynamodb->getItem([
                'TableName' => $this->tableName,
                'Key' => ['id' => ['S' => $id]]
            ]);

            if (!isset($result['Item'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found'
                ], 404);
            }

            $article = [];
            foreach ($result['Item'] as $key => $value) {
                if ($key === 'tags') {
                    $article[$key] = isset($value['L']) ? array_map(fn($tag) => $tag['S'], $value['L']) : [];
                } else {
                    $article[$key] = reset($value);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $article
            ]);

        } catch (DynamoDbException $e) {
            Log::error('DynamoDB error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch article'
            ], 500);
        }
    }

    /**
     * POST /api/admin/articles - Create a new article
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

            $id = Str::uuid()->toString();
            $slug = Str::slug($validated['title']);
            $excerpt = $validated['excerpt'] ?? substr(strip_tags($validated['content']), 0, 160);

            $item = [
                'TableName' => $this->tableName,
                'Item' => [
                    'id' => ['S' => $id],
                    'title' => ['S' => $validated['title']],
                    'content' => ['S' => $validated['content']],
                    'excerpt' => ['S' => $excerpt],
                    'slug' => ['S' => $slug],
                    'image_url' => ['S' => $validated['image_url'] ?? ''],
                    'tags' => ['L' => array_map(fn($tag) => ['S' => $tag], $validated['tags'] ?? [])],
                    'published_at' => ['S' => now()->toIso8601String()],
                    'updated_at' => ['S' => now()->toIso8601String()],
                    'author' => ['S' => $request->user['cognito:username'] ?? 'admin']
                ]
            ];

            $this->dynamodb->putItem($item);

            Log::info('Article created in DynamoDB', ['id' => $id, 'title' => $validated['title']]);

            return response()->json([
                'success' => true,
                'message' => 'Article created successfully',
                'data' => ['id' => $id, 'slug' => $slug]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (DynamoDbException $e) {
            Log::error('Error creating article in DynamoDB: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create article: ' . $e->getAwsErrorMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/admin/articles/{id} - Update an article
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

            // Check if article exists
            $check = $this->dynamodb->getItem([
                'TableName' => $this->tableName,
                'Key' => ['id' => ['S' => $id]]
            ]);

            if (!isset($check['Item'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found'
                ], 404);
            }

            $updateExpression = 'SET updated_at = :updated_at';
            $expressionValues = [':updated_at' => ['S' => now()->toIso8601String()]];

            if (isset($validated['title'])) {
                $updateExpression .= ', title = :title';
                $expressionValues[':title'] = ['S' => $validated['title']];
                $updateExpression .= ', slug = :slug';
                $expressionValues[':slug'] = ['S' => Str::slug($validated['title'])];
            }
            if (isset($validated['content'])) {
                $updateExpression .= ', content = :content';
                $expressionValues[':content'] = ['S' => $validated['content']];
            }
            if (isset($validated['excerpt'])) {
                $updateExpression .= ', excerpt = :excerpt';
                $expressionValues[':excerpt'] = ['S' => $validated['excerpt']];
            }
            if (isset($validated['image_url'])) {
                $updateExpression .= ', image_url = :image_url';
                $expressionValues[':image_url'] = ['S' => $validated['image_url']];
            }
            if (isset($validated['tags'])) {
                $updateExpression .= ', tags = :tags';
                $expressionValues[':tags'] = ['L' => array_map(fn($tag) => ['S' => $tag], $validated['tags'])];
            }

            $this->dynamodb->updateItem([
                'TableName' => $this->tableName,
                'Key' => ['id' => ['S' => $id]],
                'UpdateExpression' => $updateExpression,
                'ExpressionAttributeValues' => $expressionValues
            ]);

            Log::info('Article updated in DynamoDB', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Article updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (DynamoDbException $e) {
            Log::error('Error updating article in DynamoDB: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update article: ' . $e->getAwsErrorMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/articles/{id} - Delete an article
     */
    public function destroy($id)
    {
        try {
            // Check if article exists
            $check = $this->dynamodb->getItem([
                'TableName' => $this->tableName,
                'Key' => ['id' => ['S' => $id]]
            ]);

            if (!isset($check['Item'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found'
                ], 404);
            }

            $this->dynamodb->deleteItem([
                'TableName' => $this->tableName,
                'Key' => ['id' => ['S' => $id]]
            ]);

            Log::info('Article deleted from DynamoDB', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Article deleted successfully'
            ]);

        } catch (DynamoDbException $e) {
            Log::error('Error deleting article from DynamoDB: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete article: ' . $e->getAwsErrorMessage()
            ], 500);
        }
    }
}
