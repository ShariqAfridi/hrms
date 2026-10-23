<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Ai\GeminiHrmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiAgentController extends Controller
{
    protected GeminiHrmsService $geminiService;

    public function __construct(GeminiHrmsService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function query(Request $request)
    {
        $user = Auth::user();

        // 1. Role Authorization Check: Only Super Admin and Developer Admin can access the Copilot
        if (!$user || (!$user->isSuperAdmin() && !$user->isDeveloperAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'The AxeCorp Executive AI Copilot is restricted to Super Admin and Developer Admin accounts.',
            ], 403);
        }

        $prompt = trim($request->input('prompt', ''));

        if (empty($prompt)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a search prompt or question.',
            ]);
        }

        // 2. Model Selection: Allow on-the-fly model override from request or fallback to system default
        $model = $request->input('model'); // e.g. 'gemini-1.5-flash' or 'gemini-1.5-pro'

        // 3. Delegate to Gemini Service
        $result = $this->geminiService->query($prompt, $model);

        return response()->json([
            'success' => $result['success'] ?? true,
            'prompt' => $prompt,
            'summary' => $result['summary'] ?? '',
            'model' => $result['model'] ?? 'gemini-1.5-flash',
            'tools_called' => $result['tools_called'] ?? [],
            'telemetry' => $result['telemetry'] ?? null,
            'type' => $result['type'] ?? 'general',
            'metrics' => $result['metrics'] ?? [],
            'steps' => $result['steps'] ?? [],
            'data' => $result['data'] ?? [],
            'action_link' => $result['action_link'] ?? null,
            'action_text' => $result['action_text'] ?? null,
        ]);
    }
}
