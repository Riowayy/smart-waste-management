<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ReportingHttpIntegrationTest extends TestCase
{
    public function test_mobile_service_uploads_real_multipart_and_reads_report_back_over_http(): void
    {
        $directory = sys_get_temp_dir().'/ecotrack-report-'.bin2hex(random_bytes(8));
        mkdir($directory);
        touch($directory.'/database.sqlite');
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $this->assertNotFalse($socket);
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        $baseUrl = 'http://'.$address;
        $environment = [
            'APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'APP_URL' => $baseUrl,
            'APP_CONFIG_CACHE' => $directory.'/config.php',
            'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $directory.'/database.sqlite', 'DB_URL' => '',
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'LOG_CHANNEL' => 'null',
            'ECOTRACK_TEST_STORAGE' => $directory.'/photos',
        ];
        $server = null;
        $pdo = null;
        try {
            $migration = new Process([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction'], base_path(), $environment);
            $migration->setTimeout(60);
            $this->assertSame(0, $migration->run(), 'Migration database test terisolasi gagal.');
            $seed = new Process([PHP_BINARY, 'artisan', 'db:seed', '--class=WasteCategorySeeder', '--force', '--no-interaction'], base_path(), $environment);
            $seed->setTimeout(30);
            $this->assertSame(0, $seed->run(), 'Seed kategori database test terisolasi gagal.');
            $server = new Process([PHP_BINARY, '-S', $address, base_path('tests/Feature/ReportingHttpRouter.php')], base_path(), $environment);
            $server->disableOutput();
            $server->start();
            $ready = false;
            for ($i = 0; $i < 100; $i++) {
                $probe = @stream_socket_client('tcp://'.$address, $errorCode, $errorMessage, 0.1);
                if ($probe) { fclose($probe); $ready = true; break; }
                usleep(50000);
            }
            $this->assertTrue($ready, 'Server test tidak siap.');
            $client = new Process(['node', 'tests/Unit/reporting-http.cjs'], base_path('mobile'), [
                'ECOTRACK_TEST_API' => $baseUrl.'/api/v1',
            ]);
            $client->setTimeout(60);
            $this->assertSame(0, $client->run(), $client->getErrorOutput());
            $pdo = new \PDO('sqlite:'.$directory.'/database.sqlite');
            $report = $pdo->query('SELECT * FROM waste_reports')->fetch(\PDO::FETCH_ASSOC);
            $this->assertIsArray($report);
            $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM waste_reports')->fetchColumn());
            $this->assertSame('pending', $report['status']);
            $ownerId = $pdo->query("SELECT id FROM users WHERE email = 'owner@example.test'")->fetchColumn();
            $this->assertSame((int) $ownerId, (int) $report['user_id']);
            $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM report_status_histories')->fetchColumn());
            $this->assertFileExists($directory.'/photos/'.$report['image_path']);
            $pdo = null;
        } finally {
            $server?->stop();
            $pdo = null;
            // Hanya direktori sementara unik milik test ini yang boleh dibersihkan.
            $resolved = realpath($directory);
            $this->assertTrue($resolved !== false && str_starts_with($resolved, realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'ecotrack-report-'));
            $this->assertTrue(File::deleteDirectory($resolved), 'Pembersihan fixture sementara gagal.');
        }
    }
}
