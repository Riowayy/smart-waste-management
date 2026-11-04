<?php

// Render layout sebenarnya tanpa akun atau data database untuk pengujian browser drawer.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array', 'app.url' => 'http://ecotrack.test']);
app(Illuminate\Foundation\Vite::class)->useHotFile('tests/Feature/nonexistent-vite-hot');
$role = ($argv[1] ?? '') === 'user' ? 'user' : 'admin';
Illuminate\Support\Facades\Auth::setUser(new App\Models\User(['name' => 'Pengujian Drawer', 'email' => 'drawer@example.test']));
echo view('layouts.'.$role)->render();
