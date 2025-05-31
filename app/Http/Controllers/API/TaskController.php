<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller; // ✅ Import base Controller
use App\Models\Task;
use Illuminate\Http\Request;
use App\Providers\Services\NotificationService;

class TaskController extends Controller
{
    protected NotificationService $notifier;

    public function __construct(NotificationService $notifier)
    {
        $this->notifier = $notifier;
    }

    public function index()
    {
        return response()->json(Task::all());
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $task = Task::create($data);

        // Attempt to send a push notification
        try {
            $notificationResult = $this->notifier->send(
                'New Task Created',
                $task->title,
                ['task_id' => (string) $task->id]
            );
        } catch (\Throwable $e) {
            $notificationResult = [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }

        return response()->json([
            'task'         => $task,
            'notification' => $notificationResult,
        ], 201);
    }

    public function show(Task $task)
    {
        return response()->json($task);
    }

    public function update(Request $req, Task $task)
    {
        $data = $req->validate([
            'title'        => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'is_completed' => 'boolean',
        ]);

        $task->update($data);

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(null, 204);
    }
}
