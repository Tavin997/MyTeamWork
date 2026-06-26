// backend/apps/tasks/models/Task.php
<?php

namespace apps\tasks\models;

class Task extends \Model {
    public $table = 'mtw_tasks';
    
    public $fields = [
        'id' => ['type' => 'int', 'primary' => true],
        'project_id' => ['type' => 'int', 'required' => true],
        'title' => ['type' => 'string', 'required' => true, 'length' => 255],
        'description' => ['type' => 'text'],
        'status' => ['type' => 'string', 'default' => 'todo'],
        'priority' => ['type' => 'string', 'default' => 'medium'],
        'assigned_to' => ['type' => 'int'],
        'due_date' => ['type' => 'date'],
        'created_by' => ['type' => 'int'],
        'created_at' => ['type' => 'datetime', 'default' => 'now()'],
        'updated_at' => ['type' => 'datetime', 'default' => 'now()'],
    ];
    
    public $statuses = ['todo', 'in_progress', 'review', 'done'];
    public $priorities = ['low', 'medium', 'high', 'urgent'];
    
    public function getTasksByProject($projectId) {
        return $this->query()
            ->where('project_id', $projectId)
            ->order('priority', 'desc')
            ->fetch();
    }
    
    public function getTasksByUser($userId) {
        return $this->query()
            ->where('assigned_to', $userId)
            ->where('status', '!=', 'done')
            ->order('due_date', 'asc')
            ->fetch();
    }
}