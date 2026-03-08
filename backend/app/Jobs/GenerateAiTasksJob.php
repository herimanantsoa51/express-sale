<?php

namespace App\Jobs;

use App\Models\AiProviderConfig;
use App\Models\AiTask;
use App\Services\AiTaskOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAiTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function __construct(public int $aiTaskId)
    {
    }

    public function handle(AiTaskOrchestrator $orchestrator): void
    {
        $task = AiTask::find($this->aiTaskId);
        if (!$task) {
            return;
        }

        $config = AiProviderConfig::find($task->provider_config_id);
        if (!$config) {
            $task->update([
                'status' => 'failed',
                'error_message' => 'Provider config missing.',
                'completed_at' => now(),
            ]);
            return;
        }

        try {
            $orchestrator->process($task, $config);
        } catch (\Throwable $e) {
            $task->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}
