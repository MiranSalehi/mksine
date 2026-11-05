<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Install\Steps\ConfigureRuntimeStep;

/**
 * Prints ready-to-paste Nginx, Apache, Supervisor and cron configuration for a dedicated server.
 */
class ServerConfigCommand extends Command
{
    /** @var list<string> */
    public const TYPES = ['nginx', 'apache', 'supervisor', 'cron'];

    protected $signature = 'mksine:server-config
                            {type=all : nginx, apache, supervisor, cron or all}
                            {--domain= : Server name (defaults to the APP_URL host)}
                            {--php-fpm=unix:/run/php/php8.4-fpm.sock : PHP-FPM address for Nginx}
                            {--user=www-data : System user for the queue worker}';

    protected $description = 'Print web server, queue worker and cron configuration for this project';

    public function handle(): int
    {
        $type = (string) $this->argument('type');
        $types = $type === 'all' ? self::TYPES : [$type];

        if (array_diff($types, self::TYPES) !== []) {
            $this->components->error("Unknown type [{$type}]. Use " . implode(', ', self::TYPES) . ' or all.');

            return self::FAILURE;
        }

        foreach ($types as $index => $configType) {
            if (count($types) > 1) {
                $this->line(($index === 0 ? '' : PHP_EOL) . '# --- ' . $configType . ' ---');
            }

            $this->line($this->render($configType));
        }

        return self::SUCCESS;
    }

    public function render(string $type): string
    {
        return match ($type) {
            'nginx' => $this->nginx(),
            'apache' => $this->apache(),
            'supervisor' => $this->supervisor(),
            default => ConfigureRuntimeStep::cronLine(),
        };
    }

    private function domain(): string
    {
        $domain = $this->option('domain');

        if (is_string($domain) && $domain !== '') {
            return $domain;
        }

        return (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'example.com');
    }

    private function nginx(): string
    {
        $domain = $this->domain();
        $root = public_path();
        $fpm = (string) $this->option('php-fpm');

        return <<<NGINX
        server {
            listen 80;
            server_name {$domain};
            root {$root};

            index index.php;
            charset utf-8;
            client_max_body_size 64M;

            add_header X-Frame-Options "SAMEORIGIN";
            add_header X-Content-Type-Options "nosniff";

            location / {
                try_files \$uri \$uri/ /index.php?\$query_string;
            }

            location = /favicon.ico { access_log off; log_not_found off; }
            location = /robots.txt  { access_log off; log_not_found off; }

            error_page 404 /index.php;

            location ~ ^/index\.php(/|$) {
                fastcgi_pass {$fpm};
                fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
                include fastcgi_params;
                fastcgi_hide_header X-Powered-By;
            }

            location ~ /\.(?!well-known).* {
                deny all;
            }
        }
        NGINX;
    }

    private function apache(): string
    {
        $domain = $this->domain();
        $root = public_path();

        return <<<APACHE
        <VirtualHost *:80>
            ServerName {$domain}
            DocumentRoot {$root}

            <Directory {$root}>
                AllowOverride All
                Require all granted
            </Directory>
        </VirtualHost>
        APACHE;
    }

    private function supervisor(): string
    {
        $artisan = base_path('artisan');
        $php = PHP_BINARY;
        $user = (string) $this->option('user');
        $log = storage_path('logs/worker.log');

        return <<<SUPERVISOR
        [program:mksine-worker]
        process_name=%(program_name)s_%(process_num)02d
        command={$php} {$artisan} queue:work --sleep=3 --tries=3 --max-time=3600
        autostart=true
        autorestart=true
        stopasgroup=true
        killasgroup=true
        user={$user}
        numprocs=1
        redirect_stderr=true
        stdout_logfile={$log}
        stopwaitsecs=3600
        SUPERVISOR;
    }
}
