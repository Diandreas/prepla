<?php

namespace App\Console\Commands;

use App\Models\CenterGoogleDrive;
use App\Services\Center\GoogleDrive;
use App\Services\Center\SauvegardeDrive;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sauvegarde hebdomadaire : chaque centre qui a connecte son Google Drive
 * recoit un JSON de ses contenus dans son dossier « Sauvegardes ». Un centre en
 * echec (jeton revoque...) n'empeche pas les autres.
 */
class SauvegarderCentresDrive extends Command
{
    protected $signature = 'centres:sauvegarder-drive {--centre= : id d\'un seul centre}';

    protected $description = 'Depose une sauvegarde JSON des contenus de chaque centre dans son Google Drive';

    public function handle(SauvegardeDrive $sauvegarde): int
    {
        if (! GoogleDrive::estConfigure()) {
            $this->info('Google Drive non configuré : rien à faire.');

            return self::SUCCESS;
        }

        $drives = CenterGoogleDrive::query()
            ->when($this->option('centre'), fn ($q, $id) => $q->where('center_id', (int) $id))
            ->with('center')->get();

        $echecs = 0;
        foreach ($drives as $drive) {
            try {
                $sauvegarde->sauvegarder($drive);
                $this->line("Centre {$drive->center_id} : sauvegardé.");
            } catch (Throwable $e) {
                $echecs++;
                report($e);
                $this->warn("Centre {$drive->center_id} : échec ({$e->getMessage()}).");
            }
        }

        $this->info(count($drives)." centre(s), {$echecs} échec(s).");

        return self::SUCCESS;
    }
}
