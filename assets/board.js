(() => {
  const board = document.querySelector(".board");
  const moveForm = document.getElementById("move-task-form");
  if (!board || !moveForm) return;

  const interactive = "input, select, textarea, button, a, summary, label, details";
  let dragTask = null;

  function clearDropState() {
    board.querySelectorAll(".column.is-drop-target").forEach((col) => {
      col.classList.remove("is-drop-target");
    });
  }

  function columnFromEvent(event) {
    return event.target.closest(".column[data-status]");
  }

  board.addEventListener("dragstart", (event) => {
    const task = event.target.closest(".task[data-task-id]");
    if (!task || !board.contains(task)) return;
    if (event.target.closest(interactive)) {
      event.preventDefault();
      return;
    }

    dragTask = task;
    task.classList.add("is-dragging");
    event.dataTransfer.effectAllowed = "move";
    event.dataTransfer.setData("text/plain", task.dataset.taskId);
  });

  board.addEventListener("dragend", () => {
    if (dragTask) dragTask.classList.remove("is-dragging");
    dragTask = null;
    clearDropState();
  });

  board.addEventListener("dragover", (event) => {
    const column = columnFromEvent(event);
    if (!column || !dragTask) return;
    event.preventDefault();
    event.dataTransfer.dropEffect = "move";
    clearDropState();
    column.classList.add("is-drop-target");
  });

  board.addEventListener("dragleave", (event) => {
    const column = columnFromEvent(event);
    if (!column) return;
    if (column.contains(event.relatedTarget)) return;
    column.classList.remove("is-drop-target");
  });

  board.addEventListener("drop", (event) => {
    const column = columnFromEvent(event);
    if (!column || !dragTask) return;
    event.preventDefault();

    const nextStatus = column.dataset.status;
    const taskId = dragTask.dataset.taskId;
    const currentStatus = dragTask.dataset.status;

    clearDropState();
    dragTask.classList.remove("is-dragging");

    if (!nextStatus || !taskId || nextStatus === currentStatus) {
      dragTask = null;
      return;
    }

    moveForm.querySelector('[name="task_id"]').value = taskId;
    moveForm.querySelector('[name="status"]').value = nextStatus;
    moveForm.submit();
  });
})();
