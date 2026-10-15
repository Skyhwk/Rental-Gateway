<?php

namespace App\Console\Commands;

use App\Services\Gateway\GatewayOrphanConfigService;
use Illuminate\Console\Command;

class GatewayPruneOrphansCommand extends Command
{
    protected $signature = 'gateway:prune-orphans';

    protected $description = 'Hapus file vhost gateway untuk aplikasi yang sudah tidak ada di database';

    public function handle(GatewayOrphanConfigService $orphans)
    {
        $ids = $orphans->orphanApplicationIds();
        if ($ids === []) {
            $this->info('Tidak ada config gateway yatim.');

            return self::SUCCESS;
        }

        $this->warn('Config yatim (app id): '.implode(', ', $ids));

        $result = $orphans->pruneOrphans();

        if ($result['removed'] !== []) {
            $this->info('Dihapus: '.implode(', ', $result['removed']));
        }

        foreach ($result['failed'] as $id => $message) {
            $this->error("Gagal hapus app-{$id}: {$message}");
        }

        $conflicts = $orphans->hostnameConflicts();
        if ($conflicts !== []) {
            $this->warn('Masih ada hostname bentrok di disk: '.implode(', ', array_keys($conflicts)));
        }

        return $result['failed'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
