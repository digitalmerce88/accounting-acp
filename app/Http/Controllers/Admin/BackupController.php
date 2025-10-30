<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller {
    public function download(): StreamedResponse {
        abort_unless(Auth::user() && Auth::user()->role === 'owner', 403);
        $db = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $file = 'backup_' . date('Ymd_His') . '.sql';
        $cmd = sprintf("mysqldump -h%s -u%s -p%s %s",
            escapeshellarg($host), escapeshellarg($user), escapeshellarg($pass), escapeshellarg($db));
        return response()->streamDownload(function() use ($cmd) {
            $proc = popen($cmd, 'r'); fpassthru($proc); pclose($proc);
        }, $file);
    }
}
