import React, { createContext, useContext, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import aiTaskService from '../services/aiTaskService';
import { executeAiTasks } from '../utils/aiTaskExecutor';

const AiTaskContext = createContext(null);

export const AiTaskProvider = ({ children }) => {
  const navigate = useNavigate();
  const [isRunning, setIsRunning] = useState(false);

  const runAiTask = async ({ intent, context, provider_config_id, handlers }) => {
    setIsRunning(true);
    try {
      const created = await aiTaskService.create({ intent, context, provider_config_id });
      const taskId = created?.task?.id;
      if (!taskId) {
        return { status: 'failed', error: 'Aucune tâche créée.' };
      }

      let task = created.task;
      const maxAttempts = 20;
      let attempt = 0;

      while (attempt < maxAttempts && task.status !== 'completed' && task.status !== 'failed') {
        // eslint-disable-next-line no-await-in-loop
        await new Promise((r) => setTimeout(r, 1000));
        // eslint-disable-next-line no-await-in-loop
        const fetched = await aiTaskService.get(taskId);
        task = fetched?.task || task;
        attempt += 1;
      }

      if (task.status === 'completed' && task.tasks?.length) {
        const results = await executeAiTasks(task.tasks, { navigate, handlers });
        await aiTaskService.markExecuted(taskId);
        return { status: 'executed', task, results };
      }

      return { status: task.status, task, error: task.error_message };
    } finally {
      setIsRunning(false);
    }
  };

  const value = useMemo(() => ({ runAiTask, isRunning }), [isRunning]);

  return (
    <AiTaskContext.Provider value={value}>
      {children}
    </AiTaskContext.Provider>
  );
};

export const useAiTask = () => {
  const ctx = useContext(AiTaskContext);
  if (!ctx) {
    throw new Error('useAiTask must be used within AiTaskProvider');
  }
  return ctx;
};
