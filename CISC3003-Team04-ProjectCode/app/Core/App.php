<?php

namespace App\Core;

use Throwable;

class App
{
    protected array $config = [];

    protected Database $db;

    protected Session $session;

    protected Csrf $csrf;

    protected Request $request;

    protected Response $response;

    protected View $view;

    protected Validator $validator;

    protected Router $router;

    public function __construct()
    {
        $this->loadConfig();
        $this->configurePhp();
        $this->registerErrorHandling();
        $this->bootServices();
        $this->loadRoutes();
    }

    public function run(): void
    {
        try {
            $this->router->dispatch($this->request->method(), $this->request->uri());
        } catch (Throwable $throwable) {
            $this->handleException($throwable);
        }
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function db(): Database
    {
        return $this->db;
    }

    public function session(): Session
    {
        return $this->session;
    }

    public function csrf(): Csrf
    {
        return $this->csrf;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function response(): Response
    {
        return $this->response;
    }

    public function view(): View
    {
        return $this->view;
    }

    public function validator(): Validator
    {
        return $this->validator;
    }

    public function router(): Router
    {
        return $this->router;
    }

    protected function loadConfig(): void
    {
        $this->config['app'] = require ROOT_PATH . '/config/app.php';
        $this->config['database'] = require ROOT_PATH . '/config/database.php';
        $this->config['mail'] = require ROOT_PATH . '/config/mail.php';
    }

    protected function configurePhp(): void
    {
        date_default_timezone_set($this->config('app.timezone', 'UTC'));
        ini_set('display_errors', $this->config('app.debug', false) ? '1' : '0');
        error_reporting(E_ALL);
    }

    protected function registerErrorHandling(): void
    {
        set_exception_handler(function (Throwable $throwable): void {
            $this->handleException($throwable);
        });

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    protected function bootServices(): void
    {
        $databaseConfig = $this->config('database.connections.' . $this->config('database.default'));
        $this->db = new Database($databaseConfig);
        $this->session = new Session();
        $this->session->start();
        $this->csrf = new Csrf($this->session);
        $this->request = new Request();
        $this->response = new Response();
        $this->view = new View(ROOT_PATH . '/app/Views');
        $this->validator = new Validator($this->db);
        $this->router = new Router($this);
    }

    protected function loadRoutes(): void
    {
        $router = $this->router;
        $app = $this;

        require ROOT_PATH . '/routes/web.php';
        require ROOT_PATH . '/routes/api.php';
    }

    protected function handleException(Throwable $throwable): void
    {
        if ($this->config('app.debug', false)) {
            $content = '<h1>Application Error</h1>'
                . '<p><strong>' . htmlspecialchars($throwable->getMessage(), ENT_QUOTES, 'UTF-8') . '</strong></p>'
                . '<pre>' . htmlspecialchars($throwable->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
            $this->response->html($content, 500);
            return;
        }

        $logFile = ROOT_PATH . '/storage/logs/app.log';
        $message = '[' . date('Y-m-d H:i:s') . '] ' . $throwable->getMessage() . ' in ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL;
        file_put_contents($logFile, $message, FILE_APPEND);
        $this->response->html('<h1>Server Error</h1>', 500);
    }
}
