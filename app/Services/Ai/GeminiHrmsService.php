<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\SystemSetting;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GeminiHrmsService
{
    protected Client $client;
    protected HrmsScanner $scanner;

    public function __construct(HrmsScanner $scanner)
    {
        $this->scanner = $scanner;
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Get active API key from system settings or env
     */
    public function getApiKey(): ?string
    {
        $key = SystemSetting::get('gemini_api_key');
        if (!empty($key) && trim($key) !== '') {
            return trim($key);
        }

        return config('services.gemini.api_key', env('GEMINI_API_KEY'));
    }

    /**
     * Get default model
     */
    public function getDefaultModel(): string
    {
        $model = SystemSetting::get('gemini_default_model', config('services.gemini.model', env('GEMINI_MODEL', 'gemini-flash-latest')));
        return $this->resolveModelName($model);
    }

    /**
     * Map model aliases or legacy models to active Google AI endpoints
     */
    public function resolveModelName(?string $model): string
    {
        $model = trim((string) $model);
        if (empty($model)) {
            return 'gemini-3.6-flash';
        }

        $map = [
            'gemini-1.5-flash'    => 'gemini-3.6-flash',
            'gemini-1.5-pro'      => 'gemini-3.6-flash',
            'gemini-2.0-flash'    => 'gemini-3.6-flash',
            'gemini-2.5-flash'    => 'gemini-3.6-flash',
            'gemini-2.5-pro'      => 'gemini-3.6-flash',
            'gemini-flash-latest' => 'gemini-3.6-flash',
            'gemini-pro-latest'   => 'gemini-3.6-flash',
            'flash'               => 'gemini-3.6-flash',
            'pro'                 => 'gemini-3.6-flash',
        ];

        return $map[$model] ?? $model;
    }

    /**
     * Test connection to Google Gemini API
     */
    public function testConnection(?string $apiKey = null): array
    {
        $key = $apiKey ?: $this->getApiKey();

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'No Gemini API Key provided. Please enter a valid API key.',
            ];
        }

        $model = $this->getDefaultModel();
        $startTime = microtime(true);

        $attemptModel = function ($modelToTest) use ($key, $startTime) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelToTest}:generateContent?key={$key}";

            $response = $this->client->post($url, [
                'json' => [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => 'Ping check. Respond with "PONG".'],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 10,
                        'temperature' => 0.1,
                    ],
                ],
            ]);

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            $data = json_decode((string) $response->getBody(), true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return [
                'success' => true,
                'latency_ms' => $latencyMs,
                'model' => $modelToTest,
                'message' => "Connected successfully to {$modelToTest}! Response: " . trim($text),
            ];
        };

        try {
            return $attemptModel($model);
        } catch (\Throwable $e) {
            // Auto-fallback: if requested model returned 404, try gemini-3.6-flash
            if (str_contains($e->getMessage(), '404') && $model !== 'gemini-3.6-flash') {
                try {
                    $res = $attemptModel('gemini-3.6-flash');
                    SystemSetting::set('gemini_default_model', 'gemini-3.6-flash');
                    return $res;
                } catch (\Throwable $ex) {}
            }

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            $safeMsg = preg_replace('/key=[^&\s"\']+/', 'key=***', $e->getMessage());
            return [
                'success' => false,
                'latency_ms' => $latencyMs,
                'message' => 'API Connection failed: ' . $safeMsg,
            ];
        }
    }

    /**
     * Query Gemini with whole-HRMS scanning and tool calling
     */
    public function query(string $prompt, ?string $modelOverride = null): array
    {
        $apiKey = $this->getApiKey();
        $user = Auth::user();

        if (empty($apiKey)) {
            return [
                'success' => false,
                'summary' => "⚠️ **Google Gemini API Key is missing.**\n\nPlease add your API Key in [Settings & Controls -> AI Copilot & Monitoring](" . route('settings.index', ['tab' => 'ai_copilot']) . ") or in your `.env` file (`GEMINI_API_KEY=...`). You can get a free or paid key instantly from [Google AI Studio](https://aistudio.google.com/).",
                'type' => 'alert',
                'metrics' => [],
                'tools_called' => [],
            ];
        }

        $model = $this->resolveModelName($modelOverride ?: $this->getDefaultModel());

        $temperature = (float) SystemSetting::get('gemini_temperature', '0.1');
        $maxTokens = (int) SystemSetting::get('gemini_max_tokens', '2048');

        $systemInstruction = [
            'parts' => [
                [
                    'text' => "You are the AxeCorp HRMS Executive Copilot, an elite Chief of Staff and HR Auditor for AxeCorp HRMS.
                    
CRITICAL STRICT OPERATING RULES:
1. STRICT HRMS CONFINEMENT: You operate EXCLUSIVELY inside AxeCorp HRMS. If the user asks about world trivia, sports, general programming, or anything outside AxeCorp HRMS workforce management, payroll, attendance, company entities, leaves, tasks, and system operations, you MUST politely refuse and state: 'I am restricted to AxeCorp HRMS operations. I can assist you with employee records, attendance statistics, payroll disbursements, leave approvals, and system administration.'
2. ZERO CONVERSATIONAL FLUFF: Never say 'Hello', 'I hope this email finds you well', 'As an AI language model', or polite pleasantries. Begin IMMEDIATELY with the answer, numbers, and findings.
3. EXECUTIVE DATA PRESENTATION: Use clean Markdown tables for records, concise bullet points, and bold key figures.
4. ANOMALY DETECTION: Always highlight red flags or policy breaches (e.g. employees with 3+ late clock-ins in a week, unapproved advance salary requests, negative leave balances, or missing punch-outs).
5. DEEP SCANNING: When the user asks about employees, attendance, payroll, leaves, projects, or settings, ALWAYS call the appropriate tool to retrieve live, accurate database records.
6. ACTIONABLE LINKS: Whenever relevant, provide direct Markdown links to HRMS screens (e.g. [Open Employee Directory](" . route('employees.index') . "), [Open Multi-Company Payroll](" . route('payroll.index') . "), [Open Attendance Logs](" . route('attendance.index') . "), [Open Leave Requests](" . route('leaves.index') . "), [Open Projects Hub](" . route('projects.index') . "), [Open Settings](" . route('settings.index') . ")).
Current date: " . now()->format('Y-m-d (l, F Y)') . ".
Current Super Admin: " . ($user ? "{$user->name} ({$user->email})" : 'Super Admin') . "."
                ]
            ]
        ];

        $tools = [
            [
                'function_declarations' => $this->getToolDeclarations()
            ]
        ];

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ];

        $startTime = microtime(true);
        $executedTools = [];
        $totalPromptTokens = 0;
        $totalResponseTokens = 0;

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            // Multi-turn tool calling loop (up to 3 iterations)
            $maxTurns = 3;
            $finalAnswer = '';

            for ($turn = 0; $turn < $maxTurns; $turn++) {
                $payload = [
                    'contents' => $contents,
                    'systemInstruction' => $systemInstruction,
                    'tools' => $tools,
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'maxOutputTokens' => $maxTokens,
                    ],
                ];

                $res = $this->client->post($url, ['json' => $payload]);
                $body = json_decode((string) $res->getBody(), true);

                if (isset($body['usageMetadata'])) {
                    $totalPromptTokens += (int)($body['usageMetadata']['promptTokenCount'] ?? 0);
                    $totalResponseTokens += (int)($body['usageMetadata']['candidatesTokenCount'] ?? 0);
                }

                $candidate = $body['candidates'][0] ?? null;
                if (!$candidate) {
                    break;
                }

                $parts = $candidate['content']['parts'] ?? [];
                $hasFunctionCall = false;

                // Sanitize candidate parts before pushing into history.
                // Google Gemini Protobuf requires `args` to be a Struct (JSON Object {}), never a list ([]).
                // When args is empty, PHP's json_encode outputs "[]" instead of "{}", causing:
                // "Invalid JSON payload received. Unknown name 'args' at ... Proto field is not repeating, cannot start list."
                $cleanModelParts = [];
                $functionResponseParts = [];

                foreach ($parts as $part) {
                    if (isset($part['functionCall'])) {
                        $hasFunctionCall = true;
                        $fnName = $part['functionCall']['name'] ?? '';
                        $fnArgs = (array)($part['functionCall']['args'] ?? []);

                        // Force empty args to encode as object {}
                        $part['functionCall']['args'] = empty($fnArgs) ? (object)[] : $fnArgs;

                        $executedTools[] = $fnName;

                        // Execute scanner method safely
                        $toolResult = $this->executeTool($fnName, $fnArgs);

                        $functionResponseParts[] = [
                            'functionResponse' => [
                                'name' => $fnName,
                                'response' => [
                                    'name' => $fnName,
                                    'content' => empty($toolResult) ? (object)[] : $toolResult
                                ]
                            ]
                        ];
                    }

                    if (isset($part['text'])) {
                        $finalAnswer .= $part['text'];
                    }

                    $cleanModelParts[] = $part;
                }

                // Add model candidate response to message history with strictly typed parts
                $contents[] = [
                    'role' => 'model',
                    'parts' => $cleanModelParts
                ];

                if ($hasFunctionCall && !empty($functionResponseParts)) {
                    // Send tool output back to model for synthesis
                    $contents[] = [
                        'role' => 'user',
                        'parts' => $functionResponseParts
                    ];
                } else {
                    // No more function calls, we have the final answer
                    break;
                }
            }

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            $totalTokens = $totalPromptTokens + $totalResponseTokens;
            $costUsd = $this->calculateCost($model, $totalPromptTokens, $totalResponseTokens);

            // Log telemetry safely
            if (Schema::hasTable('ai_usage_logs')) {
                try {
                    AiUsageLog::create([
                        'user_id' => $user?->id,
                        'user_name' => $user?->name ?? 'Admin',
                        'model_used' => $model,
                        'prompt' => $prompt,
                        'response_summary' => substr(strip_tags($finalAnswer), 0, 500),
                        'tools_called' => $executedTools,
                        'prompt_tokens' => $totalPromptTokens,
                        'response_tokens' => $totalResponseTokens,
                        'total_tokens' => $totalTokens,
                        'cost_usd' => $costUsd,
                        'latency_ms' => $latencyMs,
                        'status' => 'SUCCESS',
                    ]);
                } catch (\Throwable $logEx) {
                    Log::warning('Could not write AI usage log: ' . $logEx->getMessage());
                }
            }

            return [
                'success' => true,
                'summary' => $finalAnswer,
                'model' => $model,
                'tools_called' => $executedTools,
                'telemetry' => [
                    'prompt_tokens' => $totalPromptTokens,
                    'response_tokens' => $totalResponseTokens,
                    'total_tokens' => $totalTokens,
                    'cost_usd' => $costUsd,
                    'cost_pkr' => round($costUsd * 280, 2),
                    'latency_ms' => $latencyMs,
                ],
            ];

        } catch (\Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            $rawMsg = $e->getMessage();
            Log::error('Gemini Copilot Error: ' . $rawMsg, ['trace' => $e->getTraceAsString()]);

            // User-friendly error message resolution
            $friendlySummary = '';
            if (str_contains($rawMsg, '429')) {
                $friendlySummary = "⏳ **Google Gemini Rate Limit Reached (HTTP 429)**\n\nThe Google AI Studio free tier quota ceiling has temporarily paused requests for this model. Please wait ~30 seconds and try again, or link a billing account on [Google AI Studio](https://aistudio.google.com/) for higher throughput.";
            } elseif (str_contains($rawMsg, '503')) {
                $friendlySummary = "⏳ **Google Gemini High Demand (HTTP 503)**\n\nGoogle's AI model servers are currently experiencing heavy demand spikes. Please wait 10–15 seconds and resubmit your question.";
            } elseif (str_contains($rawMsg, '404')) {
                $friendlySummary = "⚠️ **Model Not Available (HTTP 404)**\n\nThe requested Gemini model is deprecated or not available for your Google Cloud project. Please switch to **gemini-3.6-flash** in [Settings -> AI Copilot](" . route('settings.index', ['tab' => 'ai_copilot']) . ").";
            } else {
                $sanitized = preg_replace('/key=[^&\s"\']+/', 'key=***', $rawMsg);
                $friendlySummary = "⚠️ **Error communicating with Gemini AI Engine:** " . $sanitized;
            }

            if (Schema::hasTable('ai_usage_logs')) {
                try {
                    AiUsageLog::create([
                        'user_id' => $user?->id,
                        'user_name' => $user?->name ?? 'Admin',
                        'model_used' => $model,
                        'prompt' => $prompt,
                        'status' => 'FAILED',
                        'error_message' => preg_replace('/key=[^&\s"\']+/', 'key=***', $rawMsg),
                        'latency_ms' => $latencyMs,
                    ]);
                } catch (\Throwable $logEx) {
                    // Ignore
                }
            }

            return [
                'success' => false,
                'summary' => $friendlySummary,
                'model' => $model,
                'tools_called' => $executedTools,
                'telemetry' => [
                    'latency_ms' => $latencyMs,
                ],
            ];
        }
    }

    /**
     * Dispatch tool execution to HrmsScanner
     */
    protected function executeTool(string $name, array $args): array
    {
        switch ($name) {
            case 'scan_employees':
                return $this->scanner->scan_employees($args);
            case 'scan_attendance':
                return $this->scanner->scan_attendance($args);
            case 'scan_payroll':
                return $this->scanner->scan_payroll($args);
            case 'scan_leaves':
                return $this->scanner->scan_leaves($args);
            case 'scan_projects_tasks':
                return $this->scanner->scan_projects_tasks($args);
            case 'scan_sales_kpis':
                return $this->scanner->scan_sales_kpis($args);
            case 'scan_system_configs':
                return $this->scanner->scan_system_configs($args);
            case 'universal_hrms_scan':
                return $this->scanner->universal_hrms_scan($args);
            default:
                return ['error' => "Unknown tool: {$name}"];
        }
    }

    /**
     * Calculate cost based on official Google Cloud pricing
     */
    protected function calculateCost(string $model, int $promptTokens, int $responseTokens): float
    {
        if (str_contains($model, 'pro')) {
            // Gemini 1.5 Pro: $1.25 / 1M input, $5.00 / 1M output
            $inputCost = ($promptTokens / 1000000) * 1.25;
            $outputCost = ($responseTokens / 1000000) * 5.00;
        } else {
            // Gemini 1.5 Flash: $0.075 / 1M input, $0.30 / 1M output
            $inputCost = ($promptTokens / 1000000) * 0.075;
            $outputCost = ($responseTokens / 1000000) * 0.30;
        }

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Tool / Function declarations for Gemini
     */
    protected function getToolDeclarations(): array
    {
        return [
            [
                'name' => 'scan_employees',
                'description' => 'Scans the employee workforce directory. Returns names, departments, companies, designations, base salaries, contact info, and active/inactive status.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'search' => ['type' => 'STRING', 'description' => 'Search term for name, email, or phone.'],
                        'department' => ['type' => 'STRING', 'description' => 'Department name filter (e.g. Engineering, Design, Sales, HR).'],
                        'company' => ['type' => 'STRING', 'description' => 'Corporate entity filter (e.g. AxeCorp Digital, Apex Logistics).'],
                        'is_active' => ['type' => 'BOOLEAN', 'description' => 'Filter active or inactive staff.'],
                    ],
                ],
            ],
            [
                'name' => 'scan_attendance',
                'description' => 'Scans daily attendance records, punch clock-in/out times, late arrivals, total hours worked, overtime, and absences.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'date' => ['type' => 'STRING', 'description' => 'Specific date in YYYY-MM-DD format.'],
                        'date_from' => ['type' => 'STRING', 'description' => 'Start date range YYYY-MM-DD.'],
                        'date_to' => ['type' => 'STRING', 'description' => 'End date range YYYY-MM-DD.'],
                        'status' => ['type' => 'STRING', 'description' => 'Status filter: PRESENT, LATE, ABSENT, HALF_DAY.'],
                        'department' => ['type' => 'STRING', 'description' => 'Filter by department name.'],
                        'user_name' => ['type' => 'STRING', 'description' => 'Filter by employee name.'],
                    ],
                ],
            ],
            [
                'name' => 'scan_payroll',
                'description' => 'Scans multi-company payroll tiers, payslips, net pay disbursements, deductions, tax withholdings, and advance salary requests.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'month' => ['type' => 'INTEGER', 'description' => 'Month number (1 to 12).'],
                        'year' => ['type' => 'INTEGER', 'description' => 'Year (e.g. 2026).'],
                        'company' => ['type' => 'STRING', 'description' => 'Company entity filter.'],
                    ],
                ],
            ],
            [
                'name' => 'scan_leaves',
                'description' => 'Scans employee leave requests, approval statuses (PENDING, APPROVED, REJECTED), leave types, reasons, and duration.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'status' => ['type' => 'STRING', 'description' => 'Leave status filter: PENDING, APPROVED, REJECTED.'],
                        'employee' => ['type' => 'STRING', 'description' => 'Employee name.'],
                    ],
                ],
            ],
            [
                'name' => 'scan_projects_tasks',
                'description' => 'Scans software house client projects, ClickUp-style Kanban tasks, completion stages, overdue deadlines, and developer workloads.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'status' => ['type' => 'STRING', 'description' => 'Task status filter: TODO, IN_PROGRESS, REVIEW, COMPLETED.'],
                        'assigned_to' => ['type' => 'STRING', 'description' => 'Employee name assigned to task.'],
                        'priority' => ['type' => 'STRING', 'description' => 'Task priority: URGENT, HIGH, NORMAL.'],
                    ],
                ],
            ],
            [
                'name' => 'scan_sales_kpis',
                'description' => 'Scans sales teams, revenue targets, achieved amounts, commissions, and staff performance KPIs.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'month' => ['type' => 'INTEGER', 'description' => 'Month number.'],
                        'year' => ['type' => 'INTEGER', 'description' => 'Year.'],
                    ],
                ],
            ],
            [
                'name' => 'scan_system_configs',
                'description' => 'Scans company corporate entities, shift timings, attendance grace periods (09:15 AM), late penalty fees (PKR 500), and global system rules.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'category' => ['type' => 'STRING', 'description' => 'Category filter: attendance, payroll, general, leave.'],
                    ],
                ],
            ],
            [
                'name' => 'universal_hrms_scan',
                'description' => 'Deep fallback scan across employees, companies, departments, and tasks when the query is freeform or generic.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'query' => ['type' => 'STRING', 'description' => 'Search term.'],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }
}
