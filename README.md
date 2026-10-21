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

You need MySQL, Composer and PHP installed and in your path.
Your copy of PHP also needs to have the pdo_mysql extension enabled.

```bash
composer install
composer dump-autoload

# 2. Create DB + app user in MySQL (this can also be executed in Workbench)
mysql -u root -p <<'SQL'
CREATE DATABASE minijira CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'minijira_app'@'localhost' IDENTIFIED BY 'replace_with_real_password';
CREATE USER 'minijira_app'@'127.0.0.1' IDENTIFIED BY 'replace_with_real_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON minijira.* TO 'minijira_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON minijira.* TO 'minijira_app'@'127.0.0.1';

CREATE USER IF NOT EXISTS 'minijira_migrator'@'localhost' IDENTIFIED BY 'migrator_password';
CREATE USER IF NOT EXISTS 'minijira_migrator'@'127.0.0.1' IDENTIFIED BY 'migrator_password';

GRANT CREATE, ALTER, DROP, INDEX, REFERENCES, SELECT, INSERT, UPDATE, DELETE
    ON minijira.* TO 'minijira_migrator'@'localhost';
GRANT CREATE, ALTER, DROP, INDEX, REFERENCES, SELECT, INSERT, UPDATE, DELETE
    ON minijira.* TO 'minijira_migrator'@'127.0.0.1';

FLUSH PRIVILEGES;
SQL

# 3. Create .env
cp .env.example .env
# edit .env and set DB_PASS

# 4. Run migrations
vendor/bin/phinx migrate

# 5. Serve
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
