# JavaBlog — Symfony CRUD Project

A complete Blog CRUD application built with **Symfony 7**, covering **Posts** and **Comments** entities.

---

## 📁 Project Structure

```
src/
├── Controller/
│   ├── PostController.php       # Full CRUD for Posts
│   └── CommentController.php    # Edit & Delete for Comments
├── Entity/
│   ├── Post.php                 # Post entity (OneToMany → Comments)
│   └── Comment.php              # Comment entity (ManyToOne → Post)
├── Form/
│   ├── PostType.php             # Post form
│   └── CommentType.php          # Comment form
└── Repository/
    ├── PostRepository.php
    └── CommentRepository.php

templates/
├── base.html.twig               # Layout with Bootstrap 5
├── post/
│   ├── index.html.twig          # List all posts
│   ├── show.html.twig           # View post + inline comment form
│   ├── new.html.twig            # Create post form
│   └── edit.html.twig           # Edit post form
└── comment/
    ├── index.html.twig          # List all comments (admin view)
    └── edit.html.twig           # Edit comment form

migrations/
└── Version20240407000001.php    # SQL migration for all 3 tables
```

---

## 🚀 Setup Instructions

### 1. Create a new Symfony project

```bash
composer create-project symfony/skeleton javablog
cd javablog
composer require orm twig form validator asset
```

### 2. Copy the source files

Copy all files from this repository into the matching folders of your project.

### 3. Configure the database

```bash
cp .env.example .env.local
# Edit .env.local and set your DB password:
# DATABASE_URL="mysql://root:YOUR_PASSWORD@127.0.0.1:3306/javablog?serverVersion=8.0&charset=utf8mb4"
```

### 4. Create the database and run migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

> ✅ If your `javablog` DB already has the tables, skip the migration.
> Instead, update your entities to match existing columns.

### 5. Start the development server

```bash
symfony server:start
# or
php -S localhost:8000 -t public/
```

### 6. Open in browser

```
http://localhost:8000/blog/post/
```

---

## 🗺 Routes

| Method | URL                          | Name                  | Action                     |
|--------|------------------------------|-----------------------|----------------------------|
| GET    | /blog/post/                  | app_post_index        | List all posts             |
| GET    | /blog/post/new               | app_post_new          | Show create form           |
| POST   | /blog/post/new               | app_post_new          | Submit new post            |
| GET    | /blog/post/{id}              | app_post_show         | View post + add comment    |
| POST   | /blog/post/{id}              | app_post_show         | Submit inline comment      |
| GET    | /blog/post/{id}/edit         | app_post_edit         | Show edit form             |
| POST   | /blog/post/{id}/edit         | app_post_edit         | Submit updated post        |
| POST   | /blog/post/{id}/delete       | app_post_delete       | Delete post (+ comments)   |
| GET    | /blog/comment/               | app_comment_index     | List all comments          |
| GET    | /blog/comment/{id}/edit      | app_comment_edit      | Edit a comment             |
| POST   | /blog/comment/{id}/edit      | app_comment_edit      | Submit updated comment     |
| POST   | /blog/comment/{id}/delete    | app_comment_delete    | Delete a comment           |

---

## ⚙️ Requirements

- PHP 8.2+
- Symfony 7.x
- MySQL/MariaDB (database: `javablog`)
- Composer

---

## 🎨 Features

- ✅ Full CRUD for Posts (Create, Read, Update, Delete)
- ✅ Comments linked to posts (ManyToOne relationship)
- ✅ Inline comment form on the post show page
- ✅ Comment list/edit/delete management page
- ✅ CSRF protection on all delete forms
- ✅ Flash messages for user feedback
- ✅ Bootstrap 5 responsive UI
- ✅ Doctrine migrations included
