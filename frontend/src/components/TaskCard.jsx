// frontend/src/components/TaskCard.jsx
import { useState } from 'preact';
import './TaskCard.css';

export default function TaskCard({ task, onUpdate }) {
    const [isEditing, setIsEditing] = useState(false);
    const [title, setTitle] = useState(task.title);
    
    const handleDragStart = (e) => {
        e.dataTransfer.setData('taskId', task.id);
    };
    
    const handleDrop = (e) => {
        const taskId = e.dataTransfer.getData('taskId');
        onUpdate(taskId, { status: task.status });
    };
    
    return (
        <div 
            className="task-card"
            draggable
            onDragStart={handleDragStart}
            onDrop={handleDrop}
            onDragOver={(e) => e.preventDefault()}
        >
            <div className="task-header">
                <h3>{task.title}</h3>
                <span className={`priority priority-${task.priority}`}>
                    {task.priority}
                </span>
            </div>
            <div className="task-meta">
                <span>👤 {task.assignee}</span>
                <span>📅 {task.dueDate}</span>
            </div>
            <div className="task-actions">
                <button onClick={() => setIsEditing(true)}>✏️ Editar</button>
                <button onClick={() => onUpdate(task.id, { status: 'completed' })}>
                    ✅ Concluir
                </button>
            </div>
        </div>
    );
}