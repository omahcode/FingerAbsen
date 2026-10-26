<?php

namespace App\Services;

use App\Models\ParentPushSubscription;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    protected ?WebPush $webPush = null;

    public function __construct()
    {
        $this->ensureOpenSslConfig();

        $vapidConfig = config('webpush.vapid', []);
        $auth = [
            'VAPID' => [
                'subject' => $vapidConfig['subject'] ?? 'mailto:admin@smk.sch.id',
                'publicKey' => $vapidConfig['public_key'] ?? '',
                'privateKey' => $vapidConfig['private_key'] ?? '',
            ],
        ];

        try {
            $this->webPush = new WebPush($auth);
            $this->webPush->setReuseVAPIDHeaders(true);
        } catch (\Throwable $e) {
            Log::error('WebPush initialization failed: '.$e->getMessage());
        }
    }

    /**
     * Pastikan OPENSSL_CONF terkonfigurasi di lingkungan Windows agar enkripsi P-256 berjalan lancar.
     */
    protected function ensureOpenSslConfig(): void
    {
        if (DIRECTORY_SEPARATOR === '\\' && ! getenv('OPENSSL_CONF')) {
            $candidates = [
                'C:\Users\srihi\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\extras\ssl\openssl.cnf',
                'C:\laragon\bin\php\extras\ssl\openssl.cnf',
                'D:\laragon\bin\php\extras\ssl\openssl.cnf',
            ];
            foreach ($candidates as $path) {
                if (file_exists($path)) {
                    putenv("OPENSSL_CONF={$path}");
                    break;
                }
            }
        }
    }

    protected array $lastErrors = [];

    public function getLastErrors(): array
    {
        return $this->lastErrors;
    }

    /**
     * Kirim notifikasi push ke seluruh perangkat terdaftar milik orang tua siswa.
     */
    public function sendToStudent(Student $student, string $title, string $body, array $extra = []): int
    {
        $this->lastErrors = [];

        if (! $this->webPush) {
            $this->lastErrors[] = 'WebPush library belum terinisialisasi pada server.';

            return 0;
        }

        $subscriptions = ParentPushSubscription::where('student_id', $student->id)->get();
        if ($subscriptions->isEmpty()) {
            $this->lastErrors[] = "Belum ada perangkat HP yang terdaftar untuk ananda {$student->name}.";

            return 0;
        }

        $payload = json_encode(array_merge([
            'title' => $title,
            'body' => $body,
            'icon' => asset('favicon.svg'),
            'badge' => asset('favicon.svg'),
            'url' => route('parent.index'),
            'timestamp' => now()->timestamp,
        ], $extra));

        $queuedCount = 0;
        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->p256dh,
                    'authToken' => $sub->auth,
                ]);

                $this->webPush->queueNotification($subscription, $payload);
                $queuedCount++;
            } catch (\Throwable $e) {
                $this->lastErrors[] = "Subscription #{$sub->id}: ".$e->getMessage();
                Log::warning("Gagal antre push untuk subscription ID {$sub->id}: ".$e->getMessage());
            }
        }

        $successCount = 0;
        if ($queuedCount > 0) {
            try {
                foreach ($this->webPush->flush() as $report) {
                    $endpoint = (string) $report->getRequest()->getUri();
                    if ($report->isSuccess()) {
                        $successCount++;
                    } else {
                        $reason = $report->getReason();
                        $this->lastErrors[] = 'Endpoint: '.substr($endpoint, 0, 45)."... - {$reason}";
                        Log::warning('Push gagal ke endpoint '.$endpoint.': '.$reason);
                        if ($report->isSubscriptionExpired()) {
                            ParentPushSubscription::where('endpoint_hash', hash('sha256', $endpoint))->delete();
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->lastErrors[] = 'Error flush WebPush: '.$e->getMessage();
                Log::error('Gagal mengirim Web Push: '.$e->getMessage());
            }
        }

        return $successCount;
    }

    /**
     * Kirim notifikasi uji coba ke satu subscription tertentu.
     */
    public function sendToSubscription(ParentPushSubscription $sub, string $title, string $body, array $extra = []): bool
    {
        if (! $this->webPush) {
            return false;
        }

        $payload = json_encode(array_merge([
            'title' => $title,
            'body' => $body,
            'icon' => asset('favicon.ico'),
            'url' => route('parent.index'),
            'timestamp' => now()->timestamp,
        ], $extra));

        try {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh,
                'authToken' => $sub->auth,
            ]);

            $report = $this->webPush->sendOneNotification($subscription, $payload);

            return $report->isSuccess();
        } catch (\Throwable $e) {
            Log::error('Gagal sendOneNotification: '.$e->getMessage());

            return false;
        }
    }
}
