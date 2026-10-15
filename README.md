# Mini Jira — Week 1 (PHP)

A simple project tracker built with **PHP + CSS**.

## Week 1 features

- Create / rename / delete projects
- Manage teammates
- Kanban board: Todo → In Progress → Done
- Create / edit / delete tasks
- Assign teammates
- Move tasks with a status dropdown
- Save data in a JSON file (`data/app.json`)

## How to run

You need PHP installed.

```bash
php -S localhost:8000 router.php
```

Open [http://localhost:8000](http://localhost:8000)

## Main files

| File | What it does |
|------|----------------|
| `index.php` | Projects page |
| `teammates.php` | Teammates page |
| `board.php` | Kanban board |
| `actions.php` | Handles form submits |
| `includes/data.php` | Load/save JSON data |
| `assets/style.css` | Look and layout |
| `router.php` | Routes HTTP requests |
