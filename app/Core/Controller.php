<?php

namespace App\Core;

class Controller
{
    protected App $app;

    protected Database $db;

    protected Session $session;

    protected Request $request;

    protected Response $response;

    protected Csrf $csrf;

    protected View $viewEngine;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->db = $app->db();
        $this->session = $app->session();
        $this->request = $app->request();
        $this->response = $app->response();
        $this->csrf = $app->csrf();
        $this->viewEngine = $app->view();
    }

    protected function view(string $template, array $data = []): void
    {
        $this->response->html($this->viewEngine->render($template, $data));
    }

    protected function json(array $data, int $status = 200): void
    {
        $this->response->json($data, $status);
    }

    protected function redirect(string $url): void
    {
        $this->response->redirect($url);
    }

    protected function back(): void
    {
        $this->response->redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}
