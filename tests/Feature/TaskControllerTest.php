<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Events\TasksChanged;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Path returned by the mocked "save as" dialog, or null when dismissed.
     */
    protected ?string $dialogSavePath = null;

    protected function setUp(): void
    {
        parent::setUp();

        // NativePHP desktop APIs talk to Electron over HTTP. Tests stub that bridge.
        Http::preventStrayRequests();
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'dialog/save')) {
                return Http::response(['result' => $this->dialogSavePath]);
            }

            return Http::response([
                'value' => null,
                'result' => null,
                'reference' => 'native-test',
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Buy groceries',
            'body' => 'Milk, bread, eggs',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Medium->value,
            'due_date' => '2026-10-01',
        ], $overrides);
    }

    public function test_index_lists_tasks(): void
    {
        $task = Task::factory()->create(['title' => 'Shopping list']);

        $response = $this->get(route('tasks.index'));

        $response->assertOk()
            ->assertSee('Shopping list')
            ->assertSee((string) $task->id, false);
    }

    public function test_index_filters_by_status_and_priority(): void
    {
        $match = Task::factory()->create([
            'title' => 'Task A',
            'status' => TaskStatus::Done,
            'priority' => TaskPriority::High,
        ]);
        Task::factory()->create([
            'title' => 'Task B',
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Low,
        ]);

        $response = $this->get(route('tasks.index', [
            'status' => TaskStatus::Done->value,
            'priority' => TaskPriority::High->value,
        ]));

        $response->assertOk()
            ->assertSee('Task A')
            ->assertDontSee('Task B');
        $this->assertTrue($match->exists());
    }

    public function test_index_overdue_filter_excludes_done_and_future_tasks(): void
    {
        Task::factory()->create([
            'title' => 'Overdue task',
            'status' => TaskStatus::Pending,
            'due_date' => now()->subDay(),
        ]);
        Task::factory()->create([
            'title' => 'Overdue but done',
            'status' => TaskStatus::Done,
            'due_date' => now()->subDay(),
        ]);
        Task::factory()->create([
            'title' => 'Future task',
            'status' => TaskStatus::Pending,
            'due_date' => now()->addDay(),
        ]);

        $response = $this->get(route('tasks.index', ['overdue' => 1]));

        $response->assertOk()
            ->assertSee('Overdue task')
            ->assertDontSee('Overdue but done')
            ->assertDontSee('Future task');
    }

    public function test_fragment_applies_the_same_filters(): void
    {
        Task::factory()->create(['title' => 'Visible task', 'status' => TaskStatus::InProgress]);
        Task::factory()->create(['title' => 'Hidden task', 'status' => TaskStatus::Pending]);

        $this->get(route('tasks.fragment', ['status' => TaskStatus::InProgress->value]))
            ->assertOk()
            ->assertSee('Visible task')
            ->assertDontSee('Hidden task');
    }

    public function test_create_form_is_displayed(): void
    {
        $this->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('New Task');
    }

    public function test_store_creates_a_task_and_redirects(): void
    {
        Event::fake([TasksChanged::class]);

        $response = $this->from(route('tasks.create'))->post(
            route('tasks.store'),
            $this->validPayload(),
        );

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'Buy groceries',
            'body' => 'Milk, bread, eggs',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => '2026-10-01 00:00:00',
        ]);
        Event::assertDispatched(TasksChanged::class, fn (TasksChanged $event) => $event->action === 'created');
    }

    public function test_store_keeps_quick_task_form_open(): void
    {
        $response = $this->post(route('tasks.store'), $this->validPayload([
            'title' => 'Quick task',
            'quick' => '1',
        ]));

        $response->assertRedirect(route('tasks.create', ['quick' => 1]));
    }

    public function test_store_requires_title_status_and_priority(): void
    {
        $response = $this->from(route('tasks.create'))->post(route('tasks.store'), [
            'title' => '',
            'status' => 'nope',
            'priority' => 'nope',
            'due_date' => 'not-a-date',
        ]);

        $response->assertRedirect(route('tasks.create'))
            ->assertSessionHasErrors(['title', 'status', 'priority', 'due_date']);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_edit_form_is_displayed(): void
    {
        $task = Task::factory()->create(['title' => 'Editable task']);

        $this->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSee('Editable task');
    }

    public function test_update_updates_the_task(): void
    {
        Event::fake([TasksChanged::class]);
        $task = Task::factory()->create(['title' => 'Old title']);

        $response = $this->from(route('tasks.index'))->put(
            route('tasks.update', $task),
            $this->validPayload([
                'title' => 'New title',
                'status' => TaskStatus::Done->value,
                'priority' => TaskPriority::High->value,
            ]),
        );

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'New title',
            'status' => 'done',
            'priority' => 'high',
        ]);
        Event::assertDispatched(TasksChanged::class, fn (TasksChanged $event) => $event->action === 'updated');
    }

    public function test_destroy_deletes_the_task(): void
    {
        Event::fake([TasksChanged::class]);
        $task = Task::factory()->create();

        $response = $this->from(route('tasks.index'))->delete(route('tasks.destroy', $task));

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        Event::assertDispatched(TasksChanged::class, fn (TasksChanged $event) => $event->action === 'deleted');
    }

    public function test_fragment_returns_the_tasks_list_partial(): void
    {
        Task::factory()->create(['title' => 'Fragment task']);

        $this->get(route('tasks.fragment'))
            ->assertOk()
            ->assertSee('Fragment task');
    }

    public function test_clear_feed_only_deletes_live_feed_tasks(): void
    {
        Event::fake([TasksChanged::class]);
        $feed = Task::factory()->feed()->create();
        $keep = Task::factory()->create(['title' => 'Normal task']);

        $response = $this->from(route('tasks.index'))->delete(route('tasks.clearFeed'));

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseMissing('tasks', ['id' => $feed->id]);
        $this->assertDatabaseHas('tasks', ['id' => $keep->id]);
        Event::assertDispatched(TasksChanged::class);
    }

    public function test_copy_uses_the_clipboard_bridge(): void
    {
        $task = Task::factory()->create([
            'title' => 'Copy me',
            'body' => 'Body text',
        ]);

        $response = $this->from(route('tasks.index'))->get(route('tasks.copy', $task));

        $response->assertRedirect(route('tasks.index'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'clipboard'));
    }

    public function test_export_cancels_when_the_dialog_is_dismissed(): void
    {
        $task = Task::factory()->create();

        $response = $this->from(route('tasks.index'))->get(route('tasks.export', $task));

        $response->assertRedirect(route('tasks.index'));
    }

    public function test_export_writes_a_text_file(): void
    {
        $task = Task::factory()->create([
            'title' => 'Export me',
            'body' => 'File content',
            'status' => TaskStatus::Done,
            'priority' => TaskPriority::High,
            'due_date' => '2026-10-15',
        ]);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'task-export-'.uniqid().'.txt';
        $this->dialogSavePath = $path;

        $response = $this->from(route('tasks.index'))->get(route('tasks.export', $task));

        $response->assertRedirect(route('tasks.index'));
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);
        $this->assertStringContainsString('Export me', $content);
        $this->assertStringContainsString('File content', $content);
        $this->assertStringContainsString('Done', $content);
        $this->assertStringContainsString('High', $content);

        @unlink($path);
    }
}
