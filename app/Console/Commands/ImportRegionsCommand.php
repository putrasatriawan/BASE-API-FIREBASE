<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportRegionsCommand extends Command
{
    protected $signature = 'import:regions';
    protected $description = 'Import provinces, cities, and districts from XLSX file';

    public function handle()
    {
        $filePath = resource_path('regiondata/districts-mapped.xlsx');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Loading file: {$filePath}");

        try {

            // ===============================
            // LOAD FILE
            // ===============================
            $spreadsheet = IOFactory::load($filePath);
            $worksheet   = $spreadsheet->getActiveSheet();
            $rows        = $worksheet->toArray();

            array_shift($rows); // remove header

            $this->info("Found " . count($rows) . " rows");

            // ===============================
            // TRUNCATE ALL TABLES
            // ===============================
            $this->info("Clearing existing data...");

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            District::truncate();
            City::truncate();
            Province::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // ===============================
            // PREPARE DATA
            // ===============================
            $provinces = [];
            $cities    = [];
            $districts = [];

            $progressBar = $this->output->createProgressBar(count($rows));
            $progressBar->start();

            foreach ($rows as $row) {

                if (empty($row[1]) || empty($row[3]) || empty($row[5])) {
                    $progressBar->advance();
                    continue;
                }

                $districtName = trim($row[1]); // B
                $cityId       = trim($row[2]); // C
                $cityType     = trim($row[3]); // D
                $cityName     = trim($row[4]); // E
                $provinceId   = trim($row[5]); // F
                $provinceName = trim($row[6]); // G

                // Province
                if (!isset($provinces[$provinceId])) {
                    $provinces[$provinceId] = [
                        'id' => $provinceId,
                        'province_name' => $provinceName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // City
                $cityKey = "{$provinceId}-{$cityId}";
                if (!isset($cities[$cityKey])) {
                    $cities[$cityKey] = [
                        'id' => $cityId,
                        'province_id' => $provinceId,
                        'city_type' => $cityType,
                        'city_name' => $cityName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // District
                $districts[] = [
                    'city_id' => $cityId,
                    'district_name' => $districtName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // ===============================
            // INSERT DATA
            // ===============================
            $this->info("Inserting provinces...");
            foreach (array_chunk(array_values($provinces), 100) as $chunk) {
                Province::insert($chunk);
            }

            $this->info("Inserting cities...");
            foreach (array_chunk(array_values($cities), 100) as $chunk) {
                City::insert($chunk);
            }

            $this->info("Inserting districts...");
            foreach (array_chunk($districts, 500) as $chunk) {
                District::insert($chunk);
            }

            $this->info("✓ Import completed successfully!");

            $this->table(
                ['Type', 'Count'],
                [
                    ['Provinces', count($provinces)],
                    ['Cities', count($cities)],
                    ['Districts', count($districts)],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
    }
}
