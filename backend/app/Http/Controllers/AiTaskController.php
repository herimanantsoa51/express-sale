<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAiTasksJob;
use App\Models\AiProviderConfig;
use App\Models\AiTask;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\AiTaskOrchestrator;

class AiTaskController extends Controller
{
    /**
     * POST /api/ai/langgraph/query — Relayer une question vers langgraph_service
     */
    public function langgraphQuery(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:5000',
            'top_k' => 'nullable|integer|min:1|max:10',
            'context' => 'nullable|array',
            'session_id' => 'required|string|max:120',
            'debug' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $base = config('services.langgraph_service.url');
        if (!$base) {
            return response()->json(['message' => 'LangGraph service not configured'], 400);
        }

        $data = $validator->validated();
        $userId = $request->user()->id;
        $requestHistory = array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $data['context'] ?? []
        )));
        $history = $requestHistory !== []
            ? $requestHistory
            : $this->buildLanggraphHistory($userId, $data['session_id'], 6);

        $task = AiTask::create([
            'user_id' => $request->user()->id,
            'provider_config_id' => null,
            'status' => 'processing',
            'intent' => $data['text'],
            'context' => [
                'session_id' => $data['session_id'],
                'history' => $history,
                'top_k' => $data['top_k'] ?? 3,
                'channel' => 'langgraph',
                'debug' => (bool) ($data['debug'] ?? false),
            ],
            'model' => 'langgraph-rag-v1',
            'started_at' => now(),
        ]);

        try {
            /** @var Response $response */
            $response = Http::timeout(30)->post(rtrim($base, '/') . '/query', [
                'text' => $data['text'],
                'top_k' => $data['top_k'] ?? 3,
                'context' => $history,
                'debug' => (bool) ($data['debug'] ?? false),
                'metadata' => [
                    'session_id' => $data['session_id'],
                    'user_id' => $userId,
                ],
            ]);
            if (!$response->successful()) {
                $task->update([
                    'status' => 'failed',
                    'error_message' => 'LangGraph query failed',
                    'completed_at' => now(),
                ]);
                return response()->json(['message' => 'LangGraph query failed'], 400);
            }

            $payload = $response->json();
            $safePayload = is_array($payload) ? $payload : ['raw' => (string) $response->body()];
            $assistantText = $safePayload['response'] ?? '';
            $task->update([
                'status' => 'completed',
                'tasks' => [[
                    'type' => 'advisor',
                    'action' => 'message',
                    'payload' => ['text' => $assistantText],
                ]],
                'raw_response' => $safePayload,
                'completed_at' => now(),
            ]);

            return response()->json([
                'task_id' => $task->id,
                'session_id' => $data['session_id'],
                ...$safePayload,
            ]);
        } catch (\Throwable $e) {
            $task->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            return response()->json(['message' => 'LangGraph query error'], 400);
        }
    }

    /**
     * POST /api/ai/langgraph/rag/faqs — Ingestion FAQ FR/MG
     */
    public function langgraphIngestFaqs(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|in:fr,mg',
            'replace' => 'nullable|boolean',
            'faqs' => 'required|array|min:1',
            'faqs.*.question' => 'required|string|max:2000',
            'faqs.*.answer' => 'required|string|max:5000',
            'faqs.*.id' => 'nullable|string|max:120',
            'faqs.*.tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $base = config('services.langgraph_service.url');
        if (!$base) {
            return response()->json(['message' => 'LangGraph service not configured'], 400);
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(30)->post(rtrim($base, '/') . '/rag/faqs', $validator->validated());
            if (!$response->successful()) {
                return response()->json(['message' => 'LangGraph FAQ ingestion failed'], 400);
            }
            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'LangGraph FAQ ingestion error'], 400);
        }
    }

    /**
     * POST /api/ai/langgraph/rag/faqs/pairs — Ingestion FAQ en paires FR/MG
     */
    public function langgraphIngestFaqPairs(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'replace' => 'nullable|boolean',
            'faqs' => 'required|array|min:1',
            'faqs.*.id' => 'nullable|string|max:120',
            'faqs.*.question_fr' => 'required|string|max:2000',
            'faqs.*.answer_fr' => 'required|string|max:5000',
            'faqs.*.question_mg' => 'required|string|max:2000',
            'faqs.*.answer_mg' => 'required|string|max:5000',
            'faqs.*.tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $base = config('services.langgraph_service.url');
        if (!$base) {
            return response()->json(['message' => 'LangGraph service not configured'], 400);
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(30)->post(rtrim($base, '/') . '/rag/faqs/pairs', $validator->validated());
            if (!$response->successful()) {
                return response()->json(['message' => 'LangGraph FAQ pairs ingestion failed'], 400);
            }
            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'LangGraph FAQ pairs ingestion error'], 400);
        }
    }

    /**
     * POST /api/ai/langgraph/rag/pdfs — Ingestion PDF vers RAG
     */
    public function langgraphIngestPdfs(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:pdf|max:20480',
            'language' => 'nullable|in:fr,mg',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $base = config('services.langgraph_service.url');
        if (!$base) {
            return response()->json(['message' => 'LangGraph service not configured'], 400);
        }

        try {
            $http = Http::timeout(60);
            foreach ($request->file('files', []) as $file) {
                $http = $http->attach('files', file_get_contents($file->getRealPath()), $file->getClientOriginalName());
            }

            $endpoint = rtrim($base, '/') . '/rag/pdfs';
            if ($request->filled('language')) {
                $endpoint .= '?language=' . $request->input('language');
            }

            /** @var Response $response */
            $response = $http->post($endpoint);
            if (!$response->successful()) {
                return response()->json(['message' => 'LangGraph PDF ingestion failed'], 400);
            }
            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'LangGraph PDF ingestion error'], 400);
        }
    }

    /**
     * POST /api/ai/langgraph/rag/page-routes — Ingestion page_routes.json vers RAG
     */
    public function langgraphIngestPageRoutes(): JsonResponse
    {
        $base = config('services.langgraph_service.url');
        if (!$base) {
            return response()->json(['message' => 'LangGraph service not configured'], 400);
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(30)->post(rtrim($base, '/') . '/rag/page-routes');
            if (!$response->successful()) {
                return response()->json(['message' => 'LangGraph page routes ingestion failed'], 400);
            }
            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'LangGraph page routes ingestion error'], 400);
        }
    }

    /**
     * GET /api/ai/langgraph/rag/status — Statut des documents LangGraph
     */
    public function langgraphRagStatus(): JsonResponse
    {
        $base = config('services.langgraph_service.url');
        if (!$base) {
            return response()->json(['message' => 'LangGraph service not configured'], 400);
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(10)->get(rtrim($base, '/') . '/rag/status');
            if (!$response->successful()) {
                return response()->json(['message' => 'LangGraph RAG status unavailable'], 400);
            }
            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'LangGraph RAG status error'], 400);
        }
    }

    /**
     * GET /api/ai/langgraph/conversations — Historique conversations LangGraph via ai_tasks
     */
    public function langgraphConversations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:120',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $query = AiTask::query()
            ->where('user_id', $request->user()->id)
            ->where('model', 'langgraph-rag-v1')
            ->where('context->session_id', $data['session_id'])
            ->orderBy('id');

        $perPage = (int) ($data['per_page'] ?? 20);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'tasks' => $paginator->getCollection()->map(fn ($task) => $this->formatTask($task)),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/ai/langgraph/messages — Messages chat (user/assistant) pour une session
     */
    public function langgraphMessages(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:120',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $limit = (int) ($data['limit'] ?? 100);

        $tasks = AiTask::query()
            ->where('user_id', $request->user()->id)
            ->where('model', 'langgraph-rag-v1')
            ->where('context->session_id', $data['session_id'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $messages = [];
        foreach ($tasks as $task) {
            $messages[] = [
                'id' => 'u-' . $task->id,
                'role' => 'user',
                'content' => $task->intent,
                'task_id' => $task->id,
                'created_at' => $task->created_at?->toISOString(),
            ];

            $assistantText = $this->extractAssistantMessage($task);
            if ($assistantText !== '') {
                $messages[] = [
                    'id' => 'a-' . $task->id,
                    'role' => 'assistant',
                    'content' => $assistantText,
                    'task_id' => $task->id,
                    'created_at' => $task->completed_at?->toISOString() ?? $task->updated_at?->toISOString(),
                ];
            }
        }

        return response()->json([
            'session_id' => $data['session_id'],
            'messages' => $messages,
            'turns' => $tasks->count(),
        ]);
    }

    /**
     * POST /api/ai/chat-sync — Chat AI synchrone (sans queue)
     */
    public function chatSync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'intent' => 'required|string|max:2000',
            'context' => 'nullable|array',
            'provider_config_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $config = null;
        if (!empty($data['provider_config_id'])) {
            $config = AiProviderConfig::where('user_id', $request->user()->id)
                ->where('id', $data['provider_config_id'])
                ->first();
        }

        if (!$config) {
            $config = AiProviderConfig::where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->first();
        }

        if (!$config) {
            return response()->json([
                'message' => 'Aucun provider AI actif trouvé.',
            ], 400);
        }

        if (!$config->has_key) {
            return response()->json([
                'message' => 'Clé API manquante pour ce provider.',
            ], 400);
        }

        if ($config->isRateLimited()) {
            return response()->json([
                'message' => 'Limite atteinte pour ce provider.',
            ], 429);
        }

        $task = AiTask::create([
            'user_id' => $request->user()->id,
            'provider_config_id' => $config->id,
            'status' => 'processing',
            'intent' => $data['intent'],
            'context' => $data['context'] ?? null,
            'model' => $config->default_model,
            'started_at' => now(),
        ]);

        try {
            app(AiTaskOrchestrator::class)->process($task, $config);
        } catch (\Throwable $e) {
            $task->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return response()->json([
            'task' => $this->formatTask($task->fresh()),
        ]);
    }
    /**
     * GET /api/ai/tasks — Liste paginée avec filtres
     * Filtres: status, provider_config_id, intent, date_from, date_to, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|max:50',
            'provider_config_id' => 'nullable|integer',
            'intent' => 'nullable|string|max:1000',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $query = AiTask::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id');

        if (!empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (!empty($data['provider_config_id'])) {
            $query->where('provider_config_id', $data['provider_config_id']);
        }
        if (!empty($data['intent'])) {
            $query->where('intent', 'like', '%' . $data['intent'] . '%');
        }
        if (!empty($data['date_from'])) {
            $query->whereDate('created_at', '>=', $data['date_from']);
        }
        if (!empty($data['date_to'])) {
            $query->whereDate('created_at', '<=', $data['date_to']);
        }

        $perPage = (int) ($data['per_page'] ?? 20);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'tasks' => $paginator->getCollection()->map(fn ($task) => $this->formatTask($task)),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * POST /api/ai/tasks — Créer une demande AI (exécution en queue)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'intent' => 'required|string|max:1000',
            'context' => 'nullable|array',
            'provider_config_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $config = null;
        if (!empty($data['provider_config_id'])) {
            $config = AiProviderConfig::where('user_id', $request->user()->id)
                ->where('id', $data['provider_config_id'])
                ->first();
        }

        if (!$config) {
            $config = AiProviderConfig::where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->first();
        }

        if (!$config) {
            return response()->json([
                'message' => 'Aucun provider AI actif trouvé.',
            ], 400);
        }

        if (!$config->has_key) {
            return response()->json([
                'message' => 'Clé API manquante pour ce provider.',
            ], 400);
        }

        if ($config->isRateLimited()) {
            return response()->json([
                'message' => 'Limite atteinte pour ce provider.',
            ], 429);
        }

        $task = AiTask::create([
            'user_id' => $request->user()->id,
            'provider_config_id' => $config->id,
            'status' => 'queued',
            'intent' => $data['intent'],
            'context' => $data['context'] ?? null,
            'model' => $config->default_model,
        ]);

        GenerateAiTasksJob::dispatch($task->id)->onQueue('ai');

        return response()->json([
            'message' => 'Tâche AI en file d’attente.',
            'task' => $this->formatTask($task->fresh()),
        ], 202);
    }

    /**
     * GET /api/ai/tasks/{id} — Détail d'une tâche AI
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $task = AiTask::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json([
            'task' => $this->formatTask($task),
        ]);
    }

    /**
     * PATCH /api/ai/tasks/{id}/executed — Marquer la tâche comme exécutée côté frontend
     */
    public function markExecuted(Request $request, int $id): JsonResponse
    {
        $task = AiTask::where('user_id', $request->user()->id)->findOrFail($id);

        $task->update([
            'status' => 'executed',
            'completed_at' => $task->completed_at ?? now(),
        ]);

        return response()->json([
            'message' => 'Tâche marquée comme exécutée.',
            'task' => $this->formatTask($task->fresh()),
        ]);
    }

    /**
     * GET /api/ai/rag/status — Statut du RAG (service Python)
     */
    public function ragStatus(): JsonResponse
    {
        $base = config('services.ai_service.url');
        if (!$base) {
            return response()->json(['message' => 'AI service not configured'], 400);
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(10)->get(rtrim($base, '/') . '/rag/status');
            if (!$response->successful()) {
                return response()->json(['message' => 'RAG status unavailable'], 400);
            }
            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'RAG status error'], 400);
        }
    }

    /**
     * POST /api/ai/rag/refresh — Rafraîchir l’index RAG (service Python)
     */
    public function ragRefresh(): JsonResponse
    {
        $base = config('services.ai_service.url');
        if (!$base) {
            return response()->json(['message' => 'AI service not configured'], 400);
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(30)->post(rtrim($base, '/') . '/rag/refresh');
            if (!$response->successful()) {
                return response()->json(['message' => 'RAG refresh failed'], 400);
            }
            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'RAG refresh error'], 400);
        }
    }

    /**
     * GET /api/ai/rag/manifest — Manifest pages (service Python)
     */
    public function ragManifest(): JsonResponse
    {
        $base = config('services.ai_service.url');
        if (!$base) {
            return response()->json(['message' => 'AI service not configured'], 400);
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(10)->get(rtrim($base, '/') . '/rag/manifest');
            if (!$response->successful()) {
                return response()->json(['message' => 'RAG manifest unavailable'], 400);
            }
            return response()->json($response->json());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'RAG manifest error'], 400);
        }
    }

    private function formatTask(AiTask $task): array
    {
        return [
            'id' => $task->id,
            'status' => $task->status,
            'intent' => $task->intent,
            'context' => $task->context,
            'tasks' => $task->tasks,
            'raw_response' => $task->raw_response,
            'error_message' => $task->error_message,
            'model' => $task->model,
            'provider_config_id' => $task->provider_config_id,
            'started_at' => $task->started_at?->toISOString(),
            'completed_at' => $task->completed_at?->toISOString(),
            'created_at' => $task->created_at?->toISOString(),
            'updated_at' => $task->updated_at?->toISOString(),
        ];
    }

    private function buildLanggraphHistory(int $userId, string $sessionId, int $maxMessages = 6): array
    {
        $tasks = AiTask::query()
            ->where('user_id', $userId)
            ->where('model', 'langgraph-rag-v1')
            ->where('status', 'completed')
            ->where('context->session_id', $sessionId)
            ->orderBy('id')
            ->get();

        $history = [];
        foreach ($tasks as $task) {
            $history[] = (string) $task->intent;
            $assistant = $this->extractAssistantMessage($task);
            if ($assistant !== '') {
                $history[] = $assistant;
            }
        }

        if (count($history) > $maxMessages) {
            $history = array_slice($history, -$maxMessages);
        }
        return $history;
    }

    private function extractAssistantMessage(AiTask $task): string
    {
        $tasks = $task->tasks;
        if (is_array($tasks) && isset($tasks[0]['payload']['text']) && is_string($tasks[0]['payload']['text'])) {
            return trim($tasks[0]['payload']['text']);
        }

        $raw = $task->raw_response;
        if (is_array($raw) && isset($raw['response']) && is_string($raw['response'])) {
            return trim($raw['response']);
        }

        return '';
    }
}
