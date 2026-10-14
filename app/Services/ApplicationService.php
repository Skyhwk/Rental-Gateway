<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDomain;
use App\Models\GatewayNode;
use App\Support\HostnameNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function create(array $data, array $primaryHostname, array $additionalHostnames)
    {
        return DB::transaction(function () use ($data, $primaryHostname, $additionalHostnames) {
            $node = GatewayNode::defaultNode();
            if (! $node) {
                throw ValidationException::withMessages([
                    'gateway_node_id' => ['Tidak ada gateway node default. Jalankan migrasi.'],
                ]);
            }

            $data['gateway_node_id'] = $node->id;
            $data['slug'] = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']));

            $application = Application::create($data);
            $this->syncDomains($application, $primaryHostname, $additionalHostnames);

            return $application->load(['client', 'domains']);
        });
    }

    public function update(Application $application, array $data, array $primaryHostname, array $additionalHostnames)
    {
        return DB::transaction(function () use ($application, $data, $primaryHostname, $additionalHostnames) {
            if (isset($data['slug'])) {
                $data['slug'] = $this->uniqueSlug($data['slug'], $application->id);
            }

            $application->update($data);
            $this->syncDomains($application, $primaryHostname, $additionalHostnames);

            return $application->load(['client', 'domains']);
        });
    }

    protected function uniqueSlug($slug, $ignoreId = null)
    {
        $base = Str::slug($slug) ?: 'app';
        $candidate = $base;
        $i = 1;

        while ($this->slugExists($candidate, $ignoreId)) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    protected function slugExists($slug, $ignoreId = null)
    {
        $query = Application::withTrashed()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<int, string>  $primaryHostname  single element
     * @param  array<int, string>  $additionalHostnames
     */
    protected function syncDomains(Application $application, array $primaryHostname, array $additionalHostnames)
    {
        $primary = HostnameNormalizer::normalize($primaryHostname[0] ?? '');
        if ($primary === '') {
            throw ValidationException::withMessages([
                'primary_hostname' => ['Domain utama wajib diisi.'],
            ]);
        }

        $all = array_merge([$primary], $additionalHostnames);
        $all = array_values(array_unique(array_map([HostnameNormalizer::class, 'normalize'], $all)));

        if (count($all) !== count(array_unique($all))) {
            throw ValidationException::withMessages([
                'additional_hostnames' => ['Domain duplikat dalam daftar.'],
            ]);
        }

        foreach ($all as $host) {
            if (! HostnameNormalizer::isValid($host)) {
                throw ValidationException::withMessages([
                    'primary_hostname' => ["Format domain tidak valid: {$host}"],
                ]);
            }

            $existing = ApplicationDomain::where('hostname', $host)
                ->where('application_id', '!=', $application->id)
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'primary_hostname' => ["Domain sudah digunakan: {$host}"],
                ]);
            }
        }

        $application->domains()->delete();

        foreach ($all as $host) {
            $application->domains()->create([
                'hostname' => $host,
                'is_primary' => $host === $primary,
            ]);
        }
    }
}
